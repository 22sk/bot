<?php
namespace in;

class Message {
  private $types;

  public function __construct($message) {
    if(gettype($message) == 'object')
      foreach(get_object_vars($message) as $key => $value) {
        $this->$key = $value;
        $GLOBALS[$key] = $value;
      }
    else if(gettype($message) == 'array')
      foreach($message as $item => $value) $this->$item = $value;
    else throw new \Exception("Invalid message type! Allowed: Array or Object.", 415);
  }

  public function getType() {
    $this->types = json_decode(file_get_contents('in/types.json'));
    foreach($this->types as $value) {
      if(property_exists($this, $value)) return $value;
    }
    throw new \Exception("No valid message field found.", 406);
  }

  public function process() {
    switch($this->getType()) {
      case 'text':
        if(Command::parseMessage(get_object_vars($this)['text'])) {
          $cmd = new Command($this);
          $cmd->process();
        }
        break;
    }
    $user = new \in\User($this->user);
    $user->updateUser($user);
  }
}
