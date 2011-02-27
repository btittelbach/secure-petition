<?php
ini_set("register_argc_argv",True);
ini_set("magic_quotes_gpc",False);
require("config.php");

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
  //return unserialize($data);  //PHP only
  if ($data)
    return yaml_parse(trim($data), 0);
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

  if ($result = $mysqli->query("SELECT id,crypted_data,crypted_envkey,crypted_enviv,crypted_mode,verify_ok,display FROM entry"))
  {
    while ($entry_row = $result->fetch_assoc())
    {
      $decrypted_row = unserialize_array(unseal_data($entry_row["crypted_mode"],$entry_row["crypted_data"],$entry_row["crypted_envkey"],$entry_row["crypted_enviv"],$seckey));
      if ($decrypted_row)
      {
        $stmt =  $mysqli_target->stmt_init();
        if ($stmt->prepare("INSERT INTO decrypted_entry(id,gname,sname,email,addr_street,addr_city,addr_postcode,addr_country,verify_ok,display) VALUES(?,?,?,?,?,?,?,?,?,?)")) 
        {
          //mysqli_stmt_bind_param($stmt, 'ssssssbbssss', 
          mysqli_stmt_bind_param($stmt, 'dsssssssss', 
            $entry_row['id'],
            $decrypted_row["gname"], 
            $decrypted_row["sname"],
            $decrypted_row["email"],
            $decrypted_row["addr_street"],
            $decrypted_row["addr_city"],
            $decrypted_row["addr_postcode"],
            $decrypted_row["addr_country"],
            $entry_row["verify_ok"],
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

assert(extension_loaded('yaml'));
assert(extension_loaded('openssl'));
$seckey = openssl_pkey_get_private($GLOBALS['dec_db']['seckey']);
decrypt_in_database($seckey);
openssl_free_key($seckey);

?>