<?php
/**
 * Cookie storage object
 *
 * @package Requests
 * @subpackage Cookies
 */

/**
 * Cookie storage object
 *
 * @package Requests
 * @subpackage Cookies
 */
class Requests_Cookie {
	// refactored. public $name;
	// :
	// refactored. public function is_expired() {}

	/**
	 * Check if a cookie is valid for a given URI
	 *
	 * @param Requests_IRI $uri URI to check
	 * @return boolean Whether the cookie is valid for the given URI
	 */
	public function uri_matches(Requests_IRI $uri) {
		if (!$this->domain_matches($uri->host)) {
			return false;
		}

		if (!$this->path_matches($uri->path)) {
			return false;
		}

		return empty($this->attributes['secure']) || $uri->scheme === 'https';
	}

	// refactored. public function domain_matches($string) {}

	/**
	 * Check if a cookie is valid for a given path
	 *
	 * From the path-match check in RFC 6265 section 5.1.4
	 *
	 * @param string $request_path Path to check
	 * @return boolean Whether the cookie is valid for the given path
	 */
	public function path_matches($request_path) {
		if (empty($request_path)) {
			// Normalize empty path to root
			$request_path = '/';
		}

		if (!isset($this->attributes['path'])) {
			// Cookies created manually; cookies created by Requests will set
			// the path to the requested path
			return true;
		}

		$cookie_path = $this->attributes['path'];

		if ($cookie_path === $request_path) {
			// The cookie-path and the request-path are identical.
			return true;
		}

		if (strlen($request_path) > strlen($cookie_path) && substr($request_path, 0, strlen($cookie_path)) === $cookie_path) {
			if (substr($cookie_path, -1) === '/') {
				// The cookie-path is a prefix of the request-path, and the last
				// character of the cookie-path is %x2F ("/").
				return true;
			}

			if (substr($request_path, strlen($cookie_path), 1) === '/') {
				// The cookie-path is a prefix of the request-path, and the
				// first character of the request-path that is not included in
				// the cookie-path is a %x2F ("/") character.
				return true;
			}
		}

		return false;
	}

	// refactored. public function normalize() {}
	// :
	// refactored. public function format_for_header() {}

	/**
	 * Format a cookie for a Cookie header
	 *
	 * @codeCoverageIgnore
	 * @deprecated Use {@see Requests_Cookie::format_for_header}
	 * @return string
	 */
	public function formatForHeader() {
		return $this->format_for_header();
	}

	/**
	 * Format a cookie for a Set-Cookie header
	 *
	 * This is used when sending cookies to clients. This isn't really
	 * applicable to client-side usage, but might be handy for debugging.
	 *
	 * @return string Cookie formatted for Set-Cookie header
	 */
	public function format_for_set_cookie() {
		$header_value = $this->format_for_header();
		if (!empty($this->attributes)) {
			$parts = array();
			foreach ($this->attributes as $key => $value) {
				// Ignore non-associative attributes
				if (is_numeric($key)) {
					$parts[] = $value;
				}
				else {
					$parts[] = sprintf('%s=%s', $key, $value);
				}
			}

			$header_value .= '; ' . implode('; ', $parts);
		}
		return $header_value;
	}

	/**
	 * Format a cookie for a Set-Cookie header
	 *
	 * @codeCoverageIgnore
	 * @deprecated Use {@see Requests_Cookie::format_for_set_cookie}
	 * @return string
	 */
	public function formatForSetCookie() {
		return $this->format_for_set_cookie();
	}

	/**
	 * Get the cookie value
	 *
	 * Attributes and other data can be accessed via methods.
	 */
	public function __toString() {
		return $this->value;
	}

	// refactored. public static function parse($string, $name = '', $reference_time = null) {}
	// refactored. public static function parse_from_headers(Requests_Response_Headers $headers, Requests_IRI $origin = null, $time = null) {}

	/**
	 * Parse all Set-Cookie headers from request headers
	 *
	 * @codeCoverageIgnore
	 * @deprecated Use {@see Requests_Cookie::parse_from_headers}
	 * @return string
	 */
	public static function parseFromHeaders(Requests_Response_Headers $headers) {
		return self::parse_from_headers($headers);
	}
}
