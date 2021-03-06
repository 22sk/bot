<?php

/**
 * @author Samuel Kaiser <samuel.kaiser01@gmail.com>
 * @since 14.05.2016
 */

namespace {

  $GLOBALS['processors'] = [
    "Command",
    "InlineQuery",
    "Keyword"
  ];

  /**
   * Sends Responses and manages and runs Processors that produce Responses
   */
  class Bot {
    public $req;
    private $output;

    public $processors;

    /**
     * Creates a new Bot.
     * @param string $url
     * @param Request|\stdClass $req
     */
    public function __construct($url, $req = null) {
      $this->url = $url;
      $this->req = $req instanceof Request ? $req : (isset($req) ? Request::map($req) : Request::getRequest());

      global $processors;
      foreach($processors as $processor) self::register($processor);

      processors\Command::register($this, "help", (new \registerables\Command(function($req, $args=null) {
        if(empty($args)) {
          $text = "*All commands are listed below:*\n";
          foreach($this->command as $name => $value) {
            $text.="/".$name
              .(!empty($value->syntax) ? ' `'.$value->syntax.'`' : '')
              .(!empty($value->description) ? ' '.$value->description : '')
              ."\n";
          }
        } else {
          $name = strtolower(trim($args));
          $cmd = $this->command[$name];
          $text = '/'.$name.(!empty($cmd->syntax) ? ' `'.$cmd->syntax.'`' : '')."\n"
            .(!empty($cmd->description) ? '*'.$cmd->description.'*' : '')."\n"
            .(!empty($cmd->help)? "".$cmd->help : '');
        }
        return (new responses\Message($text, $req))->parse_mode("Markdown");
      }))->description("Prints the help message")->syntax("[command]")
         ->help("Oh, hey! You found me! Here, have a cookie: ".json_decode('"\ud83c\udf6a"')."\n"
         ."Used to send help for a specific command or list all commands."));
    }

    /**
     * Get the bot's user data
     * @param bool $update Declare if you want the bot to update the existing data
     * @return stdClass
     */
    public function me($update = false) {
      if($update or !isset($this->me)) $this->me = $this->send(new Response("getMe", []));
      return $this->me->ok ? $this->me->result : false;
    }

    public function processor_exists($type, $name) {
      return array_key_exists($name, $this->$type);
    }

    public function run() {
      $this->output = ["request" => $this->req];
      foreach($this->processors as $class) {
        $class = 'processors\\'.$class;
        /** @var processors\Processor $class */
        $res = $class::process($this);
        if($res instanceof Response) $this->send($res);
      }
      echo json_encode($this->output, JSON_PRETTY_PRINT);
    }

    /** @param string $processor */
    public function register($processor) {
      $this->processors[] = $processor;
    }

    /**
     * @param Response $response
     * @return mixed
     */
    public function send($response) {
      $context = stream_context_create([
        'http' => [
          // http://www.php.net/manual/de/context.http.php
          'method'  => 'POST',
          'header'  => 'Content-Type: application/json',
          'ignore_errors' => true,
          'content' => json_encode($response->content)
        ]
      ]);
      $url = $this->url . $response->method;
      $result = json_decode(file_get_contents($url, false, $context));
      $i = isset($this->echo['responses']) ? count($this->echo['responses']) : 0;
      $this->output['responses'][$i]['response']['method'] = $response->method;
      $this->output['responses'][$i]['response']['content'] = $response->content;
      $this->output['responses'][$i]['result'] = $result;
      return $result;
    }
  }


  /** @see https://core.telegram.org/bots/api#update */
  class Request {
    private function __construct() {}

    /**
     * @param stdClass $json json_decode()'d API request
     * @return Request
     */
    public static function map($json) {
      $request = obj2obj($json, "Request");
      return $request;
    }
    public static function getRequest() {
      $req = json_decode(file_get_contents("php://input"));
      if(!isset($req)) throw new Exception("No POST data sent!");
      return self::map($req);
    }
  }

  /** @see https://core.telegram.org/bots/api#available-methods */
  class Response {
    public $method, $content;

    protected $req;

