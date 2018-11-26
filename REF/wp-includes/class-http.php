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
 */

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
}
