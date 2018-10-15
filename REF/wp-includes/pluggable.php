<?php
/**
 * These functions can be replaced via plugins.
 * If plugins do not redefine these functions, then these will be used instead.
 *
 * @package WordPress
 */

if ( ! function_exists( 'wp_set_current_user' ) ) {
	/**
	 * Changes the current user by ID or name.
	 *
	 * Set $id to null and specify a name if you do not know a user's ID.
	 *
	 * Some WordPress functionality is based on the current user and not based on the signed in user.
	 * Therefore, it opens the ability to edit and perform actions on users who aren't signed in.
	 *
	 * @since  2.0.3
	 * @global WP_User $current_user The current user object which holds the user data.
	 *
	 * @param  int     $id   User ID.
	 * @param  string  $name User's username.
	 * @return WP_User Current user User object.
	 */
	function wp_set_current_user( $id, $name = '' )
	{
		global $current_user;

		// If `$id` matches the user who's already current, there's nothing to do.
		if ( isset( $current_user ) && ( $current_user instanceof WP_User ) && ( $id == $current_user->ID ) && ( NULL !== $id ) ) {
			return $current_user;
		}

		$current_user = new WP_User( $id, $name );
		setup_userdata( $current_user->ID );

		/**
		 * Fires after the current user is set.
		 *
		 * @since 2.0.1
		 */
		do_action( 'set_current_user' );

		return $current_user;
	}
}

if ( ! function_exists( 'wp_get_current_user' ) ) {
	/**
	 * Retrieve the current user object.
	 *
	 * Will set the current user, if the current user is not set.
	 * The current user will be set to the logged-in person.
	 * If no user is logged-in, then it will set the current user to 0, which is invalid and won't have any permissions.
	 *
	 * @since  2.0.3
	 * @see    _wp_get_current_user()
	 * @global WP_User $current_user Checks if the current user is set.
	 *
	 * @return WP_User Current WP_User instance.
	 */
	function wp_get_current_user()
	{
		return _wp_get_current_user();
	}
}

if ( ! function_exists( 'get_userdata' ) ) {
	/**
	 * Retrieve user info by user ID.
	 *
	 * @since 0.71
	 *
	 * @param  int           $user_id User ID.
	 * @return WP_User|false WP_User object on success, false on failure.
	 */
	function get_userdata( $user_id )
	{
		return get_user_by( 'id', $user_id );
	}
}

if ( ! function_exists( 'get_user_by' ) ) {
	/**
	 * Retrieve user info by a given field.
	 *
	 * @since 2.8.0
	 * @since 4.4.0 Added 'ID' as an alias of 'id' for the `$field` parameter.
	 *
	 * @param  string        $field The field to retrieve the user with.
	 *                              id | ID | slug | email | login.
	 * @param  int|string    $value A value for $field.
	 *                              A user ID, slug, email address, or login name.
	 * @return WP_User|false WP_User object on success, false on failure.
	 */
	function get_user_by( $field, $value )
	{
		$userdata = WP_User::get_data_by( $field, $value );

		if ( ! $userdata ) {
			return FALSE;
		}

		$user = new WP_User;
		$user->init( $userdata );
		return $user;
	}
}
