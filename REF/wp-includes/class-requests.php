<?php
/**
 * Requests for PHP
 *
 * Inspired by Requests for Python.
 *
 * Based on concepts from SimplePie_File, RequestCore and WP_Http.
 *
 * @package Requests
 */

/**
 * Requests for PHP
 *
 * Inspired by Requests for Python.
 *
 * Based on concepts from SimplePie_File, RequestCore and WP_Http.
 *
 * @package Requests
 */
class Requests
{
	/**
	 * POST method.
	 *
	 * @var string
	 */
	const POST = 'POST';

	/**
	 * PUT method.
	 *
	 * @var string
	 */
	const PUT = 'PUT';

	/**
	 * GET method.
	 *
	 * @var string
	 */
	const GET = 'GET';

	/**
	 * HEAD method.
	 *
	 * @var string
	 */
	const HEAD = 'HEAD';

	/**
	 * DELETE method.
	 *
	 * @var string
	 */
	const DELETE = 'DELETE';

	/**
	 * OPTIONS method.
	 *
	 * @var string
	 */
	const OPTIONS = 'OPTIONS';

	/**
	 * TRACE method.
	 *
	 * @var string
	 */
	const TRACE = 'TRACE';

	/**
	 * PATCH method.
	 *
	 * @link https://tools.ietf.org/html/rfc5789
	 *
	 * @var string
	 */
	const PATCH = 'PATCH';

	/**
	 * Default size of buffer size to read streams.
	 *
	 * @var integer
	 */
	const BUFFER_SIZE = 1160;

	/**
	 * Current version of Requests.
	 *
	 * @var string
	 */
	const VERSION = '1.7';

	/**
	 * Registered transport classes.
	 *
	 * @var array
	 */
	protected static $transports = array();

	/**
	 * Selected transport name.
	 *
	 * Use {@see get_transport()} instead.
	 *
	 * @var array
	 */
	public static $transport = array();

	/**
	 * Default certificate path.
	 *
	 * @see Requests::get_certificate_path()
	 * @see Requests::set_certificate_path()
	 *
	 * @var string
	 */
	protected static $certificate_path;

	/**
	 * This is a static class, do not instantiate it.
	 *
	 * @codeCoverageIgnore
	 */
	private function __construct()
	{}

	/**
	 * Register the built-in autoloader.
	 *
	 * @codeCoverageIgnore
	 */
	public static function register_autoloader()
	{
		spl_autoload_register( array( 'Requests', 'autoloader' ) );
	}

	/**
	 * Main interface for HTTP requests.
	 *
	 * This method initiates a request and sends it via a transport before parsing.
	 *
	 * The `$options` parameter takes an associative array with the following options:
	 *
	 * - `timeout`         : How long should we wait for a response?
	 *                       Note: for cURL, a minimum of 1 second applies, as DNS resolution operates at second-resolution only.
	 *                       (float, seconds with a millisecond precision, default: 10, example: 0.01)
	 * - `connect_timeout` : How long should we wait while trying to connect?
	 *                       (float, seconds with a millisecond precision, default: 10, example: 0.01)
	 * - `useragent`       : Useragent to send to the server
	 *                       (string, default: php-requests/$version)
	 * - `follow_redirects`: Should we follow 3xx redirects?
	 *                       (bool, default: true)
	 * - `redirects`       : How many times should we redirect before erroring?
	 *                       (int, default: 10)
	 * - `blocking`        : Should we block processing on this request?
	 *                       (bool, default: true)
	 * - `filename`        : File to stream the body to instead.
	 *                       (string|bool, default: false)
	 * - `auth`            : Authentication handler or array of user/password details to use for Basic authentication
	 *                       (Requests_Auth|array|bool, default: false)
	 * - `proxy`           : Proxy details to use for proxy by-passing and authentication
	 *                       (Requests_Proxy|array|string|bool, default: false)
	 * - `max_bytes`       : Limit for the response body size.
	 *                       (int|bool, default: false)
	 * - `idn`             : Enable IDN parsing
	 *                       (bool, default: true)
	 * - `transport`       : Custom transport.
	 *                       Either a class name, or a transport object.
	 *                       Defaults to the first working transport from {@see getTransport()}
	 *                       (string|Requests_Transport, default: {@see getTransport()})
	 * - `hooks`           : Hooks handler.
	 *                       (Requests_Hooker, default: new Requests_Hooks())
	 * - `verify`          : Should we verify SSL certificates?
	 *                       Allows passing in a custom certificate file as a string.
	 *                       (Using true uses the system-wide root certificate store instead, but this may have different behaviour across transports.)
	 *                       (string|bool, default: library/Requests/Transport/cacert.pem)
	 * - `verifyname`      : Should we verify the common name in the SSL certificate?
	 *                       (bool, default: true)
	 * - `data_format`     : How should we send the `$data` parameter?
	 *                       (string, one of 'query' or 'body', default: 'query' for HEAD/GET/DELETE, 'body' for POST/PUT/OPTIONS/PATCH)
	 *
	 * @throws Requests_Exception On invalid URLs (`nonhttp`)
	 *
	 * @param  string            $url     URL to request.
	 * @param  array             $headers Extra headers to send with the request.
	 * @param  array|null        $data    Data to send either as a query string for GET/HEAD requests, or in the body for POST requests.
	 * @param  string            $type    HTTP request type (use Requests constants).
	 * @param  array             $options Options for the request (see description for more information).
	 * @return Requests_Response
	 */
	public static function request( $url, $headers = array(), $data = array(), $type = self::GET, $options = array() )
	{
		if ( empty( $options['type'] ) ) {
			$options['type'] = $type;
		}

		$options = array_merge( self::get_default_options(), $options );
		self::set_defaults( $url, $headers, $data, $type, $options );
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
 * @NOW 013: wp-includes/class-requests.php: Requests::request( string $url [, array $headers = array() [, array|null $data = array() [, string $type = self::GET [, array $options = array()]]]] )
 * ......->: wp-includes/class-requests.php: Requests::set_defaults( &string $url, &array $headers, &array|null $data, &string $type, &array $options )
 */
	}

