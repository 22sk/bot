<?php
namespace in;

class Update {
  public function __construct($update = null) {
    if(!isset($update) and file_get_contents('php://input') != null) {
      $update = json_decode(file_get_contents('php://input'));
    } else $update = json_decode(file_get_contents('in/sample_update.json'));

    foreach($update as $key => $value) {
      $this->$key = $value;
    }
    $GLOBALS['update'] = $update;
    echo "Update:\n".json_encode($update, JSON_PRETTY_PRINT)."\n";
  }

  public function getType() {
    $array = array_keys(get_object_vars($this));
    return end($array);
  }

  public function process() {
    $type = $this->getType();
    $user = new User($this->$type->from);
    if($user->isBanned()) return false;
    $update = null;
    switch($type) {
      case 'message': $update = new Message(get_object_vars($this)['message']); break;
      case 'inline_query': $update = new InlineQuery(get_object_vars($this)['inline_query']); break;
      case 'chosen_inline_result': $update = new ChosenInlineResult(get_object_vars($this)['chosen_inline_result']); break;
    } return $update->process();
  }
}
