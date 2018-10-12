<?php
/**
 * Main WordPress API
 *
 * @package WordPress
 */

require( ABSPATH . WPINC . '/option.php' );

/**
 * Unserialize value only if it was serialized.
 *
 * @since 2.0.0
 *
 * @param  string $original Maybe unserialized original, if is needed.
 * @return mixed  Unserialized data can be any type.
 */
function maybe_unserialize( $original )
{
	if ( is_serialized( $original ) ) {
		// Don't attempt to unserialize data that wasn't serialized going in.
		return @unserialize( $original );
	}

	return $original;
}

/**
 * Check value to find if it was serialized.
 *
 * If $data is not an string, then returned value will always be false.
 * Serialized data is always a string.
 *
 * @since 2.0.5
 *
 * @param  string $data   Value to check to see if was serialized.
 * @param  bool   $strict Optional.
 *                        Whether to be strict about the end of the string.
 *                        Default true.
 * @return bool   False if not serialized and true if it was.
 */
function is_serialized( $data, $strict = TRUE )
{
	// If it isn't a string, it isn't serialized.
	if ( ! is_string( $data ) ) {
		return FALSE;
	}

	$data = trim( $data );

	if ( 'N;' == $data ) {
		return TRUE;
	}

	if ( strlen( $data ) < 4 ) {
		return FALSE;
	}

	if ( ':' !== $data[1] ) {
		return FALSE;
	}

	if ( $strict ) {
		$lastc = substr( $data, -1 );

		if ( ';' !== $lastc && '}' !== $lastc ) {
			return FALSE;
		}
	} else {
		$semicolon = strpos( $data, ';' );
		$brace     = strpos( $data, '}' );

		// Either ; or } must exist.
		if ( FALSE === $semicolon && FALSE === $brace ) {
			return FALSE;
		}

		// But neither must be in the first X characters.
		if ( FALSE !== $semicolon && $semicolon < 3 ) {
			return FALSE;
		}

		if ( FALSE !== $brace && $brace < 4 ) {
			return FALSE;
		}
	}

	$token = $data[0];

	switch ( $token ) {
		case 's':
			if ( $strict ) {
				if ( '"' !== substr( $data, -2, 1 ) ) {
					return FALSE;
				}
			} elseif ( FALSE === strpos( $data, '"' ) ) {
				return FALSE;
			}

			// Or else fall through.

		case 'a':
		case 'O':
			return ( bool ) preg_match( "/^{$token}:[0-9]+:/s", $data );

		case 'b':
		case 'i':
		case 'd':
			$end = $strict ? '$' : '';
			return ( bool ) preg_match( "/^{$token}:[0-9.E-]+;$end/", $data );
	}

	return FALSE;
}

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

// @NOW 026

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

/**
 * Return a comma-separated string of functions that have been called to get to the current point in code.
 *
 * @since 3.4.0
 * @see   https://core.trac.wordpress.org/ticket/19589
 *
 * @param  string       $ignore_class Optional.
 *                                    A class to ignore all function calls within - useful when you want to just give info about the callee.
 *                                    Default null.
 * @param  int          $skip_frames  Optional.
 *                                    A number of stack frames to skip - useful for unwinding back to the source of the issue.
 *                                    Default 0.
 * @param  bool         $pretty       Optional.
 *                                    Whether or not you want a comma separated string or raw array returned.
 *                                    Default true.
 * @return string|array Either a string containing a reversed comma separated trace or an array of individual calls.
 */
function wp_debug_backtrace_summary( $ignore_class = NULL, $skip_frames = 0, $pretty = TRUE )
{
	$trace = version_compare( PHP_VERSION, '5.2.5', '>=' ) ? debug_backtrace( FALSE ) : debug_backtrace();
	$caller = [];
	$check_class = ! is_null( $ignore_class );
	$skip_frames++; // Skip this function.

	foreach ( $trace as $call ) {
		if ( $skip_frames > 0 ) {
			$skip_frames--;
		} elseif ( isset( $call['class'] ) ) {
			if ( $check_class && $ignore_class == $call['class'] ) {
				continue; // Filter out calls.
			}

			$caller[] = "{$call['class']}{$call['type']}{$call['function']}";
		} else {
			$caller[] = in_array( $call['function'], ['do_action', 'apply_filters'] )
				? "{$call['function']}('{$call['args'][0]}')"
				: ( in_array( $call['function'], ['include', 'include_once', 'require', 'require_once'] )
					? $call['function'] . "('" . str_replace( [WP_CONTENT_DIR, ABSPATH], '', $call['args'][0] ) . "')"
					: $call['function'] );
		}
	}

	return $pretty
		? join( ', ', array_reverse( $caller ) )
		: $caller;
}

/**
 * Set the mbstring internal encoding to a binary safe encoding when func_overload is enabled.
 *
 * When mbstring.func_overload is in use for multi-byte encodings, the results from strlen() and similar functions respect the utf8 characters, causing binary data to return incorrect lengths.
 *
 * This function overrides the mbstring encoding to a binary-safe encoding, and resets it to the users expected encoding afterwards through the `reset_mbstring_encoding` function.
 *
 * It is safe to recursively call this function, however each `mbstring_binary_safe_encoding()` call must be followed up with an equal number of `reset_mbstring_encoding()` calls.
 *
 * @since     3.7.0
 * @see       reset_mbstring_encoding()
 * @staticvar array $encodings
 * @staticvar bool  $overloaded
 *
 * @param bool Optional.
 *             Whether to reset the encoding back to a previously-set encoding.
 *             Default false.
 */
function mbstring_binary_safe_encoding( $reset = FALSE )
{
	static $encodings = [];
	static $overloaded = NULL;

	if ( is_null( $overloaded ) ) {
		$overloaded = function_exists( 'mb_internal_encoding' ) && ( ini_get( 'mbstring.func_overload' ) & 2 );
	}

	if ( FALSE === $overloaded ) {
		return;
	}

	if ( ! $reset ) {
		$encoding = mb_internal_encoding();
		array_push( $encodings, $encoding );
		mb_internal_encoding( 'ISO-8859-1' );
	}

	if ( $reset && $encodings ) {
		$encoding = array_pop( $encodings );
		mb_internal_encoding( $encoding );
	}
}

/**
 * Reset the mbstring internal encoding to a users previously set encoding.
 *
 * @see   mbstring_binary_safe_encoding()
 * @since 3.7.0
 */
function reset_mbstring_encoding()
{
	mbstring_binary_safe_encoding( TRUE );
}
