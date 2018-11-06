<?php
/**
 * User API: WP_User class
 *
 * @package WordPress
 * @subpackage Users
 * @since 4.4.0
 */

/**
 * Core class used to implement the WP_User object.
 *
 * @since 2.0.0
 *
 * @property string $nickname
 * @property string $description
 * @property string $user_description
 * @property string $first_name
 * @property string $user_firstname
 * @property string $last_name
 * @property string $user_lastname
 * @property string $user_login
 * @property string $user_pass
 * @property string $user_nicename
 * @property string $user_email
 * @property string $user_url
 * @property string $user_registered
 * @property string $user_activation_key
 * @property string $user_status
 * @property int    $user_level
 * @property string $display_name
 * @property string $spam
 * @property string $deleted
 * @property string $locale
 * @property string $rich_editing
 * @property string $syntax_highlighting
 */
class WP_User {
	// refactored. public $data;
	// :
	// refactored. public function __get( $key ) {}

	/**
	 * Magic method for setting custom user fields.
	 *
	 * This method does not update custom fields in the database. It only stores
	 * the value on the WP_User instance.
	 *
	 * @since 3.3.0
	 *
	 * @param string $key   User meta key.
	 * @param mixed  $value User meta value.
	 */
	public function __set( $key, $value ) {
		if ( 'id' == $key ) {
			_deprecated_argument( 'WP_User->id', '2.1.0',
				sprintf(
					/* translators: %s: WP_User->ID */
					__( 'Use %s instead.' ),
					'<code>WP_User->ID</code>'
				)
			);
			$this->ID = $value;
			return;
		}

		$this->data->$key = $value;
	}

	/**
	 * Magic method for unsetting a certain custom field.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key User meta key to unset.
	 */
	public function __unset( $key ) {
		if ( 'id' == $key ) {
			_deprecated_argument( 'WP_User->id', '2.1.0',
				sprintf(
					/* translators: %s: WP_User->ID */
					__( 'Use %s instead.' ),
					'<code>WP_User->ID</code>'
				)
			);
		}

		if ( isset( $this->data->$key ) ) {
			unset( $this->data->$key );
		}

		if ( isset( self::$back_compat_keys[ $key ] ) ) {
			unset( self::$back_compat_keys[ $key ] );
		}
	}

	// refactored. public function exists() {}
	// :
	// refactored. public function has_prop( $key ) {}

	/**
	 * Return an array representation.
	 *
	 * @since 3.5.0
	 *
	 * @return array Array representation.
	 */
	public function to_array() {
		return get_object_vars( $this->data );
	}

	/**
	 * Makes private/protected methods readable for backward compatibility.
	 *
	 * @since 4.3.0
	 *
	 * @param callable $name      Method to call.
	 * @param array    $arguments Arguments to pass when calling.
	 * @return mixed|false Return value of the callback, false otherwise.
	 */
	public function __call( $name, $arguments ) {
		if ( '_init_caps' === $name ) {
			return call_user_func_array( array( $this, $name ), $arguments );
		}
		return false;
	}

	/**
	 * Set up capability object properties.
	 *
	 * Will set the value for the 'cap_key' property to current database table
	 * prefix, followed by 'capabilities'. Will then check to see if the
	 * property matching the 'cap_key' exists and is an array. If so, it will be
	 * used.
	 *
	 * @since 2.1.0
	 * @deprecated 4.9.0 Use WP_User::for_site()
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $cap_key Optional capability key
	 */
	protected function _init_caps( $cap_key = '' ) {
		global $wpdb;

		_deprecated_function( __METHOD__, '4.9.0', 'WP_User::for_site()' );

		if ( empty( $cap_key ) ) {
			$this->cap_key = $wpdb->get_blog_prefix( $this->site_id ) . 'capabilities';
		} else {
			$this->cap_key = $cap_key;
		}

		$this->caps = $this->get_caps_data();

		$this->get_role_caps();
	}

	// refactored. public function get_role_caps() {}

	/**
	 * Add role to user.
	 *
	 * Updates the user's meta data option with capabilities and roles.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role Role name.
	 */
	public function add_role( $role ) {
		if ( empty( $role ) ) {
			return;
		}

		$this->caps[$role] = true;
		update_user_meta( $this->ID, $this->cap_key, $this->caps );
		$this->get_role_caps();
		$this->update_user_level_from_caps();

		/**
		 * Fires immediately after the user has been given a new role.
		 *
		 * @since 4.3.0
		 *
		 * @param int    $user_id The user ID.
		 * @param string $role    The new role.
		 */
		do_action( 'add_user_role', $this->ID, $role );
	}

	/**
	 * Remove role from user.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role Role name.
	 */
	public function remove_role( $role ) {
		if ( !in_array($role, $this->roles) )
			return;
		unset( $this->caps[$role] );
		update_user_meta( $this->ID, $this->cap_key, $this->caps );
		$this->get_role_caps();
		$this->update_user_level_from_caps();

		/**
		 * Fires immediately after a role as been removed from a user.
		 *
		 * @since 4.3.0
		 *
		 * @param int    $user_id The user ID.
		 * @param string $role    The removed role.
		 */
		do_action( 'remove_user_role', $this->ID, $role );
	}

