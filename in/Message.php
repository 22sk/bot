<?php
namespace in;

class Message implements \JsonSerializable {
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
        $types = json_decode(file_get_contents('in/types.json'), true);
        if(isset($types['message'][$key]['__class']) and isset($value)) {
          $this->$key = new $types['message'][$key]['__class']($value);
        } else $this->$key = $value;
        if(!isset($GLOBALS[$key])) $GLOBALS[$key] = $this->$key;
      }
    else if(gettype($message) == 'array')
      foreach($message as $item => $value) $this->$item = $value;
    else throw new \Exception("Invalid message type! Allowed: Array or Object.", 415);
  }

  /**
   * @return string
   */
  public function getText() {
    return $this->text;
  }

  /**
   * @return string
   */
  public function getAudio() {
    return $this->audio;
  }

  /**
   * @return string
   */
  public function getDocument() {
    return $this->document;
  }

  /**
   * @return string
   */
  public function getPhoto() {
    return $this->photo;
  }

  /**
   * @return string
   */
  public function getSticker() {
    return $this->sticker;
  }

  /**
   * @return string
   */
  public function getVideo() {
    return $this->video;
  }

  /**
   * @return string
   */
  public function getVoice() {
    return $this->voice;
  }

  /**
   * @return integer
   */
  public function getMessageId() {
    return $this->message_id;
  }

  /**
   * @return \in\Chat
   */
  public function getChat() {
    return $this->chat;
  }

  /**
   * @return \in\Chat
   */
  public function getFrom() {
    return $this->from;
  }

  /**
   * @return \in\Message
   */
  public function getReplyToMessage() {
    return $this->reply_to_message;
  }

  /**
   * @return integer
   */
  public function getDate() {
    return $this->date;
  }

  public function process() {
    global $from;
    $done = false;
    debug("Message Type: ".$this->getType()."\n");
    switch($this->getType()) {
      case 'text':
        if(Command::parseMessage($this->text)) {
          $cmd = new Command($this);
          $done = $cmd->process();
        } break;
    }
    $user = new User($from);
    if(!$done and !$user->isSkipped()) $this->textReply();


    $user = new User($this->from);
    debug("User:\n".json_encode($user, JSON_PRETTY_PRINT)."\n");
    $user->updateUserData();

    $chat = new Chat($this->chat);
    debug("Chat:\n".json_encode($chat, JSON_PRETTY_PRINT)."\n");
    if($chat->getType() != 'private') $chat->updateGroupData();

    return $done;
  }

  public function getType() {
    $types = array_keys(json_decode(file_get_contents('types.json'), true));
    foreach($types as $item) {
      if(property_exists($this, $item)) return $item;
    } return false;
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

  public function jsonSerialize() {
    $array = obj2array($this);
    unset($array['method']);
    return $array;
  }
}
