<?php
// This is the remote database holding the encrypted petition Data
$GLOBALS['pet_db'] = array(
        'db_host' => 'localhost',
        'db_port' => '3306',
        'db_name' => 'petition',
        'db_user' => '',
        'db_pass' => ''
);

// This is the local database where we will save the decrypted Data
$GLOBALS['dec_db'] = array(
        'db_host' => 'localhost',
        'db_port' => '3306',
        'db_name' => 'petition',
        'db_user' => '',
        'db_pass' => '',
        'seckey' => file_get_contents("./petition-data.key"),
        'sql_create_table' => file_get_contents("decrypted-petition.sql")
);

?>
