<?php

/**
 * @author Samuel Kaiser <samuel.kaiser01@gmail.com>
 * @since 14.05.2016
 */
echo(file_get_contents("php://input"));
ini_set('display_errors', 1);
error_reporting(E_ALL);

if(!array_key_exists('url', $_GET) or $_GET['url']!=getenv('API_URL')){
  http_response_code(403); exit("Unauthorized URL");
}

require_once("bot.php");

$bot = new Bot(getenv('API_URL'), json_decode(file_get_contents("php://input")));

$commands = array (
  "ping" => function($req) {
    return Response::build($req, array("text" => "Hauptsache schneller als @Levon30bot! ;D"));
  },
  "hello" => function($req) {
    return Response::build($req, array("text" => "hello you. :3"));
  },

  "date" => function($req) {
    return Response::build($req, array (
      "text" => "I'm glad you asked! It's ".date("l").", the ".date('d').date('S')." of ".date('F')." ".date('Y')
        .", ".date('H').":".date('i').":".date('s'),
      "chat_id" => $req->message->chat->id
    ));
  },

  "echo" => function($req) {
    return Response::build($req, array (
      "text" => $req->command->args,
      "parse_mode" => "Markdown"
    ));
  },

  "about" => function($req) {
    return Response::build($req, array(
      "text" => "Bot made by @samuelk22. View source code on [GitHub](https://github.com/22sk/telegram-bot).",
      "parse_mode" => "Markdown"
    ));
  }
);

foreach($commands as $key => $command) $bot->register(Bot::COMMAND, $key, $command);

$bot->register(Bot::KEYWORD, array("hitler", "nazi"), function($req) {
  return Response::build($req, array("text" => "D:"));
});

$bot->register(Bot::INLINE, "default", function($req) {
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
