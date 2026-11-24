--TEST--
GHSA-r6x9-5r99-36j7 (Various packet overreads in mysqlnd_writeprotocol.c - sha256 public key response shorter than expected)
--SKIPIF--
<?php
require_once('skipif.inc');
ob_start();
phpinfo(INFO_MODULES);
if (strpos(ob_get_clean(), 'auth_plugin_sha256_password') === false)
    die("skip mysqlnd is built without the sha256_password plugin");
?>
--FILE--
<?php
require_once 'fake_server.inc';

$servername = "127.0.0.1";
$username = "root";
$password = "secret";

$process = run_fake_server_in_background('sha256_pk_response_len');
$process->wait();

echo "[*] Connecting to the fake server...\n";
$conn = new mysqli($servername, $username, $password, "", 33305);

$conn->close();

$process->terminate(true);

print "done!";
?>
--EXPECTF--
[*] Server started
[*] Connecting to the fake server...
[*] Connection established
[*] Sending - Server Greeting: 520000000a352e352e352d31302e352e31382d4d6172696144420003000000473e3f6047257c6700fef7080200ff81150000000000000f0000006c6b55463f49335f686c6431007368613235365f70617373776f7264
[*] Received: %x
[*] Sending - Malicious SHA256 PK Response [packet too short]: 0100000200

Warning: mysqli::%s(): Premature end of data (mysqlnd_wireprotocol.c:%d) in %s on line %d

Warning: mysqli::%s(): SHA256_PK_REQUEST_RESPONSE packet shorter than expected in %s on line %d

Warning: Error while receiving public key. PID=%d in %s on line %d
[*] Received: %x
[*] Sending - Server OK: 0700000400000002000000
[*] Received: %x
[*] Server finished
done!%A
