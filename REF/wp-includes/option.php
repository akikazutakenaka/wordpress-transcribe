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

		if ( isset( $alloptions[ $option ] ) ) {
			$value = $alloptions[ $option ];
		} else {
			$value = wp_cache_get( $option, 'options' );

			if ( FALSE === $value ) {
				$row = $wpdb->get_row( $wpdb->prepare( <<<EOQ
SELECT option_value
FROM $wpdb->options
WHERE option_name = %s
LIMIT 1
EOQ
					, $option ) );

				// Has to be get_row instead of get_var because of funkiness with 0, false, null values.
				if ( is_object( $row ) ) {
					$value = $row->option_value;
					wp_cache_add( $option, $value, 'options' );
				} else {
					// Option does not exist, so we must cache its non-existence.
					if ( ! is_array( $notoptions ) ) {
						$notoptions = array();
					}

					$notoptions[ $option ] = TRUE;
					wp_cache_set( 'notoptions', $notoptions, 'options' );

					// This filter is documented in wp-includes/option.php
					return apply_filters( "default_option_{$option}", $default, $option, $passed_default );
				}
			}
		}
	} else {
		$suppress = $wpdb->suppress_errors();
		$row = $wpdb->get_row( $wpdb->prepare( <<<EOQ
SELECT option_value
FROM $wpdb->options
WHERE option_name = %s
LIMIT 1
EOQ
			, $option ) );
		$wpdb->suppress_errors( $suppress );

		if ( is_object( $row ) ) {
			$value = $row->option_value;
		} else {
			// This filter is documented in wp-includes/option.php
			return apply_filters( "default_option_{$option}", $default, $option, $passed_default );
		}
	}

	// If home is not set use siteurl.
	if ( 'home' == $option && '' == $value ) {
		return get_option( 'siteurl' );
	}

	if ( in_array( $option, array( 'siteurl', 'home', 'category_base', 'tag_base' ) ) ) {
		$value = untrailingslashit( $value );
	}

	/**
	 * Filters the value of an existing option.
	 *
	 * The dynamic portion of the hook name, `$option`, refers to the option name.
	 *
	 * @since 1.5.0 As 'option_' . $setting
	 * @since 3.0.0
	 * @since 4.4.0 The `$option` parameter was added.
	 *
	 * @param mixed  $value  Value of the option.
	 *                       If stored serialized, it will be unserialized prior to being returned.
	 * @param string $option Option name.
	 */
	return apply_filters( "option_{$option}", maybe_unserialize( $value ), $option );
}

/**
 * Protect WordPress special option from being modified.
 *
 * Will die if $option is in protected list.
 * Protected options are 'alloptions' and 'notoptions' options.
 *
 * @since 2.2.0
 *
 * @param string $option Option name.
 */
