<?php
/**
 * Requests for PHP
 *
 * Inspired by Requests for Python.
 *
 * Based on concepts from SimplePie_File, RequestCore and WP_Http.
 *
 * @package Requests
 */

/**
 * Requests for PHP
 *
 * Inspired by Requests for Python.
 *
 * Based on concepts from SimplePie_File, RequestCore and WP_Http.
 *
 * @package Requests
 */
class Requests {
	// refactored. const POST = 'POST';
	// :
	// refactored. private function __construct() {}

	/**
	 * Autoloader for Requests
	 *
	 * Register this with {@see register_autoloader()} if you'd like to avoid
	 * having to create your own.
	 *
	 * (You can also use `spl_autoload_register` directly if you'd prefer.)
	 *
	 * @codeCoverageIgnore
	 *
	 * @param string $class Class name to load
	 */
	public static function autoloader($class) {
		// Check that the class starts with "Requests"
		if (strpos($class, 'Requests') !== 0) {
			return;
		}

		$file = str_replace('_', '/', $class);
		if (file_exists(dirname(__FILE__) . '/' . $file . '.php')) {
			require_once(dirname(__FILE__) . '/' . $file . '.php');
		}
	}

	// refactored. public static function register_autoloader() {}

	/**
	 * Register a transport
	 *
	 * @param string $transport Transport class to add, must support the Requests_Transport interface
	 */
	public static function add_transport($transport) {
		if (empty(self::$transports)) {
			self::$transports = array(
				'Requests_Transport_cURL',
				'Requests_Transport_fsockopen',
			);
		}

		self::$transports = array_merge(self::$transports, array($transport));
	}

	// refactored. protected static function get_transport($capabilities = array()) {}

	/**#@+
	 * @see request()
	 * @param string $url
	 * @param array $headers
	 * @param array $options
	 * @return Requests_Response
	 */
	/**
	 * Send a GET request
	 */
	public static function get($url, $headers = array(), $options = array()) {
		return self::request($url, $headers, null, self::GET, $options);
	}

	/**
	 * Send a HEAD request
	 */
	public static function head($url, $headers = array(), $options = array()) {
		return self::request($url, $headers, null, self::HEAD, $options);
	}

	/**
	 * Send a DELETE request
	 */
	public static function delete($url, $headers = array(), $options = array()) {
		return self::request($url, $headers, null, self::DELETE, $options);
	}

	/**
	 * Send a TRACE request
	 */
	public static function trace($url, $headers = array(), $options = array()) {
		return self::request($url, $headers, null, self::TRACE, $options);
	}
	/**#@-*/

	/**#@+
	 * @see request()
	 * @param string $url
	 * @param array $headers
	 * @param array $data
	 * @param array $options
	 * @return Requests_Response
	 */
	/**
	 * Send a POST request
	 */
	public static function post($url, $headers = array(), $data = array(), $options = array()) {
		return self::request($url, $headers, $data, self::POST, $options);
	}
	/**
	 * Send a PUT request
	 */
	public static function put($url, $headers = array(), $data = array(), $options = array()) {
		return self::request($url, $headers, $data, self::PUT, $options);
	}

	/**
	 * Send an OPTIONS request
	 */
	public static function options($url, $headers = array(), $data = array(), $options = array()) {
		return self::request($url, $headers, $data, self::OPTIONS, $options);
	}

	/**
	 * Send a PATCH request
	 *
	 * Note: Unlike {@see post} and {@see put}, `$headers` is required, as the
	 * specification recommends that should send an ETag
	 *
	 * @link https://tools.ietf.org/html/rfc5789
	 */
	public static function patch($url, $headers, $data = array(), $options = array()) {
		return self::request($url, $headers, $data, self::PATCH, $options);
	}
	/**#@-*/

	// refactored. public static function request($url, $headers = array(), $data = array(), $type = self::GET, $options = array()) {}