    public function __construct($method, $content) {
      $this->method = $method;
      $this->content = $content;
    }

    public function method($method) {
      $this->method = $method; return $this;
    }

    public function __call($name, $args) { $this->content[$name] = $args[0]; return $this; }
    public function __set($name, $value) { $this->content[$name] = $value; }
  }


  // http://stackoverflow.com/questions/3243900/convert-cast-an-stdclass-object-to-another-class
  function obj2obj($instance, $class) {
    return unserialize(sprintf('O:%d:"%s"%s', strlen($class), $class, strstr(strstr(serialize($instance), '"'), ':')));
  }
  function markdown_escape($string) {
    return preg_replace('/(?=[*_`])/', '\\', $string);
  }
}

namespace processors {

  abstract class Processor {
    /**
     * @param \Bot $bot
     * @return \Response|false
     */
    public static function process($bot) {}

    public static function get_class_type() {
      $class = get_called_class();
      $class = explode("\\", $class);
      return strtolower(end($class));
    }

    /**
     * @param \Bot $bot
     * @param string|array $name
     * @param \registerables\Registerable $registerable
     * @return bool
     */
    public static function register($bot, $name, $registerable) {
      if(gettype($name) == 'array') {
        foreach($name as $item) self::register($bot, $item, $registerable);
        return true;
      } else if($registerable instanceof \registerables\Registerable) {
        $class = self::get_class_type();
        $name = strtolower($name);
        if(!isset($bot->$class)) $bot->$class = [];
        $array = &$bot->$class;
        $array[$name] = $registerable;
        return true;
      } return false;
    }
  }

  class Command extends Processor {
    /**
     * @param \Request $req
     * @param string $command
     * @return \responses\Message A message that should be sent if the requested command is not registered
     */
    public static function invalid_command($req, $command=null) {
      return (new \responses\Message(
        "I don't know ".(empty($command)?"that command":"the command /".$command).". Sorry for that!", $req
      ))->parse_mode("Markdown");
    }

    /**
     * @param \Bot $bot
     * @param string $command
     * @return mixed
     */
    public static function help($bot, $command) {
      return $bot->command['help']->callable($bot->req, str_replace('/', '', $command));
    }

    public static function register($bot, $name, $registerable) {
      return parent::register($bot, $name, $registerable);
    }

    /**
     * @param \Bot $bot
     * @param \seperators\Command $command
     * @param null|\Request $req
     * @return mixed
     */
    private static function execute($bot, $command, $req=null) {
      return $bot->command[$command->cmd]->callable(isset($req) ? $req : $bot->req, $command->args);
    }

    public static function process($bot) {
      if(empty($bot->req->message) or empty($bot->req->message->text) or empty($bot->command))
        return false; // Abort if request has no text

      $command = new \seperators\Command($bot->req->message->text); // Generate Command from message text
      $command->cmd = strtolower($command->cmd);
      if($command->valid) {
        if(!$bot->processor_exists(self::get_class_type(), $command->cmd)) {
          if($command->botname_equals($bot->me()->username) or $bot->req->message->chat->type == 'private') {
            $bot->send(self::invalid_command($bot->req, $command->cmd));
          } return false;
        } elseif(empty($command->bot) or $command->botname_equals($bot->me()->username)) {
          // Executing the command if it exists and the bot is stated or no bot name is given
          if($res = self::execute($bot, $command)) return $res;
          else {
            $bot->send(self::help($bot, $command->cmd));
            return false;
          }
        }
      } return false;
    }
  }

  class InlineQuery extends Processor {

    public static function process($bot) {
      if(empty($bot->req->inline_query) or empty($bot->inlinequery)) return false;
      $inlinequery = new \seperators\InlineQuery($bot->req->inline_query->query);
      foreach($bot->inlinequery as $name => $value) {
        if(strcasecmp($inlinequery->cmd, $name) == 0)
          return $value->callable($bot->req, $inlinequery);
      } if(array_key_exists('default', $bot->inlinequery)) {
        return $bot->inlinequery['default']->callable($bot->req);
      }
      return false;
    }
  }

