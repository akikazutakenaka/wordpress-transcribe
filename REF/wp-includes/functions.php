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
	if ( is_int( $args ) ) {
		$args = ['response' => $args];
	} elseif ( is_int( $title ) ) {
		$args = ['response' => $title];
		$title = '';
	}

	if ( wp_doing_ajax() ) {
		/**
		 * Filters the callback for killing WordPress execution for Ajax requests.
		 *
		 * @since 3.4.0
		 *
		 * @param callable $function Callback function name.
		 */
		$function = apply_filters( 'wp_die_ajax_handler', '_ajax_wp_die_handler' );
	} elseif ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		/**
		 * Filters the callback for killing WordPress execution for XML-RPC requests.
		 *
		 * @since 3.4.0
		 *
		 * @param callable $function Callback function name.
		 */
		$function = apply_filters( 'wp_die_xmlrpc_handler', '_xmlrpc_wp_die_handler' );
	} else {
		/**
		 * Filters the callback for killing WordPress execution for all non-Ajax, non-XML-RPC requests.
		 *
		 * @since 3.0.0
		 *
		 * @param callable $function Callback function name.
		 */
		$function = apply_filters( 'wp_die_handler', '_default_wp_die_handler' );
	}

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

/**
 * Mark something as being incorrectly called.
 *
 * There is ahook {@see 'doing_it_wrong_run'} that will be called that can be used to get the backtrace up to what file and function called the deprecated function.
 *
 * The current behavior is to trigger a user error if `WP_DEBUG` is true.
 *
 * @since  3.1.0
 * @access private
 *
 * @param string $function The function that was called.
 * @param string $message  A message explaining what has been done incorrectly.
 * @param string $version  The version of WordPress where the message was added.
 */
function _doing_it_wrong( $function, $message, $version )
{
	/**
	 * Fires when the given function is being used incorrectly.
	 *
	 * @since 3.1.0
	 *
	 * @param string $function The function that was called.
	 * @param string $message  A message explaining what has been done incorrectly.
	 * @param string $version  The version of WordPress where the message was added.
	 */
	do_action( 'doing_it_wrong_run', $function, $message, $version );

	/**
	 * Filters whether to trigger an error for _doing_it_wrong() calls.
	 *
	 * @since 3.1.0
	 *
	 * @param bool $trigger Whether to trigger the error for _doing_it_wrong() calls.
	 *                      Default true.
	 */
	if ( WP_DEBUG && apply_filters( 'doing_it_wrong_trigger_error', TRUE ) ) {
		if ( function_exists( '__' ) ) {
			$version = is_null( $version ) ? '' : sprintf( __( '(This message was added in version %s.)' ), $version );
			$message .= ' ' . sprintf( __( 'Please see <a href="%s">Debugging in WordPress</a> for more information.' ), __( 'https://codex.wordpress.org/Debugging_in_WordPress' ) );
			trigger_error( sprintf( __( '%1$s was called <strong>incorrectly</strong>. %2$s %3$s' ), $function, $message, $version ) );
		} else {
			$version = is_null( $version ) ? '' : sprintf( '(This message was added in version %s.)', $version );
			$message .= sprintf( ' Please see <a href="%s">Debugging in WordPress</a> for more information.', 'https://codex.wordpress.org/Debugging_in_WordPress' );
			trigger_error( sprintf( '%1$s was called <strong>incorrectly</strong>. %2$s %3$s', $function, $message, $version ) );
		}
	}
}

/**
 * Temporarily suspend cache additions.
 *
 * Stops more data being added to the cache, but still allows cache retrieval.
 * This is useful for actions, such as imports, when a lot of data would otherwise be almost uselessly added to the cache.
 *
 * Suspension lasts for a single page load at most.
 * Remember to call this function again if you wish to re-enable cache adds earlier.
 *
 * @since     3.3.0
 * @staticvar bool $_suspend
 *
 * @param  bool $suspend Optional.
 *                       Suspends additions if true, re-enables them if false.
 * @return bool The current suspend setting.
 */
function wp_suspend_cache_addition( $suspend = NULL )
{
	static $_suspend = FALSE;

	if ( is_bool( $suspend ) ) {
		$_suspend = $suspend;
	}

	return $_suspend;
}
