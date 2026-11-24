--TEST--
GHSA-r6x9-5r99-36j7 (Various packet overreads in mysqlnd_writeprotocol.c - change user response shorter than expected)
--SKIPIF--
<?php require_once('skipif.inc'); ?>
--FILE--
<?php
require_once 'fake_server.inc';

$servername = "127.0.0.1";
$username = "root";
$password = "";

$process = run_fake_server_in_background('chg_user_response_len');
$process->wait();

$conn = new mysqli($servername, $username, $password, "", 33305);

echo "[*] Changing user on the fake server...\n";
var_dump($conn->change_user("root2", "", ""));

$conn->close();

$process->terminate(true);

print "done!";
?>
--EXPECTF--
[*] Server started
[*] Connection established
[*] Sending - Server Greeting: 580000000a352e352e352d31302e352e31382d4d6172696144420003000000473e3f6047257c6700fef7080200ff81150000000000000f0000006c6b55463f49335f686c6431006d7973716c5f6e61746976655f70617373776f7264
[*] Received: %x
[*] Sending - Server OK: 0700000200000002000000
[*] Changing user on the fake server...
[*] Received: %x
[*] Sending - Malicious Change User Response [packet too short]: 0100000100

Warning: mysqli::change_user(): Premature end of data (mysqlnd_wireprotocol.c:%d) in %s on line %d

Warning: mysqli::change_user(): CHANGE_USER packet shorter than expected in %s on line %d
bool(false)
[*] Received: %x
[*] Server finished
done!
