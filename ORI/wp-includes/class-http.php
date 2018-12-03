<?php
/**
 * HTTP API: WP_Http class
 *
 * @package WordPress
 * @subpackage HTTP
 * @since 2.7.0
 */

if ( ! class_exists( 'Requests' ) ) {
	require( ABSPATH . WPINC . '/class-requests.php' );

	Requests::register_autoloader();
	Requests::set_certificate_path( ABSPATH . WPINC . '/certificates/ca-bundle.crt' );
}

/**
 * Core class used for managing HTTP transports and making HTTP requests.
 *
 * This class is used to consistently make outgoing HTTP requests easy for developers
 * while still being compatible with the many PHP configurations under which
 * WordPress runs.
 *
 * Debugging includes several actions, which pass different variables for debugging the HTTP API.
 *
 * @since 2.7.0
 */
class WP_Http {
	// refactored. const HTTP_CONTINUE                   = 100;
	// :
	// refactored. public function _get_first_available_transport( $args, $url = null ) {}

	/**
	 * Dispatches a HTTP request to a supporting transport.
	 *
	 * Tests each transport in order to find a transport which matches the request arguments.
	 * Also caches the transport instance to be used later.
	 *
	 * The order for requests is cURL, and then PHP Streams.
	 *
	 * @since 3.2.0
	 *
	 * @static
	 *
	 * @param string $url URL to Request
	 * @param array $args Request arguments
	 * @return array|WP_Error Array containing 'headers', 'body', 'response', 'cookies', 'filename'. A WP_Error instance upon error
	 */
	private function _dispatch_request( $url, $args ) {
		static $transports = array();

		$class = $this->_get_first_available_transport( $args, $url );
		if ( !$class )
			return new WP_Error( 'http_failure', __( 'There are no HTTP transports available which can complete the requested request.' ) );

		// Transport claims to support request, instantiate it and give it a whirl.
		if ( empty( $transports[$class] ) )
			$transports[$class] = new $class;

		$response = $transports[$class]->request( $url, $args );

		/** This action is documented in wp-includes/class-http.php */
		do_action( 'http_api_debug', $response, 'response', $class, $args, $url );

		if ( is_wp_error( $response ) )
			return $response;

		/**
		 * Filters the HTTP API response immediately before the response is returned.
		 *
		 * @since 2.9.0
		 *
		 * @param array  $response HTTP response.
		 * @param array  $args     HTTP request arguments.
		 * @param string $url      The request URL.
		 */
		return apply_filters( 'http_response', $response, $args, $url );
	}

	// refactored. public function post($url, $args = array()) {}

	/**
	 * Uses the GET HTTP method.
	 *
	 * Used for sending data that is expected to be in the body.
	 *
	 * @since 2.7.0
	 *
	 * @param string $url The request URL.
	 * @param string|array $args Optional. Override the defaults.
	 * @return array|WP_Error Array containing 'headers', 'body', 'response', 'cookies', 'filename'. A WP_Error instance upon error
	 */
	public function get($url, $args = array()) {
		$defaults = array('method' => 'GET');
		$r = wp_parse_args( $args, $defaults );
		return $this->request($url, $r);
	}

	/**
	 * Uses the HEAD HTTP method.
	 *
	 * Used for sending data that is expected to be in the body.
	 *
	 * @since 2.7.0
	 *
	 * @param string $url The request URL.
	 * @param string|array $args Optional. Override the defaults.
	 * @return array|WP_Error Array containing 'headers', 'body', 'response', 'cookies', 'filename'. A WP_Error instance upon error
	 */
	public function head($url, $args = array()) {
		$defaults = array('method' => 'HEAD');
		$r = wp_parse_args( $args, $defaults );
		return $this->request($url, $r);
	}

	/**
	 * Parses the responses and splits the parts into headers and body.
	 *
	 * @static
	 * @since 2.7.0
	 *
	 * @param string $strResponse The full response string
	 * @return array Array with 'headers' and 'body' keys.
	 */
	public static function processResponse($strResponse) {
		$res = explode("\r\n\r\n", $strResponse, 2);

		return array('headers' => $res[0], 'body' => isset($res[1]) ? $res[1] : '');
	}

	// refactored. public static function processHeaders( $headers, $url = '' ) {}

