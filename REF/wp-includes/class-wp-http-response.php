<?php
/**
 * HTTP API: WP_HTTP_Response class
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      4.4.0
 */

/**
 * Core class used to prepare HTTP responses.
 *
 * @since 4.4.0
 */
class WP_HTTP_Response
{
	/**
	 * Response data.
	 *
	 * @since 4.4.0
	 *
	 * @var mixed
	 */
	public $data;

	/**
	 * Response headers.
	 *
	 * @since 4.4.0
	 *
	 * @var array
	 */
	public $headers;

	/**
	 * Response status.
	 *
	 * @since 4.4.0
	 *
	 * @var int
	 */
	public $status;

	/**
	 * Constructor.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $data    Response data.
	 *                       Default null.
	 * @param int   $status  Optional.
	 *                       HTTP status code.
	 *                       Default 200.
	 * @param array $headers Optional.
	 *                       HTTP header map.
	 *                       Default empty array.
	 */
	public function __construct( $data = NULL, $status = 200, $headers = array() )
	{
		$this->set_data( $data );
		$this->set_status( $status );
		$this->set_headers( $headers );
	}

	/**
	 * Sets all header values.
	 *
	 * @since 4.4.0
	 *
	 * @param array $headers Map of header name to header value.
	 */
	public function set_headers( $headers )
	{
		$this->headers = $headers;
	}

	/**
	 * Sets the 3-digit HTTP status code.
	 *
	 * @since 4.4.0
	 *
	 * @param int $code HTTP status.
	 */
	public function set_status( $code )
	{
		$this->status = absint( $code );
	}

	/**
	 * Sets the response data.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $data Response data.
	 */
	public function set_data( $data )
	{
		$this->data = $data;
	}
}
