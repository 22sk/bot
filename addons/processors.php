<?php
/**
 * @author Samuel Kaiser <samuel.kaiser01@gmail.com>
 * @since 17.05.2016
 */

class InlineQuery extends Processor {
  public static function register($bot, $name, $callable, $help = null, $hidden = false) {
    return parent::register($bot, $name, $callable, $help, $hidden);
  }

  public static function process($bot) {
    if(empty($bot->req->inline_query)) return false;
    preg_match("/^\w+/", $bot->req->inline_query->query, $match);
    $word = $match[0];
    foreach($bot->inline as $inline => $value) {
      if(strcasecmp($word, $inline) == 0) return $value['callable']($bot->req);
    } if(array_key_exists('default', $bot->inlines)) {
      return $bot->inline['default']['callable']($bot->req);
    }
    return false;
  }
}

class Keyword extends Processor {
  public static function register($bot, $name, $callable, $help = null, $hidden = false) {
    return parent::register($bot, $name, $callable, $help, $hidden);
  }

  public static function process($bot) {
    if(empty($bot->req->message)) return false;
    foreach($bot->keyword as $word => $value) {
      if(stristr($bot->req->message->text, $word)) return $value['callable']($bot->req);
    } return false;
  }
}
