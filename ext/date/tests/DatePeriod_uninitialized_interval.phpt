--TEST--
DatePeriod::__construct() restores error handling when the DateInterval is not initialized
--FILE--
<?php
class UninitializedInterval extends DateInterval
{
    public function __construct()
    {
    }
}

try {
    new DatePeriod(new DateTime('2020-01-01'), new UninitializedInterval(), 1);
} catch (Exception $e) {
    echo get_class($e), ': ', $e->getMessage(), "\n";
}

// The constructor must have restored the error handling: this is a warning, not an exception
var_dump(strlen());
?>
===DONE===
--EXPECTF--
Exception: %sThe DateInterval object has not been correctly initialized by its constructor

Warning: strlen() expects exactly 1 parameter, 0 given in %s on line %d
NULL
===DONE===
