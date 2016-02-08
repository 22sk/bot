<?php
namespace in;

class Message {
  private $message_id;
  private $chat;
  private $from;
  private $date;
  private $reply_to_message;

  private $text;
  private $audio;
  private $document;
  private $photo;
  private $sticker;
  private $video;
  private $voice;

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

  public function __get($name) {
    return $this->$name;
  }

  public function getMessageId() {
    return $this->message_id;
  }
  public function getChat() {
    return $this->chat;
  }
  public function getFrom() {
    return $this->from;
  }
  public function getReplyToMessage() {
    return $this->reply_to_message;
  }
  public function getDate() {
    return $this->date;
  }

  public function getType() {
    $types = array_keys(json_decode(file_get_contents('types.json'), true));
    foreach($types as $value) {
      if(property_exists($this, $value)) return $value;
    }
    throw new \Exception("No valid message field found.", 406);
  }

  public function process() {
    global $from;
    $done = false;
    switch($this->getType()) {
      case 'text':
        if(Command::parseMessage(get_object_vars($this)['text'])) {
          $cmd = new Command($this);
          $done = $cmd->process();
        } break;
    }
    $user = new User($from);
    if(!$done and !$user->isSkipped()) $this->textReply();


    $user = new User($this->from);
    echo "User:\n".json_encode($user, JSON_PRETTY_PRINT)."\n";
    $user->updateUserData();

    $chat = new Chat($this->chat);
    echo "Chat:\n".json_encode($chat, JSON_PRETTY_PRINT)."\n";
    if($chat->getType() != 'private') $chat->updateGroupData();

    return $done;
  }

  public function textReply() {
    $replys = json_decode(
      file_get_contents('https://gist.githubusercontent.com/22sk/f2ab9f34b4cc1ee81b4a/raw/replys.json'), true
    );
    $reply = null;
    foreach($replys['text'] as $key => $value) {
      if (strpos(strtolower($this->text), $key) !== false) $reply = $key;
    }
    if(isset($reply))
      return \out\Message::auto($replys['text'][$reply]['texts'][
      array_rand($replys['text'][$reply]['texts'])
      ], 'Markdown');
    else return false;
  }
}
