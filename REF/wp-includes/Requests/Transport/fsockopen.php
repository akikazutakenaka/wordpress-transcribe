<?php
/**
 * fsockopen HTTP transport
 *
 * @package    Requests
 * @subpackage Transport
 */

/**
 * fsockopen HTTP transport.
 *
 * @package    Requests
 * @subpackage Transport
 */
class Requests_Transport_fsockopen implements Requests_Transport
{
	/**
	 * Second to microsecond conversion.
	 *
	 * @var int
	 */
	const SECOND_IN_MICROSECONDS = 1000000;

	/**
	 * Raw HTTP data.
	 *
	 * @var string
	 */
	public $headers = '';

	/**
	 * Stream metadata.
	 *
	 * @var array Associative array of properties, see {@see https://secure.php.net/stream_get_meta_data}
	 */
	public $info;

	/**
	 * What's the maximum number of bytes we should keep?
	 *
	 * @var int|bool Byte count, or false if no limit.
	 */
	protected $max_bytes = FALSE;

	protected $connect_error = '';

	/**
	 * Whether this transport is valid.
	 *
	 * @return bool True if the transport is valid, false otherwise.
	 */
	public static function test( $capabilities = array() )
	{
		if ( ! function_exists( 'fsockopen' ) ) {
			return FALSE;
		}

		// If needed, check that streams support SSL.
		if ( isset( $capabilities['ssl'] ) && $capabilities['ssl'] ) {
			if ( ! extension_loaded( 'openssl' ) || ! function_exists( 'openssl_x509_parse' ) ) {
				return FALSE;
			}

			// Currently broken, thanks to https://github.com/facebook/hhvm/issues/2156
			if ( defined( 'HHVM_VERSION' ) ) {
				return FALSE;
			}
		}

		return TRUE;
	}
}
