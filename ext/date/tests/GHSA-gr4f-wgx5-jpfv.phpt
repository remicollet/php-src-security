--TEST--
GHSA-gr4f-wgx5-jpfv: DATEG(tzcache) use-after-free
--CREDITS--
Sebastian Bergmann
--GET--
a=1&b=2
--INI--
error_log="{PWD}/GHSA-gr4f-wgx5-jpfv.log"
log_errors=1
max_input_vars=1
date.timezone=Europe/Prague
extension=GHSA-gr4f-wgx5-jpfv-does-not-exist
--FILE--
<?php
/* There is no zend_test extension here: a missing extension gives the warning
 * during module startup. The time zone cache must only be used while a request
 * is active, so messages logged outside of it carry UTC timestamps. */
error_log("in request");
foreach (file(__DIR__ . "/GHSA-gr4f-wgx5-jpfv.log") as $line) {
    if (preg_match('/^\[\S+ \S+ (\S+)\] (?:PHP Warning:  )?(PHP Startup|Unknown|in request)/', $line, $m)) {
        echo $m[2], ": ", $m[1], "\n";
    }
}
echo "Hello world\n";
?>
--CLEAN--
<?php
@unlink(__DIR__ . "/GHSA-gr4f-wgx5-jpfv.log");
?>
--EXPECTF--
%AWarning: Unknown: Input variables exceeded 1. To increase the limit change max_input_vars in php.ini. in Unknown on line 0
PHP Startup: UTC
Unknown: UTC
in request: Europe/Prague
Hello world%A
