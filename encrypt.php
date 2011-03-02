<?php
require_once("config.php");

define(NO_ERROR, 0);
define(ERROR_MISSING_FIELDS, 1);
define(ERROR_DB_REPOST, 2);
define(ERROR_SEND_EMAIL, 3);

function serialize_array($data)
{
  return serialize($data);  //PHP only
  //return yaml_emit($data, YAML_UTF8_ENCODING, YAML_LN_BREAK);  //cross-platform cross-language, needs PECL YAML
}

function generate_verification_code()
{
  return hash($GLOBALS['pet_db']['hash_function'],uniqid(mt_rand(), true));
}

function hash_data($cleartext)
{
  return hash($GLOBALS['pet_db']['hash_function'], hash($GLOBALS['pet_db']['hash_function'], $GLOBALS['pet_db']['hash_salt'].$cleartext).$GLOBALS['pet_db']['hash_salt']);
}

function seal_data($cleartext)
{  
  $pubkey = openssl_pkey_get_public($GLOBALS['pet_db']['pubkey']);
  
  if (($GLOBALS['pet_db']['crypt_method'] == "AUTO" && !extension_loaded('mcrypt')) || $GLOBALS['pet_db']['crypt_method'] == "OPENSSL_SEAL")
  {
    //using RC4 128bit openssl:
    openssl_seal($cleartext, $sealed_data, $envelope_keys, array($pubkey));
    openssl_free_key($pubkey);
    return array(mode=>"OPENSSL_SEAL", data=>base64_encode($sealed_data), envkey=>base64_encode($envelope_keys[0]));
  }
  elseif ($GLOBALS['pet_db']['crypt_method'] == "AUTO" || $GLOBALS['pet_db']['crypt_method'] == "OPENSSL_MCRYPT_AES128" || $GLOBALS['pet_db']['crypt_method'] == "OPENSSL_MCRYPT_AES256")
  {
    //using AES mcrypt
    if ($GLOBALS['pet_db']['crypt_method'] == "OPENSSL_MCRYPT_AES256")
    {
      $aes_algorithm = MCRYPT_RIJNDAEL_256;
      $db_used_encryption_mode = "OPENSSL_MCRYPT_AES256";
    }
    else
    {
      $aes_algorithm = MCRYPT_RIJNDAEL_128;
      $db_used_encryption_mode = "OPENSSL_MCRYPT_AES128";
    }
      
    $size_iv = mcrypt_get_iv_size($aes_algorithm, MCRYPT_MODE_CBC);
    $size_key = mcrypt_get_key_size($aes_algorithm, MCRYPT_MODE_CBC);
    $iv = mcrypt_create_iv($size_iv, MCRYPT_DEV_URANDOM);
    $key = mcrypt_create_iv($size_key, MCRYPT_DEV_RANDOM);
    $ciphertext = mcrypt_encrypt($aes_algorithm, $key, $cleartext, MCRYPT_MODE_CBC, $iv);
    
    $encrypted_key = "";
    openssl_public_encrypt ($key, $encrypted_key, $pubkey, OPENSSL_PKCS1_OAEP_PADDING);
    openssl_free_key($pubkey);
    return array(mode=>$db_used_encryption_mode, data=>base64_encode($ciphertext), envkey=>base64_encode($encrypted_key), enviv=>base64_encode($iv));
  }
  else
    die("Configuration Error, crypt_method hast invalid value!");
}

