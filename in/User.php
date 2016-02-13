<?php
namespace in;

class User implements \JsonSerializable {
  private $id;
  private $username;
  private $first_name;
  private $last_name;

  /**
   * User constructor.
   * @param array|object $user
   */
  public function __construct($user) {
    if(gettype($user) == 'array') foreach($user as $item => $value) $this->$item = $value;
    else foreach(get_object_vars($user) as $item => $value) $this->$item = $value;
  }

  /**
   * @param \out\Bot|null $bot
   * @return mixed
   * @throws \Exception
   */
  public static function getMe($bot = null) {
    if(!isset($bot)) $bot = $GLOBALS['bot'];
    $update = new \out\Update(0, 'getMe');
    $result = $bot->send($update);
    if(!$result->ok) throw new \Exception($result->description, $result->error_code);
    return $result->result;
  }

  public function updateUserData() {
    debug("Object vars: ".json_encode(get_object_vars($this), JSON_PRETTY_PRINT)."\n");
    $mysqli = db_connect();

    $sql = "SELECT * FROM userdata WHERE id='{$this->id}'";
    debug("\nSQL: {$sql}");
    $result = $mysqli->query($sql);

    debug("\nResult -> Num Rows: ". $result->num_rows);

    if($result and mysqli_num_rows($result)>0) {
      $array = array();
      foreach(get_object_vars($this) as $item => $value) {
        array_push($array, "{$item}='{$value}'");
      }
      $sql = "UPDATE userdata SET "
        . implode(", ", $array)
        . " WHERE id={$this->id}";
    } else {
      $keys = implode(", ", array_keys(get_object_vars($this)));
      $values = "'".implode("', '", get_object_vars($this))."'";
      $sql = "INSERT INTO userdata ($keys) VALUES ($values)";
    }
    debug("\n".$sql."\n");
    $result = $mysqli->query($sql);
    debug("Result: "); if(DEBUG) var_dump($result);
    $mysqli->close();
    return $result;
  }


  public function getId() {
    return $this->id;
  }
  public function getUsername() {
    return markdown_escape($this->username);
  }
  public function getFirstName() {
    return markdown_escape($this->first_name);
  }
  public function getLastName() {
    return markdown_escape($this->last_name);
  }

  public function isSkipped($mysqli = null) {
    if(!isset($mysqli)) {
      $close = true;
      $mysqli = db_connect();
    } else $close = false;

    if($this->userExists($mysqli)) {
      $sql = "SELECT skipped FROM userdata WHERE id={$this->id}";
      $result = $mysqli->query($sql);
    } else return false;
    if($close) $mysqli->close();

    if($result->fetch_assoc()['skipped']) return true;
    else return false;
  }

  public function userExists($mysqli = null) {
    if(!isset($mysqli)) {
      $close = true;
      $mysqli = db_connect();
    } else $close = false;
    $sql = "SELECT id FROM userdata WHERE id={$this->id}";
    $result = $mysqli->query($sql);
    if($close) $mysqli->close();
    return ($result and $result->num_rows>0) ? true : false;
  }

  public function isBanned($mysqli = null) {
    if(!isset($mysqli)) {
      $close = true;
      $mysqli = db_connect();
    } else $close = false;

    if($this->userExists($mysqli)) {
      $sql = "SELECT banned FROM userdata WHERE id={$this->id}";
      $result = $mysqli->query($sql);
    } else return false;
    if($close) $mysqli->close();

    if($result->fetch_assoc()['banned']) return true;
    else return false;
  }

  public function jsonSerialize() {
    $array = obj2array($this);
    unset($array['method']);
    return $array;
  }
}
