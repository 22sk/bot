<?php

/**
 * @author Samuel Kaiser <samuel.kaiser01@gmail.com>
 * @since 14.05.2016
 */
class Bot {
  /** @var Request  */ public $req;
  /** @var Response */ public $res;

  /** @var array */ private $commands = array();
  /** @var array */ private $keywords = array();
  /** @var array */ private $inlines  = array();

  private $me;

  public function __get($name) { return $this->$name; }

  public function me($update = false) {
    if($update or !isset($this->me)) $this->me = $this->send(new Response("getMe", array()))->result;
    return $this->me;
  }

  /**
   * Creates new Bot.
   * @param string $url
   * @param Request|stdClass $req
   */
  public function __construct($url, $req = null) {
    $this->url = $url;
    $this->req = $req instanceof Request ? $req : (isset($req) ? Request::map($req) : Request::getRequest());
  }

  const COMMAND = "commands",
        KEYWORD = "keywords",
        INLINE  = "inlines";
  /**
   * @param string $name
   * @see Bot::COMMAND, Bot::KEYWORD, Bot::INLINE
   * @param string|array $register
   * @param callable $callable
   */
  public function register($name, $register, $callable) {
    if(gettype($register) != 'array') {
      $array = &$this->$name;
      $array[$register] = $callable;
    } else foreach($register as $r) $this->register($name, $r, $callable);
  }

  public function run() {
    if($res = Command::process($this)) $this->send($res);
    if($res = Keyword::process($this)) $this->send($res);
    if($res =  Inline::process($this)) $this->send($res);
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
    $response = file_get_contents($url, false, $context);
    return json_decode($response);
  }
}

class Command {
  /**
   * Used to separate a message into an Command containing all necessary information.
   * @param string $msg Message to generate the Command from.
   */
  public $valid;
  public $text, $cmd, $bot, $args;
  
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

  /**
   * @param Bot $bot
   * @return Response|false
   */
  public static function process($bot) {
    if(!isset($bot->req->message)) return false;
    if($bot->req->command->valid and array_key_exists($bot->req->command->cmd, $bot->commands)
      and (empty($bot->req->command->bot) or $bot->req->command->bot == $bot->me()->username)) {
      return $bot->commands[$bot->req->command->cmd]($bot->req);
    } return false;
  }
}

class Keyword {
  private $keywords;
  public function __construct($keyword, $_) {
    foreach(func_get_args() as $word) array_push($this->keywords, $word);
  }

  /**
   * @param Bot $bot
   * @return Response|false
   */
  public static function process($bot) {
    if(!isset($bot->req->message)) return false;
    foreach($bot->keywords as $word => $callable) {
      if(stristr($bot->req->message->text, $word)) return $callable($bot->req);
    } return false;
  }
}

class Inline {
  /**
   * @param Bot $bot
   * @return Response|false
   */
  public static function process($bot) {
    if(!isset($bot->req->inline_query)) return false;
    preg_match("/^\w+/", $bot->req->inline_query->query, $match);
    $word = $match[0];
    foreach($bot->inlines as $inline => $callable) {
      if(strcasecmp($word, $inline)) $callable($bot->req);
    } if(array_key_exists('default', $bot->inlines)) $bot->inlines['default']($bot->req);
    return false;
  }
}

/** @see https://core.telegram.org/bots/api#available-methods */
class Response {
  public $method;
  public $content;

  public function __construct($method, $content) {
    $this->method = $method;
    $this->content = $content;
  }

  const REPLY_IN_GROUP = 0, TO_CHAT = 1, REPLY_TO_MESSAGE = 2, REPLY_TO_REPLIED = 3, TO_SENDER = 4;

  /**
   * @see Response::TO_CHAT, Response::TO_SENDER, Response::REPLY_TO_MESSAGE, Response::REPLY_TO_REPLIED
   * @param integer $type   Type of response (class constants)
   * @param Request $req    The Request to generate the Response from
   * @param array   $add    Data to add to the generated Response
   * @param bool    $bypass
   *   If reply_to_message is non-existent, false is returned, if bypass is set to false.
   *   Else, the original message is used instead.
   * @return Response
   */
  public static function build($req, $add = null, $method = "sendMessage",
                               $type = Response::REPLY_IN_GROUP, $bypass = true) {
    $array = array();
    switch ($type) {
      case self::REPLY_TO_MESSAGE:
        $array['reply_to_message_id'] = $req->message->message_id;
        $array['chat_id'] = $req->message->chat->id;
        break;
      case self::REPLY_IN_GROUP:
        if($req->message->chat->type != 'private') $array['reply_to_message_id'] = $req->message->message_id;
        $array['chat_id'] = $req->message->chat->id;
        break;
      case self::REPLY_TO_REPLIED:
        if(isset($req->message->reply_to_message)) $array['reply_to_message_id'] = $req->message->reply_to_message->id;
        else if(!$bypass) return false;
        $array['chat_id'] = $req->message->chat->id;
        break;
      case self::TO_CHAT:   $array['chat_id'] = $req->message->chat->id; break;
      case self::TO_SENDER: $array['chat_id'] = $req->message->from->id; break;
    }
    $array = array_merge($array, $add);
    return new Response($method, $array);
  }
}

/** @see https://core.telegram.org/bots/api#update */
class Request {
  private function __construct() {}

  /** @var Command */
  public $command;

  /**
   * @param stdClass $json json_decode()'d API request
   * @return Request
   */
  public static function map($json) {
    $request = obj2obj($json, "Request");
    if(isset($request->message->text)) $request->command = new Command($request->message->text);
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

