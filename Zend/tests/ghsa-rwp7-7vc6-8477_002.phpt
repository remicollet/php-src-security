--TEST--
GHSA-rwp7-7vc6-8477: Use-after-free for ??= due to incorrect live-range calculation
--FILE--
<?php

class Foo {
    public int $prop;

    public function getThis() {
        return $this;
    }
}

$foo = new Foo();

try {
    $foo->getThis()->prop ??= 'foo';
} catch (Error $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Typed property Foo::$prop must be int, string used