	/**
	 * Send multiple HTTP requests simultaneously
	 *
	 * The `$requests` parameter takes an associative or indexed array of
	 * request fields. The key of each request can be used to match up the
	 * request with the returned data, or with the request passed into your
	 * `multiple.request.complete` callback.
	 *
	 * The request fields value is an associative array with the following keys:
	 *
	 * - `url`: Request URL Same as the `$url` parameter to
	 *    {@see Requests::request}
	 *    (string, required)
	 * - `headers`: Associative array of header fields. Same as the `$headers`
	 *    parameter to {@see Requests::request}
	 *    (array, default: `array()`)
	 * - `data`: Associative array of data fields or a string. Same as the
	 *    `$data` parameter to {@see Requests::request}
	 *    (array|string, default: `array()`)
	 * - `type`: HTTP request type (use Requests constants). Same as the `$type`
	 *    parameter to {@see Requests::request}
	 *    (string, default: `Requests::GET`)
	 * - `cookies`: Associative array of cookie name to value, or cookie jar.
	 *    (array|Requests_Cookie_Jar)
	 *
	 * If the `$options` parameter is specified, individual requests will
	 * inherit options from it. This can be used to use a single hooking system,
	 * or set all the types to `Requests::POST`, for example.
	 *
	 * In addition, the `$options` parameter takes the following global options:
	 *
	 * - `complete`: A callback for when a request is complete. Takes two
	 *    parameters, a Requests_Response/Requests_Exception reference, and the
	 *    ID from the request array (Note: this can also be overridden on a
	 *    per-request basis, although that's a little silly)
	 *    (callback)
	 *
	 * @param array $requests Requests data (see description for more information)
	 * @param array $options Global and default options (see {@see Requests::request})
	 * @return array Responses (either Requests_Response or a Requests_Exception object)
	 */
	public static function request_multiple($requests, $options = array()) {
		$options = array_merge(self::get_default_options(true), $options);

		if (!empty($options['hooks'])) {
			$options['hooks']->register('transport.internal.parse_response', array('Requests', 'parse_multiple'));
			if (!empty($options['complete'])) {
				$options['hooks']->register('multiple.request.complete', $options['complete']);
			}
		}

		foreach ($requests as $id => &$request) {
			if (!isset($request['headers'])) {
				$request['headers'] = array();
			}
			if (!isset($request['data'])) {
				$request['data'] = array();
			}
			if (!isset($request['type'])) {
				$request['type'] = self::GET;
			}
			if (!isset($request['options'])) {
				$request['options'] = $options;
				$request['options']['type'] = $request['type'];
			}
			else {
				if (empty($request['options']['type'])) {
					$request['options']['type'] = $request['type'];
				}
				$request['options'] = array_merge($options, $request['options']);
			}

			self::set_defaults($request['url'], $request['headers'], $request['data'], $request['type'], $request['options']);

			// Ensure we only hook in once
			if ($request['options']['hooks'] !== $options['hooks']) {
				$request['options']['hooks']->register('transport.internal.parse_response', array('Requests', 'parse_multiple'));
				if (!empty($request['options']['complete'])) {
					$request['options']['hooks']->register('multiple.request.complete', $request['options']['complete']);
				}
			}
		}
		unset($request);

		if (!empty($options['transport'])) {
			$transport = $options['transport'];

			if (is_string($options['transport'])) {
				$transport = new $transport();
			}
		}
		else {
			$transport = self::get_transport();
		}
		$responses = $transport->request_multiple($requests, $options);

		foreach ($responses as $id => &$response) {
			// If our hook got messed with somehow, ensure we end up with the
			// correct response
			if (is_string($response)) {
				$request = $requests[$id];
				self::parse_multiple($response, $request);
				$request['options']['hooks']->dispatch('multiple.request.complete', array(&$response, $id));
			}
		}

		return $responses;
	}

	// refactored. protected static function get_default_options($multirequest = false) {}
	// :
	// refactored. protected static function set_defaults(&$url, &$headers, &$data, &$type, &$options) {}

