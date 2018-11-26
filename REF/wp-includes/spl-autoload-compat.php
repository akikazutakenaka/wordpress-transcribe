<?php
/**
 * Polyfill for SPL autoload feature.
 * This file is separate to prevent compiler notices on the deprecated __autoload() function.
 *
 * See https://core.trac.wordpress.org/ticket/41134
 *
 * @package PHP
 * @access  private
 */

if ( ! function_exists( 'spl_autoload_register' ) ) {
	$_wp_spl_autoloaders = array();

	/**
	 * Registers a function to be autoloaded.
	 *
	 * @since 4.6.0
	 *
	 * @param callable $autoload_function The function to register.
	 * @param bool     $throw             Optional.
	 *                                    Whether the function should throw an exception if the function isn't callable.
	 *                                    Default true.
	 * @param bool     $prepend           Whether the function should be prepended to the stack.
	 *                                    Default false.
	 */
	function spl_autoload_register( $autoload_function, $throw = TRUE, $prepend = FALSE )
	{
		if ( $throw && ! is_callable( $autoload_function ) ) {
			// String not translated to match PHP core.
			throw new Exception( 'Function not callable' );
		}

		global $_wp_spl_autoloaders;

		// Don't allow multiple registration.
		if ( in_array( $autoload_function, $_wp_spl_autoloaders ) ) {
			return;
		}

		if ( $prepend ) {
			array_unshift( $_wp_spl_autoloaders, $autoload_function );
		} else {
			$_wp_spl_autoloaders[] = $autoload_function;
		}
	}
}
