<?php
/**
 * Theme, template, and stylesheet functions.
 *
 * @package    WordPress
 * @subpackage Theme
 */

// @NOW 010

/**
 * Retrieves the URI of current theme stylesheet.
 *
 * The stylesheet file name is 'style.css' which is appended to the stylesheet directory URI path.
 * See get_stylesheet_directory_uri().
 *
 * @since 1.5.0
 *
 * @return string
 */
function get_stylesheet_uri()
{
	$stylesheet_dir_uri = get_stylesheet_directory_uri();
// @NOW 009 -> wp-includes/theme.php
}
