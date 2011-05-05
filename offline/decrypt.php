<?php
ini_set("register_argc_argv",True);
ini_set("magic_quotes_gpc",False);
require("config.php");

function hash_data($cleartext, $hash_function)
{
  return hash($hash_function, hash($hash_function, $GLOBALS['pet_db']['hash_salt'].trim($cleartext)).$GLOBALS['pet_db']['hash_salt']);
}

function unseal_data($method, $ciphertext, $envkey, $enviv, $seckey)
{
  $ciphertext = base64_decode($ciphertext);
  $envkey = base64_decode($envkey);
  $enviv = base64_decode($enviv);
  if ($method == "OPENSSL_SEAL")
  {
    //using RC4 128bit openssl:
    openssl_open($ciphertext, $decrypted_data, $envkey, $seckey);
  }
  elseif ($method == "OPENSSL_MCRYPT_AES128" || $method == "OPENSSL_MCRYPT_AES256")
  {
    assert(extension_loaded('mcrypt'));
    //using AES mcrypt
    if ($method == "OPENSSL_MCRYPT_AES256")
    {
      $aes_algorithm = MCRYPT_RIJNDAEL_256;
    }
    else
    {
      $aes_algorithm = MCRYPT_RIJNDAEL_128;
    }

    openssl_private_decrypt ($envkey, $decrypted_key, $seckey, OPENSSL_PKCS1_OAEP_PADDING);

    $decrypted_data = mcrypt_decrypt($aes_algorithm, $decrypted_key, $ciphertext, MCRYPT_MODE_CBC, $enviv);
  }
  else
  {
    print("ERROR, unknown encryption method encountered: ". $method);
    $decrypted_data=False;
  }
  
  return $decrypted_data;
}

function unserialize_array($data)
{
  
  if ($data)
  return unserialize($data);  //PHP only
  //return yaml_parse(trim($data), 0);
  else
    return $data;
}

function decrypt_in_database($seckey)
{
  $mysqli = new mysqli($GLOBALS['pet_db']['db_host'], $GLOBALS['pet_db']['db_user'], $GLOBALS['pet_db']['db_pass'], $GLOBALS['pet_db']['db_name']);
  if (mysqli_connect_errno())
  {
    die("Connect failed: " . mysqli_connect_error());
  }
  
  $mysqli_target = new mysqli($GLOBALS['dec_db']['db_host'], $GLOBALS['dec_db']['db_user'], $GLOBALS['dec_db']['db_pass'], $GLOBALS['dec_db']['db_name']);
  if (mysqli_connect_errno())
  {
    die("Connect failed: " . mysqli_connect_error());
  }

  $mysqli_target->query("DROP TABLE IF EXISTS decrypted_entry");
  $mysqli_target->query($GLOBALS['dec_db']['sql_create_table']);

  if ($result = $mysqli->query("SELECT id,crypted_data,crypted_envkey,crypted_enviv,crypted_mode,hash,hash_function,verify_ok,display FROM entry"))
  {
    while ($entry_row = $result->fetch_assoc())
    {
      $unsealed_data_string = unseal_data($entry_row["crypted_mode"],$entry_row["crypted_data"],$entry_row["crypted_envkey"],$entry_row["crypted_enviv"],$seckey);
      
      if ($GLOBALS['conversion']['check_hash'])
      {
        $hashed_data = hash_data($unsealed_data_string, $entry_row["hash_function"]);
        //print("check ".$entry_row["hash"]." == ".$hashed_data."\n");
        if ($hashed_data != $entry_row["hash"])
        {
          print("ignoring entry w/ id ".$entry_row['id'].", hash does not match\n");
          continue;
        }
      }
      
      $decrypted_row = unserialize_array($unsealed_data_string);
      if ($decrypted_row)
      {
        
        foreach (array("option1","option2","option3") as $checkbox_field)
        {
          if (isset($decrypted_row[$checkbox_field]) and ($decrypted_row[$checkbox_field] == "Y" || $decrypted_row[$checkbox_field] == "on" || $decrypted_row[$checkbox_field] === True || $decrypted_row[$checkbox_field] == "True" || $decrypted_row[$checkbox_field] == 1))
            $decrypted_row[$checkbox_field] = 'Y';
          else
            $decrypted_row[$checkbox_field] = 'N';
        }
        
        $stmt =  $mysqli_target->stmt_init();
        if ($stmt->prepare("INSERT INTO decrypted_entry(id,salutation,gname,sname,email,addr_street,addr_city,addr_postcode,addr_country,verify_ok,option1,option2,option3,display) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)")) 
        {
          //mysqli_stmt_bind_param($stmt, 'ssssssbbssssss', 
          mysqli_stmt_bind_param($stmt,   'dsssssssssssss', 
            $entry_row['id'],
            $decrypted_row["salutation"], 
            $decrypted_row["gname"], 
            $decrypted_row["sname"],
            $decrypted_row["email"],
            $decrypted_row["addr_street"],
            $decrypted_row["addr_city"],
            $decrypted_row["addr_postcode"],
            $decrypted_row["addr_country"],
            $entry_row["verify_ok"],
            $decrypted_row["option1"],
            $decrypted_row["option2"],
            $decrypted_row["option3"],
            $entry_row["display"]
            );

          $stmt->execute();
          $stmt->close();
        }
        else
          print("ERROR executing INSERT Statement: " . mysqli_error($mysqli_target)."\n");
      }
    }
    $result->free();
  }
  if ($mysqli != $mysqli_target)
    $mysqli_target->close();
  $mysqli->close();
  return True;
}

//assert(extension_loaded('yaml'));
assert(extension_loaded('openssl'));
$seckey = openssl_pkey_get_private($GLOBALS['dec_db']['seckey']);
decrypt_in_database($seckey);
openssl_free_key($seckey);

?>
