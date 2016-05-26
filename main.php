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
  "start" => [
    "callable" => function($req) {
      return (new responses\Message("I'm here! 😄 What can I do for you? 😊\n".
      "Check out /help if you want to know more! 😁",
        $req))->parse_mode("Markdown");
    },
    "help" => "Prints the welcome message"
  ],
  "ping" => [
    "callable" => function($req) {
      return (new responses\Message("*Pong!* ".(time()-$req->message->date)."s", $req))->parse_mode("Markdown");
    },
    "help" => "Pong!"
  ],
  "hello" => [
    "callable" => function($req) {
      return (new responses\Message("hello you. :3", $req))->parse_mode("Markdown");
    },
    "help" => "Hey!"
  ],

  "date" => [
    "callable" => function($req) {
      return new responses\Message("I'm glad you asked! It's ".date("l").", the "
        .date('d').date('S')." of ".date('F')." ".date('Y') .", ".date('H').":".date('i').":".date('s'), $req);
    },
    "help" => "Prints the current date and time"
  ],

  "echo" => [
    "callable" => function($req, $command) {
      return (new responses\Message($command->args, $req))->parse_mode("Markdown");
    },
    "help" => "I'm a parrot!",
    "syntax" => "<text>"
  ],

  "about" => [
    "callable" => function($req) {
      return (new responses\Message(
        "Bot made by @samuelk22. View source code on [GitHub](https://github.com/22sk/telegram-bot).",
        $req))->parse_mode("Markdown");
    },
    "help" => "Prints information about this bot's creator and its source code"
  ],

  "debug" => [
    "callable" => function($req) use($bot) {
      $bot->send(new responses\Message("Hey!", $req));
      return (new responses\Message("```\n".json_encode($bot->echo, JSON_PRETTY_PRINT)."\n```", $req))
        ->parse_mode("Markdown");
    },
    "help" => "Prints all information received from and sent to the Telegram API"
  ],
);


foreach($commands as $key => $command) processors\Command::register($bot, $key, $command['callable'],
  isset($command['help']) ? $command['help'] : null,
  isset($command['syntax']) ? $command['syntax'] : null,
  isset($command['hidden']) ? $command['hidden'] : null);

processors\Keyword::register($bot, ["hitler", "nazi"], function($req) {
  return new responses\Message("D:", $req);
});

processors\InlineQuery::register($bot, "default", function($req) {
  return (new responses\Inline([[
    "type" => "article",
    "id" => uniqid(),
    "title" => "Hey!",
    "input_message_content" => [
      "message_text" => $req->inline_query->query,
      "parse_mode" => "Markdown"
    ]
  ]], $req));
});

processors\InlineQuery::register($bot, "hello", function($req) {
  return (new responses\Inline([[
    "type" => "article",
    "id" => uniqid(),
    "title" => "Hello World!",
    "input_message_content" => [
      "message_text" => "Hello World",
      "parse_mode" => "Markdown"
    ]
  ]], $req));
});

function str_clean($str) {
  return preg_replace('/[^A-Za-z0-9\-\s]/', '', strtolower($str));
}
$bot->run();
