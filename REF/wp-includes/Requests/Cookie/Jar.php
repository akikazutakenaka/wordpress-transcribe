<?php
/**
 * Cookie holder object
 *
 * @package    Requests
 * @subpackage Cookies
 */

/**
 * Cookie holder object.
 *
 * @package    Requests
 * @subpackage Cookies
 */
class Requests_Cookie_Jar implements ArrayAccess, IteratorAggregate
{
	/**
	 * Actual item data.
	 *
	 * @var array
	 */
	protected $cookies = array();

	/**
	 * Create a new jar.
	 *
	 * @param array $cookies Existing cookie values.
	 */
	public function __construct( $cookies = array() )
	{
		$this->cookies = $cookies;
	}

	/**
	 * Normalize cookie data into a Requests_Cookie.
	 *
	 * @param  string|Requests_Cookie $cookie
	 * @param  string                 $key
	 * @return Requests_Cookie
	 */
	public function normalize_cookie( $cookie, $key = NULL )
	{
		if ( $cookie instanceof Requests_Cookie ) {
			return $cookie;
		}

		return Requests_Cookie::parse( $cookie, $key );
	}

	/**
	 * Register the cookie handler with the request's hooking system.
	 *
	 * @param Requests_Hooker $hooks Hooking system.
	 */
	public function register( Requests_Hooker $hooks )
	{
		$hooks->register( 'requests.before_request', array( $this, 'before_request' ) );
		$hooks->register( 'requests.before_redirect_check', array( $this, 'before_redirect_check' ) );
	}

	/**
	 * Add Cookie header to a request if we have any.
	 *
	 * As per RFC 6265, cookies are separated by '; '.
	 *
	 * @param string $url
	 * @param array  $headers
	 * @param array  $data
	 * @param string $type
	 * @param array  $options
	 */
	public function before_request( $url, &$headers, &$data, &$type, &$options )
	{
		if ( ! $url instanceof Requests_IRI ) {
			$url = new Requests_IRI( $url );
		}

		if ( ! empty( $this->cookies ) ) {
			$cookies = array();

			foreach ( $this->cookies as $key => $cookie ) {
				$cookie = $this->normalize_cookie( $cookie, $key );

				// Skip expired cookies.
				if ( $cookie->is_expired() ) {
					continue;
				}

				if ( $cookie->domain_matches( $url->host ) ) {
					$cookies[] = $cookie->format_for_header();
				}
			}

			$headers['Cookie'] = implode( '; ', $cookies );
		}
	}

	/**
	 * Parse all cookies from a response and attach them to the response.
	 *
	 * @var Requests_Response $response
	 */
	public function before_redirect_check( Requests_Response &$return )
	{
		$url = $return->url;

		if ( ! $url instanceof Requests_IRI ) {
			$url = new Requests_IRI( $url );
		}

		$cookies = Requests_Cookie::parse_from_headers( $return->headers, $url );
		$this->cookies = array_merge( $this->cookies, $cookies );
		$return->cookies = $this;
	}
}
