--TEST--
OOM: Hard limit exceeded
--SKIPIF--
<?php
if (!extension_loaded('zend-test')) die('skip zend-test extension not loaded');
--INI--
memory_limit=10M
--FILE--
<?php


function f() {
    zend_test_emalloc(1024*1024*1024);
}

function shutdown_function() {
    echo "Not executed\n";
}

register_shutdown_function('shutdown_function');
f();

?>
--EXPECTF--
Hard memory limit of 20971520 bytes exhausted %S(tried to allocate %d bytes) in %s on line 5

Termsig=9