	/**
	 * Takes the arguments for a ::request() and checks for the cookie array.
	 *
	 * If it's found, then it upgrades any basic name => value pairs to WP_Http_Cookie instances,
	 * which are each parsed into strings and added to the Cookie: header (within the arguments array).
	 * Edits the array by reference.
	 *
	 * @since 2.8.0
	 * @static
	 *
	 * @param array $r Full array of args passed into ::request()
	 */
	public static function buildCookieHeader( &$r ) {
		if ( ! empty($r['cookies']) ) {
			// Upgrade any name => value cookie pairs to WP_HTTP_Cookie instances.
			foreach ( $r['cookies'] as $name => $value ) {
				if ( ! is_object( $value ) )
					$r['cookies'][ $name ] = new WP_Http_Cookie( array( 'name' => $name, 'value' => $value ) );
			}

			$cookies_header = '';
			foreach ( (array) $r['cookies'] as $cookie ) {
				$cookies_header .= $cookie->getHeaderValue() . '; ';
			}

			$cookies_header = substr( $cookies_header, 0, -2 );
			$r['headers']['cookie'] = $cookies_header;
		}
	}

	/**
	 * Decodes chunk transfer-encoding, based off the HTTP 1.1 specification.
	 *
	 * Based off the HTTP http_encoding_dechunk function.
	 *
	 * @link https://tools.ietf.org/html/rfc2616#section-19.4.6 Process for chunked decoding.
	 *
	 * @since 2.7.0
	 * @static
	 *
	 * @param string $body Body content
	 * @return string Chunked decoded body on success or raw body on failure.
	 */
	public static function chunkTransferDecode( $body ) {
		// The body is not chunked encoded or is malformed.
		if ( ! preg_match( '/^([0-9a-f]+)[^\r\n]*\r\n/i', trim( $body ) ) )
			return $body;

		$parsed_body = '';

		// We'll be altering $body, so need a backup in case of error.
		$body_original = $body;

		while ( true ) {
			$has_chunk = (bool) preg_match( '/^([0-9a-f]+)[^\r\n]*\r\n/i', $body, $match );
			if ( ! $has_chunk || empty( $match[1] ) )
				return $body_original;

			$length = hexdec( $match[1] );
			$chunk_length = strlen( $match[0] );

			// Parse out the chunk of data.
			$parsed_body .= substr( $body, $chunk_length, $length );

			// Remove the chunk from the raw data.
			$body = substr( $body, $length + $chunk_length );

			// End of the document.
			if ( '0' === trim( $body ) )
				return $parsed_body;
		}
	}

	// refactored. public function block_request($uri) {}

	/**
	 * Used as a wrapper for PHP's parse_url() function that handles edgecases in < PHP 5.4.7.
	 *
	 * @deprecated 4.4.0 Use wp_parse_url()
	 * @see wp_parse_url()
	 *
	 * @param string $url The URL to parse.
	 * @return bool|array False on failure; Array of URL components on success;
	 *                    See parse_url()'s return values.
	 */
	protected static function parse_url( $url ) {
		_deprecated_function( __METHOD__, '4.4.0', 'wp_parse_url()' );
		return wp_parse_url( $url );
	}