	/**
	 * Get the default options.
	 *
	 * @see Requests::request() for values returned by this method.
	 *
	 * @param  bool  $multirequest Is this a multirequest?
	 * @return array Default option values.
	 */
	protected static function get_default_options( $multirequest = FALSE )
	{
		$defaults = array(
			'timeout'            => 10,
			'connection_timeout' => 10,
			'useragent'          => 'php-requests/' . self::VERSION,
			'protocol_version'   => 1.1,
			'redirected'         => 0,
			'redirects'          => 10,
			'follow_redirects'   => TRUE,
			'blocking'           => TRUE,
			'type'               => self::GET,
			'filename'           => FALSE,
			'auth'               => FALSE,
			'proxy'              => FALSE,
			'cookies'            => FALSE,
			'max_bytes'          => FALSE,
			'idn'                => TRUE,
			'hooks'              => NULL,
			'transport'          => NULL,
			'verify'             => Requests::get_certificate_path(),
			'verifyname'         => TRUE
		);

		if ( $multirequest !== FALSE ) {
			$defaults['complete'] = NULL;
		}

		return $defaults;
	}

	/**
	 * Get default certificate path.
	 *
	 * @return string Default certificate path.
	 */
	public static function get_certificate_path()
	{
		return ! empty( Requests::$certificate_path )
			? Requests::$certificate_path
			: dirname( __FILE__ ) . '/Requests/Transport/cacert.pem';
	}

	/**
	 * Set default certificate path.
	 *
	 * @param string $path Certificate path, pointing to a PEM file.
	 */
	public static function set_certificate_path( $path )
	{
		Requests::$certificate_path = $path;
	}

	/**
	 * Set the default values.
	 *
	 * @param  string     $url     URL to request.
	 * @param  array      $headers Extra headers to send with the request.
	 * @param  array|null $data    Data to send either as a query string for GET/HEAD requests, or in the body for POST requests.
	 * @param  string     $type    HTTP request type.
	 * @param  array      $options Options for the request.
	 * @return array      $options
	 */
	protected static function set_defaults( &$url, &$headers, &$data, &$type, &$options )
	{
		if ( ! preg_match( '/^http(s)?:\/\//i', $url, $matches ) ) {
			throw new Requests_Exception( 'Only HTTP(S) requests are handled.', 'nonhttp', $url );
		}

		if ( empty( $options['hooks'] ) ) {
			$options['hooks'] = new Requests_Hooks();
		}

		if ( is_array( $options['auth'] ) ) {
			$options['auth'] = new Requests_Auth_Basic( $options['auth'] );
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
 * @NOW 014: wp-includes/class-requests.php: Requests::set_defaults( &string $url, &array $headers, &array|null $data, &string $type, &array $options )
 */
		}
	}
}
