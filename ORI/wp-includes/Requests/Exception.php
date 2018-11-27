<?php
/**
 * Exception for HTTP requests
 *
 * @package Requests
 */

/**
 * Exception for HTTP requests
 *
 * @package Requests
 */
class Requests_Exception extends Exception {
	// refactored. protected $type;
	// :
	// refactored. public function __construct($message, $type, $data = null, $code = 0) {}

	/**
	 * Like {@see getCode()}, but a string code.
	 *
	 * @codeCoverageIgnore
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Gives any relevant data
	 *
	 * @codeCoverageIgnore
	 * @return mixed
	 */
	public function getData() {
		return $this->data;
	}
}