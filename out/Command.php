<?php
namespace out;
use \out\Message as msg;

abstract class Command {
  protected $cmd;
  protected $args;
  protected $result;

  /**
   * Command constructor.
   * @param \in\Command $cmd
   */
  public function __construct($cmd) {
    $this->cmd = $cmd;
    $this->args = $cmd->getArgs();
    $this->result = $this->process();
  }

  protected function process() { return false; }

  public function getCmd() { return $this->cmd; }

  public function getArgs() { return $this->args; }

  public function getResult() { return $this->result; }

}

class CommandPing extends Command {
  protected function process() {
    $time = time() - $this->cmd->getMessage()->getDate();
    return msg::auto("*Pong!* {$time}s", "Markdown");
  }
}

class CommandPong extends Command {
  protected function process() {
    $time = $this->cmd->getMessage()->getDate() - time();
    return msg::auto("*Ping!* {$time}s", "Markdown");
  }
}

class CommandAbout extends Command {
  private $you;
  private $message;
  public function __construct(\in\Command $cmd) {
    $this->you = null; // "Modified by @yourusername";
    $this->message = file_get_contents(
      'https://gist.githubusercontent.com/22sk/655d8aaa39c947ea79dc/raw/about.md'
    ); // DO NOT EDIT
    parent::__construct($cmd);
  }
  protected function process() {
    return msg::auto(
    # DO NOT REMOVE $this->message!
      $this->message."\n".(isset($this->you) ? $this->you : ''), "Markdown"
    );
  }
}

class CommandSkip extends Command {
  protected function process() {
    $user = new \in\User($this->cmd->getMessage()->getFrom());
    $this->skip($user);
  }
  /**
   * @param \in\User $user
   * @throws \Exception MySQLi error
   */
  private function skip($user) {
    $db_name = getenv("DB_NAME");
    if($mysqli = db_connect()) {
      if ($user->isSkipped($mysqli)) {
        msg::auto("Welcome back!");
        $sql = "UPDATE {$db_name}.userdata SET skipped='0' WHERE userdata.id = "
          . $this->cmd->getMessage()->getFrom()->getId();
      } else {
        msg::auto("Disabling automatic replys for you.");
        $sql = "UPDATE {$db_name}.userdata SET skipped='1' WHERE userdata.id = "
          . $this->cmd->getMessage()->getFrom()->getId();
      }
      if (!$mysqli->query($sql))
        throw new \Exception($mysqli->error, $mysqli->errno);
      $mysqli->close();
    } else {
      msg::auto("MySQL database not reachable!");
    }
  }
}

class CommandUpdate extends Command {
  protected function process() {
    global $update;
    return msg::auto("```\n".json_encode($update, JSON_PRETTY_PRINT)."\n```", "Markdown");
  }
}

class CommandHelp extends Command {
  protected function process() {
    return msg::auto(
      "*Not all commands have already been implemented yet!*\n".
      file_get_contents('https://gist.githubusercontent.com/22sk/7cc3f6e109779353aa2b/raw/commands.txt'), "Markdown");
  }
}

class CommandMeme extends Command {
  protected function process() {
    $memes = json_decode(
      file_get_contents('https://gist.githubusercontent.com/22sk/92e7e0d2577ac3e1c167/raw/memes.json'), true
    );
    $name = str_clean($this->args);

    if(empty($this->args)) {
      return msg::auto("Available memes:\n`".implode(", ", array_keys($memes))."`", "Markdown");
    } else if(array_key_exists($name, $memes) or find_alias($memes, strtolower($name))) {
      $method = Update::getMethodIn($memes[$name]);
      if(!isset($method)) throw new \Exception("Invalid meme!");
      return Update::auto($memes[$name], Update::getMethodIn($memes[$name]));
    } else {
      return Message::auto("Unknown meme! Use /meme to get a list of all available memes.");
    }
  }
}

class CommandHost extends Command {
  protected function process() {
    return Message::auto("Hoster: `".getHostname()."`", "Markdown");
  }
}


class CommandUser extends Command {
  protected function process() {
    if($mysqli = db_connect()) {
      if (empty($this->args)) {
        if ($this->cmd->getMessage()->getReplyToMessage() != null)
          $user = $this->cmd->getMessage()->getReplyToMessage()->getFrom();
        else $user = $this->cmd->getMessage()->getFrom();
        $user = new \in\User($user);
        msg::auto(
          "Username: @{$user->getUsername()}\n" .
          "First name: `{$user->getFirstName()}`\n" .
          "Last name: `{$user->getLastName()}`\n" .
          "User ID: `{$user->getID()}`\n",
          "Markdown"
        );
      } else {
        if (intval($this->args)) {
          $id = intval($this->args);
          $result = $mysqli->query("SELECT * FROM userdata WHERE id = {$id}");
        } else {
          $result = $mysqli->query("SELECT * FROM userdata WHERE LOWER(username) = LOWER('{$this->args}')");
        }
        if (mysqli_num_rows($result) > 0) {
          $result = markdown_escape(mysqli_fetch_assoc($result));
          msg::auto(
            "Username: @{$result['username']}\n" .
            "First name: `{$result['first_name']}`\n" .
            "Last name: `{$result['last_name']}`\n" .
            "User ID: `{$result['id']}`\n" .
            "Last updated: `{$result['last_updated']}`\n",
            "Markdown"
          );
        } else msg::auto("Unknown user.");
      }
    } else {
      msg::auto("MySQL database not reachable!");
    }
  }
}

class CommandId extends Command {
  protected function process() {
    if(empty($args) and is_null($this->cmd->getMessage()->getReplyToMessage())) {
      if($this->cmd->getMessage()->getChat()->getType() != 'private') {
        msg::auto("Group ID:"); msg::sendMessage('`'.$this->cmd->getMessage()->getChat()->getId().'`', "Markdown");
      } else {
        msg::auto("User ID:"); msg::sendMessage('`'.$this->cmd->getMessage()->getFrom()->getId().'`', "Markdown");
      }
    }
  }
}

class CommandAll extends Command {
  protected function process() {
    /** @var \in\Chat $chat */
    global $chat;
    if($mysqli = db_connect()) {
      $result = $mysqli->query("SELECT members FROM groupdata WHERE id={$chat->getId()}");
      if($result->num_rows>0) {
        $array = json_decode($result->fetch_assoc()['members'], true);
        $usernames = array();
        for($i=0; $i<count($array); $i++) {
          array_push($usernames, \in\User::getUserDatabase($array[$i], $mysqli)->getUsername(false));
        }
        msg::sendMessage('@'.implode(', @', $usernames));
      }
    }
  }
}
