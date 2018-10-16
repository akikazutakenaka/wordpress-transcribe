<?php
/**
 * Core User API
 *
 * @package    WordPress
 * @subpackage Users
 */

//
// User option functions
//

/**
 * Get the current user's ID.
 *
 * @since MU (3.0.0)
 *
 * @return int The current user's ID, or 0 if no user is logged in.
 */
function get_current_user_id()
{
	if ( ! function_exists( 'wp_get_current_user' ) ) {
		return 0;
	}

	$user = wp_get_current_user();

	return isset( $user->ID )
		? ( int ) $user->ID
		: 0;
}

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

//
// Private helper functions
//

/**
 * Set up global user vars.
 *
 * Used by wp_set_current_user() for back compat.
 * Might be deprecated in the future.
 *
 * @since  2.0.4
 * @global string  $user_login    The user username for logging in.
 * @global WP_User $userdata      User data.
 * @global int     $user_level    The level of the user.
 * @global int     $user_ID       The ID of the user.
 * @global string  $user_email    The email address of the user.
 * @global string  $user_url      The url in the user's profile.
 * @global string  $user_identity The display name of the user.
 *
 * @param int $for_user_id Optional.
 *                         User ID to set up global data.
 */
function setup_userdata( $for_user_id = '' )
{
	global $user_login, $userdata, $user_level, $user_ID, $user_email, $user_url, $user_identity;

	if ( '' == $for_user_id ) {
		$for_user_id = get_current_user_id();
	}

	$user = get_userdata( $for_user_id );

	if ( ! $user ) {
		$user_ID    = 0;
		$user_level = 0;
		$userdata   = NULL;
		$user_login = $user_email = $user_url = $user_identity = '';
		return;
	}

	$user_ID       = ( int ) $user->ID;
	$user_level    = ( int ) $user->user_level;
	$userdata      = $user;
	$user_login    = $user->user_login;
	$user_email    = $user->user_email;
	$user_url      = $user->user_url;
	$user_identity = $user->display_name;
}

/**
 * Update all user caches.
 *
 * @since 3.0.0
 *
 * @param  WP_User   $user User object to be cached.
 * @return bool|null Returns false on failure.
 */
function update_user_caches( $user )
{
	if ( $user instanceof WP_User ) {
		if ( ! $user->exists() ) {
			return FALSE;
		}

		$user = $user->data;
	}

	wp_cache_add( $user->ID, $user, 'users' );
	wp_cache_add( $user->user_login, $user->ID, 'userlogins' );
	wp_cache_add( $user->user_email, $user->ID, 'useremail' );
	wp_cache_add( $user->user_nicename, $user->ID, 'userslugs' );
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
			return $current_user;
		}

		// Upgrade stdClass to WP_User
		if ( is_object( $current_user ) && isset( $current_user->ID ) ) {
			$cur_id = $current_user->ID;
			$current_user = NULL;
			wp_set_current_user( $cur_id );
			return $current_user;
		}

		/**
		 * $current_user has a junk value.
		 * Force to WP_User with ID 0.
		 */
		$current_user = NULL;
		wp_set_current_user( 0 );
		return $current_user;
	}

	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		wp_set_current_user( 0 );
		return $current_user;
	}

	/**
	 * Filters the current user.
	 *
	 * The default filters use this to determine the current user from the request's cookies, if available.
	 *
	 * Returning a value of false will effectively short-circuit setting the current user.
	 *
	 * @since 3.9.0
	 *
	 * @param int|bool $user_id User ID if one has been determined, false otherwise.
	 */
	$user_id = apply_filters( 'determine_current_user', FALSE );

	if ( ! $user_id ) {
		wp_set_current_user( 0 );
		return $current_user;
	}

	wp_set_current_user( $user_id );
	return $current_user;
}
