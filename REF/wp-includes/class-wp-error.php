<?php
/**
 * WordPress Error API.
 *
 * Contains the WP_Error class and the is_wp_error() function.
 *
 * @package WordPress
 */

/**
 * WordPress Error class.
 *
 * Container for checking for WordPress errors and error messages.
 * Return WP_Error and use is_wp_error() to check if this class is returned.
 * Many core WordPress functions pass this class in the event of an error and if not handled properly will result in code errors.
 *
 * @since 2.1.0
 */
class WP_Error
{
	/**
	 * Stores the list of errors.
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	public $errors = [];

	/**
	 * Stores the list of data for error codes.
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	public $error_data = [];

	// @NOW 021
}
