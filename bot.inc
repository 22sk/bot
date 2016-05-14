<?php

/**
 * @author Samuel Kaiser <samuel.kaiser01@gmail.com>
 * @since 14.05.2016
 */
class Bot {
  /** @var Request  */ public $req;
  /** @var Response */ public $res;
  /** @var array    */ private $commands;

  const NAME = "sk22testbot";

  /**
   * Creates new Bot.
   * @param Request $req
   */
  public function __construct($url, $req = null) {
    $this->url = $url;
    $this->req = isset($req) ? Request::map($req) : Request::getRequest();
    var_dump($this);
  }

  /**
   * @param string $command
   * @param callable $callable
   */
  public function register($command, $callable) {
    $this->commands[$command] = $callable;
  }

  public function run() {
    if($this->req->command->valid and array_key_exists($this->req->command->cmd, $this->commands)
       and (empty($this->req->command->bot) or $this->req->command->bot == self::NAME)) {
      $this->send($this->commands[$this->req->command->cmd]($this->req));
    }
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
   * Used to separate a Message into an array containing all necessary information.
   * @param string $msg
   *   Message to generate the Command from.
   * @param bool $del_message
   *   Set to true to not include the Message object in the returned array.
   *   Is always true if a string is passed.
   * @return array|bool
   *   Array including all information or false if cannot be parsed to a Command.
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
      for ($cmd = array(), $i = 0; $i < count($array); $i++) $this->$keys[$i] = $array[$i];
      $this->valid = true;
    }
  }
}

/** @see https://core.telegram.org/bots/api#available-methods */
class Response {
  public function __construct($method, $content) {
    $this->method = $method;
    $this->content = $content;
  }

  public $method;
  public $content;
}

/** @see https://core.telegram.org/bots/api#update */
class Request {
  private function __construct() {}

  /** @var Command */
  public $command;

  /**
   * @param $json
   * @throws Exception
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
