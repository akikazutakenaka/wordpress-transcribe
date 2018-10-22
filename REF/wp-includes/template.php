<?php
/**
 * Template WordPress Administration API.
 *
 * A Big Mess.
 * Also some neat functions that are nicely written.
 *
 * @package    WordPress
 * @subpackage Administration
 */

// Walker_Category_Checklist class
require_once( ABSPATH . 'wp-admin/includes/class-walker-category-checklist.php' );

// WP_Internal_Pointers class
require_once( ABSPATH . 'wp-admin/includes/class-wp-internal-pointers.php' );

/**
 * Register a settings error to be displayed to the user.
 *
 * Part of the Settings API.
 * Use this to show messages to users about settings validation problems, missing settings or anything else.
 *
 * Settings errors should be added inside the $sanitize_callback function defined in register_setting() for a given setting to give feedback about the submission.
 *
 * By default messages will show immediately after the submission that generated the error.
 * Additional calls to settings_errors() can be used to show errors even when the settings page is first accessed.
 *
 * @since  3.0.0
 * @global array $wp_settings_errors Storage array of errors registered during this pageload.
 *
 * @param string $setting Slug title of the setting to which this error applies.
 * @param string $code    Slug-name to identify the error.
 *                        Used as part of 'id' attribute in HTML output.
 * @param string $message The formatted message text to display to the user (will be shown inside styled `<div>` and `<p>` tags).
 * @param string $type    Optional.
 *                        Message type, controls HTML class.
 *                        Accepts 'error' or 'updated'.
 *                        Default 'error'.
 */
function add_settings_error( $setting, $code, $message, $type = 'error' )
{
	global $wp_settings_errors;
	$wp_settings_errors[] = array(
		'setting' => $setting,
		'code'    => $code,
		'message' => $message,
		'type'    => $type
	);
}
