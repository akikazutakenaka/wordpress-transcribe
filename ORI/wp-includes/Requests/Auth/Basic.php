<?php
/**
 * Basic Authentication provider
 *
 * @package Requests
 * @subpackage Authentication
 */

/**
 * Basic Authentication provider
 *
 * Provides a handler for Basic HTTP authentication via the Authorization
 * header.
 *
 * @package Requests
 * @subpackage Authentication
 */
class Requests_Auth_Basic implements Requests_Auth {
	// refactored. public $user;
	// :
	// refactored. public function curl_before_send(&$handle) {}

	/**
	 * Add extra headers to the request before sending
	 *
	 * @param string $out HTTP header string
	 */
	public function fsockopen_header(&$out) {
		$out .= sprintf("Authorization: Basic %s\r\n", base64_encode($this->getAuthString()));
	}

	// refactored. public function getAuthString() {}
}