<?php
/**
 * Core User API
 *
 * @package    WordPress
 * @subpackage Users
 */

/**
 * Retrieve user meta field for a user.
 *
 * @since 3.0.0
 * @link  https://codex.wordpress.org/Function_Reference/get_user_meta
 *
 * @param  int    $user_id User ID.
 * @param  string $key     Optional.
 *                         The meta key to retrieve.
 *                         By default, returns data for all keys.
 * @param  bool   $single  Whether to return a single value.
 * @return mixed  Will be an array if $single is false.
 *                Will be value of meta data field if $single is true.
 */
function get_user_meta( $user_id, $key = '', $single = FALSE )
{
	return get_metadata( 'user', $user_id, $key, $single );
}

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
