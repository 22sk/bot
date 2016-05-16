<?php
/**
 * @author Samuel Kaiser <samuel.kaiser01@gmail.com>
 * @since 16.05.2016
 */

class Keyword extends Processable {
  public static function register($bot, $name, $callable, $help = null, $hidden = false) {
    return parent::register($bot, $name, $callable, $help, $hidden);
  }

  public static function process($bot) {
    if(empty($bot->req->message)) return false;
    foreach($bot->keyword as $word => $value) {
      if(stristr($bot->req->message->text, $word)) return $value['callable']($bot->req);
    } return false;
  }
}

/**
 * @method $this photo(string $photo)
 * @method $this caption(string $caption)
 */
class Photo extends Sendable {
  public $method = "sendPhoto";
  public function __construct($photo, $req, $add = null) {
    parent::__construct($this->method, $add);
    $this->photo($photo);
  }
}

/**
 * @method $this audio(string $audio)
 * @method $this duration(integer $duration)
 * @method $this performer(string $performer)
 * @method $this title(string $title)
 */
class Audio extends Sendable {
  public $method = "sendPhoto";
  public function __construct($audio, $req, $add = null) {
    parent::__construct($req, $add);
    $this->audio($audio);
  }
}

/**
 * @method $this document(string $document)
 * @method $this caption(string $caption)
 */
class Document extends Sendable {
  public $method = "sendDocument";
  public function __construct($document, $req, $add = null) {
    parent::__construct($req, $add);
    $this->document($document);
  }
}

/**
 * @method $this sticker(string $sticker)
 */
class Sticker extends Sendable {
  public $method = "sendSticker";
  public function __construct($sticker, $req, $add = null) {
    parent::__construct($req, $add);
    $this->sticker($sticker);
  }
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
  public function __construct($video, $req, $add = null) {
    parent::__construct($req, $add);
    $this->video($video);
  }
}

/**
 * @method $this voice(string $voice)
 * @method $this duration(integer $duration)
 */
class Voice extends Sendable {
  public $method = "sendVoice";
  public function __construct($voice, $req, $add = null) {
    parent::__construct($req, $add);
    $this->voice($voice);
  }
}

/**
 * @method $this latitude(float $latitude)
 * @method $this longitude(float $longitude)
 */
class Location extends Sendable {
  public $method = "sendLocation";
  public function __construct($latitude, $longitude, $req, $add = null) {
    parent::__construct($req, $add);
    $this->latitude($latitude);
    $this->longitude($longitude);
  }
}
