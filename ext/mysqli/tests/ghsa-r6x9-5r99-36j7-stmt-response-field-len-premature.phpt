--TEST--
GHSA-r6x9-5r99-36j7 (Various packet overreads in mysqlnd_writeprotocol.c - stmt field length over-reading into fixed block)
--SKIPIF--
<?php require_once('skipif.inc'); ?>
--FILE--
<?php
require_once 'fake_server.inc';

$servername = "127.0.0.1";
$username = "root";
$password = "";

$process = run_fake_server_in_background('stmt_response_field_len_premature');
$process->wait();

$conn = new mysqli( $servername, $username, $password, "", 33305 );

echo "[*] Preparing statement on the fake server...\n";
$conn->prepare("SELECT item FROM items");

$conn->close();

$process->terminate();

print "done!";
?>
--EXPECTF--
[*] Server started
[*] Connection established
[*] Sending - Server Greeting: 580000000a352e352e352d31302e352e31382d4d6172696144420003000000473e3f6047257c6700fef7080200ff81150000000000000f0000006c6b55463f49335f686c6431006d7973716c5f6e61746976655f70617373776f7264
[*] Received: %x
[*] Sending - Server OK: 0700000200000002000000
[*] Preparing statement on the fake server...
[*] Received: %x
[*] Sending - Malicious Stmt Prepare items [Field length over-read]: 0c0000010001000000010000000000003000000203646566087068705f74657374056974656d73056974656d73046974656d0c6974656d0ce000c8000000fd011000000005000003fe00000200

Warning: mysqli::prepare(): Protocol error. Server sent false length. Expected 12 in %s on line %d

Warning: mysqli::prepare(): Premature end of data (mysqlnd_wireprotocol.c:%d) in %s on line %d

Warning: mysqli::prepare(): Result set field packet shorter than expected in %s on line %d
done!
