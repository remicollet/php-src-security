--TEST--
openssl_csr_get_public_key() does not return the private key of the request
--SKIPIF--
<?php
if (!extension_loaded("openssl")) die("skip openssl extension not available");
?>
--FILE--
<?php
$args = array(
	'config' => __DIR__ . DIRECTORY_SEPARATOR . 'openssl.cnf',
	'private_key_bits' => 1024,
	'private_key_type' => OPENSSL_KEYTYPE_RSA,
);
$key = openssl_pkey_new($args);
$details = openssl_pkey_get_details($key);
var_dump(isset($details['rsa']['d']));

$csr = openssl_csr_new(array('commonName' => 'example.com'), $key, $args);
$details = openssl_pkey_get_details(openssl_csr_get_public_key($csr));
var_dump(isset($details['rsa']['n']), isset($details['rsa']['d']));
?>
--EXPECT--
bool(true)
bool(true)
bool(false)
