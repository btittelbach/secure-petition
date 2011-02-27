<?php

$outfile_privkey="petition-data.key";
$outfile_pubkey="petition-data.pub";

$config = array('private_key_bits' => 2048);
$keypair = openssl_pkey_new($config);
openssl_pkey_export_to_file($keypair, $outfile_privkey);
print("* wrote $outfile_privkey\n");
$keyData = openssl_pkey_get_details($keypair);
file_put_contents($outfile_pubkey, $keyData['key']);
print("* wrote $outfile_pubkey\n");
openssl_pkey_free($keypair);

print("\nKEEP $outfile_privkey SAFE !!!\nNOT on the machine with the online petition !\n\n");
?>
