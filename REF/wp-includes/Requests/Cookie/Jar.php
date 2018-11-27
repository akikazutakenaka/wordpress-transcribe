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
}