  class Keyword extends Processor {
    public static function register($bot, $name, $registerable) {
      return parent::register($bot, $name, $registerable);
    }

    public static function process($bot) {
      if(empty($bot->req->message) or empty($bot->keyword)) return false;
      $text = $bot->req->message->text;
      foreach($bot->keyword as $name => $value) {
        $regex = "/".($value->word?"\b":"")."($name)".($value->word?"\b":"")."/";
        if(preg_match($regex, $text))
          return $value->callable($bot->req);
      } return false;
    }
  }
}

namespace registerables {

  /**
   * Class Registerable
   * @package registerables
   * @method \Response callable(callable $callable)
   */
  class Registerable {
    /** @var callable */ public $callable;
    public function __call($name, $arguments) {
      if($name == 'callable') return call_user_func_array($this->$name, $arguments);
      else $this->$name = $arguments[0]; return $this;
    }

    public function __construct($callable, $meta=null) {
      $this->callable = $callable;
      if(!empty($meta)) foreach($meta as $item => $value) $this->$item = $value;
    }
  }

  /**
   * @method $this description(string $description)
   * @method $this syntax(string $syntax)
   * @method $this help(string $help)
   * @method $this hidden(bool $hidden)
   */
  class Command extends Registerable {}

  /**
   * @method $this description(string $description)
   * @method $this help(string $help)
   * @method $this syntax(string $syntax)
   * @method $this hidden(bool $hidden)
   */
  class InlineQuery extends Registerable {}

  /**
   * @method $this word(bool $word)
   * @method $this description(string $description)
   * @method $this help(string $help)
   * @method $this hidden(bool $hidden)
   */
  class Keyword extends Registerable {}
}

namespace seperators {

  class Command {
    public $valid;
    public $text, $cmd, $bot, $args;
    public function __construct($msg) {
      $keys = ['text', 'cmd', 'bot', 'args'];
      // Writing the command's information into $array
      preg_match("/^\/([^@\s]+)@?(?:(\S+)|)\s?(.*)$/i", $msg, $array);
      $this->valid = false;
      if(!empty($array)) {
        // Setting object's values
        for ($i=0; $i<count($array); $i++) $this->$keys[$i] = $array[$i];
        $this->cmd = trim(strtolower($this->cmd));
        $this->valid = true;
      }
    }
    public function botname_equals($name) {
      return strcasecmp($this->bot, $name) == 0;
    }
  }

  class InlineQuery {
    public $query, $cmd, $args;

    public function __construct($query) {
      if(!empty($query)){
        preg_match("/(\w+)\s*(.*)/", $query, $match);
        $this->query = $match[0];
        $this->cmd   = $match[1];
        $this->args  = $match[2];
      }
    }
  }

}

namespace responses {

  /**
   * @method $this chat_id(integer $chat_id)
   * @method $this disable_notification(bool $disable_notification)
   * @method $this reply_to_message_id(integer $reply_to_message_id)
   * @method $this reply_markup(array $reply_markup)
   */
  abstract class ResponseBuilder extends \Response {
    public $name;

    public function __construct($value, $req, $add = null) {
      parent::__construct($this->method, $add);
      $this->req = $req;
      if (!empty($this->name)) {
        $name = $this->name;
        $this->$name($value);
      }
    }
  }

  abstract class Sendable extends ResponseBuilder {
    const REPLY_IN_GROUP = 0, TO_CHAT = 1, REPLY_TO_MESSAGE = 2, REPLY_TO_REPLIED = 3, TO_SENDER = 4;

    public function __construct($value, $req, $add = null) {
      parent::__construct($value, $req, $add);
      $this->to(self::REPLY_IN_GROUP);
    }

