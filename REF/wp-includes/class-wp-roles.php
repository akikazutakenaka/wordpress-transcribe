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

	/**
	 * Constructor.
	 *
	 * @since  2.0.0
	 * @since  4.9.0 The $site_id argument was added.
	 * @global array $wp_user_roles Used to set the 'roles' property value.
	 *
	 * @param int $site_id Site ID to initialize roles for.
	 *                     Default is the current site.
	 */
	public function __construct( $site_id = NULL )
	{
		global $wp_user_roles;
		$this->use_db = empty( $wp_user_roles );
		$this->for_site( $site_id );
	}

	/**
	 * Sets the site to operate on.
	 * Defaults to the current site.
	 *
	 * @since  4.9.0
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $site_id Site ID to initialize roles for.
	 *                     Default is the current site.
	 */
	public function for_site( $site_id = NULL )
	{
		global $wpdb;
		$this->site_id = ! empty( $site_id )
			? absint( $site_id )
			: get_current_blog_id();
		$this->role_key = $wpdb->get_blog_prefix( $this->site_id ) . 'user_roles';

		if ( ! empty( $this->roles ) && ! $this->use_db )
			return;

		$this->roles = $this->get_roles_data();
// @NOW 019 -> wp-includes/class-wp-roles.php
	}

	/**
	 * Gets the available roles data.
	 *
	 * @since  4.9.0
	 * @global array $wp_user_roles Used to set the 'roles' property value.
	 *
	 * @return array Roles array.
	 */
	protected function get_roles_data()
	{
		global $wp_user_roles;

		if ( ! empty( $wp_user_roles ) ) {
			return $wp_user_roles;
		}

		if ( is_multisite() && $this->site_id != get_current_blog_id() ) {
			remove_action( 'switch_blog', 'wp_switch_roles_and_user', 1 );
			$roles = get_blog_option( $this->site_id, $this->role_key, [] );
// @NOW 020 -> wp-includes/ms-blogs.php
		}
	}
}
