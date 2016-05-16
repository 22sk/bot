<?php

/**
 * @author Samuel Kaiser <samuel.kaiser01@gmail.com>
 * @since 14.05.2016
 */
if(file_exists('addons.php')) include('addons.php');

class Bot {
  public $req;

  /**
   * Creates a new Bot.
   * @param string $url
   * @param Request|stdClass $req
   */
  public function __construct($url, $req = null) {
    $this->url = $url;
    $this->req = $req instanceof Request ? $req : (isset($req) ? Request::map($req) : Request::getRequest());

    Command::register($this, "help", function($req) {
      $text = "All commands are listed below:\n";
      foreach($this->command as $name => $value) {
        $text.="/".$name.(isset($value['help']) ? ": ".$value['help'] : '')."\n";
      }
      return (new Message($text, $req))->parse_mode("Markdown");
    }, "Prints this message");
  }

  /**
   * Get the bot's user data
   * @param bool $update Declare if you want the bot to update the existing data
   * @return stdClass
   */
  public function me($update = false) {
    if($update or !isset($this->me)) $this->me = $this->send(new Response("getMe", array()))->result;
    return $this->me;
  }

  public function run() {
    $this->echo = array("request" => $this->req);
    // Execute process() for all classes that implement Processable
    foreach($classes = get_declared_classes() as $class) {
      if(is_subclass_of($class, 'Processable')) {
        /** @var Processable $class */
        $res = $class::process($this);
        // $res = forward_static_call(array($class, 'process'), $this);
        if($res instanceof Response) $this->send($res);
      }
    }
    echo json_encode($this->echo, JSON_PRETTY_PRINT);
  }

