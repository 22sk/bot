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

  public static function auto($update, $method = null) {
    /**
     * @var \in\Chat $chat
     */
    global $chat;
    if($chat->getType() == 'group')
      return self::reply($update, $method);
    else
      return self::send($update, $method);
  }

  public static function reply($update, $method = null, $reply_to_message_id = null) {
    /**
     * @var \in\Chat $chat
     * @var \out\Bot $bot
     */
    global $bot, $message_id, $chat;
    if ($update instanceof Update) return $bot->send($update);
    elseif (gettype($update) == 'array') {
      if (!isset($reply_to_message_id)) $reply_to_message_id = $message_id;
      if (!isset($update['chat_id'])) $update['chat_id'] = $chat->getId();
      $update['reply_to_message_id'] = $reply_to_message_id;
      $result = Update::send($update, $method);
      if(json_decode($result)->ok == 'false') {
        unset($update[$reply_to_message_id]);
        return Update::send($update, $method);
      } else return $result;
    } else throw new \Exception("Invalid update!", 415);
  }

  public static function send($update, $method = null) {
    /**
     * @var \in\Chat $chat
     * @var \out\Bot $bot
     */
    global $bot, $chat;
    if ($update instanceof Update) return $bot->send($update);
    elseif (gettype($update) == 'array') {
      if (!isset($update['chat_id'])) $update['chat_id'] = $chat->getId();
      return $bot->send(new self($update, $method));
    } else throw new \Exception("Invalid update!", 415);
  }

  public static function getMethodIn($meme) {
    $types = json_decode(file_get_contents('https://bot-22sk.rhcloud.com/types.json'));
    foreach($meme as $item => $value) {
      if(isset($types->$item)) return $types->$item;
    } return false;
  }

  public function __get($name) {
    return $this->$name;
  }

  public function getMethod() {
    return $this->method;
  }

  public function setReply($reply_to_message_id) {
    if(intval($reply_to_message_id)) {
      $this->reply_to_message_id = intval($reply_to_message_id);
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
}
