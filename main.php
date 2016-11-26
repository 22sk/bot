<?php

/**
 * @author Samuel Kaiser <samuel.kaiser01@gmail.com>
 * @since 14.05.2016
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

if(!array_key_exists('url', $_GET) or $_GET['url']!=getenv('API_URL')){
  http_response_code(403); exit("Unauthorized");
}

require("bot.php");

$bot = new Bot(getenv('API_URL'));

use \processors\Command as cmd;
/** REGISTER COMMANDS */ {

  cmd::register($bot, "start", (new \registerables\Command(
    function($req) {
      return (new \responses\Message("I'm here! What can I do for you?\n".
        "Check out /help if you want to know more!",
        $req))->parse_mode("Markdown");
    }
  ))->description("Prints the welcome message"));

  cmd::register($bot, "ping", (new \registerables\Command(
    function($req) {
      return (new \responses\Message("*Pong!* ".(time()-$req->message->date)."s", $req))->parse_mode("Markdown");
    }
  ))->description("Pong!"));

  cmd::register($bot, "hello",  (new \registerables\Command(
    function($req) {
      return (new \responses\Message("hello. (:", $req));
    }
  ))->description("Hey!"));

  cmd::register($bot, "date", (new \registerables\Command(
    function($req) {
      return new \responses\Message("I'm glad you asked! It's ".date("l").", the "
        .date('d').date('S')." of ".date('F')." ".date('Y') .", ".date('H').":".date('i').":".date('s'), $req);
    }
  ))->description("Prints the current date and time"));

  cmd::register($bot, "echo", (new \registerables\Command(
    function($req, $args) {
      if (!empty($args)) return (new \responses\Message($args, $req))->parse_mode("Markdown");
      else return false;
    }
  ))->description("I'm a parrot!")->syntax("<text>"));

  cmd::register($bot, "about", (new \registerables\Command(
    function($req) {
      return (new \responses\Message(
        "Bot made by @samuelk22. View source code on [GitHub](https://github.com/22sk/telegrambot.php).",
        $req))->parse_mode("Markdown");
    }
  ))->description("Prints information about this bot's creator and its source code"));

  cmd::register($bot, "debug", (new \registerables\Command(
    function($req) use($bot) {
      $bot->send(new \responses\Message("Hey!", $req));
      return (new \responses\Message("```\n".json_encode($bot->echo, JSON_PRETTY_PRINT)."\n```", $req))
        ->parse_mode("Markdown");
    }
  ))->description("Prints all information received from and sent to the Telegram API"));

  cmd::register($bot, "user", (new \registerables\Command(
    function($req) {
      $text = "";
      $user = (empty($req->message->reply_to_message)) ? $req->message->from : $req->message->reply_to_message->from;
      foreach((array)$user as $item => $value) $text.=markdown_escape($item).": `".markdown_escape($value)."`\n";
      return (new \responses\Message($text, $req))->parse_mode('Markdown');
    }
  )));

  cmd::register($bot, "chat", (new \registerables\Command(
    function($req) {
      $text = "";
      $chat = $req->message->chat;
      foreach((array)$chat as $item => $value) $text.=markdown_escape($item).": `".markdown_escape($value)."`\n";
      return (new \responses\Message($text, $req))->parse_mode('Markdown');
    }
  )));
}

use processors\Keyword as kwd;
/** REGISTER KEYWORDS */ {

  kwd::register($bot, ["hi", "hello"], (new \registerables\Keyword(function($req) {
    return new responses\Message("D:", $req);
  }))->word(true));
}

use processors\InlineQuery as iln;
/** REGISTER INLINE QUERYS */ {

  iln::register($bot, "default", new \registerables\InlineQuery(function($req) {
    return (new responses\Inline([[
      "type" => "article",
      "id" => uniqid(),
      "title" => "Hey!",
      "input_message_content" => [
        "message_text" => $req->inline_query->query,
        "parse_mode" => "Markdown"
      ]
    ]], $req));
  }));

  iln::register($bot, "hello", new \registerables\InlineQuery(function($req) {
    return (new responses\Inline([[
      "type" => "article",
      "id" => uniqid(),
      "title" => "Hello World!",
      "input_message_content" => [
        "message_text" => "Hello World",
        "parse_mode" => "Markdown"
      ]
    ]], $req));
  }));
}

$bot->run();
