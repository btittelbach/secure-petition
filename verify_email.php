<?php
require("config.php");

function verify_email($verification_code)
{
  $mysqli = new mysqli($GLOBALS['pet_db']['db_host'], $GLOBALS['pet_db']['db_user'], $GLOBALS['pet_db']['db_pass'], $GLOBALS['pet_db']['db_name']);
  if (mysqli_connect_errno()) {
      die("Connect failed: " . mysqli_connect_error());
  }

  $stmt =  $mysqli->stmt_init();
  if ($stmt->prepare("UPDATE entry SET verify_ok='Y' where verify_code = ?")) 
  {
    mysqli_stmt_bind_param($stmt, 's', $verification_code);
    $stmt->execute();
    //the query should affect exactly one row ! If so the verification was a success
    $success = (mysqli_stmt_affected_rows($stmt) == 1);
    $stmt->close();
  }
  $mysqli->close();
  return $success;
}

assert(extension_loaded('filter'));
if (extension_loaded('pecl_http'))
  $language = http_negotiate_language($GLOBALS['supported_lang']);
else
  $language = $GLOBALS['supported_lang'][0];
$verification_code = filter_var($_REQUEST["code"], FILTER_SANITIZE_STRIPPED);
if (verify_email($verification_code))
  readfile($GLOBALS['pet_html']['verify_ok'][$language]);
else
  readfile($GLOBALS['pet_html']['verify_fail'][$language]);
?>

