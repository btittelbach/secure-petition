<?php
//valid encryption methods are:
// * 'AUTO'                   choose OPENSSL_MCRYPT_AES128 or else OPENSSL_SEAL if mcrypt unavailable
// * 'OPENSSL_SEAL'           fast not as secure (RC4 and 1024bit RSA key)
// * 'OPENSSL_MCRYPT_AES256'  takes more time but is more secure (AES256 and 2048bit RSA key)
// * 'OPENSSL_MCRYPT_AES128'  similar to above but faster because less random material is being generated (AES128 and 2048bit RSA key)

//valid hash functions are:
// * 'ripemd256'
// * 'sha256'
// * 'ripemd128'
// * 'ripemd160'

//note: hash_function and hash_salt are used the check agains accidental resubmissions
//      don't change them later or the check will stop working
//      (or if you are really unlucky, it may even reject valid new data as already submitted)
$GLOBALS['pet_db'] = array(
        'db_host' => 'localhost',
        'db_port' => '3306',
        'db_name' => 'petition',
        'db_user' => '',
        'db_pass' => '',
        'pubkey' => file_get_contents("./petition-data.pub"),
        'crypt_method' => 'AUTO',
        'hash_function' => 'ripemd256',
        'hash_salt' => '<get some (~128) random bytes from /dev/random and put them here>'
);


// // alternativly:
// $GLOBALS['pet_db']['pubkey'] = <<<EOF
// -----BEGIN PUBLIC KEY-----
// [..]
// -----END PUBLIC KEY-----
// EOF; 

//no required fields:
$GLOBALS['required_fields'] = array();
//require all fields to be set:
//$GLOBALS['required_fields'] = array('gname', 'sname', 'email', 'addr_country', 'addr_city', 'addr_postcode', 'addr_street');

//first language is default
$GLOBALS['supported_lang'] = array("en","de");
$GLOBALS['pet_html']['submit_ok']['en'] = "submission_thankyou.html";
$GLOBALS['pet_html']['submit_ok']['de'] = "submission_danke.html";
$GLOBALS['pet_html']['submit_fail']['en'] = "submission_failure.html";
$GLOBALS['pet_html']['submit_fail']['de'] = "submission_fehler.html";

$GLOBALS['pet_html']['verify_ok']['en'] = "verify_thankyou.html";
$GLOBALS['pet_html']['verify_ok']['de'] = "verify_danke.html";
$GLOBALS['pet_html']['verify_fail']['en'] = "verify_failure.html";
$GLOBALS['pet_html']['verify_fail']['de'] = "verify_fehler.html";

$GLOBALS['pet_email']['send_email_verification'] = True;
$GLOBALS['pet_email']['verification_code_subst'] = "%VERIFICATION_CODE%";
$GLOBALS['pet_email']['headers'] = 'From: verification@example.com' . "\r\n" .
    'Reply-To: noreply@example.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
$GLOBALS['pet_email']['subject']['en'] = "Thank you for supporting our cause (e-mail verification)";
$GLOBALS['pet_email']['body']['en'] = <<<EOF
Dear Supporter,

please verify your e-mail address by clicking the following link:

https://www.example.com/verify_email.php?code=%VERIFICATION_CODE%

EOF;
$GLOBALS['pet_email']['subject']['de'] = "Vielen Dank für Ihre Unterstützung (e-mail Verifikation)";
$GLOBALS['pet_email']['body']['de'] = <<<EOF
Lieber Unterstützer,

Bitte verifizieren sie Ihre e-mail Addresse durch einen Klick auf folgenden Link:

https://www.example.com/verify_email.php?code=%VERIFICATION_CODE%

EOF;
?>
