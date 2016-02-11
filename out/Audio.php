<?php
namespace out;

class Audio extends Update {
  protected $method = "sendAudio";

  protected $audio;
  protected $performer;
  protected $title;
  protected $duration;

  public function __construct($chat_id, $audio, $performer = null, $title = null, $duration = null) {
    parent::__construct($chat_id);
    $this->setAudio($audio);
    $this->setPerformer($performer);
    $this->setTitle($title);
    $this->setDuration($duration);
  }

  private function setAudio($audio) {
    $this->audio = $audio;
  }

  public function setPerformer($performer) {
    $this->performer = $performer;
  }

  public function setTitle($title) {
    $this->title = $title;
  }

  public function setDuration($duration) {
    $this->duration = $duration;
  }
}
