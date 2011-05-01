<?php
require_once("config.php");
require_once("encrypt.php");

session_start();
if ($_SESSION['captcha_text'] != $_REQUEST["captcha"])
{
  die("Captcha ivalid");
}
else
{
  //caption ok, invalidating session data
  unset($_SESSION['captcha_text']);
}

## save_data_encrypted takes the Form Request and returns an associative array
## with the following keys:
##  "success" : Boolean : false if error occured
##  "error"   : Integer : Error Code. One of:
##                        NO_ERROR               : no error
##                        ERROR_MISSING_FIELDS   : some required inputfields configured in config.php were empty
##                        ERROR_DB_REPOST        : the submitted data was already in the database (hash already exists)
##                        ERROR_SEND_EMAIL       : error when trying to send verification e-mail
##  "info"    : additional information, in case of ERROR_MISSING_FIELDS it's an array specifying the empty but required inputfield names
$save_result = save_data_encrypted($_REQUEST);
if ($save_result["success"])
  readfile($GLOBALS['pet_html']['submit_ok'][$language]);
else
  readfile($GLOBALS['pet_html']['submit_fail'][$language]);
?>
