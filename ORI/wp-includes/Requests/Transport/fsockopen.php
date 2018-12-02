<?php
/**
 * fsockopen HTTP transport
 *
 * @package Requests
 * @subpackage Transport
 */

/**
 * fsockopen HTTP transport
 *
 * @package Requests
 * @subpackage Transport
 */
class Requests_Transport_fsockopen implements Requests_Transport {
	// refactored. const SECOND_IN_MICROSECONDS = 1000000;
	// :
	// refactored. public function request($url, $headers = array(), $data = array(), $options = array()) {}

	/**
	 * Send multiple requests simultaneously
	 *
	 * @param array $requests Request data (array of 'url', 'headers', 'data', 'options') as per {@see Requests_Transport::request}
	 * @param array $options Global options, see {@see Requests::response()} for documentation
	 * @return array Array of Requests_Response objects (may contain Requests_Exception or string responses as well)
	 */
	public function request_multiple($requests, $options) {
		$responses = array();
		$class = get_class($this);
		foreach ($requests as $id => $request) {
			try {
				$handler = new $class();
				$responses[$id] = $handler->request($request['url'], $request['headers'], $request['data'], $request['options']);

				$request['options']['hooks']->dispatch('transport.internal.parse_response', array(&$responses[$id], $request));
			}
			catch (Requests_Exception $e) {
				$responses[$id] = $e;
			}

			if (!is_string($responses[$id])) {
				$request['options']['hooks']->dispatch('multiple.request.complete', array(&$responses[$id], $id));
			}
		}

		return $responses;
	}

	// refactored. protected static function accept_encoding() {}
	// :
	// refactored. public static function test($capabilities = array()) {}
}
