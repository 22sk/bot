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
  if(getenv('DEBUG')) echo $str."\n";
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

/**
 * @param $array
 * @param $needle
 * @param string $aliaskey
 * @return bool
 */
function find_alias($array, $needle, $aliaskey = "alias") {
  debug("\nIN FIND ALIAS");
  foreach($array as $value) {
    var_dump($value);
    echo "alias in array? "; var_dump(isset($value[$aliaskey]));
    if(array_key_exists($aliaskey, $value) and in_array($needle, $value[$aliaskey])) return $value;
  } return false;
}

function db_connect() {
  try {
    $mysqli = new mysqli(
      getenv('OPENSHIFT_MYSQL_DB_HOST'),
      getenv('OPENSHIFT_MYSQL_DB_USERNAME'),
      getenv('OPENSHIFT_MYSQL_DB_PASSWORD'),
      getenv('DB_NAME'),
      getenv('OPENSHIFT_MYSQL_DB_PORT')
    );
    if ($mysqli->connect_errno) throw new \Exception($mysqli->connect_error, $mysqli->connect_errno);
  } catch(\Exception $e) {
    debug("MySQLi Connect Error {$e->getCode()}: {$e->getMessage()}");
    return false;
  }
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
