<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

define('ROOT', dirname(__FILE__));
define('DEBUG', getenv('DEBUG'));

function __autoload($class) {
  include(ROOT.'/'.implode('/', explode('\\', $class)).'.php');
}

if(!array_key_exists('url', $_GET) or $_GET['url']!=getenv('API_URL')) {
  http_response_code(403);
  exit("Unauthorized URL");
}
require('functions.php');

$bot = new out\Bot(getenv('API_URL'));
$update = new in\Update();

$update->process();
