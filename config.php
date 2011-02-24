<?php

//valid encryption methods are:
// * 'AUTO'                   choose OPENSSL_MCRYPT_AES128 or else OPENSSL_SEAL if mcrypt unavailable
// * 'OPENSSL_SEAL'           fast not as secure (RC4 and 1024bit RSA key)
// * 'OPENSSL_MCRYPT_AES256'  takes more time but is more secure (AES256 and 2048bit RSA key)
// * 'OPENSSL_MCRYPT_AES128'  similar to above but faster because less random material is being generated (AES128 and 2048bit RSA key)
$GLOBALS['pet_db'] = array(
        'db_host' => 'localhost',
        'db_port' => '3306',
        'db_name' => 'petition',
        'db_user' => '',
        'db_pass' => '',
        'pubkey' => file_get_contents("./petition-data.pub"),
        'crypt_method' => 'AUTO'
);


// // alternativly:
// $GLOBALS['pet_db']['pubkey'] = <<<EOF
// -----BEGIN PUBLIC KEY-----
// [..]
// -----END PUBLIC KEY-----
// EOF; 

//first language is default
$GLOBALS['supported_lang'] = array("en","de");
$GLOBALS['pet_html']['submit_ok']['en'] = "submission_thankyou.html";
$GLOBALS['pet_html']['submit_ok']['de'] = "submission_danke.html";

$GLOBALS['pet_html']['verify_ok']['en'] = "verify_thankyou.html";
$GLOBALS['pet_html']['verify_ok']['de'] = "verify_danke.html";
$GLOBALS['pet_html']['verify_fail']['en'] = "verify_failure.html";
$GLOBALS['pet_html']['verify_fail']['de'] = "verify_fehler.html";

$GLOBALS['pet_email']['verification_code_subst'] = "%VERIFICATION_CODE%";
$GLOBALS['pet_email']['headers'] = 'From: verification@example.com' . "\r\n" .
    'Reply-To: noreply@example.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
$GLOBALS['pet_email']['subject']['en'] = "Thank you for supporting our cause (e-mail verification)";
$GLOBALS['pet_email']['body']['en'] = <<<EOF
Dear Supporter,

please verify your e-mail address by clicking the following link:

https://www.example.com/email_verify.php?code=%VERIFICATION_CODE%

EOF;
$GLOBALS['pet_email']['subject']['de'] = "Vielen Dank für Ihre Unterstützung (e-mail Verifikation)";
$GLOBALS['pet_email']['body']['de'] = <<<EOF
Lieber Unterstützer,

Bitte verifizieren sie Ihre e-mail Addresse durch einen Klick auf folgenden Link:

https://www.example.com/email_verify.php?code=%VERIFICATION_CODE%

EOF;
?>
