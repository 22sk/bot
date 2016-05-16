<?php

/**
 * @author Samuel Kaiser <samuel.kaiser01@gmail.com>
 * @since 14.05.2016
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

if(!array_key_exists('url', $_GET) or $_GET['url']!=getenv('API_URL')){
  http_response_code(403); exit("Unauthorized URL");
}

require_once("bot.php");

$bot = new Bot(getenv('API_URL'), json_decode(file_get_contents("php://input")));

$commands = array (
  "start" => array(
    "callable" => function($req) {
      return Response::build($req, array(
        "text" => "I'm here! ".json_decode("\ud83d\ude04")." What can I do for you? ".json_decode("\ud83d\ude0a")."\n".
        "Check out /help want to know more! ".json_decode("\ud83d\ude09")
      ));
    },
    "help" => "Prints the welcome message"
  ),
  "ping" => array(
    "callable" => function($req) {
      return Response::build($req, array("text" => "Hauptsache schneller als @Levon30bot! ;D"));
    },
    "help" => "Pong!"
  ),
  "hello" => array(
    "callable" => function($req) {
      return Response::build($req, array("text" => "hello you. :3"));
    },
    "help" => "Hey!"
  ),

  "date" => array(
    "callable" => function($req) {
      return Response::build($req, array (
        "text" => "I'm glad you asked! It's ".date("l").", the ".date('d').date('S')." of ".date('F')." ".date('Y')
          .", ".date('H').":".date('i').":".date('s'),
        "chat_id" => $req->message->chat->id
      ));
    },
    "help" => "Prints the current date and time"
  ),

  "echo" => array(
    "callable" => function($req) {
      return Response::build($req, array (
        "text" => $req->command->args,
        "parse_mode" => "Markdown"
      ));
    },
    "help" => "I'm a parrot!"
  ),

  "about" => array(
    "callable" => function($req) {
      return Response::build($req, array(
        "text" => "Bot made by @samuelk22. View source code on [GitHub](https://github.com/22sk/telegram-bot).",
        "parse_mode" => "Markdown"
      ));
    },
    "help" => "Prints information about this bot's creator and it's source code"
  )
);

foreach($commands as $key => $command) $bot->register("command", $key, $command['callable'], $command['help']);

$bot->register("keyword", array("hitler", "nazi"), function($req) {
  return Response::build($req, array("text" => "D:"));
});

$bot->register("inline", "default", function($req) {
  return new Response("answerInlineQuery", array(
    "inline_query_id" => $req->inline_query->id,
    "results" => array(
      array(
        "type" => "article",
        "id" => uniqid(),
        "title" => "Hey!",
        "input_message_content" => array(
          "message_text" => $req->inline_query->query,
          "parse_mode" => "Markdown"
        )
      )
    )
  ));
});

$bot->run();
