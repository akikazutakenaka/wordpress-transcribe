<?php
/**
 * These functions can be replaced via plugins.
 * If plugins do not redefine these functions, then these will be used instead.
 *
 * @package WordPress
 */

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
