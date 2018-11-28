<?php
/**
 * Basic Authentication provider
 *
 * @package    Requests
 * @subpackage Authentication
 */

/**
 * Basic Authentication provider.
 *
 * Provides a handler for Basic HTTP authentication via the Authorization header.
 *
 * @package    Requests
 * @subpackage Authentication
 */
class Requests_Auth_Basic implements Requests_Auth
{
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
	 * Constructor.
	 *
	 * @throws Requests_Exception On incorrect number of arguments (`authbasicbadargs`).
	 *
	 * @param array|null $args Array of user and password.
	 *                         Must have exactly two elements.
	 */
	public function __construct( $args = NULL )
	{
		if ( is_array( $args ) ) {
			if ( count( $args ) !== 2 ) {
				throw new Requests_Exception( 'Invalid number of arguments.', 'authbasicbadargs' );
			}

			list( $this->user, $this->pass ) = $args;
		}
	}
}
