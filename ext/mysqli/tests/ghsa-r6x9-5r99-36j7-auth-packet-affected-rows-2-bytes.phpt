--TEST--
GHSA-r6x9-5r99-36j7 (Various packet overreads in mysqlnd_writeprotocol.c - auth packet with affected rows on 2 bytes)
--SKIPIF--
<?php require_once('skipif.inc'); ?>
--FILE--
<?php
require_once 'fake_server.inc';

$servername = "127.0.0.1";
$username = "root";
$password = "";

$process = run_fake_server_in_background('auth_affected_rows_2_bytes');
$process->wait();

$conn = new mysqli( $servername, $username, $password, "", 33305 );
echo "[*] connect_errno: ", $conn->connect_errno, "\n";

$conn->close();

$process->terminate();

print "done!";
?>
--EXPECTF--
[*] Server started
[*] Connection established
[*] Sending - Server Greeting: 580000000a352e352e352d31302e352e31382d4d6172696144420003000000473e3f6047257c6700fef7080200ff81150000000000000f0000006c6b55463f49335f686c6431006d7973716c5f6e61746976655f70617373776f7264
[*] Received: %x
[*] Sending - OK Auth Response [affected rows on 2 bytes]: 0900000200fc34120002000000
[*] connect_errno: 0
done!%A
