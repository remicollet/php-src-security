--TEST--
GHSA-h96m-rvf9-jgm2
--FILE--
<?php

$power = 20; // Chosen to be well within a memory_limit
$arr = range(0, 2**$power);
try {
    array_merge(...array_fill(0, 2**(32-$power), $arr));
} catch (Error $e) {
    echo $e->getMessage(), "\n";
}

?>
--EXPECTF--
Fatal error: Allowed memory size %s
