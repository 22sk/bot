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

function debug($str) {
  if(getenv('DEBUG')) echo $str;
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
  if(gettype($str) == 'array') {
    foreach($str as &$value) $value = markdown_escape($value);
    return $str;
  } else return preg_replace('/(?=[*_`])/', '\\', $str);
}

function array_remove(&$array) {
  if(gettype($array) == 'object') $array = get_object_vars($array);
  foreach($array as $item => &$value) {
    if(gettype($value) == 'array' or gettype($value) == 'object') array_remove($value);
    elseif(strpos($item, '__') === 0) {
      debug("Unset $item\n");
      unset($array[$item]);
    }
  } return $array;
}
