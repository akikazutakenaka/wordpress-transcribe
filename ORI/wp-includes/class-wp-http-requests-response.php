<?php
/**
 * HTTP API: WP_HTTP_Requests_Response class
 *
 * @package WordPress
 * @subpackage HTTP
 * @since 4.6.0
 */

/**
 * Core wrapper object for a Requests_Response for standardisation.
 *
 * @since 4.6.0
 *
 * @see WP_HTTP_Response
 */
class WP_HTTP_Requests_Response extends WP_HTTP_Response {
	// refactored. protected $response;
	// refactored. protected $filename;
	// refactored. public function __construct( Requests_Response $response, $filename = '' ) {}

	/**
	 * Retrieves the response object for the request.
	 *
	 * @since 4.6.0
	 *
	 * @return Requests_Response HTTP response.
	 */
	public function get_response_object() {
		return $this->response;
	}

	// refactored. public function get_headers() {}

	/**
	 * Sets all header values.
	 *
	 * @since 4.6.0
	 *
	 * @param array $headers Map of header name to header value.
	 */
	public function set_headers( $headers ) {
		$this->response->headers = new Requests_Response_Headers( $headers );
	}

	/**
	 * Sets a single HTTP header.
	 *
	 * @since 4.6.0
	 *
	 * @param string $key     Header name.
	 * @param string $value   Header value.
	 * @param bool   $replace Optional. Whether to replace an existing header of the same name.
	 *                        Default true.
	 */
	public function header( $key, $value, $replace = true ) {
		if ( $replace ) {
			unset( $this->response->headers[ $key ] );
		}

		$this->response->headers[ $key ] = $value;
	}

	// refactored. public function get_status() {}

	/**
	 * Sets the 3-digit HTTP status code.
	 *
	 * @since 4.6.0
	 *
	 * @param int $code HTTP status.
	 */
	public function set_status( $code ) {
		$this->response->status_code = absint( $code );
	}

	// refactored. public function get_data() {}

	/**
	 * Sets the response data.
	 *
	 * @since 4.6.0
	 *
	 * @param mixed $data Response data.
	 */
	public function set_data( $data ) {
		$this->response->body = $data;
	}

	// refactored. public function get_cookies() {}
	// refactored. public function to_array() {}
}
