<?php
namespace in;

class Chat implements \JsonSerializable {
  private $id;
  private $type;
  private $title;
  private $username;
  private $first_name;
  private $last_name;

  /**
   * Chat constructor.
   * @param array|Object $chat
   */
  public function __construct($chat) {
    if(gettype($chat) == 'array') foreach($chat as $item => $value) $this->$item = $value;
    else foreach(get_object_vars($chat) as $item => $value) $this->$item = $value;
  }

  public function updateGroupData() {
    debug("Attempting to update group data.\n");
    $vars = array_filter(get_object_vars($this));
    debug("Object vars: ".json_encode($vars, JSON_PRETTY_PRINT)."\n");
    $mysqli = db_connect();
    $sql = "SELECT * FROM groupdata WHERE id={$this->id}";
    debug("SQL: {$sql}\n");
    $result = $mysqli->query($sql);

    debug("Num Rows: ".mysqli_num_rows($result)."\n");
    if(mysqli_num_rows($result)>0) {
      $array = array();
      foreach ($vars as $item => $value) {
        array_push($array, "{$item}='{$value}''");
      }
      $sql = "UPDATE groupdata SET "
        . implode(", ", $array)
        . " WHERE id={$this->id}";
      debug("SQL: {$sql}\n");
    } else {
      $keys = implode(", ", array_keys($vars)); $values = "'".implode("', '", $vars)."'";
      $sql = "INSERT INTO groupdata ($keys) VALUES ($values)";
      debug("SQL: {$sql}\n");
    }
    $mysqli->query($sql);
    $mysqli->close();
  }


  public function getId() {
    return $this->id;
  }
  public function getType() {
    return $this->type;
  }
  public function getTitle() {
    return markdown_escape($this->title);
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
