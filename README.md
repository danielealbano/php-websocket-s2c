# php-websocket-s2c

## Introduction

php-websocket-s2c is a PHP Websocket native implementation able to serve requests through apache, nginx or any other webserver and proxy without any particular configuration.

The websocket support in PHP is almost absent because, usually, you need support for them at webserver/proxy level, the approach used in this implementation allows to send packets only from the server to the client and not the reverse without having to change any configuration parameter on your proxy and/or on your webserver, the **s2c** suffixs stands for **Server To Client**.

## Supported features

The library supports the following features:
- Server to Client messages (text (json), binary, ping/pong)
- Fragmented messages (used to stream data)
- Packet size up to 2^64
- Try to automatically disable http compression (gzip) and chunking
- Websocket Protocols (Sec-Websocket-Protocol header)
- Protocol version 13 (https://en.wikipedia.org/wiki/WebSocket#Browser_implementation)

What is unsupported and/or untested:
- Extensions (multiplexing, compression, etc.)
- SSL/TLS

This library has been tested with PHP 5.4, PHP 5.5 and PHP 5.6 on Apache 2.2 with mod_php

## Bugs

This library is very young and uses tricks to avoid starting a standalone webserver, if something doesn't work, before opening an issue, please check if your webserver is using/requesting:
- the compression enabled (Content-Encoding header should be absent or its value should be **identity**);
- the chunking enabled (Transfer-Encoding header should be absent or its value should be **identity**).

When you report an issue please remember to mention:
- if Content-Length header has a value different than zero;
- if Connection header is absent or has a different value than **Upgrade**;
- if Upgrade header is absent or has a different value than **websocket**;
- Your webserver (name and version);
- If you are using php as module or as fastcgi;
- If you are using a proxy and, if yes, which one

## Examples

### Plain Websocket

##### New the websocket server
```php
require_once('src/WebSocket.php');
require_once('src/Frame.php');

$ws = new WebsocketS2C\WebSocket();
```

Using composer will not be necessary to include the sources.

##### Check if a websocket is requested
```php
if ($ws->isWebsocket() == false) {
	echo 'Not a websocket!';
	die();
}
```

##### Accept connections
```php
$ws->accept();
```

##### Send text frames (first example)
```php
$ws->text(utf8_encode('This is a test!'));
```

##### Send text frames (second example)
```php
for($i = 0; $i < 3; $i++) {
	$ws->text(utf8_encode('Loop ' . $i));
	sleep(1);
}
```

##### Send json frames
```php
$ws->json([
	'hello' => 'world'
]);
```

##### Send binary (blob) frames
```php
$ws->binary('This is a test!');
```

##### Stream data
```php
$isFirst = true;
$ws->text('[', false, false);
foreach([ 'this', 'is', 'a', 'multipart', 'message' ] as $part) {
	if ($isFirst == false) {
		$ws->text(',', false, false);
	}

	$ws->json([
		'alot' => true,
		'of' => true,
		'data' => $part
	], false);

	$isFirst = false;
}
$ws->text(']');
```
