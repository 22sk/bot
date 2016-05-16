<?php
/**
 * @author Samuel Kaiser <samuel.kaiser01@gmail.com>
 * @since 16.05.2016
 */


class Keyword extends Processable {
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
