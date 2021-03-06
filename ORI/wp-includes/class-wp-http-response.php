<?php
/**
 * HTTP API: WP_HTTP_Response class
 *
 * @package WordPress
 * @subpackage HTTP
 * @since 4.4.0
 */

/**
 * Core class used to prepare HTTP responses.
 *
 * @since 4.4.0
 */
class WP_HTTP_Response {
	// refactored. public $data;
	// :
	// refactored. public function __construct( $data = null, $status = 200, $headers = array() ) {}

	/**
	 * Retrieves headers associated with the response.
	 *
	 * @since 4.4.0
	 *
	 * @return array Map of header name to header value.
	 */
	public function get_headers() {
		return $this->headers;
	}

	// refactored. public function set_headers( $headers ) {}

	/**
	 * Sets a single HTTP header.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key     Header name.
	 * @param string $value   Header value.
	 * @param bool   $replace Optional. Whether to replace an existing header of the same name.
	 *                        Default true.
	 */
	public function header( $key, $value, $replace = true ) {
		if ( $replace || ! isset( $this->headers[ $key ] ) ) {
			$this->headers[ $key ] = $value;
		} else {
			$this->headers[ $key ] .= ', ' . $value;
		}
	}

	/**
	 * Retrieves the HTTP return code for the response.
	 *
	 * @since 4.4.0
	 *
	 * @return int The 3-digit HTTP status code.
	 */
	public function get_status() {
		return $this->status;
	}

	// refactored. public function set_status( $code ) {}

	/**
	 * Retrieves the response data.
	 *
	 * @since 4.4.0
	 *
	 * @return mixed Response data.
	 */
	public function get_data() {
		return $this->data;
	}

	// refactored. public function set_data( $data ) {}

	/**
	 * Retrieves the response data for JSON serialization.
	 *
	 * It is expected that in most implementations, this will return the same as get_data(),
	 * however this may be different if you want to do custom JSON data handling.
	 *
	 * @since 4.4.0
	 *
	 * @return mixed Any JSON-serializable value.
	 */
	public function jsonSerialize() {
		return $this->get_data();
	}
}
