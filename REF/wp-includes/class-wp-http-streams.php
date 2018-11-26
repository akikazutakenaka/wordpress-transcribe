<?php
/**
 * HTTP API: WP_Http_Streams class
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      4.4.0
 */

/**
 * Core class used to integrate PHP Streams as an HTTP transport.
 *
 * @since 2.7.0
 * @since 3.7.0 Combined with the fsockopen transport and switched to `stream_socket_client()`.
 */
class WP_Http_Streams
{
	/**
	 * Determines whether this class can be used for retrieving a URL.
	 *
	 * @static
	 * @since  2.7.0
	 * @since  3.7.0 Combined with the fsockopen transport and switched to stream_socket_client().
	 *
	 * @param  array $args Optional.
	 *                     Array of request arguments.
	 *                     Default empty array.
	 * @return bool  False means this class can not be used, true means it can.
	 */
	public static function test( $args = array() )
	{
		if ( ! function_exists( 'stream_socket_client' ) ) {
			return FALSE;
		}

		$is_ssl = isset( $args['ssl'] ) && $args['ssl'];

		if ( $is_ssl ) {
			if ( ! extension_loaded( 'openssl' ) ) {
				return FALSE;
			}

			if ( ! function_exists( 'openssl_x509_parse' ) ) {
				return FALSE;
			}
		}

		/**
		 * Filters whether streams can be used as a transport for retrieving a URL.
		 *
		 * @since 2.7.0
		 *
		 * @param bool  $use_class Whether the class can be used.
		 *                         Default true.
		 * @param array $args      Request arguments.
		 */
		return apply_filters( 'use_streams_transport', TRUE, $args );
	}
}
