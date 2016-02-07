<?php
namespace in;

use out\Message;

class Command implements \JsonSerializable {
  private $message;
  private $text;
  private $cmd;
  private $bot;
  private $args;

  /**
   * Command constructor.
   * @param Message|string $message
   */
  public function __construct($message) {
    foreach (self::parseMessage($message) as $key => $value) {
      $this->$key = $value;
    }
  }

  public function getMessage() {
    return $this->message;
  }
  public function getText() {
    return $this->text;
  }
  public function getBot() {
    return $this->bot;
  }
  public function getArgs() {
    return $this->args;
  }

  public function __isset($name) {
    return isset($this->$name);
  }

  /**
   * Used to separate a Message into an array containing all necessary information.
   * @param Message|string $msg
   *   Message to generate the Command from.
   * @param bool $del_message
   *   Set to true to not include the Message object in the returned array.
   *   Is always true if a string is passed.
   * @return array|bool
   *   Array including all information or false if cannot be parsed to a Command.
   */
  public static function parseMessage($msg, $del_message = false) {
    if(gettype($msg) == 'string') {
      $msg = new \in\Message(array('text' => $msg));
      $del_message = true;
    }
    $keys = array('message', 'text', 'cmd', 'bot', 'args');
    preg_match("/^\/([^@\s]+)@?(?:(\S+)|)\s?(.*)$/i", get_object_vars($msg)['text'], $array);
    if (empty($array)) return false;

    $cmd = array('message' => $msg);
    for ($i = 0; $i < count($array); $i++) {
      $cmd[$keys[$i + 1]] = $array[$i];
    }
    if($del_message) unset($cmd['message']);
    return array_filter($cmd);
  }



  public function process() {
    global $bot;
    echo "Command:\n".json_encode($this, JSON_PRETTY_PRINT)."\n";
    if(!isset($this->bot) or $this->bot == User::getMe()->username) {
      $func = 'cmd'.$this->cmd;
      if(method_exists('\out\Command', $func))
        return \out\Command::$func($this->args, clone $this);
      elseif(array_key_exists(strtolower($this->cmd), json_decode(
        file_get_contents('https://gist.githubusercontent.com/22sk/f2ab9f34b4cc1ee81b4a/raw/replys.json'), true
      )['command'])) {
        $replys = json_decode(
          file_get_contents('https://gist.githubusercontent.com/22sk/f2ab9f34b4cc1ee81b4a/raw/replys.json'), true
        );
        return Message::auto($replys['command'][strtolower($this->cmd)]['texts'][
          array_rand($replys['command'][strtolower($this->cmd)]['texts'])
        ], "Markdown");
      } elseif($this->bot == User::getMe()->username) {
        Message::auto("That command does not exist or has not been implemented yet.");
        return false;
      }
    }
  }

  public function jsonSerialize() {
    return get_object_vars($this);
  }
}
