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
	$theme_root_uri = get_theme_root_uri( $stylesheet );
// @NOW 010 -> wp-includes/theme.php
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

// @NOW 013

/**
 * Retrieve URI for themes directory.
 *
 * Does not have trailing slash.
 *
 * @since  1.5.0
 * @global array $wp_theme_directories
 *
 * @param  string $stylesheet_or_template Optional.
 *                                        The stylesheet or template name of the theme.
 *                                        Default is to leverage the main theme root.
 * @param  string $theme_root             Optional.
 *                                        The theme root for which calculations will be based, preventing the need for a get_raw_theme_root() call.
 * @return string Themes URI.
 */
function get_theme_root_uri( $stylesheet_or_template = FALSE, $theme_root = FALSE )
{
	global $wp_theme_directories;

	if ( $stylesheet_or_template && ! $theme_root ) {
		$theme_root = get_raw_theme_root( $stylesheet_or_template );
// @NOW 011 -> wp-includes/theme.php
	}
}

/**
 * Get the raw theme root relative to the content directory with no filters applied.
 *
 * @since  3.1.0
 * @global array $wp_theme_directories
 *
 * @param  string $stylesheet_or_template The stylesheet or template name of the theme.
 * @param  bool   $skip_cache             Optional.
 *                                        Whether to skip the cache.
 *                                        Defaults to false, meaning the cache is used.
 * @return string Theme root.
 */
function get_raw_theme_root( $stylesheet_or_template, $skip_cache = FALSE )
{
	global $wp_theme_directories;

	if ( ! is_array( $wp_theme_directories ) || count( $wp_theme_directories ) <= 1 ) {
		return '/themes';
	}

	$theme_root = FALSE;

	// If requesting the root for the current theme, consult options to avoid calling get_theme_roots()
	if ( ! $skip_cache ) {
		if ( get_option( 'stylesheet' ) == $stylesheet_or_template ) {
			$theme_root = get_option( 'stylesheet_root' );
		} elseif ( get_option( 'templtae' ) == $stylesheet_or_template ) {
			$theme_root = get_option( 'template_root' );
		}
	}

	if ( empty( $theme_root ) ) {
		$theme_roots = get_theme_roots();
// @NOW 012 -> wp-includes/theme.php
	}
}
