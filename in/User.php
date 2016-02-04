<?php
namespace in;

class User {
  private $id;
  private $username;
  private $first_name;
  private $last_name;

  public function __construct($user) {
    if(gettype($user) == 'array') foreach($user as $item => $value) $this->item = $value;
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

  public function updateUser($user) {
    $mysqli = db_connect();

    $result = $mysqli->query("SELECT * FROM userdata WHERE id={$this->id}");

    if(mysqli_num_rows($result)>0) {
      $array = array();
      foreach(get_object_vars($this) as $item => $value) {
        array_push($array, "{$item}={$value}");
      }
      $sql = "UPDATE userdata SET "
        . implode("', '", get_object_vars($this))
        . "WHERE id={$this->id}";
    } else {
      $sql = "INSERT INTO userdata ('"
        . implode("', '", array_keys(get_object_vars($this)))
        . "') VALUES ('"
        . implode("', '", get_object_vars($this))
        ."')";
    }
    echo $sql;
    $mysqli->query($sql);

  }
}
