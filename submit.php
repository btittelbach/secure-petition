<?php
require_once("config.php");
require_once("encrypt.php");

session_start();
if ($_SESSION['captcha_text'] != $_REQUEST["captcha"])
{
  die("Captcha ivalid");
}

$save_result = save_data_encrypted($_REQUEST);
if ($save_result["success"])
  readfile($GLOBALS['pet_html']['submit_ok'][$language]);
else
  readfile($GLOBALS['pet_html']['submit_fail'][$language]);
?>
