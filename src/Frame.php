<?php namespace WebSocketS2C;

use Exception;

class Frame {
	protected $opcode;
	protected $payload;
	protected $isLastFrame;

	public function __construct($opcode = null, $isLastFrame = null, $payload = null) {
		if ($opcode !== null) {
			$this->opcode($opcode);
		}

		if ($isLastFrame !== null) {
			$this->isLastFrame($isLastFrame);
		}

		if ($payload !== null) {
			$this->payload($payload);
		}
	}

	public function opcode($opcode = null) {
		if (func_num_args() > 0) {
			$this->opcode = $opcode;
		}

		return $this->opcode;
	}

	public function payload($payload = null) {
		if (func_num_args() > 0) {
			$this->payload = $payload;
		}

		return $this->payload;
	}

	public function isLastFrame($isLastFrame = null) {
		if (func_num_args() > 0) {
			$this->isLastFrame = $isLastFrame;
		}

		return $this->isLastFrame;
	}

	static public function parse($data) {
		throw new FrameNotImplementedException();
	}

	public function build() {
		$header = $this->buildHeader();

		return $header . $this->payload;
	}

	protected function buildHeader() {
		$payloadLength = strlen($this->payload);

		if ($payloadLength <= 125) {
			$payloadLengthHeader = chr($payloadLength);
		} else {
			$bytes = $payloadLength < 65536 ? 2 : 4;

			if ($bytes == 2) {
				$payloadLengthHeader = chr(126);
			} else {
				$payloadLengthHeader = chr(127) . chr(0) . chr(0) . chr(0) . chr(0);
			}

			for ($byteIndex = 1; $byteIndex <= $bytes; $byteIndex++) {
				$payloadLengthHeader .= chr(($payloadLength >> (($bytes - $byteIndex) * 8)) & 0xFF);
			}
		}

		$options = $this->buildHeaderOptions($this->isLastFrame ? 1 : 0, 0, 0, 0);

		$responseHeader = 
			chr(($options << 4) | $this->opcode) .
			$payloadLengthHeader;

		return $responseHeader;
	}

	protected function buildHeaderOptions($fin = 0, $rsv1 = 0, $rsv2 = 0, $rsv3 = 0) {
		return ($fin << 3) | ($rsv1 << 2) | ($rsv2 << 1) | ($rsv3 << 0);
	}
}

class FrameException extends Exception {

}

class FrameNotImplementedException extends FrameException {

}

class FrameUnsupportedLengthException extends FrameException {

}
