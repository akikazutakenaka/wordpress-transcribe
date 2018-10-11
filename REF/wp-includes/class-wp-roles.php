<?php
/**
 * User API: WP_Roles class
 *
 * @package    WordPress
 * @subpackage Users
 * @since      4.4.0
 */

/**
 * Core class used to implement a user roles API.
 *
 * The role option is simple, the structure is organized by role name that store the name in value of the 'name' key.
 * The capabilities are stored as an array in the value of the 'capability' key.
 *
 *     [
 *         'rolename' => [
 *             'name'         => 'rolename',
 *             'capabilities' => []
 *         ]
 *     ]
 *
 * @since 2.0.0
 */
class WP_Roles
{
	/**
	 * List of roles and capabilities.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $roles;

	/**
	 * List of the role objects.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $role_objects = [];

	/**
	 * List of role names.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $role_names = [];

	/**
	 * Option name for storing role list.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	public $role_key;

	/**
	 * Whether to use the database for retrieval and storage.
	 *
	 * @since 2.1.0
	 *
	 * @var bool
	 */
	public $use_db = TRUE;

	/**
	 * The site ID the roles are initialized for.
	 *
	 * @since 4.9.0
	 *
	 * @var int
	 */
	protected $site_id = 0;

// @NOW 019
}
