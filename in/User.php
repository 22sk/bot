<?php
namespace in;

class User implements \JsonSerializable {
  private $id;
  private $username;
  private $first_name;
  private $last_name;

  /**
   * User constructor.
   * @param array|Object $user
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
    echo "Object vars: ".json_encode(get_object_vars(), JSON_PRETTY_PRINT)."\n";
    $mysqli = db_connect();

    $sql = "SELECT * FROM userdata WHERE id='{$this->id}'";
    echo "\nSQL: {$sql}";
    $result = $mysqli->query($sql);

    echo "\nResult: ". json_encode(mysqli_fetch_assoc($result), JSON_PRETTY_PRINT)."\n";
    echo "\nNum Rows: ".mysqli_num_rows($result)."\n";

    if(mysqli_num_rows($result)>0) {
      $array = array();
      foreach(get_object_vars($this) as $item => $value) {
        array_push($array, "{$item}='{$value}'");
      }
      $sql = "UPDATE userdata SET "
        . implode(", ", $array)
        . " WHERE id={$this->id}";
    } else {
      $sql = "INSERT INTO userdata ("
        . implode(", ", array_keys(get_object_vars($this)))
        . ") VALUES ('"
        . implode("', '", get_object_vars($this))
        ."')";
    }
    echo "\n".$sql."\n";
    $mysqli->query($sql);

    $mysqli->close();
  }


  public function getID() {
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

  public function jsonSerialize() {
    $array = obj2array($this);
    unset($array['method']);
    return $array;
  }
}
