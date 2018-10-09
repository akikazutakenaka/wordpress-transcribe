<?php
/**
 * User API: WP_User class
 *
 * @package    WordPress
 * @subpackage Users
 * @since      4.4.0
 */

/**
 * Core class used to implement the WP_User object.
 *
 * @since 2.0.0
 */
class WP_User
{
	/**
	 * User data container.
	 *
	 * @since 2.0.0
	 *
	 * @var object
	 */
	public $data;

	/**
	 * The user's ID.
	 *
	 * @since 2.1.0
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * The individual capabilities the user has been given.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $caps = [];

	/**
	 * User metadata option name.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	public $cap_key;

	/**
	 * The roles the user is part of.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $roles = [];

	/**
	 * All capabilities the user has, including individual and role based.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $allcaps = [];

	/**
	 * The filter context applied to user data fields.
	 *
	 * @since 2.9.0
	 *
	 * @var string
	 */
	public $filter = NULL;

	/**
	 * The site ID the capabilities of this user are initialized for.
	 *
	 * @since 4.9.0
	 *
	 * @var int
	 */
	private $site_id = 0;

	/**
	 * @static
	 * @since  3.3.0
	 *
	 * @var array
	 */
	private static $back_compat_keys;

	// @NOW 015
}
