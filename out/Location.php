<?php
namespace out;

class Location extends Update {
  protected $method = "sendLocation";

  protected $latitude;
  protected $longitude;

  public function __construct($chat_id, $latitude, $longitude) {
    parent::__construct($chat_id);
    $this->setLatitude($latitude);
    $this->setLongitude($longitude);
  }

  private function setLatitude($latitude) {
    $this->latitude = (float)$latitude;
  }
  private function setLongitude($longitude) {
    $this->longitude = (float)$longitude;
  }
}
