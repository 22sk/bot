<?php
namespace out;

class Photo extends Update {
  protected $method = "sendPhoto";

  protected $photo;
  protected $caption;

  public function __construct($chat_id, $photo, $caption = null) {
    parent::__construct($chat_id);
    $this->setPhoto($photo);
    $this->setCaption($caption);
  }

  private function setPhoto($photo) {
    $this->photo = $photo;
  }
  public function setCaption($caption) {
    $this->caption = $caption;
  }
}