	/**
	 * HTTP response parser
	 *
	 * @throws Requests_Exception On missing head/body separator (`requests.no_crlf_separator`)
	 * @throws Requests_Exception On missing head/body separator (`noversion`)
	 * @throws Requests_Exception On missing head/body separator (`toomanyredirects`)
	 *
	 * @param string $headers Full response text including headers and body
	 * @param string $url Original request URL
	 * @param array $req_headers Original $headers array passed to {@link request()}, in case we need to follow redirects
	 * @param array $req_data Original $data array passed to {@link request()}, in case we need to follow redirects
	 * @param array $options Original $options array passed to {@link request()}, in case we need to follow redirects
	 * @return Requests_Response
	 */
	protected static function parse_response($headers, $url, $req_headers, $req_data, $options) {
		$return = new Requests_Response();
		if (!$options['blocking']) {
			return $return;
		}

		$return->raw = $headers;
		$return->url = $url;

		if (!$options['filename']) {
			if (($pos = strpos($headers, "\r\n\r\n")) === false) {
				// Crap!
				throw new Requests_Exception('Missing header/body separator', 'requests.no_crlf_separator');
			}

			$headers = substr($return->raw, 0, $pos);
			$return->body = substr($return->raw, $pos + strlen("\n\r\n\r"));
		}
		else {
			$return->body = '';
		}
		// Pretend CRLF = LF for compatibility (RFC 2616, section 19.3)
		$headers = str_replace("\r\n", "\n", $headers);
		// Unfold headers (replace [CRLF] 1*( SP | HT ) with SP) as per RFC 2616 (section 2.2)
		$headers = preg_replace('/\n[ \t]/', ' ', $headers);
		$headers = explode("\n", $headers);
		preg_match('#^HTTP/(1\.\d)[ \t]+(\d+)#i', array_shift($headers), $matches);
		if (empty($matches)) {
			throw new Requests_Exception('Response could not be parsed', 'noversion', $headers);
		}
		$return->protocol_version = (float) $matches[1];
		$return->status_code = (int) $matches[2];
		if ($return->status_code >= 200 && $return->status_code < 300) {
			$return->success = true;
		}

		foreach ($headers as $header) {
			list($key, $value) = explode(':', $header, 2);
			$value = trim($value);
			preg_replace('#(\s+)#i', ' ', $value);
			$return->headers[$key] = $value;
		}
		if (isset($return->headers['transfer-encoding'])) {
			$return->body = self::decode_chunked($return->body);
			unset($return->headers['transfer-encoding']);
		}
		if (isset($return->headers['content-encoding'])) {
			$return->body = self::decompress($return->body);
		}

		//fsockopen and cURL compatibility
		if (isset($return->headers['connection'])) {
			unset($return->headers['connection']);
		}

		$options['hooks']->dispatch('requests.before_redirect_check', array(&$return, $req_headers, $req_data, $options));

		if ($return->is_redirect() && $options['follow_redirects'] === true) {
			if (isset($return->headers['location']) && $options['redirected'] < $options['redirects']) {
				if ($return->status_code === 303) {
					$options['type'] = self::GET;
				}
				$options['redirected']++;
				$location = $return->headers['location'];
				if (strpos($location, 'http://') !== 0 && strpos($location, 'https://') !== 0) {
					// relative redirect, for compatibility make it absolute
					$location = Requests_IRI::absolutize($url, $location);
					$location = $location->uri;
				}

				$hook_args = array(
					&$location,
					&$req_headers,
					&$req_data,
					&$options,
					$return
				);
				$options['hooks']->dispatch('requests.before_redirect', $hook_args);
				$redirected = self::request($location, $req_headers, $req_data, $options['type'], $options);
				$redirected->history[] = $return;
				return $redirected;
			}
			elseif ($options['redirected'] >= $options['redirects']) {
				throw new Requests_Exception('Too many redirects', 'toomanyredirects', $return);
			}
		}

		$return->redirects = $options['redirected'];

		$options['hooks']->dispatch('requests.after_request', array(&$return, $req_headers, $req_data, $options));
		return $return;
	}

	/**
	 * Callback for `transport.internal.parse_response`
	 *
	 * Internal use only. Converts a raw HTTP response to a Requests_Response
	 * while still executing a multiple request.
	 *
	 * @param string $response Full response text including headers and body (will be overwritten with Response instance)
	 * @param array $request Request data as passed into {@see Requests::request_multiple()}
	 * @return null `$response` is either set to a Requests_Response instance, or a Requests_Exception object
	 */
	public static function parse_multiple(&$response, $request) {
		try {
			$url = $request['url'];
			$headers = $request['headers'];
			$data = $request['data'];
			$options = $request['options'];
			$response = self::parse_response($response, $url, $headers, $data, $options);
		}
		catch (Requests_Exception $e) {
			$response = $e;
		}
	}

	// refactored. protected static function decode_chunked($data) {}
	// refactored. public static function flatten($array) {}

	/**
	 * Convert a key => value array to a 'key: value' array for headers
	 *
	 * @codeCoverageIgnore
	 * @deprecated Misspelling of {@see Requests::flatten}
	 * @param array $array Dictionary of header values
	 * @return array List of headers
	 */
	public static function flattern($array) {
		return self::flatten($array);
	}

	/**
	 * Decompress an encoded body
	 *
	 * Implements gzip, compress and deflate. Guesses which it is by attempting
	 * to decode.
	 *
	 * @param string $data Compressed data in one of the above formats
	 * @return string Decompressed string
	 */
	public static function decompress($data) {
		if (substr($data, 0, 2) !== "\x1f\x8b" && substr($data, 0, 2) !== "\x78\x9c") {
			// Not actually compressed. Probably cURL ruining this for us.
			return $data;
		}

		if (function_exists('gzdecode') && ($decoded = @gzdecode($data)) !== false) {
			return $decoded;
		}
		elseif (function_exists('gzinflate') && ($decoded = @gzinflate($data)) !== false) {
			return $decoded;
		}
		elseif (($decoded = self::compatible_gzinflate($data)) !== false) {
			return $decoded;
		}
		elseif (function_exists('gzuncompress') && ($decoded = @gzuncompress($data)) !== false) {
			return $decoded;
		}

		return $data;
	}

	// refactored. public static function compatible_gzinflate($gzData) {}

	public static function match_domain($host, $reference) {
		// Check for a direct match
		if ($host === $reference) {
			return true;
		}

		// Calculate the valid wildcard match if the host is not an IP address
		// Also validates that the host has 3 parts or more, as per Firefox's
		// ruleset.
		$parts = explode('.', $host);
		if (ip2long($host) === false && count($parts) >= 3) {
			$parts[0] = '*';
			$wildcard = implode('.', $parts);
			if ($wildcard === $reference) {
				return true;
			}
		}

		return false;
	}
}
