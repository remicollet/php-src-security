--TEST--
GHSA-h35g-vwh6-m678 (mysqlnd leaks partial content of the heap - auth message buffer over-read)
--EXTENSIONS--
mysqli
--FILE--
<?php
require_once 'fake_server.inc';

$port = 33305;
$servername = "127.0.0.1";
$username = "root";
$password = "";

$process = run_fake_server_in_background('auth_response_message_over_read', $port);
$process->wait();

try {
    $conn = new mysqli( $servername, $username, $password, "", $port );
    $info = mysqli_info($conn);
    var_dump($info);
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}

$process->terminate();

print "done!";
?>
--EXPECTF--
[*] Server started
[*] Connection established
[*] Sending - Server Greeting: 580000000a352e352e352d31302e352e31382d4d6172696144420003000000473e3f6047257c6700fef7080200ff81150000000000000f0000006c6b55463f49335f686c6431006d7973716c5f6e61746976655f70617373776f7264
[*] Received: %x
[*] Sending - Malicious OK Auth Response [Extract heap through buffer over-read]: 0900000200000002000000fcff

Warning: mysqli::__construct(): OK packet message length is past the packet size in %s on line %d
%A
