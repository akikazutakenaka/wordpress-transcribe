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
		$this->hooks = $options['hooks'];
		$this->setup_handle( $url, $headers, $data, $options );
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
 * ......->: wp-includes/Requests/Transport/cURL.php: Requests_Transport_cURL::setup_handle( string $url, array $headers, string|array $data, array $options )
 */
	}

	/**
	 * Setup the cURL handle for the given data.
	 *
	 * @param string       $url     URL to request.
	 * @param array        $headers Associative array of request headers.
	 * @param string|array $data    Data to send either as the POST body, or as parameters in the URL for a GET/HEAD.
	 * @param array        $options Request options, see {@see Requests::response()} for documentation.
	 */
	protected function setup_handle( $url, $headers, $data, $options )
	{
		$options['hooks']->dispatch( 'curl.before_request', array( &$this->handle ) );

		// Force closing the connection for old versions of cURL (<7.22).
		if ( ! isset( $headers['Connection'] ) ) {
			$headers['Connection'] = 'close';
		}

		$headers = Requests::flatten( $headers );

		if ( ! empty( $data ) ) {
			$data_format = $options['data_format'];

			if ( $data_format === 'query' ) {
				$url = self::format_get( $url, $data );
				$data = '';
			} elseif ( ! is_string( $data ) ) {
				$data = http_build_query( $data, NULL, '&' );
			}
		}

		switch ( $options['type'] ) {
			case Requests::POST:
				curl_setopt( $this->handle, CURLOPT_POST, TRUE );
				curl_setopt( $this->handle, CURLOPT_POSTFIELDS, $data );
				break;

			case Requests::HEAD:
				curl_setopt( $this->handle, CURLOPT_CUSTOMREQUEST, $options['type'] );
				curl_setopt( $this->handle, CURLOPT_NOBODY, TRUE );
				break;

			case Requests::TRACE:
				curl_setopt( $this->handle, CURLOPT_CUSTOMREQUEST, $options['type'] );
				break;

			case Requests::PATCH:
			case Requests::PUT:
			case Requests::DELETE:
			case Requests::OPTIONS:
			default:
				curl_setopt( $this->handle, CURLOPT_CUSTOMREQUEST, $options['type'] );

				if ( ! empty( $data ) ) {
					curl_setopt( $this->handle, CURLOPT_POSTFIELDS, $data );
				}
		}

		/**
		 * cURL requires a minimum timeout of 1 second when using the system DNS resolver, as it uses `alarm()`, which is second resolution only.
		 * There's no way to detect which DNS resolver is being used from our end, so we need to round up regardless of the supplied timeout.
		 *
		 * https://github.com/curl/curl/blob/4f45240bc84a9aa648c8f7243be7b79e9f9323a5/lib/hostip.c#L606-L609
		 */
		$timeout = max( $options['timeout'], 1 );

		if ( is_int( $timeout ) || $this->version < self::CURL_7_16_2 ) {
			curl_setopt( $this->handle, CURLOPT_TIMEOUT, ceil( $timeout ) );
		} else {
			curl_setopt( $this->handle, CURLOPT_TIMEOUT_MS, round( $timeout * 1000 ) );
		}

		if ( is_int( $options['connect_timeout'] ) || $this->version < self::CURL_7_16_2 ) {
			curl_setopt( $this->handle, CURLOPT_CONNECTTIMEOUT, ceil( $options['connect_timeout'] ) );
		} else {
			curl_setopt( $this->handle, CURLOPT_CONNECTTIMEOUT_MS, round( $options['connect_timeout'] * 1000 ) );
		}

		curl_setopt( $this->handle, CURLOPT_URL, $url );
		curl_setopt( $this->handle, CURLOPT_REFERER, $url );
		curl_setopt( $this->handle, CURLOPT_USERAGENT, $options['useragent'] );

		if ( ! empty( $headers ) ) {
			curl_setopt( $this->handle, CURLOPT_HTTPHEADER, $headers );
		}

		if ( $options['protocol_version'] === 1.1 ) {
			curl_setopt( $this->handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
		} else {
			curl_setopt( $this->handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0 );
		}

		if ( TRUE === $options['blocking'] ) {
			curl_setopt( $this->handle, CURLOPT_HEADERFUNCTION, array( &$this, 'stream_headers' ) );
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
 * <-......: wp-includes/Requests/Transport/cURL.php: Requests_Transport_cURL::request( string $url [, array $headers = array() [, string|array $data = array() [, array $options = array()]]] )
 * @NOW 015: wp-includes/Requests/Transport/cURL.php: Requests_Transport_cURL::setup_handle( string $url, array $headers, string|array $data, array $options )
 */
		}
	}

	/**
	 * Collect the headers as they are received.
	 *
	 * @param  resource $handle  cURL resource.
	 * @param  string   $headers Header string.
	 * @return int      Length of provided header.
	 */
	public function stream_headers( $handle, $headers )
	{
		/**
		 * Why do we do this?
		 * cURL will send both the final response and any interim responses, such as a 100 Continue.
		 * We don't need that.
		 * (We may want to keep this somewhere just in case)
		 */
		if ( $this->done_headers ) {
			$this->headers = '';
			$this->done_headers = FALSE;
		}

		$this->headers .= $headers;

		if ( $headers === "\r\n" ) {
			$this->done_headers = TRUE;
		}

		return strlen( $headers );
	}

	/**
	 * Format a URL given GET data.
	 *
	 * @param  string       $url
	 * @param  array|object $data Data to build query using, see {@see https://secure.php.net/http_build_query}.
	 * @return string       URL with data.
	 */
	protected static function format_get( $url, $data )
	{
		if ( ! empty( $data ) ) {
			$url_parts = parse_url( $url );

			if ( empty( $url_parts['query'] ) ) {
				$query = $url_parts['query'] = '';
			} else {
				$query = $url_parts['query'];
			}

			$query .= '&' . http_build_query( $data, NULL, '&' );
			$query = trim( $query, '&' );

			if ( empty( $url_parts['query'] ) ) {
				$url .= '?' . $query;
			} else {
				$url = str_replace( $url_parts['query'], $query, $url );
			}
		}

		return $url;
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
