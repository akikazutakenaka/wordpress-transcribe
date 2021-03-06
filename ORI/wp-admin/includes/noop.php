<?php
/**
 * Noop functions for load-scripts.php and load-styles.php.
 *
 * @package WordPress
 * @subpackage Administration
 * @since 4.4.0
 */

// refactored. function __() {}
// :
// refactored. function get_option() {}

/**
 * @ignore
 */
function is_lighttpd_before_150() {}

// refactored. function add_action() {}
// :
// refactored. function site_url() {}

/**
 * @ignore
 */
function admin_url() {}

// refactored. function home_url() {}
// refactored. function includes_url() {}

/**
 * @ignore
 */
function wp_guess_url() {}

if ( ! function_exists( 'json_encode' ) ) :
/**
 * @ignore
 */
function json_encode() {}
endif;

function get_file( $path ) {

	if ( function_exists('realpath') ) {
		$path = realpath( $path );
	}

	if ( ! $path || ! @is_file( $path ) ) {
		return '';
	}

	return @file_get_contents( $path );
}