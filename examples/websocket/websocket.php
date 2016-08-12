<?php

// Disable the time limit, it is a websocket
set_time_limit(0);

require_once('../../src/WebSocket.php');
require_once('../../src/Frame.php');

// Instanciate the websocket
$ws = new WebSocketS2C\WebSocket();

// Check if it is a websocket
if ($ws->isWebsocket() == false) {
	echo 'Not a websocket!';
	die();
}

// Accept the websocket
$ws->accept();

// Text (has to be UTF-8)
$ws->text(utf8_encode('This is a test!'));

for($i = 0; $i < 3; $i++) {
	$ws->text(utf8_encode('Loop ' . $i));
	sleep(1);
}

// Binary blobs
$ws->binary('This is a test!');

// JSON
$ws->json([
	'hello' => 'world'
]);

// Fragmented messages (ie. let you to "stream" some json)
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
