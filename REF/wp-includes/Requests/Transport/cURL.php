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

	/**
	 * Perform a request.
	 *
	 * @throws Requests_Exception On a cURL error (`curlerror`)
	 *
	 * @param  string       $url     URL to request.
	 * @param  array        $headers Associative array of request headers.
	 * @param  string|array $data    Data to send either as the POST body, or as parameters in the URL for a GET/HEAD.
	 * @param  array        $options Request options, see {@see Requests::response()} for documentation.
	 * @return string       Raw HTTP result.
	 */
	public function request( $url, $headers = array(), $data = array(), $options = array() )
	{
/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/post.php: wp_check_post_hierarchy_for_loops( int $post_parent, int $post_ID )
 * <-......: wp-includes/post.php: wp_insert_post( array $postarr [, bool $wp_error = FALSE] )
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::get_page_templates( [WP_Post|null $post = NULL [, string $post_type = 'page']] )
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::get_post_templates()
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::translate_header( string $header, string $value )
 * <-......: wp-admin/includes/theme.php: get_theme_feature_list( [bool $api = TRUE] )
 * <-......: wp-admin/includes/theme.php: themes_api( string $action [, array|object $args = array()] )
 * <-......: wp-includes/class-http.php: WP_Http::request( string $url [, string|array $args = array()] )
 * <-......: wp-includes/class-requests.php: Requests::request( string $url [, array $headers = array() [, array|null $data = array() [, string $type = self::GET [, array $options = array()]]]] )
 * @NOW 014: wp-includes/Requests/Transport/cURL.php: Requests_Transport_cURL::request( string $url [, array $headers = array() [, string|array $data = array() [, array $options = array()]]] )
 * ......->: wp-includes/Requests/Transport.php: Requests_Transport::request( string $url [, array $headers = array() [, string|array $data = array() [, array $options = array()]]] )
 */
	}

	/**
	 * Whether this transport is valid.
	 *
	 * @return bool True if the transport is valid, false otherwise.
	 */
	public static function test( $capabilities = array() )
	{
		if ( ! function_exists( 'curl_init' ) || ! function_exists( 'curl_exec' ) ) {
			return FALSE;
		}

		// If needed, check that our installed curl version supports SSL.
		if ( isset( $capabilities['ssl'] ) && $capabilities['ssl'] ) {
			$curl_version = curl_version();

			if ( ! ( CURL_VERSION_SSL & $curl_version['features'] ) ) {
				return FALSE;
			}
		}

		return TRUE;
	}
}
