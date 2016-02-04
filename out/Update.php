<?php
namespace out;

class Update implements \JsonSerializable {
  protected $method = 'getMe';

  protected $chat_id;
  protected $reply_to_message_id;
  protected $reply_markup;

  /**
   * Update constructor.
   * @param int|array $chat_id Chat ID or array containing the Update's information
   * @param string $method Method for the Telegram API
   */
  public function __construct($chat_id, $method = null) {
    if(gettype(func_get_arg(0)) == 'array') {
      if(isset($method)) $this->setMethod($method);
      foreach (func_get_arg(0) as $key => $value) {
        $this->$key = $value;
      }
    } else {
      $this->setChatId($chat_id);
      if(isset($method)) $this->setMethod($method);
    }
  }

  public function __get($name) {
    return $this->$name;
  }

  public function getMethod() {
    return $this->method;
  }

  private function setMethod($method) {
    $this->method = $method;
  }

  public function setChatId($chat_id) {
    if(gettype($chat_id) == 'integer') {
      $this->chat_id = $chat_id;
    } elseif(intval($chat_id)) {
      $this->chat_id = intval($chat_id);
    } else throw new \Exception('Invalid Chat ID.', 415);
  }

  public function setReply($reply_to_message_id) {
    if(!isset($reply_to_message_id)) return;
    if(gettype($reply_to_message_id) == 'integer') {
      $this->reply_to_message_id = $reply_to_message_id;
    } else throw new \Exception('Invalid Message ID.', 415);
  }

  public function setReplyMarkup($reply_markup) {
    $this->reply_markup = $reply_markup;
  }

  public function jsonSerialize() {
    $array = obj2array($this);
    unset($array['method']);
    return $array;
  }

  public static function send($update = array(), $method = null) {
    global $bot;
    if($update instanceof Update) return $bot->send($update);
    else if(gettype($update) == 'array')
      return $bot->send(new self($update, $method));
    else throw new \Exception("Invalid update!", 415);
  }

  public static function reply($update, $method = null, $reply_to_message_id = null) {
    global $bot, $message_id;
    if(!isset($reply_to_message_id)) $reply_to_message_id = $message_id;
    $update['reply_to_message_id'] = $reply_to_message_id;
    return Update::send($update, $method);
  }

  public static function auto($text, $parse_mode = "") {
    global $chat;
    if($chat->type == 'group')
      return self::reply($text, $parse_mode);
    else
      return self::send($text, $parse_mode);
  }
}
