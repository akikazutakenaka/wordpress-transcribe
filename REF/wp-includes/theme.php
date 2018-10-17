<?php
/**
 * Theme, template, and stylesheet functions.
 *
 * @package    WordPress
 * @subpackage Theme
 */

/**
 * Retrieve name of the current stylesheet.
 *
 * The theme name that the administrator has currently set the front end theme as.
 *
 * For all intents and purposes, the template name and the stylesheet name are going to be the same for most cases.
 *
 * @since 1.5.0
 *
 * @return string Stylesheet name.
 */
function get_stylesheet()
{
	/**
	 * Filters the name of current stylesheet.
	 *
	 * @since 1.5.0
	 *
	 * @param string $stylesheet Name of the current stylesheet.
	 */
	return apply_filters( 'stylesheet', get_option( 'stylesheet' ) );
}

/**
 * Retrieve stylesheet directory URI.
 *
 * @since 1.5.0
 *
 * @return string
 */
function get_stylesheet_directory_uri()
{
	$stylesheet = str_replace( '%2F', '/', rawurlencode( get_stylesheet() ) );
// @NOW 010
}

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
