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
// @NOW 018 -> wp-includes/class-wp-roles.php
	}
}
