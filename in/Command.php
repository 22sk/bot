<?php
namespace in;

use out\Message;

class Command implements \JsonSerializable {
  /**
   * @var \in\Message $message
   */
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
    preg_match("/^\/([^@\s]+)@?(?:(\S+)|)\s?(.*)$/i", $msg->text, $array);
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
      try {
        if (method_exists('\out\Command', $func))
          return \out\Command::$func($this->args, clone $this);
        elseif ($result = $this->textReply()) {
          return $result;
        } elseif ($this->bot == User::getMe()->username) {
          Message::auto("That command does not exist or has not been implemented yet.");
          return false;
        }
      } catch (\Exception $e) {
        return Message::auto($e->getCode().': '.$e->getMessage());
      }
    }
  }

  public function textReply() {
    $replys = json_decode(
      file_get_contents('https://gist.githubusercontent.com/22sk/f2ab9f34b4cc1ee81b4a/raw/replys.json'), true
    );
    if(array_key_exists(strtolower($this->cmd), $replys['command'])) {
      Message::auto("ID: ".$this->getMessage()->chat->id);
      $reply = $replys['command'][strtolower($this->cmd)];
      if(!array_key_exists('allowed', $reply) or
        (array_key_exists('allowed', $reply) and in_array($this->getMessage()->chat->id, $reply['allowed'])))
      return Message::auto($reply['texts'][array_rand($reply)['texts']], "Markdown");
      else throw new \Exception("Permission denied.", 403);
    } else return false;
  }

  public function jsonSerialize() {
    return get_object_vars($this);
  }
}
