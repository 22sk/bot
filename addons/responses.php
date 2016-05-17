<?php
/**
 * @author Samuel Kaiser <samuel.kaiser01@gmail.com>
 * @since 16.05.2016
 */

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
