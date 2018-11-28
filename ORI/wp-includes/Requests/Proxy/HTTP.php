<?php
/**
 * HTTP Proxy connection interface
 *
 * @package Requests
 * @subpackage Proxy
 * @since 1.6
 */

/**
 * HTTP Proxy connection interface
 *
 * Provides a handler for connection via an HTTP proxy
 *
 * @package Requests
 * @subpackage Proxy
 * @since 1.6
 */
class Requests_Proxy_HTTP implements Requests_Proxy {
	// refactored. public $proxy;
	// :
	// refactored. public function fsockopen_remote_host_path(&$path, $url) {}

	/**
	 * Add extra headers to the request before sending
	 *
	 * @since 1.6
	 * @param string $out HTTP header string
	 */
	public function fsockopen_header(&$out) {
		$out .= sprintf("Proxy-Authorization: Basic %s\r\n", base64_encode($this->get_auth_string()));
	}

	// refactored. public function get_auth_string() {}
}