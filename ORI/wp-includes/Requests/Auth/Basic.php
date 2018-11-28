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
	// refactored. public function __construct($args = null) {}

	/**
	 * Register the necessary callbacks
	 *
	 * @see curl_before_send
	 * @see fsockopen_header
	 * @param Requests_Hooks $hooks Hook system
	 */
	public function register(Requests_Hooks &$hooks) {
		$hooks->register('curl.before_send', array(&$this, 'curl_before_send'));
		$hooks->register('fsockopen.after_headers', array(&$this, 'fsockopen_header'));
	}

	// refactored. public function curl_before_send(&$handle) {}

	/**
	 * Add extra headers to the request before sending
	 *
	 * @param string $out HTTP header string
	 */
	public function fsockopen_header(&$out) {
		$out .= sprintf("Authorization: Basic %s\r\n", base64_encode($this->getAuthString()));
	}

	/**
	 * Get the authentication string (user:pass)
	 *
	 * @return string
	 */
	public function getAuthString() {
		return $this->user . ':' . $this->pass;
	}
}