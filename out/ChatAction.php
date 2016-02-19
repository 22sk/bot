<?php
namespace out;

class ChatAction extends Update {
  const TYPING = "typing";
  const UPLOAD_PHOTO = "update_photo";
  const RECORD_VIDEO = "record_video";
  const UPLOAD_VIDEO = "upload_video";
  const RECORD_AUDIO = "record_audio";
  const UPLOAD_AUDIO = "upload_audio";
  const UPLOAD_DOCUMENT = "upload_document";
  const FIND_LOCATION = "find_location";
  protected $method = "sendChatAction";
  protected $action;

  public function __construct($chat_id, $action) {
    parent::__construct($chat_id);
    $this->setAction($action);
  }

  public function setAction($action) {
    $this->action = $action;
  }

  public static function sendChatAction($action) {
    /**
     * @var $bot \out\Bot
     * @var $chat \in\Chat
     */
    global $bot, $chat;
    $bot->send(new self($chat->getId(), $action));
  }
}
