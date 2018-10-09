<?php
/**
 * Core User API
 *
 * @package    WordPress
 * @subpackage Users
 */

/**
 * Retrieves the current user object.
 *
 * Will set the current user, if the current user is not set.
 * The current user will be set to the logged-in person.
 * If no user is logged-in, then it will set the current user to 0, which is invalid and won't have any permissions.
 *
 * This function is used by the pluggable functions wp_get_current_user() and get_currentuserinfo(), the latter of which is deprecated but used for backward compatibility.
 *
 * @since  4.5.0
 * @access private
 * @see    wp_get_current_user()
 * @global WP_User $current_user Checks if the current user is set.
 *
 * @return WP_User Current WP_User instance.
 */
function _wp_get_current_user()
{
	global $current_user;

	if ( ! empty( $current_user ) ) {
		if ( $current_user instanceof WP_User ) {
			// @NOW 014 -> wp-includes/class-wp-user.php
		}
	}
}
