<?php
/**
 * User API: WP_Role class
 *
 * @package    WordPress
 * @subpackage Users
 * @since      4.4.0
 */

/**
 * Core class used to extend the user roles API.
 *
 * @since 2.0.0
 */
class WP_Role
{
	/**
	 * Role name.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	public $name;

	/**
	 * List of capabilities the role contains.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $capabilities;

	/**
	 * Constructor - Set up object properties.
	 *
	 * The list of capabilities, must have the key as the name of the capability and the value a boolean of whether it is granted to the role.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role         Role name.
	 * @param array  $capabilities List of capabilities.
	 */
	public function __construct( $role, $capabilities )
	{
		$this->name = $role;
		$this->capabilities = $capabilities;
	}
}
