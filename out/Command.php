<?php
namespace out;

class Command {
  public static function __call($name, $args) {
    if(strpos($name, 'cmd') !== 0) {
      $name = 'cmd' . $name;
      self::$name($args);
    }
  }

  public static function cmdPing($args = null, $cmd) {
    $time = time() - $cmd->message->date;
    return \out\Message::auto("*Pong!* {$time}s", "Markdown");
  }

  public static function cmdPong($args = null, $cmd) {
    $time = time() - $cmd->message->date;
    return \out\Message::auto("*Ping!* -{$time}s", "Markdown");
  }

  public static function cmdHelp($args = null, $cmd = null) {
    return \out\Message::auto("Maybe, some day, I'll help you.", "Markdown");
  }

  /**
   * @todo implement memes.json (https://gist.githubusercontent.com/22sk/92e7e0d2577ac3e1c167/raw/memes.json)
   */
  public static function cmdMeme($args = null, $cmd = null) {
    return \out\Message::auto("Coming soon!");
  }

  public static function cmdHost($args = null, $cmd = null) {
    return \out\Message::auto("Hoster: `".gethostname()."`", "Markdown");
  }
}
