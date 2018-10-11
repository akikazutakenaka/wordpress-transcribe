<?php
/**
 * Option API
 *
 * @package    WordPress
 * @subpackage Option
 */

/**
 * Retrieves an option value based on an option name.
 *
 * If the option does not exist or does not have a value, then the return value will be false.
 * This is useful to check whether you need to install an option and is commonly used during installation of plugin options and to test whether upgrading is required.
 *
 * If the option was serialized then it will be unserialized when it is returned.
 *
 * Any scalar values will be returned as strings.
 * You may coerce the return type of a given option by registering an {@see 'option_$option'} filter callback.
 *
 * @since  1.5.0
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param  string $option  Name of option to retrieve.
 *                         Expected to not be SQL-escaped.
 * @param  mixed  $default Optional.
 *                         Default value to return if the option does not exist.
 * @return mixed  Value set for the option.
 */
function get_option( $option, $default = FALSE )
{
	global $wpdb;
	$option = trim( $option );

	if ( empty( $option ) ) {
		return FALSE;
	}

	/**
	 * Filters the value of an existing option before it is retrieved.
	 *
	 * The dynamic portion of the hook name, `$option`, refers to the option name.
	 *
	 * Passing a truthy value to the filter will short-circuit retrieving the option value, returning the passed value instead.
	 *
	 * @since 1.5.0
	 * @since 4.4.0 The `$option` parameter was added.
	 * @since 4.9.0 The `$default` parameter was added.
	 *
	 * @param bool|mixed $pre_option The value to return instead of the option value.
	 *                               This differs from `$default`, which is used as the fallback value in the event the option doesn't exist elsewhere in get_option().
	 *                               Default false (to skip past the short-circuit).
	 * @param string     $option     Option name.
	 * @param mixed      $default    The fallback value to return if the option does not exist.
	 *                               Default is false.
	 */
	$pre = apply_filters( "pre_option_{$option}", FALSE, $option, $default );

	if ( FALSE !== $pre ) {
		return $pre;
	}

	if ( defined( 'WP_SETUP_CONFIG' ) ) {
		return FALSE;
	}

	// Distinguish between `false` as a default, and not passing one.
	$passed_default = func_num_args() > 1;

	if ( ! wp_installing() ) {
		// Prevent non-existent options from triggering multiple queries
		$notoptions = wp_cache_get( 'notoptions', 'options' );

		if ( isset( $notoptions[ $option ] ) ) {
			/**
			 * Filters the default value for an option.
			 *
			 * The dynamic portion of the hook name, `$option`, refers to the option name.
			 *
			 * @since 3.4.0
			 * @since 4.4.0 The `$option` parameter was added.
			 * @since 4.7.0 The `$passed_default` parameter was added to distinguish between a `false` value and the default parameter value.
			 *
			 * @param mixed  $default        The default value to return if the option does not exist in the database.
			 * @param string $option         Option name.
			 * @param bool   $passed_default Was `get_option()` passed a default value?
			 */
			return apply_filters( "default_option_{$option}", $default, $option, $passed_default );
		}

		$alloptions = wp_load_alloptions();
// @NOW 022 -> wp-includes/option.php
	}
}

/**
 * Loads and caches all autoloaded options, if available or all options.
 *
 * @since  2.2.0
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @return array List of all options.
 */
function wp_load_alloptions()
{
	global $wpdb;
	$alloptions = ( ! wp_installing() || ! is_multisite() )
		? wp_cache_get( 'alloptions', 'options' )
		: FALSE;

	if ( ! $alloptions ) {
		$suppress = $wpdb->suppress_errors();
// @NOW 023
	}
}
