<?php
/**
 * User API: WP_Roles class
 *
 * @package WordPress
 * @subpackage Users
 * @since 4.4.0
 */

/**
 * Core class used to implement a user roles API.
 *
 * The role option is simple, the structure is organized by role name that store
 * the name in value of the 'name' key. The capabilities are stored as an array
 * in the value of the 'capability' key.
 *
 *     array (
 *    		'rolename' => array (
 *    			'name' => 'rolename',
 *    			'capabilities' => array()
 *    		)
 *     )
 *
 * @since 2.0.0
 */
class WP_Roles {
	// refactored. public $roles;
	// refactored. public $role_objects = array();
	// refactored. public $role_names = array();
	// refactored. public $role_key;
	// refactored. public $use_db = true;
	// refactored. protected $site_id = 0;
	// refactored. public function __construct( $site_id = null ) {}

	/**
	 * Make private/protected methods readable for backward compatibility.
	 *
	 * @since 4.0.0
	 *
	 * @param callable $name      Method to call.
	 * @param array    $arguments Arguments to pass when calling.
	 * @return mixed|false Return value of the callback, false otherwise.
	 */
	public function __call( $name, $arguments ) {
		if ( '_init' === $name ) {
			return call_user_func_array( array( $this, $name ), $arguments );
		}
		return false;
	}

	/**
	 * Set up the object properties.
	 *
	 * The role key is set to the current prefix for the $wpdb object with
	 * 'user_roles' appended. If the $wp_user_roles global is set, then it will
	 * be used and the role option will not be updated or used.
	 *
	 * @since 2.1.0
	 * @deprecated 4.9.0 Use WP_Roles::for_site()
	 */
	protected function _init() {
		_deprecated_function( __METHOD__, '4.9.0', 'WP_Roles::for_site()' );

		$this->for_site();
	}

	/**
	 * Reinitialize the object
	 *
	 * Recreates the role objects. This is typically called only by switch_to_blog()
	 * after switching wpdb to a new site ID.
	 *
	 * @since 3.5.0
	 * @deprecated 4.7.0 Use WP_Roles::for_site()
	 */
	public function reinit() {
		_deprecated_function( __METHOD__, '4.7.0', 'WP_Roles::for_site()' );

		$this->for_site();
	}

	/**
	 * Add role name with capabilities to list.
	 *
	 * Updates the list of roles, if the role doesn't already exist.
	 *
	 * The capabilities are defined in the following format `array( 'read' => true );`
	 * To explicitly deny a role a capability you set the value for that capability to false.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role Role name.
	 * @param string $display_name Role display name.
	 * @param array $capabilities List of role capabilities in the above format.
	 * @return WP_Role|void WP_Role object, if role is added.
	 */
	public function add_role( $role, $display_name, $capabilities = array() ) {
		if ( empty( $role ) || isset( $this->roles[ $role ] ) ) {
			return;
		}

		$this->roles[$role] = array(
			'name' => $display_name,
			'capabilities' => $capabilities
			);
		if ( $this->use_db )
			update_option( $this->role_key, $this->roles );
		$this->role_objects[$role] = new WP_Role( $role, $capabilities );
		$this->role_names[$role] = $display_name;
		return $this->role_objects[$role];
	}

	/**
	 * Remove role by name.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role Role name.
	 */
	public function remove_role( $role ) {
		if ( ! isset( $this->role_objects[$role] ) )
			return;

		unset( $this->role_objects[$role] );
		unset( $this->role_names[$role] );
		unset( $this->roles[$role] );

		if ( $this->use_db )
			update_option( $this->role_key, $this->roles );

		if ( get_option( 'default_role' ) == $role )
			update_option( 'default_role', 'subscriber' );
	}

	/**
	 * Add capability to role.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role Role name.
	 * @param string $cap Capability name.
	 * @param bool $grant Optional, default is true. Whether role is capable of performing capability.
	 */
	public function add_cap( $role, $cap, $grant = true ) {
		if ( ! isset( $this->roles[$role] ) )
			return;

		$this->roles[$role]['capabilities'][$cap] = $grant;
		if ( $this->use_db )
			update_option( $this->role_key, $this->roles );
	}

	/**
	 * Remove capability from role.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role Role name.
	 * @param string $cap Capability name.
	 */
	public function remove_cap( $role, $cap ) {
		if ( ! isset( $this->roles[$role] ) )
			return;

		unset( $this->roles[$role]['capabilities'][$cap] );
		if ( $this->use_db )
			update_option( $this->role_key, $this->roles );
	}

	/**
	 * Retrieve role object by name.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role Role name.
	 * @return WP_Role|null WP_Role object if found, null if the role does not exist.
	 */
	public function get_role( $role ) {
		if ( isset( $this->role_objects[$role] ) )
			return $this->role_objects[$role];
		else
			return null;
	}

	/**
	 * Retrieve list of role names.
	 *
	 * @since 2.0.0
	 *
	 * @return array List of role names.
	 */
	public function get_names() {
		return $this->role_names;
	}

	// refactored. public function is_role( $role ) {}
	// refactored. public function init_roles() {}
	// refactored. public function for_site( $site_id = null ) {}

	/**
	 * Gets the ID of the site for which roles are currently initialized.
	 *
	 * @since 4.9.0
	 *
	 * @return int Site ID.
	 */
	public function get_site_id() {
		return $this->site_id;
	}

	// refactored. protected function get_roles_data() {}
}
