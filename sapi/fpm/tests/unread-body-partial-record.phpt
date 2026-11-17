--TEST--
FPM: partially read STDIN record does not make the worker reject later connections
--SKIPIF--
<?php include "skipif.inc"; ?>
--FILE--
<?php

include "include.inc";

function record($type, $content, $id)
{
    return pack('CCnnCC', 1, $type, $id, strlen($content), 0, 0) . $content;
}

function encode_length($length)
{
    return $length < 128 ? chr($length) : pack('N', $length | 0x80000000);
}

function params(array $params)
{
    $encoded = '';
    foreach ($params as $name => $value) {
        $encoded .= encode_length(strlen($name)) . encode_length(strlen($value)) . $name . $value;
    }
    return $encoded;
}

function request($id, $keep, array $params, $stdin)
{
    return record(1, pack('nCxxxxx', 1, $keep), $id)
        . record(4, params($params), $id)
        . record(4, '', $id)
        . record(5, $stdin, $id)
        . record(5, '', $id);
}

function transact($port, $request)
{
    $socket = fsockopen('127.0.0.1', $port);
    stream_set_timeout($socket, 3);
    $written = 0;
    while ($written < strlen($request)) {
        $written += fwrite($socket, substr($request, $written));
    }
    stream_socket_shutdown($socket, STREAM_SHUT_WR);
    $response = stream_get_contents($socket);
    fclose($socket);
    return $response;
}

$logfile = dirname(__FILE__).'/php-fpm.log.tmp';
$port = 9000+PHP_INT_SIZE;

$cfg = <<<EOT
[global]
error_log = $logfile
[unconfined]
listen = 127.0.0.1:$port
pm = static
pm.max_children = 1
catch_workers_output = yes
EOT;

$code = <<<'PHP'
<?php
if ($_SERVER['REMOTE_ADDR'] === '203.0.113.1') {
    echo "smuggled\n";
} else {
    echo "outer ", strlen(file_get_contents('php://input')), "\n";
}
PHP;

$script = dirname(__FILE__) . '/unread-body-partial-record.src.php';
file_put_contents($script, $code);

$fpm = run_fpm($cfg, $tail);
if (is_resource($fpm)) {
    fpm_display_log($tail, 2);

    $hiddenParams = array(
        'GATEWAY_INTERFACE' => 'FastCGI/1.0',
        'REQUEST_METHOD' => 'GET',
        'SCRIPT_FILENAME' => $script,
        'SCRIPT_NAME' => '/smuggled.php',
        'REQUEST_URI' => '/smuggled.php',
        'SERVER_PROTOCOL' => 'HTTP/1.1',
        'REMOTE_ADDR' => '203.0.113.1',
        'CONTENT_LENGTH' => '0',
    );
    $hidden = request(2, false, $hiddenParams, '');
    $outerParams = array(
        'GATEWAY_INTERFACE' => 'FastCGI/1.0',
        'REQUEST_METHOD' => 'POST',
        'SCRIPT_FILENAME' => $script,
        'SCRIPT_NAME' => '/open.php',
        'REQUEST_URI' => '/open.php',
        'SERVER_PROTOCOL' => 'HTTP/1.1',
        'REMOTE_ADDR' => '127.0.0.1',
        'CONTENT_TYPE' => 'application/octet-stream',
        'CONTENT_LENGTH' => '1',
    );
    $plainParams = array('REQUEST_METHOD' => 'GET', 'CONTENT_LENGTH' => '0') + $outerParams;
    unset($plainParams['CONTENT_TYPE']);

    /* The STDIN record is longer than CONTENT_LENGTH so it is left partially read. */
    foreach (array(false, true) as $keep) {
        $response = transact($port, request(1, $keep, $outerParams, 'A' . $hidden));
        echo 'keep ', $keep ? 'on' : 'off', ': ',
            strpos($response, 'smuggled') !== false ? 'smuggled' : 'safe', ', ',
            strpos($response, 'outer 1') !== false ? 'served' : 'not served', "\n";
        /* The only worker must still serve new connections. */
        foreach (array(1, 2) as $n) {
            $response = transact($port, request(1, false, $plainParams, ''));
            echo "  next connection $n: ", strpos($response, 'outer 0') !== false ? 'served' : 'not served', "\n";
        }
    }

    proc_terminate($fpm);
    stream_get_contents($tail);
    fclose($tail);
    proc_close($fpm);
}

?>
--EXPECTF--
[%d-%s-%d %d:%d:%d] NOTICE: fpm is running, pid %d
[%d-%s-%d %d:%d:%d] NOTICE: ready to handle connections
keep off: safe, served
  next connection 1: served
  next connection 2: served
keep on: safe, served
  next connection 1: served
  next connection 2: served
--CLEAN--
<?php
    @unlink(dirname(__FILE__).'/php-fpm.log.tmp');
    @unlink(dirname(__FILE__).'/unread-body-partial-record.src.php');
?>