    /**
     * @param integer $mode
     * @return $this
     */
    public function to($mode) {
      if (!isset($this->req->message)) return false;
      $message = &$this->req->message;
      switch ($mode) {
        case self::REPLY_TO_MESSAGE:
          $this->reply_to_message_id($message->message_id);
          $this->chat_id($message->chat->id);
          break;
        case self::REPLY_IN_GROUP:
          if ($message->chat->type != 'private') $this->reply_to_message_id($message->message_id);
          $this->chat_id($message->chat->id);
          break;
        case self::REPLY_TO_REPLIED:
          if (isset($message->reply_to_message))
            $this->reply_to_message_id($message->reply_to_message->id);
          else $this->chat_id($message->chat->id);
          break;
        case self::TO_CHAT:
          $this->chat_id($message->chat->id);
          break;
        case self::TO_SENDER:
          $this->chat_id($message->from->id);
          break;
      }
      return $this;
    }
  }


  /**
   * @method $this chat_id(integer $chat_id)
   * @method $this text(string $text)
   * @method $this parse_mode(string $parse_mode)
   * @method $this disable_web_page_preview(bool $disable_web_page_preview)
   */
  class Message extends Sendable {
    public $method = "sendMessage";
    public $name = "text";
  }

  /**
   * @method $this message_id(integer $message_id)
   * @method $this chat_id(integer $chat_id) Defaults to the chat where the request was sent from.
   * @method $this from_chat_id(integer $chat_id) Defaults to $chat_id
   * @method $this disable_notification(bool $disable_notification)
   */
  class Forward extends Sendable {
    public $method = "forwardMessage";
    public $name = "message_id";

    public function to($mode) {
      $message = $this->req->message;
      switch ($mode) {
        case self::TO_CHAT:
          $this->content['chat_id'] = $message->chat->id;
          break;
        case self::TO_SENDER:
          $array['chat_id'] = $message->from->id;
          break;
      }
      return $this;
    }
  }

  /**
   * @method $this photo(string $photo)
   * @method $this caption(string $caption)
   */
  class Photo extends Sendable {
    public $method = "sendPhoto";
    public $name = "photo";
  }

  /**
   * @method $this audio(string $audio)
   * @method $this duration(integer $duration)
   * @method $this performer(string $performer)
   * @method $this title(string $title)
   */
  class Audio extends Sendable {
    public $method = "sendPhoto";
    public $name = "audio";
  }

  /**
   * @method $this document(string $document)
   * @method $this caption(string $caption)
   */
  class Document extends Sendable {
    public $method = "sendDocument";
    public $name = "document";
  }

  /**
   * @method $this sticker(string $sticker)
   */
  class Sticker extends Sendable {
    public $method = "sendSticker";
    public $name = "sticker";
  }

  /**
   * @method $this video(string $video)
   * @method $this duration(integer $duration)
   * @method $this width(integer $width)
   * @method $this height(integer $height)
   * @method $this caption(string $caption)
   */
  class Video extends Sendable {
    public $method = "sendVideo";
    public $name = "video";
  }

  /**
   * @method $this voice(string $voice)
   * @method $this duration(integer $duration)
   */
  class Voice extends Sendable {
    public $method = "sendVoice";
    public $name = "voice";
  }

  /**
   * @method $this latitude(float $latitude)
   * @method $this longitude(float $longitude)
   */
  class Location extends Sendable {
    public $method = "sendLocation";

    public function __construct($latitude, $longitude, $req, $add = null) {
      parent::__construct(null, $req);
      $this->latitude($latitude);
      $this->longitude($longitude);
    }
  }

  /**
   * @method $this results(array $results)
   * @method $this inline_query_id(string $inline_query_id)
   * @method $this cache_time(integer $cache_time)
   * @method $this is_personal(bool $is_personal)
   * @method $this next_offset(string $next_offset)
   * @method $this switch_pm_text(string $switch_pm_text)
   * @method $this switch_pm_parameter(string $switch_pm_parameter)
   */
  class Inline extends ResponseBuilder {
    const TO_SENDER = 0;
    public $method = "answerInlineQuery";
    public $name = "results";

    public function __construct($results, $req, $add = null) {
      parent::__construct($results, $req, $add);
      $this->to(self::TO_SENDER);
    }

    public function to($mode) {
      switch ($mode) {
        case self::TO_SENDER:
          $this->inline_query_id($this->req->inline_query->id);
          break;
      }
      return $this;
    }
  }
}