function wp_protect_special_option( $option )
{
	if ( 'alloptions' === $option || 'notoptions' === $option ) {
		wp_die( sprintf( __( '%s is a protected WP option and may not be modified' ), esc_html( $option ) ) );
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

	$alloptions = ! wp_installing() || ! is_multisite()
		? wp_cache_get( 'alloptions', 'options' )
		: FALSE;

	if ( ! $alloptions ) {
		$suppress = $wpdb->suppress_errors();

		if ( ! ( $alloptions_db = $wpdb->get_results( <<<EOQ
SELECT option_name, option_value
FROM $wpdb->options
WHERE autoload = 'yes'
EOQ
				) ) ) {
			$alloptions_db = $wpdb->get_results( <<<EOQ
SELECT option_name, option_value
FROM $wpdb->options
EOQ
			);
		}

		$wpdb->suppress_errors( $suppress );
		$alloptions = array();

		foreach ( ( array ) $alloptions_db as $o ) {
			$alloptions[ $o->option_name ] = $o->option_value;
		}

		if ( ! wp_installing() || ! is_multisite() ) {
			/**
			 * Filters all options before caching them.
			 *
			 * @since 4.9.0
			 *
			 * @param array $alloptions Array with all options.
			 */
			$alloptions = apply_filters( 'pre_cache_alloptions', $alloptions );
			wp_cache_add( 'alloptions', $alloptions, 'options' );
		}
	}

	/**
	 * Filters all options after retrieving them.
	 *
	 * @since 4.9.0
	 *
	 * @param array $alloptions Array with all options.
	 */
	return apply_filters( 'alloptions', $alloptions );
}

/**
 * Removes option by name.
 * Prevents removal of protected WordPress options.
 *
 * @since  1.2.0
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param  string $option Name of option to remove.
 *                        Expected to not be SQL-escaped.
 * @return bool   True, if option is successfully deleted.
 *                False on failure.
 */
function delete_option( $option )
{
	global $wpdb;
	$option = trim( $option );

	if ( empty( $option ) ) {
		return FALSE;
	}

	wp_protect_special_option( $option );

	// Get the ID, if no ID then return.
	$row = $wpdb->get_row( $wpdb->prepare( <<<EOQ
SELECT autoload
FROM $wpdb->options
WHERE option_name = %s
EOQ
			, $option ) );

	if ( is_null( $row ) ) {
		return FALSE;
	}

	/**
	 * Fires immediately before an option is deleted.
	 *
	 * @since 2.0.0
	 *
	 * @param string $option Name of the option to delete.
	 */
	do_action( 'delete_option', $option );

	$result = $wpdb->delete( $wpdb->options, array( 'option_name' => $option ) );

	if ( ! wp_installing() ) {
		if ( 'yes' == $row->autoload ) {
			$alloptions = wp_load_alloptions();

			if ( is_array( $alloptions ) && isset( $alloptions[ $option ] ) ) {
				unset( $alloptions[ $option ] );
				wp_cache_set( 'alloptions', $alloptions, 'options' );
			}
		} else {
			wp_cache_delete( $option, 'options' );
// @NOW 016
		}
	}
}

/**
 * Retrieve an option value for the current network based on name of option.
 *
 * @since 2.8.0
 * @since 4.4.0 The `$use_cache` parameter was deprecated.
 * @since 4.4.0 Modified into wrapper for get_network_option().
 * @see   get_network_option()
 *
 * @param  string $option     Name of option to retrieve.
 *                            Expected to not be SQL-escaped.
 * @param  mixed  $default    Optional value to return if option doesn't exist.
 *                            Default false.
 * @param  bool   $deprecated Whether to use cache.
 *                            Multisite only.
 *                            Always set to true.
 * @return mixed  Value set for the option.
 */
function get_site_option( $option, $default = FALSE, $deprecated = TRUE )
{
	return get_network_option( NULL, $option, $default );
}

/**
 * Removes a option by name for the current network.
 *
 * @since 2.8.0
 * @since 4.4.0 Modified into wrapper for delete_network_option().
 * @see   delete_network_option()
 *
 * @param  string $option Name of option to remove.
 *                        Expected to not be SQL-escaped.
 * @return bool   True, if succeed.
 *                False, if failure.
 */
function delete_site_option( $option )
{
	return delete_network_option( NULL, $option );
}

/**
 * Retrieve a network's option value based on the option name.
 *
 * @since  4.4.0
 * @see    get_option()
 * @global wpdb $wpdb
 *
 * @param  int    $network_id ID of the network.
 *                            Can be null to default to the current network ID.
 * @param  string $option     Name of option to retrieve.
 *                            Expected to not be SQL-escaped.
 * @param  mixed  $default    Optional.
 *                            Value to return if the option doesn't exist.
 *                            Default false.
 * @return mixed  Value set for the option.
 */
function get_network_option( $network_id, $option, $default = FALSE )
{
	global $wpdb;

	if ( $network_id && ! is_numeric( $network_id ) ) {
		return FALSE;
	}

	$network_id = ( int ) $network_id;

	// Fallback to the current network if a network ID is not specified.
	if ( ! $network_id ) {
		$network_id = get_current_network_id();
	}

	/**
	 * Filters an existing network option before it is retrieved.
	 *
	 * The dynamic portion of the hook name, `$option`, refers to the option name.
	 *
	 * Passing a truthy value to the filter will effectively short-circuit retrieval, returning the passed value instead.
	 *
	 * @since 2.9.0 As 'pre_site_option_' . $key
	 * @since 3.0.0
	 * @since 4.4.0 The `$option` parameter was added.
	 * @since 4.7.0 The `$network_id` parameter was added.
	 * @since 4.9.0 The `$default` parameter was added.
	 *
	 * @param mixed  $pre_option The value to return instead of the option value.
	 *                           This differs from `$default`, which is used as the fallback value in the event the option doesn't exist elsewhere in get_network_option().
	 *                           Default is false (to skip past the short-circuit).
	 * @param string $option     Option name.
	 * @param int    $network_id ID of the network.
	 * @param mixed  $default    The fallback value to return if the option does not exist.
	 *                           Default is false.
	 */
	$pre = apply_filters( "pre_site_option_{$option}", FALSE, $option, $network_id, $default );

	if ( FALSE !== $pre ) {
		return $pre;
	}

	// Prevent non-existent options from triggering multiple queries.
	$notoptions_key = "$network_id:notoptions";
	$notoptions = wp_cache_get( $notoptions_key, 'site-options' );

	if ( isset( $notoptions[ $option ] ) ) {
		/**
		 * Filters a specific default network option.
		 *
		 * The dynamic portion of the hook name, `$option`, refers to the option name.
		 *
		 * @since 3.4.0
		 * @since 4.4.0 The `$option` parameter was added.
		 * @since 4.7.0 The `$network_id` parameter was added.
		 *
		 * @param mixed  $default    The value to return if the site option does not exist in the database.
		 * @param string $option     Option name.
		 * @param int    $network_id ID of the network.
		 */
		return apply_filters( "default_site_option_{$option}", $default, $option, $network_id );
	}

	if ( ! is_multisite() ) {
		// This filter is documented in wp-includes/option.php
		$default = apply_filters( 'default_site_option_' . $option, $default, $option, $network_id );
		$value = get_option( $option, $default );
	} else {
		$cache_key = "$network_id:$option";
		$value = wp_cache_get( $cache_key, 'site-options' );

		if ( ! isset( $value ) || FALSE === $value ) {
			$row = $wpdb->get_row( $wpdb->prepare( <<<EOQ
SELECT meta_value
FROM $wpdb->sitemeta
WHERE meta_key = %s
  AND site_id = %d
EOQ
					, $option, $network_id ) );

			// Has to be get_row instead of get_var because of funkiness with 0, false, null values.
			if ( is_object( $row ) ) {
				$value = $row->get_value;
				$value = maybe_unserialize( $value );
				wp_cache_set( $cache_key, $value, 'site-options' );
			} else {
				if ( ! is_array( $notoptions ) ) {
					$notoptions = array();
				}

				$notoptions[ $option ] = TRUE;
				wp_cache_set( $notoptions_key, $notoptions, 'site-options' );

				// This filter is documented in wp-includes/option.php
				$value = apply_filters( 'default_site_option_' . $option, $default, $option, $network_id );
			}
		}
	}

	/**
	 * Filters the value of an existing network option.
	 *
	 * The dynamic portion of the hook name, `$option`, refers to the option name.
	 *
	 * @since 2.9.0 As 'site_option_' . $key
	 * @since 3.0.0
	 * @since 4.4.0 The `$option` parameter was added.
	 * @since 4.7.0 The `$network_id` parameter was added.
	 *
	 * @param mixed  $value      Value of network option.
	 * @param string $option     Option name.
	 * @param int    $network_id ID of the network.
	 */
	return apply_filters( "site_option_{$option}", $value, $option, $network_id );
}

/**
 * Removes a network option by name.
 *
 * @since  4.4.0
 * @see    delete_option()
 * @global wpdb $wpdb
 *
 * @param  int    $network_id ID of the network.
 *                            Can be null to default to the current network ID.
 * @param  string $option     Name of option to remove.
 *                            Expected to not be SQL-escaped.
 * @return bool   True, if succeed.
 *                False, if failure.
 */
function delete_network_option( $network_id, $option )
{
	global $wpdb;

	if ( $network_id && ! is_numeric( $network_id ) ) {
		return FALSE;
	}

	$network_id ( int ) $network_id;

	// Fallback to the current network if a network ID is not specified.
	if ( ! $network_id ) {
		$network_id = get_current_network_id();
	}

	/**
	 * Fires immediately before a specific network option is deleted.
	 *
	 * The dynamic portion of the hook name, `$option`, refers to the option name.
	 *
	 * @since 3.0.0
	 * @since 4.4.0 The `$option` parameter was added.
	 * @since 4.7.0 The `$network_id` parameter was added.
	 *
	 * @param string $option     Option name.
	 * @param int    $network_id ID of the network.
	 */
	do_action( "pre_delete_site_option_{$option}", $option, $network_id );

	if ( ! is_multisite() ) {
		$result = delete_option( $option );
// @NOW 015 -> wp-includes/option.php
	}
}

/**
 * Get the value of a site transient.
 *
 * If the transient does not exist, does not have a value, or has expired, then the return value will be false.
 *
 * @since 2.9.0
 * @see   get_transient()
 *
 * @param  string $transient Transient name.
 *                           Expected to not be SQL-escaped.
 * @return mixed  Value of transient.
 */
function get_site_transient( $transient )
{
	/**
	 * Filters the value of an existing site transient.
	 *
	 * The dynamic portion of the hook name, `$transient`, refers to the transient name.
	 *
	 * Passing a truthy value to the filter will effectively short-circuit retrieval, returning the passed value instead.
	 *
	 * @since 2.9.0
	 * @since 4.4.0 The `$transient` parameter was added.
	 *
	 * @param mixed  $pre_site_transient The default value to return if the site transient does not exist.
	 *                                   Any value other than false will short-circuit the retrieval of the transient, and return the returned value.
	 * @param string $transient          Transient name.
	 */
	$pre = apply_filters( "pre_site_transient_{$transient}", FALSE, $transient );

	if ( FALSE !== $pre ) {
		return $pre;
	}

	if ( wp_using_ext_object_cache() ) {
		$value = wp_cache_get( $transient, 'site-transient' );
	} else {
		/**
		 * Core transients that do not have a timeout.
		 * Listed here so querying timeouts can be avoided.
		 */
		$no_timeout = array( 'update_core', 'update_plugins', 'update_themes' );
		$transient_option = '_site_transient_' . $transient;

		if ( ! in_array( $transient, $no_timeout ) ) {
			$transient_timeout = '_site_transient_timeout_' . $transient;
			$timeout = get_site_option( $transient_timeout );

			if ( FALSE !== $timeout && $timeout < time() ) {
				delete_site_option( $transient_option );
// @NOW 014 -> wp-includes/option.php
			}
		}
	}
}
