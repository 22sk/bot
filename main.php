<?php

/**
 * @author Samuel Kaiser <samuel.kaiser01@gmail.com>
 * @since 14.05.2016
 */
var_dump(file_get_contents("php://input"));
ini_set('display_errors', 1);
error_reporting(E_ALL);

if(!array_key_exists('url', $_GET) or $_GET['url']!=getenv('API_URL')){
  http_response_code(403); exit("Unauthorized URL");
}

require("bot.inc");
$bot = new Bot(getenv('API_URL'), json_decode(file_get_contents("php://input")));

$commands = array (
  "hello" => function($req) {
    return new Response("sendMessage", array (
      "text" => "hello you. :3",
      "chat_id" => $req->message->from->id
    ));
  },

  "date" => function($req) {
    return new Response("sendMessage", array (
      "text" => "I'm glad you asked! It's ".date("l").", the ".date('d').date('S')." of ".date('F')." ".date('Y')
        .", ".date('H').":".date('i').":".date('s'),
      "chat_id" => $req->message->from->id
    ));
  }
);

foreach($commands as $key => $command) $bot->register($key, $command);

$bot->run();