function save_entry_to_database($posted_data, $verification_code, $display_set)
{
  $serialized_data = serialize_array($posted_data);
  $crypto_data = seal_data($serialized_data);
  $hashed_data = hash_data($serialized_data);
  
  // after encrypting data, only save in plaintext the values that user selected to display
  $data_to_save_plaintext = array_intersect_key($posted_data, array_fill_keys($display_set,True));

  $mysqli = new mysqli($GLOBALS['pet_db']['db_host'], $GLOBALS['pet_db']['db_user'], $GLOBALS['pet_db']['db_pass'], $GLOBALS['pet_db']['db_name']);
  if (mysqli_connect_errno()) {
      die("Connect failed: " . mysqli_connect_error());
  }
  
  $rc = False;
  $stmt = $mysqli->stmt_init();
  if ($stmt->prepare("INSERT INTO entry(salutation,gname,sname,addr_street,addr_city,addr_postcode,addr_country,hash,hash_function,crypted_data,crypted_envkey,crypted_enviv,crypted_mode,verify_code,display) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")) 
  {
    //mysqli_stmt_bind_param($stmt, 'sssssssssbbssss', 
    mysqli_stmt_bind_param($stmt, 'sssssssssssssss', 
      $data_to_save_plaintext["salutation"],
      $data_to_save_plaintext["gname"],
      $data_to_save_plaintext["sname"],
      $data_to_save_plaintext["addr_street"],
      $data_to_save_plaintext["addr_city"],
      $data_to_save_plaintext["addr_postcode"],
      $data_to_save_plaintext["addr_country"],
      $hashed_data,
      $GLOBALS['pet_db']['hash_function'],
      $crypto_data["data"],
      $crypto_data["envkey"],
      $crypto_data["enviv"],
      $crypto_data["mode"],
      $verification_code,
      join(",",$display_set)
      );

    $rc = $stmt->execute();
    $stmt->close();
  }
  else
    die("Error in SQL Statement: " . mysqli_error($mysqli));
  $mysqli->close();
  return $rc;
}

function sanitize_filter_array_element(&$var, $key)
{
  $field_email = "email";
  $default_filter = FILTER_SANITIZE_STRIPPED;

  if ($key == $field_email)
  {
    $var = filter_var($var, FILTER_SANITIZE_EMAIL);
    if (! filter_var($var, FILTER_VALIDATE_EMAIL))
    {
      $var="";
    }
  }
  else
  {
    $var = filter_var($var, $default_filter);
  }
}

function sanitize_input($posted_stuff)
{
  $post_valid_fields =       array('salutation', 'gname', 'sname', 'email', 'addr_country', 'addr_city', 'addr_postcode', 'addr_street');
  $db_valid_display_values = array('salutation', 'gname', 'sname', 'addr_country', 'addr_city', 'addr_postcode', 'addr_street');
  $display_post_value_prefix = "display_";
  
  $display_set=array();
  foreach ($db_valid_display_values as $dbdfield) 
  {
    if ($posted_stuff[$display_post_value_prefix.$dbdfield] == True)
      $display_set[]=$dbdfield;
  }
  
  $posted_data = array_intersect_key($posted_stuff, array_fill_keys($post_valid_fields,True));
  array_walk($posted_data, sanitize_filter_array_element);
  $posted_data = array_filter($posted_data, create_function('$var','return (!empty($var));'));
  
  return array($posted_data, $display_set);  
}

function send_email($email, $verification_code)
{
  global $language;
  $body = str_replace($GLOBALS['pet_email']['verification_code_subst'], $verification_code, $GLOBALS['pet_email']['body'][$language]);
  mail($email, $GLOBALS['pet_email']['subject'][$language], $body, $GLOBALS['pet_email']['headers']);
}

function save_data_encrypted($posted_stuff)
{
  $error=NO_ERROR; $error_info="";
  
  list($posted_data, $display_set) = sanitize_input($posted_stuff);
  
  $missing_fields = array_diff($GLOBALS['required_fields'], array_keys($posted_data));
  if (count($missing_fields) > 0)
    return array(success=>False,error=>ERROR_MISSING_FIELDS,info=>$missing_fields);

  if ($GLOBALS['pet_email']['send_email_verification'] and !empty($posted_data["email"]))
  {
    $verification_code=generate_verification_code();
  }
  else
    unset($verification_code);
    
  if (! save_entry_to_database($posted_data, $verification_code, $display_set))
    return array(success=>False,error=>ERROR_DB_REPOST);
  
  if ($rc and isset($verification_code))
  {
    if (! send_email($posted_data["email"], $verification_code))
      return array(success=>True,error=>ERROR_SEND_EMAIL);  //success==True, because data was actually saved
  }

  return array(success=>True, error=>NO_ERROR);
}

assert(extension_loaded('filter'));
//assert(extension_loaded('yaml'));
assert(extension_loaded('openssl'));

mt_srand();

if (extension_loaded('pecl_http'))
  $language = http_negotiate_language($GLOBALS['supported_lang']);
else
  $language = $GLOBALS['supported_lang'][0];

$allowed_hash_functions = array('ripemd256','sha256','ripemd128','ripemd160');
if (array_search($GLOBALS['pet_db']['hash_function'], $allowed_hash_functions) == False)
{
  $GLOBALS['pet_db']['hash_function'] = $allowed_hash_functions[0];
}


?>
