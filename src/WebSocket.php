<?php namespace WebsocketS2C;

use WebsocketS2C\Frame as WebsocketFrame;

use Exception;

class WebSocket {
    const OPCODE_CONTINUE =  0;
    const OPCODE_TEXT     =  1;
    const OPCODE_BINARY   =  2;
    const OPCODE_CLOSE    =  8;
    const OPCODE_PING     =  9;
    const OPCODE_PONG     = 10;

    const CLOSE_REASON_NORMAL      = 1000;
    const CLOSE_REASON_GOING_AWAY  = 1001;
    const CLOSE_REASON_PROTOCOL    = 1002;
    const CLOSE_REASON_BAD_DATA    = 1003;
    const CLOSE_REASON_NO_STATUS   = 1005;
    const CLOSE_REASON_ABNORMAL    = 1006;
    const CLOSE_REASON_BAD_PAYLOAD = 1007;
    const CLOSE_REASON_POLICY      = 1008;
    const CLOSE_REASON_TOO_BIG     = 1009;
    const CLOSE_REASON_MAND_EXT    = 1010;
    const CLOSE_REASON_SRV_ERR     = 1011;
    const CLOSE_REASON_TLS         = 1015;

	const ACCEPT_GUID = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";

	protected $headers = null;
	protected $isFirstFrame = true;
	protected $sendingFragmentedMessage = false;

	public function accept() {

		if ($this->isWebsocket() == false) {
			throw new WebsocketNotAWebsocketException();
		}

		if ($this->version() != 13) {
			throw new WebsocketUnsupportedVersionException();
		}

		if (headers_sent()) {
			throw new WebsocketHeadersAlreadySentException();
		}

		$this->sendAcceptHeaders();
		$this->cleanOutputBuffer();
		$this->flushHeaders();
	}

	protected function sendFrame(WebsocketFrame $frame) {
		$this->send($frame->build());
	}

	protected function buildFrame($opcode, $isLastFrame, $payload = null) {
		return new WebsocketFrame($opcode, $isLastFrame, $payload);
	}

	protected function signKey($key) {
		return base64_encode(sha1($key . self::ACCEPT_GUID, true));
	}

	public function frame($opcode, $isLastFrame, $payload) {
		if ($isLastFrame == false) {
			if ($this->isFirstFrame == false) {
				$opcode = 0;
			}

			$this->sendingFragmentedMessage = true;
			$this->isFirstFrame = false;
		} else {
			if ($this->isFirstFrame == false) {
				$opcode = 0;
			}
			$this->sendingFragmentedMessage = false;
			$this->isFirstFrame = true;
		}

		$this->sendFrame($this->buildFrame($opcode, $isLastFrame, $payload));
	}

	public function text($text, $isLastFrame = true) {
		$this->frame(self::OPCODE_TEXT, $isLastFrame, $text);
	}

	public function json($json, $isLastFrame = true) {
		$this->frame(self::OPCODE_TEXT, $isLastFrame, json_encode($json));
	}

	public function binary($payload, $isLastFrame = true) {
		$this->frame(self::OPCODE_BINARY, $isLastFrame, $payload);
	}

	public function close($reason = 0, $message = '') {
		$payload = $message || $reason
			? chr($reason >> 8) . chr($reason & 0xFF) . $message
			: '';

		$this->frame(self::OPCODE_CLOSE, true, $payload);
	}

	public function ping($payload = null, $isLastFrame = true) {
		$this->frame(self::OPCODE_PING, $isLastFrame, $payload);
	}

	public function pong($payload = null, $isLastFrame = true) {
		$this->frame(self::OPCODE_PONG, $isLastFrame, $payload);
	}

	public function isWebsocket() {
		$headers = $this->headers();

		return
			isset($headers['connection']) && strtolower($headers['connection']) == 'upgrade' && 
			isset($headers['upgrade']) && strtolower($headers['upgrade']) == 'websocket' &&
			isset($headers['sec-websocket-key']);
	}

	protected function protocol() {
		$headers = $this->headers();

		return isset($headers['sec-websocket-protocol'])
			? $headers['sec-websocket-protocol']
			: null;
	}

	protected function version() {
		$headers = $this->headers();

		return isset($headers['sec-websocket-version'])
			? $headers['sec-websocket-version']
			: null;
	}

	protected function headers() {
		if (empty($this->headers)) {
			$headers = [ ];
			foreach(getallheaders() as $name => $value) {
				$headers[strtolower($name)] = $value;
			}

			$this->headers = $headers;
		}

		return $this->headers;
	}

	private function sendAcceptHeaders() {
		header('HTTP/1.1 101 Switching Protocols', false, 101);
		header('Connection: Upgrade');
		header('Upgrade: websocket');
		header('Content-Type: application/json');
		header('Content-Length: 0');
		header('Transfer-Encoding: identity');
		header('Content-Encoding: identity');

		if (function_exists('apache_setenv')) {
			apache_setenv('no-gzip', 1);
		}

		$headers = $this->headers();
		header('Sec-WebSocket-Accept: ' . $this->signKey($headers['sec-websocket-key']));
		if (isset($headers['sec-websocket-protocol'])) {
			header('Sec-WebSocket-Protocol: ' . $this->protocol());
		}
	}

	private function cleanOutputBuffer() {
		while(ob_get_level() > 0) {
			ob_end_flush();
		}
	}

	protected function send($data) {
		echo $data; flush();
	}

	protected function flushHeaders() {
		$this->send('');
	}
}

class WebsocketException extends Exception {

}

class WebsocketNotAWebsocketException extends WebsocketException {

}

class WebsocketUnsupportedVersionException extends WebsocketException {

}

class WebsocketHeadersAlreadySentException extends WebsocketException {

}
