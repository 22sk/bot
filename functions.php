<?php

function obj2array ( &$Instance ) {
  $clone = (array) $Instance;
  $rtn = array();
  $rtn['src'] = $clone;

  while (list ($key, $value) = each ($clone)) {
    $aux = explode ("\0", $key);
    $newkey = $aux[count($aux)-1];
    $rtn[$newkey] = &$rtn['src'][$key];
  }
  unset($rtn['src']);
  return array_filter($rtn);
}

function obj2obj($instance, $className) {
  return unserialize(sprintf(
    'O:%d:"%s"%s',
    strlen($className),
    $className,
    strstr(strstr(serialize($instance), '"'), ':')
  ));
}

function str_clean($str) {
  return preg_replace('/[^A-Za-z0-9\-\s]/', '', strtolower($str));
}

function get_list($array, $usekeys) {
  $x = false; $text = "";
  if($usekeys) {
    foreach ($array as $key => $value) {
      if($x) $text.=", ";
      $x = true;
      $text.=$key;
    }
  } else {
    foreach ($array as $value) {
      if($x) $text.=", ";
      $x = true;
      $text.=$value;
    }
  }
  return $text;
}

function db_connect() {
  $mysqli = new mysqli(
    getenv('OPENSHIFT_MYSQL_DB_HOST'),
    getenv('OPENSHIFT_MYSQL_DB_USERNAME'),
    getenv('OPENSHIFT_MYSQL_DB_PASSWORD'),
    getenv('DB_NAME'),
    getenv('OPENSHIFT_MYSQL_DB_PORT')
  );
  return $mysqli;
}
function markdown_escape($str) {
  echo "Escaping '{$str}': ".preg_replace('/(?=[*_`])/', '\\', $str)."\n";
  return preg_replace('/(?=[*_`])/', '\\', $str);
}
