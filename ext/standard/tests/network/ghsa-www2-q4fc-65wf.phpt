--TEST--
GHSA-www2-q4fc-65wf
--DESCRIPTION--
This is a ZPP test but *keep* this as it is security-sensitive!
--FILE--
<?php
try {
    dns_check_record("\0");
} catch (ValueError $e) {
    echo $e->getMessage(), "\n";
}
try {
    dns_get_mx("\0", $out);
} catch (ValueError $e) {
    echo $e->getMessage(), "\n";
}
try {
    dns_get_record("\0");
} catch (ValueError $e) {
    echo $e->getMessage(), "\n";
}
try {
    getprotobyname("\0");
} catch (ValueError $e) {
    echo $e->getMessage(), "\n";
}
try {
    getservbyname("\0", "tcp");
} catch (ValueError $e) {
    echo $e->getMessage(), "\n";
}
try {
    getservbyname("x", "tcp\0");
} catch (ValueError $e) {
    echo $e->getMessage(), "\n";
}
try {
    getservbyport(0, "tcp\0");
} catch (ValueError $e) {
    echo $e->getMessage(), "\n";
}
try {
    inet_pton("\0");
} catch (ValueError $e) {
    echo $e->getMessage(), "\n";
}
try {
    ip2long("\0");
} catch (ValueError $e) {
    echo $e->getMessage(), "\n";
}
?>
--EXPECTF--
Warning: dns_check_record() expects parameter 1 to be a valid path, string given in %s

Warning: dns_get_mx() expects parameter 1 to be a valid path, string given in %s

Warning: dns_get_record() expects parameter 1 to be a valid path, string given in %s

Warning: getprotobyname() expects parameter 1 to be a valid path, string given in %s

Warning: getservbyname() expects parameter 1 to be a valid path, string given in %s

Warning: getservbyname() expects parameter 2 to be a valid path, string given in %s

Warning: getservbyport() expects parameter 2 to be a valid path, string given in %s

Warning: inet_pton() expects parameter 1 to be a valid path, string given in %s

Warning: ip2long() expects parameter 1 to be a valid path, string given in %s

