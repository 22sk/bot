<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

function __autoload($class) { include(implode('/', explode('\\', $class)).'.php'); }

if(!isset($_ENV['API_URL'])) $_ENV['API_URL'] = file_get_contents('api_url');

if(!array_key_exists('url', $_GET) or $_GET['url']!=$_ENV['API_URL']) exit("Invalid URL.");
require('functions.php');

$bot = new out\Bot($_ENV['API_URL']);
$update = new in\Update();

$update->process();
