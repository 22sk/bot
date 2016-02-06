<?php
namespace in;

class Chat {
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
    $mysqli = db_connect();

    $sql = "SELECT * FROM groupdata WHERE id='{$this->id}'";
    $result = $mysqli->query($sql);

    if(mysqli_num_rows($result)>0) {
      $array = array();
      foreach (get_object_vars($this) as $item => $value) {
        array_push($array, "{$item}='{$value}''");
      }
      $sql = "UPDATE groupdata SET "
        . implode(", ", $array)
        . " WHERE id={$this->id}";
    } else {
      $sql = "INSERT INTO groupdata ("
        . implode(", ", array_keys(get_object_vars($this)))
        . ") VALUES ('"
        . implode("', '", get_object_vars($this))
        . "')";
    }
    $mysqli->query($sql);
    $mysqli->close();
  }


  public function getID() {
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
}
