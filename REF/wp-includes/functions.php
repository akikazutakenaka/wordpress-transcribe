<?php
/**
 * Main WordPress API
 *
 * @package WordPress
 */

require( ABSPATH . WPINC . '/option.php' );

/**
 * Kill WordPress execution and display HTML message with error message.
 *
 * This function complements the `die()` PHP function.
 * The difference is that HTML will be displayed to the user.
 * It is recommended to use this function only when the execution should not continue any further.
 * It is not recommended to call this function very often, and try to handle as many errors as possible silently or more gracefully.
 *
 * As a shorthand, the desired HTTP response code may be passed as an integer to the `$title` parameter (the default title would apply) or the `$args` parameter.
 *
 * @since 2.0.4
 * @since 4.1.0 The `$title` and `$args` parameters were changed to optionally accept an integer to be used as the response code.
 *
 * @param string|WP_Error  $message Optional.
 *                                  Error message.
 *                                  If this is a WP_Error object, and not an Ajax or XML-RPC request, the error's messages are used.
 *                                  Default empty.
 * @param string|int       $title   Optional.
 *                                  Error title.
 *                                  If `$message` is a `WP_Error` object, error data with the key 'title' may be used to specify the title.
 *                                  If `$title` is an integer, then it is treated as the response code.
 *                                  Default empty.
 * @param string|array|int $args {
 *     Optional.
 *     Arguments to control behavior.
 *     If `$args` is an integer, then it is treated as the response code.
 *     Default empty array.
 *
 *     @type int    $response       The HTTP response code.
 *                                  Default 200 for Ajax requests, 500 otherwise.
 *     @type bool   $back_link      Whether to include a link to go back.
 *                                  Default false.
 *     @type string $text_direction The text direction.
 *                                  This is only useful internally, when WordPress is still loading and the site's locale is not set up yet.
 *                                  Accepts 'rtl'.
 *                                  Default is the value of is_rtl().
 * }
 */
function wp_die( $message = '', $title = '', $args = [] )
{
	if ( is_int( $args ) )
		$args = ['response' => $args];
	elseif ( is_int( $title ) ) {
		$args = ['response' => $title];
		$title = '';
	}

	if ( wp_doing_ajax() )
		/**
		 * Filters the callback for killing WordPress execution for Ajax requests.
		 *
		 * @since 3.4.0
		 *
		 * @param callable $function Callback function name.
		 */
		$function = apply_filters( 'wp_die_ajax_handler', '_ajax_wp_die_handler' );
	elseif ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
		/**
		 * Filters the callback for killing WordPress execution for XML-RPC requests.
		 *
		 * @since 3.4.0
		 *
		 * @param callable $function Callback function name.
		 */
		$function = apply_filters( 'wp_die_xmlrpc_handler', '_xmlrpc_wp_die_handler' );
	else
		/**
		 * Filters the callback for killing WordPress execution for all non-Ajax, non-XML-RPC requests.
		 *
		 * @since 3.0.0
		 *
		 * @param callable $function Callback function name.
		 */
		$function = apply_filters( 'wp_die_handler', '_default_wp_die_handler' );

	call_user_func( $function, $message, $title, $args );
}

/**
 * Convert a value to non-negative integer.
 *
 * @since 2.5.0
 *
 * @param  mixed $maybeint Data you wish to have converted to a non-negative integer.
 * @return int   A non-negative integer.
 */
function absint( $maybeint )
{
	return abs( intval( $maybeint ) );
}

// @NOW 022
