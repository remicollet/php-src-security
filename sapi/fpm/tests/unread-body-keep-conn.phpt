--TEST--
FPM: unread request body cannot become a new request on a kept connection
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
    $input = fopen('php://input', 'r');
    echo "outer\n";
}
PHP;

$script = dirname(__FILE__) . '/unread-body-keep-conn.src.php';
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
    $body = str_pad("--B\r\nContent-Disposition: form-data\r\n\r\n", 12000, 'A');
    $body = substr_replace($body, $hidden, 5120, strlen($hidden));

    $outerParams = array(
        'GATEWAY_INTERFACE' => 'FastCGI/1.0',
        'REQUEST_METHOD' => 'POST',
        'SCRIPT_FILENAME' => $script,
        'SCRIPT_NAME' => '/open.php',
        'REQUEST_URI' => '/open.php',
        'SERVER_PROTOCOL' => 'HTTP/1.1',
        'REMOTE_ADDR' => '127.0.0.1',
        'CONTENT_TYPE' => 'multipart/form-data; boundary=B',
        'CONTENT_LENGTH' => (string) strlen($body),
    );
    foreach (array(false, true) as $keep) {
        $response = transact($port, request(1, $keep, $outerParams, $body));
        echo 'keep ', $keep ? 'on' : 'off', ': ',
            strpos($response, 'smuggled') !== false ? 'smuggled' : 'safe', "\n";
    }

    $body = 'A' . $hidden;
    $outerParams['CONTENT_TYPE'] = 'application/octet-stream';
    $outerParams['CONTENT_LENGTH'] = '1';
    $response = transact($port, request(1, true, $outerParams, $body));
    echo 'partial frame: ',
        strpos($response, 'smuggled') !== false ? 'smuggled' : 'safe', "\n";

    proc_terminate($fpm);
    stream_get_contents($tail);
    fclose($tail);
    proc_close($fpm);
}

?>
--EXPECTF--
[%d-%s-%d %d:%d:%d] NOTICE: fpm is running, pid %d
[%d-%s-%d %d:%d:%d] NOTICE: ready to handle connections
keep off: safe
keep on: safe
partial frame: safe
--CLEAN--
<?php
    @unlink(dirname(__FILE__).'/php-fpm.log.tmp');
    @unlink(dirname(__FILE__).'/unread-body-keep-conn.src.php');
?>
