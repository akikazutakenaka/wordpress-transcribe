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
	// refactored. public function __construct($args = null) {}

	/**
	 * Register the necessary callbacks
	 *
	 * @since 1.6
	 * @see curl_before_send
	 * @see fsockopen_remote_socket
	 * @see fsockopen_remote_host_path
	 * @see fsockopen_header
	 * @param Requests_Hooks $hooks Hook system
	 */
	public function register(Requests_Hooks &$hooks) {
		$hooks->register('curl.before_send', array(&$this, 'curl_before_send'));

		$hooks->register('fsockopen.remote_socket', array(&$this, 'fsockopen_remote_socket'));
		$hooks->register('fsockopen.remote_host_path', array(&$this, 'fsockopen_remote_host_path'));
		if ($this->use_authentication) {
			$hooks->register('fsockopen.after_headers', array(&$this, 'fsockopen_header'));
		}
	}

	// refactored. public function curl_before_send(&$handle) {}
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