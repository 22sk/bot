<?php
namespace in;

class Update implements \JsonSerializable {
  private $update_id;
  private $message;
  private $inline_query;
  private $chosen_inline_result;


  public function __construct($update = null) {
    if(!isset($update) and file_get_contents('php://input') != null) {
      $update = json_decode(file_get_contents('php://input'));
    } else $update = json_decode(file_get_contents('in/sample_update.json'));

    foreach(get_object_vars($update) as $key => $value) {
      $types = json_decode(file_get_contents('in/types.json'), true);
      if(isset($types[$key]['__class'])) $this->$key = new $types[$key]['__class']($value);
      else $this->$key = $value;
      if(!isset($GLOBALS[$key])) $GLOBALS[$key] = $this->$key;
    }
    $GLOBALS['update'] = $this;
    debug("Update:\n".json_encode($update, JSON_PRETTY_PRINT)."\n");
  }

  /**
   * @return integer
   */
  public function getUpdateId() {
    return $this->update_id;
  }

  /**
   * @return Message
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * @return InlineQuery
   */
  public function getInlineQuery() {
    return $this->inline_query;
  }

  /**
   * @return ChosenInlineResult
   */
  public function getChosenInlineResult() {
    return $this->chosen_inline_result;
  }

  public function process() {
    $type = $this->getType();
    debug("Type: $type\n");
    if(DEBUG) var_dump($this);
    $user = new User($this->$type->getFrom());
    if($user->isBanned()) return false;
    $update = null;

    switch ($type) {
      case 'message':
        $update = new Message(get_object_vars($this)['message']);
        break;
      case 'inline_query':
        $update = new InlineQuery(get_object_vars($this)['inline_query']);
        break;
      case 'chosen_inline_result':
        $update = new ChosenInlineResult(get_object_vars($this)['chosen_inline_result']);
        break;
    }
    return $update->process();
  }

  public function getType() {
    $array = array_keys(array_filter(get_object_vars($this)));
    return end($array);
  }

  public function jsonSerialize() {
    $array = obj2array($this);
    unset($array['method']);
    return $array;
  }
}
