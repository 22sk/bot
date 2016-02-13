<?php
namespace out;

/**
 * Used to connect and send data to the Telegram API
 * @author 22samuelk <22samuelk@gmail.com>
 */
class Bot {
  private $url;

  public function __construct($url) {
    $this->url = $url;
  }

  /**
   * Sends an Update to the Telegram API
   * @param Update $update An Update-object
   * @return object Response by the Telegram API
   */
  public function send($update) {
    $context = stream_context_create( array(
      'http' => array(
        // http://www.php.net/manual/de/context.http.php
        'method'  => 'POST',
        'header'  => 'Content-Type: application/json',
        'ignore_errors' => true,
        'content' => json_encode($update)
      )
    ));
    $url = $this->url . $update->getMethod();
    debug("Sending update:\n".json_encode($update, JSON_PRETTY_PRINT)."\n");
    $response = file_get_contents($url, false, $context);
    debug("Response:\n".json_encode(json_decode($response), JSON_PRETTY_PRINT)."\n");
    return json_decode($response);
  }
}
