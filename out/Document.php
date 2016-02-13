<?php
namespace out;

class Document extends Update {
  protected $method = "sendDocument";

  protected $document;

  public function __construct($chat_id, $document) {
    parent::__construct($chat_id);
    $this->setDocument($document);
  }

  private function setDocument($document) {
    $this->document = $document;
  }
}
