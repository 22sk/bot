<?php
namespace out;

class Forward extends Update {
  protected $method = 'forwardMessage';

  protected $from_chat_id;
  protected $message_id;

  public function __construct($chat_id, $from_chat_id, $message_id) {
    parent::__construct($chat_id);
    $this->setFromChatId($from_chat_id);
    $this->setMessageId($message_id);
  }

  private function setFromChatId($from_chat_id) {
    if(gettype($from_chat_id) == 'integer' || preg_match('/^@[A-Za-z_0-9]+$/', $from_chat_id))
      $this->from_chat_id = $from_chat_id;
    else throw new \Exception('Invalid Chat ID. Allowed are an integer or an @username of a channel.');
  }

  private function setMessageId($message_id) {
    if(gettype($message_id) == 'integer') {
      $this->message_id = $message_id;
    } else throw new \Exception('Invalid Message ID. Must be an integer.');
  }
}