  /**
   * @param Response $response
   * @return mixed
   */
  public function send($response) {
    $context = stream_context_create( array(
      'http' => array(
        // http://www.php.net/manual/de/context.http.php
        'method'  => 'POST',
        'header'  => 'Content-Type: application/json',
        'ignore_errors' => true,
        'content' => json_encode($response->content)
      )
    ));
    $url = $this->url . $response->method;
    $result = json_decode(file_get_contents($url, false, $context));
    $i = isset($this->echo['responses']) ? count($this->echo['responses']) : 0;
    $this->echo['responses'][$i]['response']['method'] = $response->method;
    $this->echo['responses'][$i]['response']['content'] = $response->content;
    $this->echo['responses'][$i]['result'] = $result;
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

// http://stackoverflow.com/questions/3243900/convert-cast-an-stdclass-object-to-another-class
function obj2obj($instance, $className) {
  return unserialize(sprintf(
    'O:%d:"%s"%s',
    strlen($className),
    $className,
    strstr(strstr(serialize($instance), '"'), ':')
  ));
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
/**
 * @method $this chat_id(integer $chat_id)
 * @method $this disable_notification(bool $disable_notification)
 * @method $this reply_to_message_id(integer $reply_to_message_id)
 * @method $this reply_markup(array $reply_markup)
 */
class Sendable extends Response {
  const REPLY_IN_GROUP = 0, TO_CHAT = 1, REPLY_TO_MESSAGE = 2, REPLY_TO_REPLIED = 3, TO_SENDER = 4;

  public function __construct($req, $add = null) {
    parent::__construct($this->method, $add);
    $this->req = $req;
    $this->to(self::REPLY_IN_GROUP);
  }

  /** @param integer $mode */
  public function to($mode) {
    if(!isset($this->req->message)) return false;
    $message = &$this->req->message;
    switch ($mode) {
      case self::REPLY_TO_MESSAGE:
        $this->reply_to_message_id($message->message_id);
        $this->chat_id($message->chat->id);
        break;
      case self::REPLY_IN_GROUP:
        if($message->chat->type != 'private') $this->reply_to_message_id($message->message_id);
        $this->chat_id($message->chat->id);
        break;
      case self::REPLY_TO_REPLIED:
        if(isset($message->reply_to_message))
          $this->reply_to_message_id($message->reply_to_message->id);
        else $this->chat_id($message->chat->id);
        break;
      case self::TO_CHAT:   $this->chat_id($message->chat->id); break;
      case self::TO_SENDER: $this->chat_id($message->from->id); break;
    } return $this;
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

  /**
   * Message constructor.
   * @param $text
   * @param Request $req Needed, if chat_id is not given
   * @param array $add Additional data
   */
  public function __construct($text, $req, $add = null) {
    parent::__construct($req, $add);
    $this->text($text);
  }
}
/**
 * @method $this message_id(integer $message_id)
 * @method $this chat_id(integer $chat_id) Defaults to the chat where the request was sent from.
 * @method $this from_chat_id(integer $chat_id) Defaults to $chat_id
 * @method $this disable_notification(bool $disable_notification)
 */
class Forward extends Sendable {
  public $method = "forwardMessage";
  public function __construct($message_id, $req, $add = null) {
    parent::__construct($req, $add);
    $this->message_id($message_id);
  }
  public function to($mode) {
    $message = $this->req->message;
    switch($mode) {
      case self::TO_CHAT:   $this->content['chat_id'] = $message->chat->id; break;
      case self::TO_SENDER: $array['chat_id'] = $message->from->id; break;
    } return $this;
  }
}


abstract class Processable {
  /**
   * @param Bot $bot
   * @return Response|false
   */
  public static function process($bot) {}
  public static function register($bot, $name, $callable, $help, $hidden) {
    if(gettype($name) != 'array') {
      $class = strtolower(get_called_class());
      $name = strtolower($name);
      if(!isset($bot->$class)) $bot->$class = array();
      $array = &$bot->$class;
      $array[$name]['callable'] = $callable;
      $array[$name]['help']     = $help;
      if($hidden) $array[$name]['hidden'] = $hidden;
    } elseif(gettype($name) == 'array') foreach($name as $r) self::register($bot, $r, $callable, $help, $hidden);
    else return false;
    return true;
  }
}

class Command extends Processable {
  /**
   * Used to separate a message into an Command containing all necessary information.
   * @param string $msg Message to generate the Command from.
   */
  public $valid;
  public $text, $cmd, $bot, $args;

  public static function register($bot, $name, $callable, $help = null, $hidden = false) {
    return parent::register($bot, $name, $callable, $help, $hidden);
  }

  public function __construct($msg) {
    $keys = array('text', 'cmd', 'bot', 'args');
    // Writing the command's information into $array
    preg_match("/^\/([^@\s]+)@?(?:(\S+)|)\s?(.*)$/i", $msg, $array);
    $this->valid = false;
    if (!empty($array)) {
      // Setting object's values
      for ($i=0; $i<count($array); $i++) $this->$keys[$i] = $array[$i];
      $this->valid = true;
    }
  }

  public static function process($bot) {
    if(empty($bot->req->message)) return false;
    $command = new Command($bot->req->message->text);
    $command->cmd = strtolower($command->cmd);
    if($command->valid
      and array_key_exists($command->cmd, $bot->command)
      and (empty($command->bot) or strcasecmp($command->bot, $bot->me()->username) == 0)) {
      return $bot->command[$command->cmd]['callable']($bot->req);
    } return false;
  }
}


class Inline extends Processable {
  public static function register($bot, $name, $callable, $help = null, $hidden = false) {
    return parent::register($bot, $name, $callable, $help, $hidden);
  }

  public static function process($bot) {
    if(empty($bot->req->inline_query)) return false;
    preg_match("/^\w+/", $bot->req->inline_query->query, $match);
    $word = $match[0];
    foreach($bot->inline as $inline => $value) {
      if(strcasecmp($word, $inline) == 0) return $value['callable']($bot->req);
    } if(array_key_exists('default', $bot->inlines)) {
      return $bot->inline['default']['callable']($bot->req);
    }
    return false;
  }
}
