<?php
/**
 * WordPress implementation for PHP functions either missing from older PHP versions or not included by default.
 *
 * @package PHP
 * @access  private
 */

/**
 * JSON_PRETTY_PRINT was introduced in PHP 5.4
 * Defined here to prevent a notice when using it with wp_json_encode()
 */
if ( ! defined( 'JSON_PRETTY_PRINT' ) ) {
	define( 'JSON_PRETTY_PRINT', 128 );
}

// random_int was introduced in PHP 7.0
if ( ! function_exists( 'random_int' ) ) {
	require ABSPATH . WPINC . '/random_compat/random.php';
// @NOW 004 -> wp-includes/random_compat/random.php
}
