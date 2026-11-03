--TEST--
mysqlnd rejects an OK message whose length exceeds the packet body
--EXTENSIONS--
mysqli
--FILE--
<?php
require_once 'fake_server.inc';
$port = 33305;
$process = run_fake_server_in_background('ok_message_length_past_packet', $port);
$process->wait();
try {
    $conn = new mysqli('127.0.0.1', 'root', '', '', $port);
    var_dump($conn->select_db('php_test'));
    $conn->close();
} catch (Exception $e) {
    echo $e->getMessage(), "\n";
}
$process->terminate(true);
echo "done\n";
?>
--EXPECTREGEX--
(?s).*OK packet .*shorter than expected.*bool\(false\).*done
