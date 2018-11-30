<?php
/**
 * Base HTTP transport
 *
 * @package Requests
 * @subpackage Transport
 */

/**
 * Base HTTP transport
 *
 * @package Requests
 * @subpackage Transport
 */
interface Requests_Transport {
	// refactored. public function request($url, $headers = array(), $data = array(), $options = array());

	/**
	 * Send multiple requests simultaneously
	 *
	 * @param array $requests Request data (array of 'url', 'headers', 'data', 'options') as per {@see Requests_Transport::request}
	 * @param array $options Global options, see {@see Requests::response()} for documentation
	 * @return array Array of Requests_Response objects (may contain Requests_Exception or string responses as well)
	 */
	public function request_multiple($requests, $options);

	// refactored. public static function test();
}