<?php
/**
 * cURL HTTP transport
 *
 * @package    Requests
 * @subpackage Transport
 */

/**
 * cURL HTTP transport.
 *
 * @package    Requests
 * @subpackage Transport
 */
class Requests_Transport_cURL implements Requests_Transport
{
	const CURL_7_10_5 = 0x070A05;
	const CURL_7_16_2 = 0x071002;

	/**
	 * Raw HTTP data.
	 *
	 * @var string
	 */
	public $headers = '';

	/**
	 * Raw body data.
	 *
	 * @var string
	 */
	public $response_data = '';

	/**
	 * Information on the current request.
	 *
	 * @var array cURL information array, see {@see https://secure.php.net/curl_getinfo}
	 */
	public $info;

	/**
	 * Version string.
	 *
	 * @var long
	 */
	public $version;

	/**
	 * cURL handle.
	 *
	 * @var resource
	 */
	protected $handle;

	/**
	 * Hook dispatcher instance.
	 *
	 * @var Requests_Hooks
	 */
	protected $hooks;

	/**
	 * Have we finished the headers yet?
	 *
	 * @var bool
	 */
	protected $done_headers = FALSE;

	/**
	 * If streaming to a file, keep the file pointer.
	 *
	 * @var resource
	 */
	protected $stream_handle;

	/**
	 * How many bytes are in the response body?
	 *
	 * @var int
	 */
	protected $response_bytes;

	/**
	 * What's the maximum number of bytes we should keep?
	 *
	 * @var int|bool Byte count, or false if no limit.
	 */
	protected $response_byte_limit;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$curl = curl_version();
		$this->version = $curl['version_number'];
		$this->handle = curl_init();
		curl_setopt( $this->handle, CURLOPT_HEADER, FALSE );
		curl_setopt( $this->handle, CURLOPT_RETURNTRANSFER, 1 );

		if ( $this->version >= self::CURL_7_10_5 ) {
			curl_setopt( $this->handle, CURLOPT_ENCODING, '' );
		}

		if ( defined( 'CURLOPT_PROTOCOLS' ) ) {
			curl_setopt( $this->handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS );
		}

		if ( defined( 'CURLOPT_REDIR_PROTOCOLS' ) ) {
			curl_setopt( $this->handle, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS );
		}
	}

	/**
	 * Destructor.
	 */
	public function __destruct()
	{
		if ( is_resource( $this->handle ) ) {
			curl_close( $this->handle );
		}
	}
}
