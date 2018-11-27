<?php
/**
 * HTTP API: WP_Http class
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      2.7.0
 */

if ( ! class_exists( 'Requests' ) ) {
	require( ABSPATH . WPINC . '/class-requests.php' );
	Requests::register_autoloader();
	Requests::set_certificate_path( ABSPATH . WPINC . '/certificates/ca-bundle.crt' );
}

/**
 * Core class used for managing HTTP transports and making HTTP requests.
 *
 * This class is used to consistently make outgoing HTTP requests easy for developers while still being compatible with the many PHP configurations under which WordPress runs.
 *
 * Debugging includes several actions, which pass different variables for debugging the HTTP API.
 *
 * @since 2.7.0
 */
class WP_Http
{
	// Aliases for HTTP response codes.
	const HTTP_CONTINUE       = 100;
	const SWITCHING_PROTOCOLS = 101;
	const PROCESSING          = 102;

	const OK                            = 200;
	const CREATED                       = 201;
	const ACCEPTED                      = 202;
	const NON_AUTHORITATIVE_INFORMATION = 203;
	const NO_CONTENT                    = 204;
	const RESET_CONTENT                 = 205;
	const PARTIAL_CONTENT               = 206;
	const MULTI_STATUS                  = 207;
	const IM_USED                       = 208;

	const MULTIPLE_CHOICES   = 300;
	const MOVED_PERMANENTLY  = 301;
	const FOUND              = 302;
	const SEE_OTHER          = 303;
	const NOT_MODIFIED       = 304;
	const USE_PROXY          = 305;
	const RESERVED           = 306;
	const TEMPORARY_REDIRECT = 307;
	const PERMANENT_REDIRECT = 308;

	const BAD_REQUEST                     = 400;
	const UNAUTHORIZED                    = 401;
	const PAYMENT_REQUIRED                = 402;
	const FORBIDDEN                       = 403;
	const NOT_FOUND                       = 404;
	const METHOD_NOT_ALLOWED              = 405;
	const NOT_ACCEPTABLE                  = 406;
	const PROXY_AUTHENTICATION_REQUIRED   = 407;
	const REQUEST_TIMEOUT                 = 408;
	const CONFLICT                        = 409;
	const GONE                            = 410;
	const LENGTH_REQUIRED                 = 411;
	const PRECONDITION_FAILED             = 412;
	const REQUEST_ENTITY_TOO_LARGE        = 413;
	const REQUEST_URI_TOO_LONG            = 414;
	const UNSUPPORTED_MEDIA_TYPE          = 415;
	const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
	const EXPECTATION_FAILED              = 417;
	const IM_A_TEAPOT                     = 418;
	const MISDIRECTED_REQUEST             = 421;
	const UNPROCESSABLE_ENTITY            = 422;
	const LOCKED                          = 423;
	const FAILED_DEPENDENCY               = 424;
	const UPGRADE_REQUIRED                = 426;
	const PRECONDITION_REQUIRED           = 428;
	const TOO_MANY_REQUESTS               = 429;
	const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
	const UNAVAILABLE_FOR_LEGAL_REASONS   = 451;

	const INTERNAL_SERVER_ERROR           = 500;
	const NOT_IMPLEMENTED                 = 501;
	const BAD_GATEWAY                     = 502;
	const SERVICE_UNAVAILABLE             = 503;
	const GATEWAY_TIMEOUT                 = 504;
	const HTTP_VERSION_NOT_SUPPORTED      = 505;
	const VARIANT_ALSO_NEGOTIATES         = 506;
	const INSUFFICIENT_STORAGE            = 507;
	const NOT_EXTENDED                    = 510;
	const NETWORK_AUTHENTICATION_REQUIRED = 511;

