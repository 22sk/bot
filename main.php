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

require("bot.php");

$bot = new Bot(getenv('API_URL'), json_decode(file_get_contents("php://input")));

$commands = array (
  "start" => array(
    "callable" => function($req) {
      return (new Message("I'm here! ".json_decode("\ud83d\ude04")." What can I do for you? "
        .json_decode("\ud83d\ude0a")."\n". "Check out /help if you want to know more! ".json_decode("\ud83d\ude09"),
        $req))->parse_mode("Markdown");
    },
    "help" => "Prints the welcome message"
  ),
  "ping" => array(
    "callable" => function($req) {
      return (new Message("*Pong!* ".(time()-$req->message->date)."s", $req))->parse_mode("Markdown");
    },
    "help" => "Pong!"
  ),
  "hello" => array(
    "callable" => function($req) {
      return (new Message("hello you. :3", $req))->parse_mode("Markdown");
    },
    "help" => "Hey!"
  ),

  "date" => array(
    "callable" => function($req) {
      return new Message("I'm glad you asked! It's ".date("l").", the "
        .date('d').date('S')." of ".date('F')." ".date('Y') .", ".date('H').":".date('i').":".date('s'), $req);
    },
    "help" => "Prints the current date and time"
  ),

  "echo" => array(
    "callable" => function($req) {
      return (new Message($req->command->args, $req))->parse_mode("Markdown");
    },
    "help" => "I'm a parrot!"
  ),

  "about" => array(
    "callable" => function($req) {
      return (new Message(
        "Bot made by @samuelk22. View source code on [GitHub](https://github.com/22sk/telegram-bot).",
        $req))->parse_mode("Markdown");
    },
    "help" => "Prints information about this bot's creator and it's source code"
  ),

  "debug" => array(
    "callable" => function($req) use($bot) {
      $bot->send(new Message("Hey!", $req));
      return (new Message("```\n".json_encode($bot->echo, JSON_PRETTY_PRINT)."\n```", $req))->parse_mode("Markdown");
    },
    "help" => "Prints all information received from and sent to the Telegram API"
  )
);

foreach($commands as $key => $command) Command::register($bot, $key, $command['callable'],
  isset($command['help']) ? $command['help'] : null, isset($command['hidden']) ? $command['hidden'] : false);

Keyword::register($bot, array("hitler", "nazi"), function($req) {
  return new Message("D:", $req);
});

Inline::register($bot, "default", function($req) {
  return new Response("answerInlineQuery", array(
    "cache_time" => 0,
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

Inline::register($bot, "hello", function($req) {
  return new Response("answerInlineQuery", array(
    "cache_time" => 0,
    "inline_query_id" => $req->inline_query->id,
    "results" => array(
      array(
        "type" => "article",
        "id" => uniqid(),
        "title" => "Hello World!",
        "input_message_content" => array(
          "message_text" => "Hello World",
          "parse_mode" => "Markdown"
        )
      )
    )
  ));
});


$bot->run();
