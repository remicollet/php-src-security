--TEST--
GHSA-r6x9-5r99-36j7 (Various packet overreads in mysqlnd_writeprotocol.c - rset header upsert packet length)
--SKIPIF--
<?php require_once('skipif.inc'); ?>
--FILE--
<?php
require_once 'fake_server.inc';

$servername = "127.0.0.1";
$username = "root";
$password = "";

$process = run_fake_server_in_background('upsert_rset_header_packet_len_over_read');
$process->wait();

$conn = new mysqli($servername, $username, $password, "", 33305);

// Construct the SQL query directly
$sql = "UPDATE data SET strval = 'test' WHERE id = 1";

// Execute the query
$result = $conn->query($sql);
if ($result) {
    echo "Affected rows: " . $conn->affected_rows . "\n";
    echo "Info: " . $conn->info . "\n";
}

// Close the connection
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
[*] Received: %x
[*] Sending - Malicious Upsert Response [affected rows overflow]: 3000000100fd000002000028526f7773206d6174636865643a203120204368616e6765643a203020205761726e696e67733a2030

Warning: mysqli::query(): RSET_HEADER packet additional data length is past 82 bytes the packet size in %s on line %d

Warning: mysqli::query(): Error reading result set's header in %s on line %d
[*] Server finished
done!
