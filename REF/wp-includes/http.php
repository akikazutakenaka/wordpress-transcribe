<?php
/**
 * Core HTTP Request API
 *
 * Standardizes the HTTP requests for WordPress.
 * Handles cookies, gzip encoding and decoding, chunk decoding, if HTTP 1.1 and various other difficult HTTP protocol implementations.
 *
 * @package    WordPress
 * @subpackage HTTP
 */

/**
 * Returns the initialized WP_Http Object.
 *
 * @since     2.7.0
 * @access    private
 * @staticvar WP_Http $http
 *
 * @return WP_Http HTTP Transport object.
 */
function _wp_http_get_object()
{
	static $http = NULL;

	if ( is_null( $http ) ) {
		$http = new WP_Http();
	}

	return $http;
}

/**
 * Retrieve the raw response from the HTTP request using the POST method.
 *
 * @since 2.7.0
 * @see   wp_remote_request() for more information on the response array format.
 * @see   WP_Http::request() for default arguments information.
 *
 * @param  string         $url  Site URL to retrieve.
 * @param  array          $args Optional.
 *                              Request arguments.
 *                              Default empty array.
 * @return WP_Error|array The response or WP_Error on failure.
 */
function wp_remote_post( $url, $args = array() )
{
	$http = _wp_http_get_object();
	return $http->post( $url, $args );
}

/**
 * Retrieve only the body from the raw response.
 *
 * @since 2.7.0
 *
 * @param  array  $response HTTP response.
 * @return string The body of the response.
 *                Empty string if no body or incorrect parameter given.
 */
function wp_remote_retrieve_body( $response )
{
	return is_wp_error( $response ) || ! isset( $response['body'] )
		? ''
		: $response['body'];
}

/**
 * Determines if there is an HTTP Transport that can process this request.
 *
 * @since 3.2.0
 *
 * @param  array  $capabilities Array of capabilities to test or a wp_remote_request() $args array.
 * @param  string $url          Optional.
 *                              If given, will check if the URL requires SSL and adds that requirement to the capabilities array.
 * @return bool
 */
function wp_http_supports( $capabilities = array(), $url = NULL )
{
	$http = _wp_http_get_object();
	$capabilities = wp_parse_args( $capabilities );
	$count = count( $capabilities );

	// If we have a numeric $capabilities array, spoof a wp_remote_request() associative $args array.
	if ( $count && count( array_filter( array_keys( $capabilities ), 'is_numeric' ) ) == $count ) {
		$capabilities = array_combine( array_values( $capabilities ), array_fill( 0, $count, TRUE ) );
	}

	if ( $url && ! isset( $capabilities['ssl'] ) ) {
		$scheme = parse_url( $url, PHP_URL_SCHEME );

		if ( 'https' == $scheme || 'ssl' == $scheme ) {
			$capabilities['ssl'] = TRUE;
		}
	}

	return ( bool ) $http->_get_first_available_transport( $capabilities );
}

/**
 * Validate a URL for safe use in the HTTP API.
 *
 * @since 3.5.2
 *
 * @param  string       $url
 * @return false|string URL or false on failure.
 */
function wp_http_validate_url( $url )
{
	$original_url = $url;
	$url = wp_kses_bad_protocol( $url, array( 'http', 'https' ) );

	if ( ! $url || strtolower( $url ) !== strtolower( $original_url ) ) {
		return FALSE;
	}

	$parsed_url = @ parse_url( $url );

	if ( ! $parsed_url || empty( $parsed_url['host'] ) ) {
		return FALSE;
	}

	if ( isset( $parsed_url['user'] ) || isset( $parsed_url['pass'] ) ) {
		return FALSE;
	}

	if ( FALSE !== strpbrk( $parsed_url['host'], ':#?[]' ) ) {
		return FALSE;
	}

	$parsed_home = @ parse_url( get_option( 'home' ) );

	$same_host = isset( $parsed_home['host'] )
		? strtolower( $parsed_home['host'] ) === strtolower( $parsed_url['host'] )
		: FALSE;

	if ( ! $same_host ) {
		$host = trim( $parsed_url['host'], '.' );

		$ip = preg_match( '#^(([1-9]?\d|1\d\d|25[0-5]|2[0-4]\d)\.){3}([1-9]?\d|1\d\d|25[0-5]|2[0-4]\d)$#', $host )
			? $host
			: ( $host === gethostbyname( $host )
				? gethostbyname( $host )
				: FALSE );

		if ( $ip ) {
			$parts = array_map( 'intval', explode( '.', $ip ) );

			if ( 127 === $parts[0]
			  || 10 === $parts[0]
			  || 0 === $parts[0]
			  || 172 === $parts[0] && 16 <= $parts[1] && 31 >= $parts[1]
			  || 192 === $parts[0] && 168 === $parts[1] ) {
				// If host appears local, reject unless specifically allowed.

				/**
				 * Check if HTTP request is external or not.
				 *
				 * Allows to change and allow external requests for the HTTP request.
				 *
				 * @since 3.6.0
				 *
				 * @param bool   false Whether HTTP request is external or not.
				 * @param string $host IP of the requested host.
				 * @param string $url  URL of the requested host.
				 */
				if ( ! apply_filters( 'http_requested_host_is_external', FALSE, $host, $url ) ) {
					return FALSE;
				}
			}
		}
	}

	if ( empty( $parsed_url['host'] ) ) {
		return $url;
	}

	$port = $parsed_url['port'];

	if ( 80 === $port || 443 === $port || 8080 === $port ) {
		return $url;
	}

	if ( $parsed_home && $same_host && isset( $parsed_home['port'] ) && $parsed_home['port'] === $port ) {
		return $url;
	}

	return FALSE;
}