	/**
	 * Converts a relative URL to an absolute URL relative to a given URL.
	 *
	 * If an Absolute URL is provided, no processing of that URL is done.
	 *
	 * @since 3.4.0
	 *
	 * @static
	 *
	 * @param string $maybe_relative_path The URL which might be relative
	 * @param string $url                 The URL which $maybe_relative_path is relative to
	 * @return string An Absolute URL, in a failure condition where the URL cannot be parsed, the relative URL will be returned.
	 */
	public static function make_absolute_url( $maybe_relative_path, $url ) {
		if ( empty( $url ) )
			return $maybe_relative_path;

		if ( ! $url_parts = wp_parse_url( $url ) ) {
			return $maybe_relative_path;
		}

		if ( ! $relative_url_parts = wp_parse_url( $maybe_relative_path ) ) {
			return $maybe_relative_path;
		}

		// Check for a scheme on the 'relative' url
		if ( ! empty( $relative_url_parts['scheme'] ) ) {
			return $maybe_relative_path;
		}

		$absolute_path = $url_parts['scheme'] . '://';

		// Schemeless URL's will make it this far, so we check for a host in the relative url and convert it to a protocol-url
		if ( isset( $relative_url_parts['host'] ) ) {
			$absolute_path .= $relative_url_parts['host'];
			if ( isset( $relative_url_parts['port'] ) )
				$absolute_path .= ':' . $relative_url_parts['port'];
		} else {
			$absolute_path .= $url_parts['host'];
			if ( isset( $url_parts['port'] ) )
				$absolute_path .= ':' . $url_parts['port'];
		}

		// Start off with the Absolute URL path.
		$path = ! empty( $url_parts['path'] ) ? $url_parts['path'] : '/';

		// If it's a root-relative path, then great.
		if ( ! empty( $relative_url_parts['path'] ) && '/' == $relative_url_parts['path'][0] ) {
			$path = $relative_url_parts['path'];

		// Else it's a relative path.
		} elseif ( ! empty( $relative_url_parts['path'] ) ) {
			// Strip off any file components from the absolute path.
			$path = substr( $path, 0, strrpos( $path, '/' ) + 1 );

			// Build the new path.
			$path .= $relative_url_parts['path'];

			// Strip all /path/../ out of the path.
			while ( strpos( $path, '../' ) > 1 ) {
				$path = preg_replace( '![^/]+/\.\./!', '', $path );
			}

			// Strip any final leading ../ from the path.
			$path = preg_replace( '!^/(\.\./)+!', '', $path );
		}

		// Add the Query string.
		if ( ! empty( $relative_url_parts['query'] ) )
			$path .= '?' . $relative_url_parts['query'];

		return $absolute_path . '/' . ltrim( $path, '/' );
	}

	/**
	 * Handles HTTP Redirects and follows them if appropriate.
	 *
	 * @since 3.7.0
	 * @static
	 *
	 * @param string $url The URL which was requested.
	 * @param array $args The Arguments which were used to make the request.
	 * @param array $response The Response of the HTTP request.
	 * @return false|object False if no redirect is present, a WP_HTTP or WP_Error result otherwise.
	 */
	public static function handle_redirects( $url, $args, $response ) {
		// If no redirects are present, or, redirects were not requested, perform no action.
		if ( ! isset( $response['headers']['location'] ) || 0 === $args['_redirection'] )
			return false;

		// Only perform redirections on redirection http codes.
		if ( $response['response']['code'] > 399 || $response['response']['code'] < 300 )
			return false;

		// Don't redirect if we've run out of redirects.
		if ( $args['redirection']-- <= 0 )
			return new WP_Error( 'http_request_failed', __('Too many redirects.') );

		$redirect_location = $response['headers']['location'];

		// If there were multiple Location headers, use the last header specified.
		if ( is_array( $redirect_location ) )
			$redirect_location = array_pop( $redirect_location );

		$redirect_location = WP_Http::make_absolute_url( $redirect_location, $url );

		// POST requests should not POST to a redirected location.
		if ( 'POST' == $args['method'] ) {
			if ( in_array( $response['response']['code'], array( 302, 303 ) ) )
				$args['method'] = 'GET';
		}

		// Include valid cookies in the redirect process.
		if ( ! empty( $response['cookies'] ) ) {
			foreach ( $response['cookies'] as $cookie ) {
				if ( $cookie->test( $redirect_location ) )
					$args['cookies'][] = $cookie;
			}
		}

		return wp_remote_request( $redirect_location, $args );
	}

	/**
	 * Determines if a specified string represents an IP address or not.
	 *
	 * This function also detects the type of the IP address, returning either
	 * '4' or '6' to represent a IPv4 and IPv6 address respectively.
	 * This does not verify if the IP is a valid IP, only that it appears to be
	 * an IP address.
	 *
	 * @link http://home.deds.nl/~aeron/regex/ for IPv6 regex
	 *
	 * @since 3.7.0
	 * @static
	 *
	 * @param string $maybe_ip A suspected IP address
	 * @return integer|bool Upon success, '4' or '6' to represent a IPv4 or IPv6 address, false upon failure
	 */
	public static function is_ip_address( $maybe_ip ) {
		if ( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $maybe_ip ) )
			return 4;

		if ( false !== strpos( $maybe_ip, ':' ) && preg_match( '/^(((?=.*(::))(?!.*\3.+\3))\3?|([\dA-F]{1,4}(\3|:\b|$)|\2))(?4){5}((?4){2}|(((2[0-4]|1\d|[1-9])?\d|25[0-5])\.?\b){4})$/i', trim( $maybe_ip, ' []' ) ) )
			return 6;

		return false;
	}

}
