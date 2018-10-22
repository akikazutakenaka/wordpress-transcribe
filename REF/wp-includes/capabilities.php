<?php
/**
 * Core User Role & Capabilities API
 *
 * @package    WordPress
 * @subpackage Users
 */

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
