--TEST--
GH-24046 (FPM: worker stdio pipe fd closed twice when a child process outlives the worker)
--SKIPIF--
<?php include "skipif.inc"; ?>
--FILE--
<?php

require_once "tester.inc";

$cfg = <<<EOT
[global]
error_log = {{FILE:LOG}}
[unconfined]
listen = {{ADDR}}
pm = static
pm.max_children = 1
pm.max_requests = 1
catch_workers_output = yes
decorate_workers_output = no
EOT;

$code = <<<'EOT'
<?php
/* dup() of the worker's stderr pipe that is inherited by the background process */
$stderr = fopen('php://stderr', 'a');
if (@fwrite($stderr, "log line\n") === false) {
    echo "write to stderr failed";
    exit;
}
if (isset($_GET['bg'])) {
    exec('sleep 3 > /dev/null 2>&1 &');
}
echo "ok";
EOT;

$tester = new FPM\Tester($cfg, $code);
$tester->start();
$tester->expectLogStartNotices();
/* The worker exits after this request while the background process still holds
 * its stderr pipe open. The replacement worker gets the same pipe fd numbers. */
$tester->request('bg=1')->expectBody('ok');
/* Wait for the postponed free of the exited worker. */
usleep(1500000);
$tester->request()->expectBody('ok');
$tester->terminate();
/* Tester::expectNoLogPattern() is not available here, check the log file directly */
$logFile = substr(__FILE__, 0, -4) . '.log';
for ($i = 0; $i < 50 && strpos((string) @file_get_contents($logFile), 'bye-bye') === false; $i++) {
    usleep(100000);
}
$log = (string) @file_get_contents($logFile);
if (preg_match('/unable to (remove|read)/', $log)) {
    echo "ERROR: unexpected message in the log\n", $log;
}
$addr = $tester->getAddr();
$tester->close();
/* The background process also inherits the listening socket: wait until it is
 * gone, otherwise the next FPM test cannot bind the same port. */
for ($i = 0; $i < 50; $i++) {
    $socket = @stream_socket_server('tcp://' . $addr);
    if ($socket) {
        fclose($socket);
        break;
    }
    usleep(100000);
}

?>
Done
--EXPECT--
Done
--CLEAN--
<?php
require_once "tester.inc";
FPM\Tester::clean();
?>
