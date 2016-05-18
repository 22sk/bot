<?php

/**
 * @author Samuel Kaiser <samuel.kaiser01@gmail.com>
 * @since 14.05.2016
 */

include "addons" . DIRECTORY_SEPARATOR . "processors.php";
include "addons".DIRECTORY_SEPARATOR."responses.php";

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
      if(is_subclass_of($class, 'Processor')) {
        /** @var Processor $class */
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

abstract class Processor {
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

class Command extends Processor {
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
