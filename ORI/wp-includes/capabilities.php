<?php
/**
 * Core User Role & Capabilities API
 *
 * @package WordPress
 * @subpackage Users
 */

// refactored. function map_meta_cap( $cap, $user_id ) {}
// refactored. function current_user_can( $capability ) {}

/**
 * Whether the current user has a specific capability for a given site.
 *
 * @since 3.0.0
 *
 * @param int    $blog_id    Site ID.
 * @param string $capability Capability name.
 * @return bool Whether the user has the given capability.
 */
function current_user_can_for_blog( $blog_id, $capability ) {
	$switched = is_multisite() ? switch_to_blog( $blog_id ) : false;

	$current_user = wp_get_current_user();

	if ( empty( $current_user ) ) {
		if ( $switched ) {
			restore_current_blog();
		}
		return false;
	}

	$args = array_slice( func_get_args(), 2 );
	$args = array_merge( array( $capability ), $args );

	$can = call_user_func_array( array( $current_user, 'has_cap' ), $args );

	if ( $switched ) {
		restore_current_blog();
	}

	return $can;
}

/**
 * Whether the author of the supplied post has a specific capability.
 *
 * @since 2.9.0
 *
 * @param int|WP_Post $post       Post ID or post object.
 * @param string      $capability Capability name.
 * @return bool Whether the post author has the given capability.
 */
function author_can( $post, $capability ) {
	if ( !$post = get_post($post) )
		return false;

	$author = get_userdata( $post->post_author );

	if ( ! $author )
		return false;

	$args = array_slice( func_get_args(), 2 );
	$args = array_merge( array( $capability ), $args );

	return call_user_func_array( array( $author, 'has_cap' ), $args );
}

// refactored. function user_can( $user, $capability ) {}
// :
// refactored. function get_role( $role ) {}

/**
 * Add role, if it does not exist.
 *
 * @since 2.0.0
 *
 * @param string $role Role name.
 * @param string $display_name Display name for role.
 * @param array $capabilities List of capabilities, e.g. array( 'edit_posts' => true, 'delete_posts' => false );
 * @return WP_Role|null WP_Role object if role is added, null if already exists.
 */
function add_role( $role, $display_name, $capabilities = array() ) {
	if ( empty( $role ) ) {
		return;
	}
	return wp_roles()->add_role( $role, $display_name, $capabilities );
}

/**
 * Remove role, if it exists.
 *
 * @since 2.0.0
 *
 * @param string $role Role name.
 */
function remove_role( $role ) {
	wp_roles()->remove_role( $role );
}

// refactored. function get_super_admins() {}
// refactored. function is_super_admin( $user_id = false ) {}

/**
 * Grants Super Admin privileges.
 *
 * @since 3.0.0
 *
 * @global array $super_admins
 *
 * @param int $user_id ID of the user to be granted Super Admin privileges.
 * @return bool True on success, false on failure. This can fail when the user is
 *              already a super admin or when the `$super_admins` global is defined.
 */
function grant_super_admin( $user_id ) {
	// If global super_admins override is defined, there is nothing to do here.
	if ( isset( $GLOBALS['super_admins'] ) || ! is_multisite() ) {
		return false;
	}

	/**
	 * Fires before the user is granted Super Admin privileges.
	 *
	 * @since 3.0.0
	 *
	 * @param int $user_id ID of the user that is about to be granted Super Admin privileges.
	 */
	do_action( 'grant_super_admin', $user_id );

	// Directly fetch site_admins instead of using get_super_admins()
	$super_admins = get_site_option( 'site_admins', array( 'admin' ) );

	$user = get_userdata( $user_id );
	if ( $user && ! in_array( $user->user_login, $super_admins ) ) {
		$super_admins[] = $user->user_login;
		update_site_option( 'site_admins' , $super_admins );

		/**
		 * Fires after the user is granted Super Admin privileges.
		 *
		 * @since 3.0.0
		 *
		 * @param int $user_id ID of the user that was granted Super Admin privileges.
		 */
		do_action( 'granted_super_admin', $user_id );
		return true;
	}
	return false;
}

/**
 * Revokes Super Admin privileges.
 *
 * @since 3.0.0
 *
 * @global array $super_admins
 *
 * @param int $user_id ID of the user Super Admin privileges to be revoked from.
 * @return bool True on success, false on failure. This can fail when the user's email
 *              is the network admin email or when the `$super_admins` global is defined.
 */
function revoke_super_admin( $user_id ) {
	// If global super_admins override is defined, there is nothing to do here.
	if ( isset( $GLOBALS['super_admins'] ) || ! is_multisite() ) {
		return false;
	}

	/**
	 * Fires before the user's Super Admin privileges are revoked.
	 *
	 * @since 3.0.0
	 *
	 * @param int $user_id ID of the user Super Admin privileges are being revoked from.
	 */
	do_action( 'revoke_super_admin', $user_id );

	// Directly fetch site_admins instead of using get_super_admins()
	$super_admins = get_site_option( 'site_admins', array( 'admin' ) );

	$user = get_userdata( $user_id );
	if ( $user && 0 !== strcasecmp( $user->user_email, get_site_option( 'admin_email' ) ) ) {
		if ( false !== ( $key = array_search( $user->user_login, $super_admins ) ) ) {
			unset( $super_admins[$key] );
			update_site_option( 'site_admins', $super_admins );

			/**
			 * Fires after the user's Super Admin privileges are revoked.
			 *
			 * @since 3.0.0
			 *
			 * @param int $user_id ID of the user Super Admin privileges were revoked from.
			 */
			do_action( 'revoked_super_admin', $user_id );
			return true;
		}
	}
	return false;
}

/**
 * Filters the user capabilities to grant the 'install_languages' capability as necessary.
 *
 * A user must have at least one out of the 'update_core', 'install_plugins', and
 * 'install_themes' capabilities to qualify for 'install_languages'.
 *
 * @since 4.9.0
 *
 * @param array $allcaps An array of all the user's capabilities.
 * @return array Filtered array of the user's capabilities.
 */
function wp_maybe_grant_install_languages_cap( $allcaps ) {
	if ( ! empty( $allcaps['update_core'] ) || ! empty( $allcaps['install_plugins'] ) || ! empty( $allcaps['install_themes'] ) ) {
		$allcaps['install_languages'] = true;
	}

	return $allcaps;
}
