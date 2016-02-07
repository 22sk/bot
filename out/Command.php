<?php
namespace out;
use \out\Message as msg;

class Command {
  public static function __callStatic($name, $args) {
    if(strpos($name, 'cmd') !== 0) {
      $name = 'cmd' . $name;
      self::$name($args);
    }
  }

  public static function cmdAbout($args = null, $cmd = null) {
    $you = ""; // "Modified by @yourusername";

    # DO NOT EDIT:
    return Message::auto(
      "Bot made by @samuelk22. View source code on [GitHub](https://github.com/22sk/telegram-bot).\n"
      .$you, "Markdown"
    );
    # -----------
  }

  /**
   * @param null $args
   * @param \in\Command $cmd
   */
  public static function cmdSkip($args = null, $cmd) {
    $mysqli = db_connect();
    $db_name = getenv("DB_NAME");

    $user = new \in\User($cmd->getMessage()->from);

    if($user->isSkipped($mysqli)) {
      msg::auto("Disabling automatic replys for you.");
      $sql = "UPDATE `{$db_name}`.`userdata` SET `skipped`='0' WHERE `userdata`.`id` = {$cmd->getMessage()->from->id}";

    } else {
      msg::auto("Welcome back!");
      $sql = "UPDATE `{$db_name}`.`userdata` SET `skipped`='1' WHERE `userdata`.`id` = {$cmd->getMessage()->from->id}";
    }
    if(!$mysqli->query($sql))
      msg::auto("Something went wrong!\n".$mysqli->errno.": ".$mysqli->error);
    $mysqli->close();
  }

  /**
   * @param null $args
   * @param \in\Command $cmd
   * @return Update
   */
  public static function cmdPing($args = null, $cmd) {
    $time = time() - $cmd->getMessage()->date;
    return Message::auto("*Pong!* {$time}s", "Markdown");
  }

  /**
   * @param null $args
   * @param \in\Command $cmd
   * @return Update
   */
  public static function cmdPong($args = null, $cmd) {
    $time = $cmd->getMessage()->date - time();
    return Message::auto("*Ping!* {$time}s", "Markdown");
  }

  public static function cmdUpdate($args = null, $cmd = null) {
    global $update;
    return Message::auto("```\n".json_encode($update, JSON_PRETTY_PRINT)."\n```", "Markdown");
  }

  public static function cmdHelp($args = null, $cmd = null) {
    return Message::auto(
      "*Not all commands have already been implemented yet!*\n".
      file_get_contents('https://gist.githubusercontent.com/22sk/7cc3f6e109779353aa2b/raw/commands.txt'), "Markdown");
  }

  public static function cmdMeme($args = null, $cmd = null) {
    $memes = json_decode(
      file_get_contents('https://gist.githubusercontent.com/22sk/92e7e0d2577ac3e1c167/raw/memes.json'), true
    );
    $name = str_clean($args);

    if(empty($args)) {
      return Message::auto("Available memes:\n`".implode(", ", array_keys($memes))."`", "Markdown");
    } else if(array_key_exists($name, $memes)) {
      $types = json_decode(file_get_contents('types.json'));
      $type = $memes[$name]['type'];
      $method = $types->$type;
      $update = array($type => $memes[$name]['id']);
      return Update::auto($update, $method);
    } else {
      return Message::auto("Unknown meme! Use /meme to get a list of all available memes.");
    }
  }

  public static function cmdHost($args = null, $cmd = null) {
    return Message::auto("Hoster: `".gethostname()."`", "Markdown");
  }

  /**
   * @param string|null $args
   * @param \in\Command|null $cmd
   */
  public static function cmdUser($args = null, $cmd = null) {
    $mysqli = db_connect();
    if(empty($args)) {
      if($cmd->getMessage()->reply_to_message != null) $user = $cmd->getMessage()->reply_to_message->from;
      else $user = $cmd->getMessage()->from;
      $user = new \in\User($user);
      Message::auto(
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
        $result = $mysqli->query("SELECT * FROM userdata WHERE LOWER(username) = LOWER('{$args}')");
      }
      if (mysqli_num_rows($result) > 0) {
        $result = markdown_escape(mysqli_fetch_assoc($result));
        Message::auto(
          "Username: @{$result['username']}\n" .
          "First name: `{$result['first_name']}`\n" .
          "Last name: `{$result['last_name']}`\n" .
          "User ID: `{$result['id']}`\n" .
          "Last updated: `{$result['last_updated']}`\n",
          "Markdown"
        );
      } else Message::auto("Unknown user.");
    }
  }
}
