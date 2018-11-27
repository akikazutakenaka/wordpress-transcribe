<?php
/**
 * HTTP API: WP_Http_Cookie class
 *
 * @package WordPress
 * @subpackage HTTP
 * @since 4.4.0
 */

/**
 * Core class used to encapsulate a single cookie object for internal use.
 *
 * Returned cookies are represented using this class, and when cookies are set, if they are not
 * already a WP_Http_Cookie() object, then they are turned into one.
 *
 * @todo The WordPress convention is to use underscores instead of camelCase for function and method
 * names. Need to switch to use underscores instead for the methods.
 *
 * @since 2.8.0
 */
class WP_Http_Cookie {
	// refactored. public $name;
	// :
	// refactored. public function __construct( $data, $requested_url = '' ) {}

	/**
	 * Confirms that it's OK to send this cookie to the URL checked against.
	 *
	 * Decision is based on RFC 2109/2965, so look there for details on validity.
	 *
	 * @since 2.8.0
	 *
	 * @param string $url URL you intend to send this cookie to
	 * @return bool true if allowed, false otherwise.
	 */
	public function test( $url ) {
		if ( is_null( $this->name ) )
			return false;

		// Expires - if expired then nothing else matters.
		if ( isset( $this->expires ) && time() > $this->expires )
			return false;

		// Get details on the URL we're thinking about sending to.
		$url = parse_url( $url );
		$url['port'] = isset( $url['port'] ) ? $url['port'] : ( 'https' == $url['scheme'] ? 443 : 80 );
		$url['path'] = isset( $url['path'] ) ? $url['path'] : '/';

		// Values to use for comparison against the URL.
		$path   = isset( $this->path )   ? $this->path   : '/';
		$port   = isset( $this->port )   ? $this->port   : null;
		$domain = isset( $this->domain ) ? strtolower( $this->domain ) : strtolower( $url['host'] );
		if ( false === stripos( $domain, '.' ) )
			$domain .= '.local';

		// Host - very basic check that the request URL ends with the domain restriction (minus leading dot).
		$domain = substr( $domain, 0, 1 ) == '.' ? substr( $domain, 1 ) : $domain;
		if ( substr( $url['host'], -strlen( $domain ) ) != $domain )
			return false;

		// Port - supports "port-lists" in the format: "80,8000,8080".
		if ( !empty( $port ) && !in_array( $url['port'], explode( ',', $port) ) )
			return false;

		// Path - request path must start with path restriction.
		if ( substr( $url['path'], 0, strlen( $path ) ) != $path )
			return false;

		return true;
	}

	/**
	 * Convert cookie name and value back to header string.
	 *
	 * @since 2.8.0
	 *
	 * @return string Header encoded cookie name and value.
	 */
	public function getHeaderValue() {
		if ( ! isset( $this->name ) || ! isset( $this->value ) )
			return '';

		/**
		 * Filters the header-encoded cookie value.
		 *
		 * @since 3.4.0
		 *
		 * @param string $value The cookie value.
		 * @param string $name  The cookie name.
		 */
		return $this->name . '=' . apply_filters( 'wp_http_cookie_value', $this->value, $this->name );
	}

	/**
	 * Retrieve cookie header for usage in the rest of the WordPress HTTP API.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function getFullHeader() {
		return 'Cookie: ' . $this->getHeaderValue();
	}

	// refactored. public function get_attributes() {}
}