	/**
	 * Set the role of the user.
	 *
	 * This will remove the previous roles of the user and assign the user the
	 * new one. You can set the role to an empty string and it will remove all
	 * of the roles from the user.
	 *
	 * @since 2.0.0
	 *
	 * @param string $role Role name.
	 */
	public function set_role( $role ) {
		if ( 1 == count( $this->roles ) && $role == current( $this->roles ) )
			return;

		foreach ( (array) $this->roles as $oldrole )
			unset( $this->caps[$oldrole] );

		$old_roles = $this->roles;
		if ( !empty( $role ) ) {
			$this->caps[$role] = true;
			$this->roles = array( $role => true );
		} else {
			$this->roles = false;
		}
		update_user_meta( $this->ID, $this->cap_key, $this->caps );
		$this->get_role_caps();
		$this->update_user_level_from_caps();

		/**
		 * Fires after the user's role has changed.
		 *
		 * @since 2.9.0
		 * @since 3.6.0 Added $old_roles to include an array of the user's previous roles.
		 *
		 * @param int    $user_id   The user ID.
		 * @param string $role      The new role.
		 * @param array  $old_roles An array of the user's previous roles.
		 */
		do_action( 'set_user_role', $this->ID, $role, $old_roles );
	}

	/**
	 * Choose the maximum level the user has.
	 *
	 * Will compare the level from the $item parameter against the $max
	 * parameter. If the item is incorrect, then just the $max parameter value
	 * will be returned.
	 *
	 * Used to get the max level based on the capabilities the user has. This
	 * is also based on roles, so if the user is assigned the Administrator role
	 * then the capability 'level_10' will exist and the user will get that
	 * value.
	 *
	 * @since 2.0.0
	 *
	 * @param int $max Max level of user.
	 * @param string $item Level capability name.
	 * @return int Max Level.
	 */
	public function level_reduction( $max, $item ) {
		if ( preg_match( '/^level_(10|[0-9])$/i', $item, $matches ) ) {
			$level = intval( $matches[1] );
			return max( $max, $level );
		} else {
			return $max;
		}
	}

	/**
	 * Update the maximum user level for the user.
	 *
	 * Updates the 'user_level' user metadata (includes prefix that is the
	 * database table prefix) with the maximum user level. Gets the value from
	 * the all of the capabilities that the user has.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	public function update_user_level_from_caps() {
		global $wpdb;
		$this->user_level = array_reduce( array_keys( $this->allcaps ), array( $this, 'level_reduction' ), 0 );
		update_user_meta( $this->ID, $wpdb->get_blog_prefix() . 'user_level', $this->user_level );
	}

	/**
	 * Add capability and grant or deny access to capability.
	 *
	 * @since 2.0.0
	 *
	 * @param string $cap Capability name.
	 * @param bool $grant Whether to grant capability to user.
	 */
	public function add_cap( $cap, $grant = true ) {
		$this->caps[$cap] = $grant;
		update_user_meta( $this->ID, $this->cap_key, $this->caps );
		$this->get_role_caps();
		$this->update_user_level_from_caps();
	}

	/**
	 * Remove capability from user.
	 *
	 * @since 2.0.0
	 *
	 * @param string $cap Capability name.
	 */
	public function remove_cap( $cap ) {
		if ( ! isset( $this->caps[ $cap ] ) ) {
			return;
		}
		unset( $this->caps[ $cap ] );
		update_user_meta( $this->ID, $this->cap_key, $this->caps );
		$this->get_role_caps();
		$this->update_user_level_from_caps();
	}

	/**
	 * Remove all of the capabilities of the user.
	 *
	 * @since 2.1.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	public function remove_all_caps() {
		global $wpdb;
		$this->caps = array();
		delete_user_meta( $this->ID, $this->cap_key );
		delete_user_meta( $this->ID, $wpdb->get_blog_prefix() . 'user_level' );
		$this->get_role_caps();
	}

	// refactored. public function has_cap( $cap ) {}
	// refactored. public function translate_level_to_cap( $level ) {}

	/**
	 * Set the site to operate on. Defaults to the current site.
	 *
	 * @since 3.0.0
	 * @deprecated 4.9.0 Use WP_User::for_site()
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $blog_id Optional. Site ID, defaults to current site.
	 */
	public function for_blog( $blog_id = '' ) {
		_deprecated_function( __METHOD__, '4.9.0', 'WP_User::for_site()' );

		$this->for_site( $blog_id );
	}

	// refactored. public function for_site( $site_id = '' ) {}

	/**
	 * Gets the ID of the site for which the user's capabilities are currently initialized.
	 *
	 * @since 4.9.0
	 *
	 * @return int Site ID.
	 */
	public function get_site_id() {
		return $this->site_id;
	}

	// refactored. private function get_caps_data() {}
}
