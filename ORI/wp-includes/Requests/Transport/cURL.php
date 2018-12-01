<?php
/**
 * cURL HTTP transport
 *
 * @package Requests
 * @subpackage Transport
 */

/**
 * cURL HTTP transport
 *
 * @package Requests
 * @subpackage Transport
 */
class Requests_Transport_cURL implements Requests_Transport {
	// refactored. const CURL_7_10_5 = 0x070A05;
	// :
	// refactored. public function request($url, $headers = array(), $data = array(), $options = array()) {}

	/**
	 * Send multiple requests simultaneously
	 *
	 * @param array $requests Request data
	 * @param array $options Global options
	 * @return array Array of Requests_Response objects (may contain Requests_Exception or string responses as well)
	 */
	public function request_multiple($requests, $options) {
		// If you're not requesting, we can't get any responses ¯\_(ツ)_/¯
		if (empty($requests)) {
			return array();
		}

		$multihandle = curl_multi_init();
		$subrequests = array();
		$subhandles = array();

		$class = get_class($this);
		foreach ($requests as $id => $request) {
			$subrequests[$id] = new $class();
			$subhandles[$id] = $subrequests[$id]->get_subrequest_handle($request['url'], $request['headers'], $request['data'], $request['options']);
			$request['options']['hooks']->dispatch('curl.before_multi_add', array(&$subhandles[$id]));
			curl_multi_add_handle($multihandle, $subhandles[$id]);
		}

		$completed = 0;
		$responses = array();

		$request['options']['hooks']->dispatch('curl.before_multi_exec', array(&$multihandle));

		do {
			$active = false;

			do {
				$status = curl_multi_exec($multihandle, $active);
			}
			while ($status === CURLM_CALL_MULTI_PERFORM);

			$to_process = array();

			// Read the information as needed
			while ($done = curl_multi_info_read($multihandle)) {
				$key = array_search($done['handle'], $subhandles, true);
				if (!isset($to_process[$key])) {
					$to_process[$key] = $done;
				}
			}

			// Parse the finished requests before we start getting the new ones
			foreach ($to_process as $key => $done) {
				$options = $requests[$key]['options'];
				if (CURLE_OK !== $done['result']) {
					//get error string for handle.
					$reason = curl_error($done['handle']);
					$exception = new Requests_Exception_Transport_cURL(
									$reason,
									Requests_Exception_Transport_cURL::EASY,
									$done['handle'],
									$done['result']
								);
					$responses[$key] = $exception;
					$options['hooks']->dispatch('transport.internal.parse_error', array(&$responses[$key], $requests[$key]));
				}
				else {
					$responses[$key] = $subrequests[$key]->process_response($subrequests[$key]->response_data, $options);

					$options['hooks']->dispatch('transport.internal.parse_response', array(&$responses[$key], $requests[$key]));
				}

				curl_multi_remove_handle($multihandle, $done['handle']);
				curl_close($done['handle']);

				if (!is_string($responses[$key])) {
					$options['hooks']->dispatch('multiple.request.complete', array(&$responses[$key], $key));
				}
				$completed++;
			}
		}
		while ($active || $completed < count($subrequests));

		$request['options']['hooks']->dispatch('curl.after_multi_exec', array(&$multihandle));

		curl_multi_close($multihandle);

		return $responses;
	}

	/**
	 * Get the cURL handle for use in a multi-request
	 *
	 * @param string $url URL to request
	 * @param array $headers Associative array of request headers
	 * @param string|array $data Data to send either as the POST body, or as parameters in the URL for a GET/HEAD
	 * @param array $options Request options, see {@see Requests::response()} for documentation
	 * @return resource Subrequest's cURL handle
	 */
	public function &get_subrequest_handle($url, $headers, $data, $options) {
		$this->setup_handle($url, $headers, $data, $options);

		if ($options['filename'] !== false) {
			$this->stream_handle = fopen($options['filename'], 'wb');
		}

		$this->response_data = '';
		$this->response_bytes = 0;
		$this->response_byte_limit = false;
		if ($options['max_bytes'] !== false) {
			$this->response_byte_limit = $options['max_bytes'];
		}
		$this->hooks = $options['hooks'];

		return $this->handle;
	}

	// refactored. protected function setup_handle($url, $headers, $data, $options) {}
	// :
	// refactored. public static function test($capabilities = array()) {}
}
