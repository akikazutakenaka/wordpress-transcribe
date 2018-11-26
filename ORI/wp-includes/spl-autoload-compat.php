<?php
/**
 * Polyfill for SPL autoload feature. This file is separate to prevent compiler notices
 * on the deprecated __autoload() function.
 *
 * See https://core.trac.wordpress.org/ticket/41134
 *
 * @package PHP
 * @access private
 */

if ( ! function_exists( 'spl_autoload_register' ) ) {
	$_wp_spl_autoloaders = array();

	/**
	 * Autoloader compatibility callback.
	 *
	 * @since 4.6.0
	 *
	 * @param string $classname Class to attempt autoloading.
	 */
	function __autoload( $classname ) {
		global $_wp_spl_autoloaders;
		foreach ( $_wp_spl_autoloaders as $autoloader ) {
			if ( ! is_callable( $autoloader ) ) {
				// Avoid the extra warning if the autoloader isn't callable.
				continue;
			}

			call_user_func( $autoloader, $classname );

			// If it has been autoloaded, stop processing.
			if ( class_exists( $classname, false ) ) {
				return;
			}
		}
	}

	// refactored. function spl_autoload_register( $autoload_function, $throw = true, $prepend = false ) {}

	/**
	 * Unregisters an autoloader function.
	 *
	 * @since 4.6.0
	 *
	 * @param callable $function The function to unregister.
	 * @return bool True if the function was unregistered, false if it could not be.
	 */
	function spl_autoload_unregister( $function ) {
		global $_wp_spl_autoloaders;
		foreach ( $_wp_spl_autoloaders as &$autoloader ) {
			if ( $autoloader === $function ) {
				unset( $autoloader );
				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieves the registered autoloader functions.
	 *
	 * @since 4.6.0
	 *
	 * @return array List of autoloader functions.
	 */
	function spl_autoload_functions() {
		return $GLOBALS['_wp_spl_autoloaders'];
	}
}
