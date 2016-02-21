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
 * @param bool $contain
 * @param string $aliaskey
 * @return array|false
 */
function find_alias($array, $needle, $contain = false, $aliaskey = "alias") {
  debug("\nIN FIND ALIAS");
  foreach($array as $value) {
    var_dump($value);
    echo "alias in array? "; var_dump(isset($value[$aliaskey]));
    if($contain) {
      foreach($value[$aliaskey] as $alias) if(strpos($needle, $alias) !== false) return $value;
    } elseif(array_key_exists($aliaskey, $value) and in_array($needle, $value[$aliaskey])) return $value;
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

function encrypt($decrypted, $password, $salt='jS*eJ9ZRE"=GMzra') {
  // Build a 256-bit $key which is a SHA256 hash of $salt and $password.
  $key = hash('SHA256', $salt . $password, true);
  // Build $iv and $iv_base64.  We use a block size of 128 bits (AES compliant) and CBC mode.  (Note: ECB mode is inadequate as IV is not used.)
  srand(); $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
  if (strlen($iv_base64 = rtrim(base64_encode($iv), '=')) != 22) return false;
  // Encrypt $decrypted and an MD5 of $decrypted using $key.  MD5 is fine to use here because it's just to verify successful decryption.
  $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $decrypted . md5($decrypted), MCRYPT_MODE_CBC, $iv));
  // We're done!
  return $iv_base64 . $encrypted;
}

function decrypt($encrypted, $password, $salt='jS*eJ9ZRE"=GMzra') {
  // Build a 256-bit $key which is a SHA256 hash of $salt and $password.
  $key = hash('SHA256', $salt . $password, true);
  // Retrieve $iv which is the first 22 characters plus ==, base64_decoded.
  $iv = base64_decode(substr($encrypted, 0, 22) . '==');
  // Remove $iv from $encrypted.
  $encrypted = substr($encrypted, 22);
  // Decrypt the data.  rtrim won't corrupt the data because the last 32 characters are the md5 hash; thus any \0 character has to be padding.
  $decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($encrypted), MCRYPT_MODE_CBC, $iv), "\0\4");
  // Retrieve $hash which is the last 32 characters of $decrypted.
  $hash = substr($decrypted, -32);
  // Remove the last 32 characters from $decrypted.
  $decrypted = substr($decrypted, 0, -32);
  // Integrity check.  If this fails, either the data is corrupted, or the password/salt was incorrect.
  if (md5($decrypted) != $hash) return false;
  // Yay!
  return $decrypted;
}

/**
 * @param array $array
 * @param mysqli $mysqli
 * @return array
 */
function mysqli_escape_all($array, $mysqli) {
  $new = array();
  foreach ($array as $key => $value) {
    $new[$key] = $mysqli->real_escape_string($value);
  }
  return $new;
}
