<?php
/**
 * Core User Role & Capabilities API
 *
 * @package    WordPress
 * @subpackage Users
 */

/**
 * Whether the current user has a specific capability.
 *
 * While checking against particular roles in place of a capability is supported in part, this practice is discouraged as it may produce unreliable results.
 *
 * Note: Will always return true if the current user is a super admin, unless specifically denied.
 *
 * @since 2.0.0
 * @see   WP_User::has_cap()
 * @see   map_meta_cap()
 *
 * @param  string $capability Capability name.
 * @param  int    $object_id  Optional.
 *                            ID of the specific object to check against if `$capability` is a "meta" cap.
 *                            "Meta" capabilities, e.g. 'edit_post', 'edit_user', etc., are capabilities used by map_meta_cap() to map to other "primitive" capabilities, e.g. 'edit_posts', 'edit_other_posts', etc.
 *                            Accessed via func_get_args() and passed to WP_User::has_cap(), then map_meta_cap().
 * @return bool   Whether the current user has the given capability.
 *                If `$capability` is a meta cap and `$object_id` is passed, whether the current user has the given meta capability for the given object.
 */
function current_user_can( $capability )
{
	$current_user = wp_get_current_user();

	if ( empty( $current_user ) ) {
		return FALSE;
	}

	$args = array_slice( func_get_args(), 1 );
	$args = array_merge( array( $capability ), $args );
	return call_user_func_array( array( $current_user, 'has_cap' ), $args );
}

/**
 * Retrieves the global WP_Roles instance and instantiates it if necessary.
 *
 * @since  4.3.0
 * @global WP_Roles $wp_roles WP_Roles global instance.
 *
 * @return WP_Roles WP_Roles global instance if not already instantiated.
 */
function wp_roles()
{
	global $wp_roles;

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}

	return $wp_roles;
}

/**
 * Retrieve role object.
 *
 * @since 2.0.0
 *
 * @param  string       $role Role name.
 * @return WP_Role|null WP_Role object if found, null if the role does not exist.
 */
function get_role( $role )
{
	return wp_roles()->get_role( $role );
}
