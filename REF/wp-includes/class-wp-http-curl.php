<?php
/**
 * HTTP API: WP_Http_Curl class
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      4.4.0
 */

/**
 * Core class used to integrate Curl as an HTTP transport.
 *
 * HTTP request method uses Curl extension to retrieve the url.
 *
 * Requires the Curl extension to be installed.
 *
 * @since 2.7.0
 */
class WP_Http_Curl
{
	/**
	 * Temporary header storage for during requests.
	 *
	 * @since 3.2.0
	 *
	 * @var string
	 */
	private $headers = '';

	/**
	 * Temporary body storage for during requests.
	 *
	 * @since 3.6.0
	 *
	 * @var string
	 */
	private $body = '';

	/**
	 * The maximum amount of data to receive from the remote server.
	 *
	 * @since 3.6.0
	 *
	 * @var int
	 */
	private $max_body_length = FALSE;

	/**
	 * The file resource used for streaming to file.
	 *
	 * @since 3.6.0
	 *
	 * @var resource
	 */
	private $stream_handle = FALSE;

	/**
	 * The total bytes written in the current request.
	 *
	 * @since 4.1.0
	 *
	 * @var int
	 */
	private $bytes_written_total = 0;

	/**
	 * Determines whether this class can be used for retrieving a URL.
	 *
	 * @static
	 * @since  2.7.0
	 *
	 * @param  array $args Optional.
	 *                     Array of request arguments.
	 *                     Default empty array.
	 * @return bool  False means this class can not be used, true means it can.
	 */
	public static function test( $args = array() )
	{
		if ( ! function_exists( 'curl_init' ) || ! function_exists( 'curl_exec' ) ) {
			return FALSE;
		}

		$is_ssl = isset( $args['ssl'] ) && $args['ssl'];

		if ( $is_ssl ) {
			$curl_version = curl_version();

			// Check whether this cURL version support SSL requests.
			if ( ! ( CURL_VERSION_SSL & $curl_version['features'] ) ) {
				return FALSE;
			}
		}

		/**
		 * Filters whether cURL can be used as a transport for retrieving a URL.
		 *
		 * @since 2.7.0
		 *
		 * @param bool  $use_class Whether the class can be used.
		 *                         Default true.
		 * @param array $args      An array of request arguments.
		 */
		return apply_filters( 'use_curl_transport', TRUE, $args );
	}
}
