<?php
/**
 * HTTP Proxy connection interface
 *
 * @package    Requests
 * @subpackage Proxy
 * @since      1.6
 */

/**
 * HTTP Proxy connection interface.
 *
 * Provides a handler for connection via an HTTP proxy.
 *
 * @package    Requests
 * @subpackage Proxy
 * @since      1.6
 */
class Requests_Proxy_HTTP implements Requests_Proxy
{
	/**
	 * Proxy host and port.
	 *
	 * Notation: "host:port" (e.g. 127.0.0.1:8080 or someproxy.com:3128)
	 *
	 * @var string
	 */
	public $proxy;

	/**
	 * Username.
	 *
	 * @var string
	 */
	public $user;

	/**
	 * Password.
	 *
	 * @var string
	 */
	public $pass;

	/**
	 * Do we need to authenticate?
	 * (i.e. username & password have been provided)
	 *
	 * @var bool
	 */
	public $use_authentication;

	/**
	 * Constructor.
	 *
	 * @since  1.6
	 * @throws Requests_Exception On incorrect number of arguments (`authbasicbadargs`)
	 *
	 * @param array|null $args Array of user and password.
	 *                         Must have exactly two elements.
	 */
	public function __construct( $args = NULL )
	{
		if ( is_string( $args ) ) {
			$this->proxy = $args;
		} elseif ( is_array( $args ) ) {
			if ( count( $args ) == 1 ) {
				list( $this->proxy ) = $args;
			} elseif ( count( $args ) == 3 ) {
				list( $this->proxy, $this->user, $this->pass ) = $args;
				$this->use_authentication = TRUE;
			} else {
				throw new Requests_Exception( 'Invalid number of arguments', 'proxyhttpbadargs' );
			}
		}
	}
}
