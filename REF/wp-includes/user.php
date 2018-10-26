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
 * Retrieve user option that can be either per Site or per Network.
 *
 * If the user ID is not given, then the current user will be used instead.
 * If the user ID is given, then the user data will be retrieved.
 * The filter for the result, will also pass the original option name and finally the user data object as the third parameter.
 *
 * The option will first check for the per site name and then the per Network name.
 *
 * @since  2.0.0
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param  string $option     User option name.
 * @param  int    $user       Optional.
 *                            User ID.
 * @param  string $deprecated Use get_option() to check for an option in the options table.
 * @return mixed  User option value on success, false on failure.
 */
function get_user_option( $option, $user = 0, $deprecated = '' )
{
	global $wpdb;

	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '3.0.0' );
	}

	if ( empty( $user ) ) {
		$user = get_current_user_id();
	}

	if ( ! $user = get_userdata( $user ) ) {
		return FALSE;
	}

	$prefix = $wpdb->get_blog_prefix();

	$result = $user->has_prop( $prefix . $option )
		? $user->get( $prefix . $option )
		: ( $user->has_prop( $option )
			? $user->get( $option )
			: FALSE );

	/**
	 * Filters a specific user option value.
	 *
	 * The dynamic portion of the hook name, `$option`, refers to the user option name.
	 *
	 * @since 2.5.0
	 *
	 * @param mixed   $result Value for the user's option.
	 * @param string  $option Name of the option being retrieved.
	 * @param WP_User $user   WP_User object of the user whose option is being retrieved.
	 */
	return apply_filters( "get_user_option_{$option}", $result, $option, $user );
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
 * Sanitize user field based on context.
 *
 * Possible context values are: 'raw', 'edit', 'db', 'display', 'attribute' and 'js'.
 * The 'display' context is used by default.
 * 'attribute' and 'js' contexts are treated like 'display' when calling for filters.
 *
 * @since 2.3.0
 *
 * @param  string $field   The user Object field name.
 * @param  mixed  $value   The user Object value.
 * @param  int    $user_id User ID.
 * @param  string $context How to sanitize user fields.
 *                         Looks for 'raw', 'edit', 'db', 'display', 'attribute' and 'js'.
 * @return mixed  Sanitized value.
 */
function sanitize_user_field( $field, $value, $user_id, $context )
{
	$int_fields = array( 'ID' );

	if ( in_array( $field, $int_fields ) ) {
		$value = ( int ) $value;
	}

	if ( 'raw' == $context ) {
		return $value;
	}

	if ( ! is_string ( $value ) && ! is_numeric( $value ) ) {
		return $value;
	}

	$prefixed = FALSE !== strpos( $field, 'user_' );

	if ( 'edit' == $context ) {
		$value = $prefixed
			? // This filter is documented in wp-includes/post.php
				apply_filters( "edit_{$field}", $value, $user_id )
			: /**
			   * Filters a user field value in the 'edit' context.
			   *
			   * The dynamic portion of the hook name, `$field`, refers to the prefixed user field being filtered, such as 'user_login', 'user_email', 'first_name', etc.
			   *
			   * @since 2.9.0
			   *
			   * @param mixed $value   Value of the prefixed user field.
			   * @param int   $user_id User ID.
			   */
				apply_filters( "edit_user_{$field}", $value, $user_id );

		$value = 'description' == $field
			? esc_html( $value )
			: esc_attr( $value );
	} elseif ( 'db' == $context ) {
		$value = $prefixed
			? // This filter is documented in wp-includes/post.php
				apply_filters( "pre_{$field}", $value )
			: /**
			   * Filters the value of a user field in the 'db' context.
			   *
			   * The dynamic portion of the hook name, `$field`, refers to the prefixed user field being filtered, such as 'user_login', 'user_email', 'first_name', etc.
			   *
			   * @since 2.9.0
			   *
			   * @param mixed $value Value of the prefixed user field.
			   */
				apply_filters( "pre_user_{$field}", $value );
	} else {
		// Use display filters by default.
		$value = $prefixed
			? // This filter is documented in wp-includes/post.php
				apply_filters( "{$field}", $value, $user_id, $context )
			: /**
			   * Filters the value of a user field in a standard context.
			   *
			   * The dynamic portion of the hook name, `$field`, refers to the prefixed user field being filtered, such as 'user_login', 'user_email', 'first_name', etc.
			   *
			   * @since 2.9.0
			   *
			   * @param mixed  $value   The user object value to sanitize.
			   * @param int    $user_id User ID.
			   * @param string $context The context to filter within.
			   */
				apply_filters( "user_{$field}", $value, $user_id, $context );
	}

	if ( 'user_url' == $field ) {
		$value = esc_url( $value );
	}

	if ( 'attribute' == $context ) {
		$value = esc_attr( $value );
	} elseif ( 'js' == $context ) {
		$value = esc_js( $value );
	}

	return $value;
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
