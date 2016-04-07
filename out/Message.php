<?php
namespace out;

class Message extends Update {
  protected $method = "sendMessage";

  protected $text;
  protected $parse_mode;
  protected $disable_web_page_preview;

  /**
   * Message constructor.
   * @param array|int $chat_id
   * @param string $text
   * @param string $parse_mode
   * @param int $reply_to_message_id
   * @param bool $disable_web_page_preview
   */
  public function __construct($chat_id, $text, $parse_mode = null,
                              $reply_to_message_id = null,
                              $disable_web_page_preview = false) {
    parent::__construct($chat_id);
    $this->setText($text);
    $this->setParseMode($parse_mode);
    $this->setDisableWebPagePreview($disable_web_page_preview);
    $this->setReply($reply_to_message_id);
  }

  private function setText($text) {
    $this->text = $text;
  }

  private function setParseMode($parse_mode) {
    $this->parse_mode = $parse_mode;
  }

  private function setDisableWebPagePreview($disable_web_page_preview) {
    $this->disable_web_page_preview = $disable_web_page_preview;
  }

  /**
   * Sends a message to the incoming message's chat.
   * Automatically decides if the given text should be sent as a
   * regular message or as a reply to the incoming message.
   * Depends on the chat type: group -> reply, else -> regular message
   * @param $text
   * @param string $parse_mode
   * @return Update
   */

  public static function auto($text, $parse_mode = "") {
    /**
     * @var \in\Chat $chat
     * @var \in\Message $message
     */
    global $chat, $message;
    if($chat->getType() == 'group') {
      if(null !== $message->getReplyToMessage())
        return self::replyMessage($text, $parse_mode, $message->getReplyToMessage()->getMessageId());
      else return self::replyMessage($text, $parse_mode);
    } else return self::sendMessage($text, $parse_mode);
  }

  /**
   * Forces to send a message with a reply to the incoming message's chat.
   * Uses the incoming message as the message to reply to if no ID is given.
   * @param $text
   * @param string $parse_mode
   * @param int $reply_to_message_id
   * @return Update
   */
  public static function replyMessage($text, $parse_mode = "", $reply_to_message_id = null) {
    /**
     * @var \in\Chat $chat
     * @var \out\Bot $bot
     */
    global $chat, $bot, $message_id;
    $result = $bot->send(new self($chat->getId(), $text, $parse_mode, $reply_to_message_id));
    if($result->ok == false) {
      return self::sendMessage($text, $parse_mode);
    } else return $result;
  }

  /**
   * Sends a regular message to the incoming message's chat.
   * @param array $text
   * @param string $parse_mode
   * @return Update
   */
  public static function sendMessage($text, $parse_mode = "") {
    /**
     * @var \in\Chat $chat
     * @var \out\Bot $bot
     */
    global $chat, $bot;
    return $bot->send(new self($chat->getId(), $text, $parse_mode));
  }
}
