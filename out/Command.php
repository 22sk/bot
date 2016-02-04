<?php
namespace out;

class Command {
  public static function __callStatic($name, $args) {
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


  // TODO: implement memes.json (https://gist.githubusercontent.com/22sk/92e7e0d2577ac3e1c167/raw/memes.json)
  public static function cmdMeme($args = null, $cmd = null) {
    return \out\Message::auto("Coming soon!");
  }

  public static function cmdHost($args = null, $cmd = null) {
    return \out\Message::auto("Hoster: `".gethostname()."`", "Markdown");
  }

  /**
   * @param string|null $args
   * @param \in\Command|null $cmd
   */
  public static function cmdUser($args = null, $cmd = null) {
    $mysqli = db_connect();
    if(empty($args)) {
      if($cmd->message->reply_to_message != null) $user = $cmd->message->reply_to_message->from;
      else $user = $cmd->message->from;

      $user = new \in\User($user);
      \out\Message::auto(
        "Username: @{$user->getUsername()}\n".
        "First name: `{$user->getFirstName()}`\n".
        "Last name: `{$user->getLastName()}`\n".
        "User ID: `{$user->getID()}`\n",
        "Markdown"
      );
    } else {
      if (intval($args)) {
        $id = intval($args);
        $result = $mysqli->query("SELECT * FROM userdata WHERE id = {$id}");
      } else {
        $result = $mysqli->query("SELECT * FROM userdata WHERE username = {$args}");
      }
      if (mysqli_num_rows($result) > 0) {
        $result = mysqli_fetch_assoc($result);
        \out\Message::auto(
          "Username: @{$result['username']}\n" .
          "First name: `{$result['first_name']}`\n" .
          "Last name: `{$result['last_name']}`\n" .
          "User ID: `{$result['id']}`\n" .
          "Last updated: `{$result['last_updated']}`\n",
          "Markdown"
        );
      } else \out\Message::auto("Unknown user.");
    }
  }
}