/**
 * A wrapper for PHP's parse_url() function that handles consistency in the return values across PHP versions.
 *
 * PHP 5.4.7 expanded parse_url()'s ability to handle non-absolute url's, including schemeless and relative url's with :// in the path.
 * This function works around those limitations providing a standard output on PHP 5.2~5.4+.
 *
 * Secondly, across various PHP version, schemeless URLs starting containing a ":" in the query are being handled inconsistently.
 * This function works around those differences as well.
 *
 * Error suppression is used as prior to PHP 5.3.3, an E_WARNING would be generated when URL parsing failed.
 *
 * @since 4.4.0
 * @since 4.7.0 The $component parameter was added for parity with PHP's parse_url().
 * @link  https://secure.php.net/manual/en/function.parse-url.php
 *
 * @param  string $url       The URL to parse.
 * @param  int    $component The specific component to retrieve.
 *                           Use one of the PHP predefined constants to specify which one.
 *                           Defaults to -1 (= return all parts as an array).
 * @return mixed  False on parse failure; Array of URL components on success; When a specific component has been requested: null if the component doesn't exist in the given URL; a string or - in the case of PHP_URL_PORT - integer when it does.
 *                See parse_url()'s return values.
 */
function wp_parse_url( $url, $component = -1 )
{
	$to_unset = array();
	$url = strval( $url );

	if ( '//' === substr( $url, 0, 2 ) ) {
		$to_unset[] = 'scheme';
		$url = 'placeholder:' . $url;
	} elseif ( '/' === substr( $url, 0, 1 ) ) {
		$to_unset[] = 'scheme';
		$to_unset[] = 'host';
		$url = 'placeholder://placeholder' . $url;
	}

	$parts = @ parse_url( $url );

	if ( FALSE === $parts ) {
		// Parsing failure.
		return $parts;
	}

	// Remove the placeholder values.
	foreach ( $to_unset as $key ) {
		unset( $parts[ $key ] );
	}

	return _get_component_from_parsed_url_array( $parts, $component );
}

/**
 * Retrieve a specific component from a parsed URL array.
 *
 * @internal
 * @since    4.7.0
 * @access   private
 * @link     https://secure.php.net/manual/en/function.parse-url.php
 *
 * @param  array|false $url_parts The parsed URL.
 *                                Can be false if the URL failed to parse.
 * @param  int         $component The specific component to retrieve.
 *                                Use one of the PHP predefined constants to specify which one.
 *                                Defaults to -1 (= return all parts as an array).
 * @return mixed       False on parse failure; Array of URL components on success; When a specific component has been requested: null if the component doesn't exist in the given URL; a string or - in the case of PHP_URL_PORT - integer when it does.
 *                     See parse_url()'s return values.
 */
function _get_component_from_parsed_url_array( $url_parts, $component = -1 )
{
	if ( -1 === $component ) {
		return $url_parts;
	}

	$key = _wp_translate_php_url_constant_to_key( $component );

	return FALSE !== $key && is_array( $url_parts ) && isset( $url_parts[ $key ] )
		? $url_parts[ $key ]
		: NULL;
}

/**
 * Translate a PHP_URL_* constant to the named array keys PHP uses.
 *
 * @internal
 * @since    4.7.0
 * @access   private
 * @link     https://secure.php.net/manual/en/url.constants.php
 *
 * @param  int         $constant PHP_URL_* constant.
 * @return string|bool The named key or false.
 */
function _wp_translate_php_url_constant_to_key( $constant )
{
	$translation = array(
		PHP_URL_SCHEME   => 'scheme',
		PHP_URL_HOST     => 'host',
		PHP_URL_PORT     => 'port',
		PHP_URL_USER     => 'user',
		PHP_URL_PASS     => 'pass',
		PHP_URL_PATH     => 'path',
		PHP_URL_QUERY    => 'query',
		PHP_URL_FRAGMENT => 'fragment'
	);

	return isset( $translation[ $constant ] )
		? $translation[ $constant ]
		: FALSE;
}
