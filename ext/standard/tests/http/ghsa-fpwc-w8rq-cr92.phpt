--TEST--
GHSA-fpwc-w8rq-cr92: strip credentials from user headers on cross-origin redirect
--INI--
allow_url_fopen=1
--SKIPIF--
<?php require 'server.inc'; http_server_skipif("tcp://127.0.0.1:12342"); ?>
--FILE--
<?php
require 'server.inc';

function count_header(string $requests, string $name): int {
    return preg_match_all('/^' . preg_quote($name, '/') . ':/mi', $requests);
}

function report(string $label, string $requests): void {
    echo $label, "\n";
    foreach (['Authorization', 'Cookie', 'Proxy-Authorization', 'X-Custom'] as $name) {
        echo "  $name: ", count_header($requests, $name), "\n";
    }
}

$ctx = stream_context_create(['http' => [
    'header' => "Authorization: Bearer SECRET\r\n"
        . "Cookie: sid=abc\r\n"
        . "Proxy-Authorization: Basic Zm9vOmJhcg==\r\n"
        . "X-Custom: keep-me",
    'follow_location' => 1,
]]);

/* server B listens on a different port than server A, so the hop from A to B is
 * cross-origin; B then redirects to itself: credentials must stay withheld for
 * that same-origin hop too */
$captureB = null;
$pidB = http_server("tcp://127.0.0.1:12342", [
    "data://text/plain,HTTP/1.1 302 Found\r\nLocation: /second\r\nContent-Length: 0\r\n\r\n",
    "data://text/plain,HTTP/1.1 200 OK\r\nContent-Length: 2\r\n\r\nOK",
], $captureB);

$captureA = null;
$pidA = http_server("tcp://127.0.0.1:12343", [
    "data://text/plain,HTTP/1.1 302 Found\r\nLocation: http://127.0.0.1:12342/first\r\nContent-Length: 0\r\n\r\n",
], $captureA);

var_dump(file_get_contents("http://127.0.0.1:12343" . '/src', false, $ctx));

http_server_kill($pidA);
http_server_kill($pidB);

rewind($captureA);
rewind($captureB);
report('--- origin A (1 request) ---', stream_get_contents($captureA));
report('--- origin B (2 requests) ---', stream_get_contents($captureB));

/* same origin throughout: credentials must be sent on both hops */
$captureC = null;
$pidC = http_server("tcp://127.0.0.1:12344", [
    "data://text/plain,HTTP/1.1 302 Found\r\nLocation: /next\r\nContent-Length: 0\r\n\r\n",
    "data://text/plain,HTTP/1.1 200 OK\r\nContent-Length: 2\r\n\r\nOK",
], $captureC);

var_dump(file_get_contents( "http://127.0.0.1:12344" . '/first', false, $ctx));

http_server_kill($pidC);

rewind($captureC);
report('--- origin C (2 requests) ---', stream_get_contents($captureC));

/* a stripped header that was last in the bag must not leave a trailing line break
 * behind, or the request body would be pushed out of the request */
$ctx = stream_context_create(['http' => [
    'method' => 'POST',
    'content' => 'hello=world',
    'header' => "X-Custom: keep-me\r\nAuthorization: Bearer SECRET",
    'follow_location' => 1,
]]);

$captureH = null;
$pidH = http_server("tcp://127.0.0.1:12345", [
    "data://text/plain,HTTP/1.1 200 OK\r\nContent-Length: 2\r\n\r\nOK",
], $captureH);

$captureG = null;
$pidG = http_server("tcp://127.0.0.1:12346", [
    "data://text/plain,HTTP/1.1 307 Temporary Redirect\r\nLocation: http://127.0.0.1:12345/second\r\nContent-Length: 0\r\n\r\n",
], $captureG);

var_dump(@file_get_contents("http://127.0.0.1:12346" . '/first', false, $ctx));

http_server_kill($pidG);
http_server_kill($pidH);

rewind($captureH);
echo "--- credential header last in the bag (307) ---\n";
echo preg_replace('/^Host:.*$/m', 'Host: ...', stream_get_contents($captureH));

echo "--- malformed header bags ---\n";
foreach ([
    'folded value      ' => "Authorization:\r\n Bearer SECRET\r\nX-Custom: keep-me",
    'folded value (tab)' => "Authorization:\r\n\tBearer SECRET\r\nX-Custom: keep-me",
    'lone CR           ' => "X-Custom: keep-me\rAuthorization: Bearer SECRET",
    'space before colon' => "Authorization : Bearer SECRET\r\nX-Custom: keep-me",
    'tab before colon  ' => "Authorization\t: Bearer SECRET\r\nX-Custom: keep-me",
] as $label => $header) {
    $ctx = stream_context_create(['http' => ['header' => $header, 'follow_location' => 1]]);

    $captureF = null;
    $pidF = http_server("tcp://127.0.0.1:12347", [
        "data://text/plain,HTTP/1.1 200 OK\r\nContent-Length: 2\r\n\r\nOK",
    ], $captureF);

    $captureE = null;
    $pidE = http_server("tcp://127.0.0.1:12348", [
        "data://text/plain,HTTP/1.1 302 Found\r\nLocation: http://127.0.0.1:12347/second\r\nContent-Length: 0\r\n\r\n",
    ], $captureE);

    file_get_contents("http://127.0.0.1:12348" . '/first', false, $ctx);

    http_server_kill($pidE);
    http_server_kill($pidF);

    rewind($captureF);
    $request = stream_get_contents($captureF);
    printf("  %s  SECRET leaked: %d, X-Custom kept: %d\n", $label,
        strpos($request, 'SECRET')!==false, strpos($request, 'X-Custom')!==false);
}
?>
--EXPECT--
string(2) "OK"
--- origin A (1 request) ---
  Authorization: 1
  Cookie: 1
  Proxy-Authorization: 1
  X-Custom: 1
--- origin B (2 requests) ---
  Authorization: 0
  Cookie: 0
  Proxy-Authorization: 0
  X-Custom: 2
string(2) "OK"
--- origin C (2 requests) ---
  Authorization: 2
  Cookie: 2
  Proxy-Authorization: 2
  X-Custom: 2
string(2) "OK"
--- credential header last in the bag (307) ---
POST /second HTTP/1.0
Host: ...
Connection: close
Content-Length: 11
X-Custom: keep-me
Content-Type: application/x-www-form-urlencoded

hello=world--- malformed header bags ---
  folded value        SECRET leaked: 0, X-Custom kept: 1
  folded value (tab)  SECRET leaked: 0, X-Custom kept: 1
  lone CR             SECRET leaked: 0, X-Custom kept: 1
  space before colon  SECRET leaked: 0, X-Custom kept: 1
  tab before colon    SECRET leaked: 0, X-Custom kept: 1