	/**
	 * Send an HTTP request to a URI.
	 *
	 * Please note: The only URI that are supported in the HTTP Transport implementation are the HTTP and HTTPS protocols.
	 *
	 * @since 2.7.0
	 *
	 * @param  string         $url  The request URL.
	 * @param  string|array   $args {
	 *     Optional.
	 *     Array or string of HTTP request arguments.
	 *
	 *     @type string       $method              Request method.
	 *                                             Accepts 'GET', 'POST', 'HEAD', or 'PUT'.
	 *                                             Some transports technically allow others, but should not be assumed.
	 *                                             Default 'GET'.
	 *     @type int          $timeout             How long the connection should stay open in seconds.
	 *                                             Default 5.
	 *     @type int          $redirection         Number of allowed redirects.
	 *                                             Not supported by all transports.
	 *                                             Default 5.
	 *     @type string       $httpversion         Version of the HTTP protocol to use.
	 *                                             Accepts '1.0' and '1.1'.
	 *                                             Default '1.0'.
	 *     @type string       $user-agent          User-agent value sent.
	 *                                             Default 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ).
	 *     @type bool         $reject_unsafe_urls  Whether to pass URLs through wp_http_validate_url().
	 *                                             Default false.
	 *     @type bool         $blocking            Whether the calling code requires the result of the request.
	 *                                             If set to false, the request will be sent to the remote server, and processing returned to the calling code immediately, the caller will know if the request succeeded or failed, but will not receive any response from the remote server.
	 *                                             Default true.
	 *     @type string|array $headers             Array or string of headers to send with the request.
	 *                                             Default empty array.
	 *     @type array        $cookies             List of cookies to send with the request.
	 *                                             Default empty array.
	 *     @type string|array $body                Body to send with the request.
	 *                                             Default null.
	 *     @type bool         $compress            Whether to compress the $body when sending the request.
	 *                                             Default false.
	 *     @type bool         $decompress          Whether to decompress a compressed response.
	 *                                             If set to false and compressed content is returned in the response anyway, it will need to be separately decompressed.
	 *                                             Default true.
	 *     @type bool         $sslverify           Whether to verify SSL for the request.
	 *                                             Default true.
	 *     @type string       $sslcertificates     Absolute path to an SSL certificate .crt file.
	 *                                             Default ABSPATH . WPINC . '/certificates/ca-bundle.crt'.
	 *     @type bool         $stream              Whether to stream to a file.
	 *                                             If set to true and no filename was given, it will be droped it in the WP temp dir and its name will be set using the basename of the URL.
	 *                                             Default false.
	 *     @type string       $filename            Filename of the file to write to when streaming.
	 *                                             $stream must be set to true.
	 *                                             Default null.
	 *     @type int          $limit_response_size Size in bytes to limit the response to.
	 *                                             Default null.
	 * }
	 * @return array|WP_Error Array containing 'headers', 'body', 'response', 'cookies', 'filename'.
	 *                        A WP_Error instance upon error.
	 */
	public function request( $url, $args = array() )
	{
		$defaults = array(
			'method'              => 'GET',

			/**
			 * Filters the timeout value for an HTTP request.
			 *
			 * @since 2.7.0
			 *
			 * @param int $timeout_value Time in seconds until a request times out.
			 *                           Default 5.
			 */
			'timeout'             => apply_filters( 'http_request_timeout', 5 ),

			/**
			 * Filters the number of redirects allowed during an HTTP request.
			 *
			 * @since 2.7.0
			 *
			 * @param int $redirect_count Number of redirects allowed.
			 *                            Default 5.
			 */
			'redirection'         => apply_filters( 'http_request_redirection_count', 5 ),

			/**
			 * Filters the version of the HTTP protocol used in a request.
			 *
			 * @since 2.7.0
			 *
			 * @param string $version Version of HTTP used.
			 *                        Accepts '1.0' and '1.1'.
			 *                        Default '1.0'.
			 */
			'httpversion'         => apply_filters( 'http_request_version', '1.0' ),

			/**
			 * Filters the user agent value sent with an HTTP request.
			 *
			 * @since 2.7.0
			 *
			 * @param string $user_agent WordPress user agent string.
			 */
			'user-agent'          => apply_filters( 'http_headers_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ) ),

			/**
			 * Filters whether to pass URLs through wp_http_validate_url() in an HTTP request.
			 *
			 * @since 3.6.0
			 *
			 * @param bool $pass_url Whether to pass URLs through wp_http_validate_url().
			 *                       Default false.
			 */
			'reject_unsafe_urls'  => apply_filters( 'http_request_reject_unsafe_urls', FALSE ),

			'blocking'            => TRUE,
			'headers'             => array(),
			'cookies'             => array(),
			'body'                => NULL,
			'compress'            => FALSE,
			'decompress'          => TRUE,
			'sslverify'           => TRUE,
			'sslcertificates'     => ABSPATH . WPINC . '/certificates/ca-bundle.crt',
			'stream'              => FALSE,
			'filename'            => NULL,
			'limit_response_size' => NULL
		);

		// Pre-parse for the HEAD checks.
		$args = wp_parse_args( $args );

		// By default, Head requests do not cause redirections.
		if ( isset( $args['method'] ) && 'HEAD' == $args['method'] ) {
			$defaults['redirection'] = 0;
		}

		$r = wp_parse_args( $args, $defaults );

		/**
		 * Filters the arguments used in an HTTP request.
		 *
		 * @since 2.7.0
		 *
		 * @param array  $r   An array of HTTP request arguments.
		 * @param string $url The request URL.
		 */
		$r = apply_filters( 'http_request_args', $r, $url );

		// The transports decrement this, store a copy of the original value for loop purposes.
		if ( ! isset( $r['_redirection'] ) ) {
			$r['_redirection'] = $r['redirection'];
		}

		/**
		 * Filters whether to preempt an HTTP request's return value.
		 *
		 * Returning a non-false value from the filter will short-circuit the HTTP request and return early with that value.
		 * A filter should return either:
		 *
		 * - An array containing 'headers', 'body', 'response', 'cookies', and 'filename' elements
		 * - A WP_Error instance
		 * - boolean false (to avoid short-circuiting the response)
		 *
		 * Returning any other value may result in unexpected behaviour.
		 *
		 * @since 2.9.0
		 *
		 * @param false|array|WP_Error $preempt Whether to preempt an HTTP request's return value.
		 *                                      Default false.
		 * @param array                $r       HTTP request arguments.
		 * @param string               $url     The request URL.
		 */
		$pre = apply_filters( 'pre_http_request', FALSE, $r, $url );

		if ( FALSE !== $pre ) {
			return $pre;
		}

		if ( function_exists( 'wp_kses_bad_protocol' ) ) {
			if ( $r['reject_unsafe_urls'] ) {
				$url = wp_http_validate_url( $url );
			}

			if ( $url ) {
				$url = wp_kses_bad_protocol( $url, array( 'http', 'https', 'ssl' ) );
			}
		}

		$arrURL = @ parse_url( $url );

		if ( empty( $url ) || empty( $arrURL['scheme'] ) ) {
			return new WP_Error( 'http_request_failed', __( 'A valid URL was not provided.' ) );
		}

		if ( $this->block_request( $url ) ) {
			return new WP_Error( 'http_request_failed', __( 'User has blocked requests through HTTP.' ) );
		}

		// If we are streaming to a file but no filename was given drop it in the WP temp dir and pick its name using the basename of the $url.
		if ( $r['stream'] ) {
			if ( empty( $r['filename'] ) ) {
				$r['filename'] = get_temp_dir() . basename( $url );
			}

			// Force some settings if we are streaming to a file and check for existence and perms of destination directory.
			$r['blocking'] = TRUE;

			if ( ! wp_is_writable( dirname( $r['filename'] ) ) ) {
				return new WP_Error( 'http_request_failed', __( 'Destination directory for file streaming does not exist or is not writable.' ) );
			}
		}

		if ( is_null( $r['headers'] ) ) {
			$r['headers'] = array();
		}

		// WP allows passing in headers as a string, weirdly.
		if ( ! is_array( $r['headers'] ) ) {
			$processedHeaders = WP_Http::processHeaders( $r['headers'] );
			$r['headers'] = $processedHeaders['headers'];
		}

		// Setup arguments.
		$headers = $r['headers'];
		$data = $r['body'];
		$type = $r['method'];
		$options = array(
			'timeout'   => $r['timeout'],
			'useragent' => $r['user-agent'],
			'blocking'  => $r['blocking'],
			'hooks'     => new WP_HTTP_Requests_Hooks( $url, $r )
		);

		// Ensure redirects follow browser behaviour.
		$options['hooks']->register( 'requests.before_redirect', array( get_class(), 'browser_redirect_compatibility' ) );

		// Validate redirected URLs.
		if ( function_exists( 'wp_kses_bad_protocol' ) && $r['reject_unsafe_urls'] ) {
			$options['hooks']->register( 'requests.before_redirect', array( get_class(), 'validate_redirects' ) );
		}

		if ( $r['stream'] ) {
			$options['filename'] = $r['filename'];
		}

		if ( empty( $r['redirection'] ) ) {
			$options['follow_redirects'] = FALSE;
		} else {
			$options['redirects'] = $r['redirection'];
		}

		// Use byte limit, if we can.
		if ( isset( $r['limit_response_size'] ) ) {
			$options['max_bytes'] = $r['limit_response_size'];
		}

		// If we've got cookies, use and convert them to Requests_Cookie.
		if ( ! empty( $r['cookies'] ) ) {
			$options['cookies'] = WP_Http::normalize_cookies( $r['cookies'] );
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
 * @NOW 012: wp-includes/class-http.php: WP_Http::request( string $url [, string|array $args = array()] )
 * ......->: wp-includes/class-http.php: WP_Http::normalize_cookies( array $cookies )
 */
		}
	}

	/**
	 * Normalizes cookies for using in Requests.
	 *
	 * @since  4.6.0
	 * @static
	 *
	 * @param  array               $cookies List of cookies to send with the request.
	 * @return Requests_Cookie_Jar Cookie holder object.
	 */
	public static function normalize_cookies( $cookies )
	{
		$cookie_jar = new Requests_Cookie_Jar();

		foreach ( $cookies as $name => $value ) {
			if ( $value instanceof WP_Http_Cookie ) {
				$cookie_jar[ $value->name ] = new Requests_Cookie( $value->name, $value->value, $value->get_attributes() );
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
 * @NOW 013: wp-includes/class-http.php: WP_Http::normalize_cookies( array $cookies )
 * ......->: wp-includes/Requests/Cookie.php: Requests_Cookie::normalize()
 */
			}
		}
	}

	/**
	 * Match redirect behaviour to browser handling.
	 *
	 * Changes 302 redirects from POST to GET to match browser handling.
	 * Per RFC 7231, user agents can deviate from the strict reading of the specification for compatibility purposes.
	 *
	 * @since  4.6.0
	 * @static
	 *
	 * @param string            $location URL to redirect to.
	 * @param array             $headers  Headers for the redirect.
	 * @param string|array      $data     Body to send with the request.
	 * @param array             $options  Redirect request options.
	 * @param Requests_Response $original Response object.
	 */
	public static function browser_redirect_compatibility( $location, $headers, $data, &$options, $original )
	{
		// Browser compat
		if ( $original->status_code === 302 ) {
			$options['type'] = Requests::GET;
		}
	}

	/**
	 * Validate redirected URLs.
	 *
	 * @since  4.7.5
	 * @throws Requests_Exception On unsuccessful URL validation
	 *
	 * @param string $location URL to redirect to.
	 */
	public static function validate_redirects( $location )
	{
		if ( ! wp_http_validate_url( $location ) ) {
			throw new Requests_Exception( __( 'A valid URL was not provided.' ), 'wp_http.redirect_failed_validation' );
		}
	}

	/**
	 * Tests which transports are capable of supporting the request.
	 *
	 * @since 3.2.0
	 *
	 * @param  array        $args Request arguments.
	 * @param  string       $url  URL to Request.
	 * @return string|false Class name for the first transport that claims to support the request.
	 *                      False if no transport claims to support the request.
	 */
	public function _get_first_available_transport( $args, $url = NULL )
	{
		$transports = array( 'curl', 'streams' );

		/**
		 * Filters which HTTP transports are available and in what order.
		 *
		 * @since 3.7.0
		 *
		 * @param array  $transports Array of HTTP transports to check.
		 *                           Default array contains 'curl', and 'streams', in that order.
		 * @param array  $args       HTTP request arguments.
		 * @param string $url        The URL to request.
		 */
		$request_order = apply_filters( 'http_api_transports', $transports, $args, $url );

		// Loop over each transport on each HTTP request looking for one which will serve this request's needs.
		foreach ( $request_order as $transport ) {
			if ( in_array( $transport, $transports ) ) {
				$transport = ucfirst( $transport );
			}

			$class = 'WP_Http_' . $transport;

			// Check to see if this transport is a possibility, calls the transport statically.
			if ( ! call_user_func( array( $class, 'test' ), $args, $url ) ) {
				continue;
			}

			return $class;
		}

		return FALSE;
	}

	/**
	 * Uses the POST HTTP method.
	 *
	 * Used for sending data that is expected to be in the body.
	 *
	 * @since 2.7.0
	 *
	 * @param  string         $url  The request URL.
	 * @param  string|array   $args Optional.
	 *                              Override the defaults.
	 * @return array|WP_Error Array containing 'headers', 'body', 'response', 'cookies', 'filename'.
	 *                        A WP_Error instance upon error.
	 */
	public function post( $url, $args = array() )
	{
		$defaults = array( 'method' => 'POST' );
		$r = wp_parse_args( $args, $defaults );
		return $this->request( $url, $r );
	}

	/**
	 * Transform header string into an array.
	 *
	 * If an array is given then it is assumed to be raw header data with numeric keys with the headers as the values.
	 * No headers must be passed that were already processed.
	 *
	 * @static
	 * @since  2.7.0
	 *
	 * @param  string|array $headers
	 * @param  string       $url     The URL that was requested.
	 * @return array        Processed string headers.
	 *                      If duplicate headers are encountered, then a numbered array is returned as the value of that header-key.
	 */
	public static function processHeaders( $headers, $url = '' )
	{
		// Split headers, one per array element.
		if ( is_string( $headers ) ) {
			// Tolerate line terminator: CRLF = LF (RFC 2616 19.3).
			$headers = str_replace( "\r\n", "\n", $headers );

			/**
			 * Unfold folded header fields.
			 * LWS = [CRLF] 1*( SP | HT ) <US-ASCII SP, space (32)>, <US-ASCII HT, horizontal-tab (9)> (RFC 2616 2.2).
			 */
			$headers = preg_replace( '/\n[ \t]/', ' ', $headers );

			// Create the headers array.
			$headers = explode( "\n", $headers );
		}

		$response = array(
			'code'    => 0,
			'message' => ''
		);

		/**
		 * If a redirection has taken place, the headers for each page request may have been passed.
		 * In this case, determine the final HTTP header and parse from there.
		 */
		for ( $i = count( $headers ) - 1; $i >= 0; $i-- ) {
			if ( ! empty( $headers[ $i ] ) && FALSE === strpos( $headers[ $i ], ':' ) ) {
				$headers = array_splice( $headers, $i );
				break;
			}
		}

		$cookies = array();
		$newheaders = array();

		foreach ( ( array ) $headers as $tempheader ) {
			if ( empty( $tempheader ) ) {
				continue;
			}

			if ( FALSE === strpos( $tempheader, ':' ) ) {
				$stack = explode( ' ', $tempheader, 3 );
				$stack[] = '';
				list( , $response['code'], $response['message'] ) = $stack;
				continue;
			}

			list( $key, $value ) = explode( ':', $tempheader, 2 );
			$key = strtolower( $key );
			$value = trim( $value );

			if ( isset( $newheaders[ $key ] ) ) {
				if ( ! is_array( $newheaders[ $key ] ) ) {
					$newheaders[ $key ] = array( $newheaders[ $key ] );
				}

				$newheaders[ $key ][] = $value;
			} else {
				$newheaders[ $key ] = $value;
			}

			if ( 'set-cookie' == $key ) {
				$cookies[] = new WP_Http_Cookie( $value, $url );
			}
		}

		// Cast the Response Code to an int.
		$response['code'] = intval( $response['code'] );

		return array(
			'response' => $response,
			'headers'  => $newheaders,
			'cookies'  => $cookies
		);
	}

	/**
	 * Block requests through the proxy.
	 *
	 * Those who are behind a proxy and want to prevent access to certain hosts may do so.
	 * This will prevent plugins from working and core functionality, if you don't include api.wordpress.org.
	 *
	 * You block external URL requests by defining WP_HTTP_BLOCK_EXTERNAL as true in your wp-config.php file and this will only allow localhost and your site to make requests.
	 * The constant WP_ACCESSIBLE_HOSTS will allow additional hosts to go through for requests.
	 * The format of the WP_ACCESSIBLE_HOSTS constant is a comma separated list of hostnames to allow, wildcard domains are supported, eg *.wordpress.org will allow for all subdomains of wordpress.org to be contacted.
	 *
	 * @since     2.8.0
	 * @link      https://core.trac.wordpress.org/ticket/8927 Allow preventing external requests.
	 * @link      https://core.trac.wordpress.org/ticket/14636 Allow wildcard domains in WP_ACCESSIBLE_HOSTS
	 * @staticvar array|null $accessible_hosts
	 * @staticvar array      $wildcard_regex
	 *
	 * @param  string $uri URI of url.
	 * @return bool   True to block, false to allow.
	 */
	public function block_request( $uri )
	{
		// We don't need to block requests, because nothing is blocked.
		if ( ! defined( 'WP_HTTP_BLOCK_EXTERNAL' ) || ! WP_HTTP_BLOCK_EXTERNAL ) {
			return FALSE;
		}

		$check = parse_url( $uri );

		if ( ! $check ) {
			return TRUE;
		}

		$home = parse_url( get_option( 'site_url' ) );

		// Don't block requests back to ourselves by default.
		if ( 'localhost' == $check['host']
		  || isset( $home['host'] ) && $home['host'] == $check['host'] ) {
			/**
			 * Filters whether to block local requests through the proxy.
			 *
			 * @since 2.8.0
			 *
			 * @param bool $block Whether to block local requests through proxy.
			 *                    Default false.
			 */
			return apply_filters( 'block_local_requests', FALSE );
		}

		if ( ! defined( 'WP_ACCESSIBLE_HOSTS' ) ) {
			return TRUE;
		}

		static $accessible_hosts = NULL;
		static $wildcard_regex = array();

		if ( NULL === $accessible_hosts ) {
			$accessible_hosts = preg_split( '|,\s*|', WP_ACCESSIBLE_HOSTS );

			if ( FALSE !== strpos( WP_ACCESSIBLE_HOSTS, '*' ) ) {
				$wildcard_regex = array();

				foreach ( $accessible_hosts as $host ) {
					$wildcard_regex[] = str_replace( '\*', '.+', preg_quote( $host, '/' ) );
				}

				$wildcard_regex = '/^(' . implode( '|', $wildcard_regex ) . ')$/i';
			}
		}

		return ! empty( $wildcard_regex )
			? ! preg_match( $wildcard_regex, $check['host'] )
			: ! in_array( $check['host'], $accessible_hosts ); // Inverse logic, if it's in the array, then we can't access it.
	}
}
