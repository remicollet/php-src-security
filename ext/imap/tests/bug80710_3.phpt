--TEST--
Bug #80710 (imap_mail_compose() header injection) - parameter names of the top-level body
--SKIPIF--
<?php
if (!extension_loaded("imap")) die("skip imap extension not available");
?>
--FILE--
<?php
$envelope = array("from" => "joe@example.com", "to" => "foo@example.com");
$injected = "x\r\nX-Injected: 1\r\nfoo";

function body($parameters, $disposition) {
	$part = array("type" => TYPETEXT, "subtype" => "plain", "contents.data" => "text");
	if ($parameters) {
		$part["type.parameters"] = $parameters;
	}
	if ($disposition) {
		$part["disposition.type"] = "attachment";
		$part["disposition"] = $disposition;
	}
	return array($part);
}

/* the injected name comes last: it is the one the check used to miss */
var_dump(imap_mail_compose($envelope, body(array("format" => "flowed", $injected => "bar"), null)));
var_dump(imap_mail_compose($envelope, body(null, array("filename" => "a.txt", $injected => "bar"))));
var_dump(is_string(imap_mail_compose($envelope, body(array("format" => "flowed"), array("filename" => "a.txt")))));
?>
--EXPECTF--
Warning: imap_mail_compose(): header injection attempt in body disposition key in %s on line %d
bool(false)

Warning: imap_mail_compose(): header injection attempt in body type.parameters key in %s on line %d
bool(false)
bool(true)
