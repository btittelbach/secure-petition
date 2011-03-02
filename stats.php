<?php
require_once("config.php");

function make_petition_stats_array($array_name)
{
  $mysqli = new mysqli($GLOBALS['pet_db']['db_host'], $GLOBALS['pet_db']['db_user'], $GLOBALS['pet_db']['db_pass'], $GLOBALS['pet_db']['db_name']);
  if (mysqli_connect_errno()) {
      die("Connect failed: " . mysqli_connect_error());
  }
  
  if ($result = $mysqli->query("SELECT count(id) as num_entries FROM entry"))
  {
    $entry_row = $result->fetch_array();
    $GLOBALS[$array_name]["num_entries"] = $entry_row[0];
  }

  if ($result = $mysqli->query("SELECT count(id) as num_verified FROM entry where verify_ok='Y'"))
  {
    $entry_row = $result->fetch_array();
    $GLOBALS[$array_name]["num_verified"] = $entry_row[0];
  }  
}

make_petition_stats_array("petition_stat");

//now include this file and display values in your php file
//e.g.:  < ?=$GLOBALS["petition_stat"]["num_entries"] ? >

?>
