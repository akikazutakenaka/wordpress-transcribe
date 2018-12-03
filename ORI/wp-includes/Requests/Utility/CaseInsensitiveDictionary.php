<?php
/**
 * Case-insensitive dictionary, suitable for HTTP headers
 *
 * @package Requests
 * @subpackage Utilities
 */

/**
 * Case-insensitive dictionary, suitable for HTTP headers
 *
 * @package Requests
 * @subpackage Utilities
 */
class Requests_Utility_CaseInsensitiveDictionary implements ArrayAccess, IteratorAggregate {
	// refactored. protected $data = array();
	// refactored. public function __construct(array $data = array()) {}

	/**
	 * Check if the given item exists
	 *
	 * @param string $key Item key
	 * @return boolean Does the item exist?
	 */
	public function offsetExists($key) {
		$key = strtolower($key);
		return isset($this->data[$key]);
	}

	/**
	 * Get the value for the item
	 *
	 * @param string $key Item key
	 * @return string Item value
	 */
	public function offsetGet($key) {
		$key = strtolower($key);
		if (!isset($this->data[$key])) {
			return null;
		}

		return $this->data[$key];
	}

	// refactored. public function offsetSet($key, $value) {}

	/**
	 * Unset the given header
	 *
	 * @param string $key
	 */
	public function offsetUnset($key) {
		unset($this->data[strtolower($key)]);
	}

	/**
	 * Get an iterator for the data
	 *
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator($this->data);
	}

	// refactored. public function getAll() {}
}
