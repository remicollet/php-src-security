--TEST--
GHSA-gr4f-wgx5-jpfv: DATEG(tzcache) use-after-free
--CREDITS--
Sebastian Bergmann
--GET--
a=1&b=2
--SKIPIF--
<?php
if (!extension_loaded('session')) die('skip session extension not available');
if (!extension_loaded('test')) die('skip zend-test extension not available');
?>
--INI--
error_log="{TMP}/GHSA-gr4f-wgx5-jpfv.log"
log_errors=1
max_input_vars=1
zend_test.warn_on_startup=1
--FILE--
<?php
echo "Hello world\n";
?>
--EXPECTF--
Warning: Unknown: Input variables exceeded 1. To increase the limit change max_input_vars in php.ini. in Unknown on line 0
Hello world%r(?:\n\nWarning: Test startup warning from zend_test in Unknown on line 0)?%r
