--TEST--
Phar::setSignatureAlgorithm() with Phar::OPENSSL signs every time (result of openssl_sign() read after it was freed)
--SKIPIF--
<?php
if (!extension_loaded("phar")) die("skip");
$arr = Phar::getSupportedSignatures();
if (!in_array("OpenSSL", $arr)) die("skip openssl support required");
?>
--INI--
phar.require_hash=0
phar.readonly=0
--FILE--
<?php
$fname = dirname(__FILE__) . '/' . basename(__FILE__, '.php') . '.phar';
$private = openssl_get_privatekey(file_get_contents(dirname(__FILE__) . '/files/private.pem'));
$pkey = '';
openssl_pkey_export($private, $pkey, NULL, array('config' => dirname(__FILE__) . '/files/openssl.cnf'));
$p = new Phar($fname);
$p['file1.txt'] = 'hi';
$failed = 0;
for ($i = 0; $i < 100; $i++) {
	try {
		$p->setSignatureAlgorithm(Phar::OPENSSL, $pkey);
		$sig = $p->getSignature();
		if ($sig['hash_type'] !== 'OpenSSL') {
			$failed++;
		}
	} catch (Exception $e) {
		$failed++;
	}
}
var_dump($failed);
?>
===DONE===
--CLEAN--
<?php
unlink(dirname(__FILE__) . '/' . basename(__FILE__, '.clean.php') . '.phar');
?>
--EXPECT--
int(0)
===DONE===
