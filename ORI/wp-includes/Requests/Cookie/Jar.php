<?php
/**
 * Cookie holder object
 *
 * @package Requests
 * @subpackage Cookies
 */

/**
 * Cookie holder object
 *
 * @package Requests
 * @subpackage Cookies
 */
class Requests_Cookie_Jar implements ArrayAccess, IteratorAggregate {
	// refactored. protected $cookies = array();
	// :
	// refactored. public function normalize_cookie($cookie, $key = null) {}

	/**
	 * Normalise cookie data into a Requests_Cookie
	 *
	 * @codeCoverageIgnore
	 * @deprecated Use {@see Requests_Cookie_Jar::normalize_cookie}
	 * @return Requests_Cookie
	 */
	public function normalizeCookie($cookie, $key = null) {
		return $this->normalize_cookie($cookie, $key);
	}

	/**
	 * Check if the given item exists
	 *
	 * @param string $key Item key
	 * @return boolean Does the item exist?
	 */
	public function offsetExists($key) {
		return isset($this->cookies[$key]);
	}

	/**
	 * Get the value for the item
	 *
	 * @param string $key Item key
	 * @return string Item value
	 */
	public function offsetGet($key) {
		if (!isset($this->cookies[$key])) {
			return null;
		}

		return $this->cookies[$key];
	}

	/**
	 * Set the given item
	 *
	 * @throws Requests_Exception On attempting to use dictionary as list (`invalidset`)
	 *
	 * @param string $key Item name
	 * @param string $value Item value
	 */
	public function offsetSet($key, $value) {
		if ($key === null) {
			throw new Requests_Exception('Object is a dictionary, not a list', 'invalidset');
		}

		$this->cookies[$key] = $value;
	}

	/**
	 * Unset the given header
	 *
	 * @param string $key
	 */
	public function offsetUnset($key) {
		unset($this->cookies[$key]);
	}

	/**
	 * Get an iterator for the data
	 *
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator($this->cookies);
	}

	// refactored. public function register(Requests_Hooker $hooks) {}
	// refactored. public function before_request($url, &$headers, &$data, &$type, &$options) {}

	/**
	 * Parse all cookies from a response and attach them to the response
	 *
	 * @var Requests_Response $response
	 */
	public function before_redirect_check(Requests_Response &$return) {
		$url = $return->url;
		if (!$url instanceof Requests_IRI) {
			$url = new Requests_IRI($url);
		}

		$cookies = Requests_Cookie::parse_from_headers($return->headers, $url);
		$this->cookies = array_merge($this->cookies, $cookies);
		$return->cookies = $this;
	}
}