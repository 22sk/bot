<?php
/**
 * @author Samuel Kaiser <samuel.kaiser01@gmail.com>
 * @since 16.05.2016
 */

/**
 * @method $this chat_id(integer $chat_id)
 * @method $this disable_notification(bool $disable_notification)
 * @method $this reply_to_message_id(integer $reply_to_message_id)
 * @method $this reply_markup(array $reply_markup)
 */
abstract class ResponseBuilder extends Response {
  public $name;
  public function __construct($value, $req, $add = null) {
    parent::__construct($this->method, $add);
    $this->req = $req;
    if(!empty($this->name)) {
      $name = $this->name;
      $this->$name($value);
    }
  }
}

abstract class Sendable extends ResponseBuilder {
  const REPLY_IN_GROUP = 0, TO_CHAT = 1, REPLY_TO_MESSAGE = 2, REPLY_TO_REPLIED = 3, TO_SENDER = 4;

  public function __construct($value, $req, $add = null) {
    parent::__construct($value, $req, $add);
    $this->to(self::REPLY_IN_GROUP);
  }

  /**
   * @param integer $mode
   * @return $this
   */
  public function to($mode) {
    if(!isset($this->req->message)) return false;
    $message = &$this->req->message;
    switch ($mode) {
      case self::REPLY_TO_MESSAGE:
        $this->reply_to_message_id($message->message_id);
        $this->chat_id($message->chat->id);
        break;
      case self::REPLY_IN_GROUP:
        if($message->chat->type != 'private') $this->reply_to_message_id($message->message_id);
        $this->chat_id($message->chat->id);
        break;
      case self::REPLY_TO_REPLIED:
        if(isset($message->reply_to_message))
          $this->reply_to_message_id($message->reply_to_message->id);
        else $this->chat_id($message->chat->id);
        break;
      case self::TO_CHAT:   $this->chat_id($message->chat->id); break;
      case self::TO_SENDER: $this->chat_id($message->from->id); break;
    } return $this;
  }
}


/**
 * @method $this chat_id(integer $chat_id)
 * @method $this text(string $text)
 * @method $this parse_mode(string $parse_mode)
 * @method $this disable_web_page_preview(bool $disable_web_page_preview)
 */
class Message extends Sendable {
  public $method = "sendMessage";
  public $name = "text";
}

/**
 * @method $this message_id(integer $message_id)
 * @method $this chat_id(integer $chat_id) Defaults to the chat where the request was sent from.
 * @method $this from_chat_id(integer $chat_id) Defaults to $chat_id
 * @method $this disable_notification(bool $disable_notification)
 */
class Forward extends Sendable {
  public $method = "forwardMessage";
  public $name = "message_id";

  public function to($mode) {
    $message = $this->req->message;
    switch($mode) {
      case self::TO_CHAT:   $this->content['chat_id'] = $message->chat->id; break;
      case self::TO_SENDER: $array['chat_id'] = $message->from->id; break;
    } return $this;
  }
}

/**
 * @method $this photo(string $photo)
 * @method $this caption(string $caption)
 */
class Photo extends Sendable {
  public $method = "sendPhoto";
  public $name = "photo";
}

/**
 * @method $this audio(string $audio)
 * @method $this duration(integer $duration)
 * @method $this performer(string $performer)
 * @method $this title(string $title)
 */
class Audio extends Sendable {
  public $method = "sendPhoto";
  public $name = "audio";
}

/**
 * @method $this document(string $document)
 * @method $this caption(string $caption)
 */
class Document extends Sendable {
  public $method = "sendDocument";
  public $name = "document";
}

/**
 * @method $this sticker(string $sticker)
 */
class Sticker extends Sendable {
  public $method = "sendSticker";
  public $name = "sticker";
}

/**
 * @method $this video(string $video)
 * @method $this duration(integer $duration)
 * @method $this width(integer $width)
 * @method $this height(integer $height)
 * @method $this caption(string $caption)
 */
class Video extends Sendable {
  public $method = "sendVideo";
  public $name = "video";
}

/**
 * @method $this voice(string $voice)
 * @method $this duration(integer $duration)
 */
class Voice extends Sendable {
  public $method = "sendVoice";
  public $name = "voice";
}

/**
 * @method $this latitude(float $latitude)
 * @method $this longitude(float $longitude)
 */
class Location extends Sendable {
  public $method = "sendLocation";
  public function __construct($latitude, $longitude, $req, $add = null) {
    parent::__construct(null, $req);
    $this->latitude($latitude);
    $this->longitude($longitude);
  }
}

/**
 * @method $this results(array $results)
 * @method $this inline_query_id(string $inline_query_id)
 * @method $this cache_time(integer $cache_time)
 * @method $this is_personal(bool $is_personal)
 * @method $this next_offset(string $next_offset)
 * @method $this switch_pm_text(string $switch_pm_text)
 * @method $this switch_pm_parameter(string $switch_pm_parameter)
 */
class Inline extends ResponseBuilder {
  const TO_SENDER = 0;
  public $method = "answerInlineQuery";
  public $name = "results";
  public function __construct($results, $req, $add = null) {
    parent::__construct($results, $add);
    $this->to(self::TO_SENDER);
  }
  public function to($mode) {
    switch($mode) {
      case self::TO_SENDER: $this->inline_query_id($this->req->inline_query->id); break;
    } return $this;
  }
}
