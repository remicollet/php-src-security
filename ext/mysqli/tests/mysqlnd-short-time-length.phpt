--TEST--
mysqlnd rejects a binary TIME field whose declared length is too short
--EXTENSIONS--
mysqli
--FILE--
<?php
require_once 'fake_server.inc';
$port = 33305;
$process = run_fake_server_in_background('stmt_response_row_short_length_time', $port);
$process->wait();
$conn = new mysqli('127.0.0.1', 'root', '', '', $port);
$stmt = $conn->prepare('SELECT strval, timval FROM data');
$stmt->execute();
$stmt->bind_result($str, $value);
var_dump($stmt->fetch());
$stmt->close();
$conn->close();
$process->terminate(true);
echo "done\n";
?>
--EXPECTREGEX--
(?s).*Malformed server packet\. Field length is too short for the field type.*bool\(false\).*done
