<?php
/**
 * Option API
 *
 * @package WordPress
 * @subpackage Option
 */

// refactored. function get_option( $option, $default = false ) {}
// refactored. function wp_protect_special_option( $option ) {}

/**
 * Print option value after sanitizing for forms.
 *
 * @since 1.5.0
 *
 * @param string $option Option name.
 */
function form_option( $option ) {
	echo esc_attr( get_option( $option ) );
}

// refactored. function wp_load_alloptions() {}

/**
 * Loads and caches certain often requested site options if is_multisite() and a persistent cache is not being used.
 *
 * @since 3.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $network_id Optional site ID for which to query the options. Defaults to the current site.
 */
function wp_load_core_site_options( $network_id = null ) {
	global $wpdb;

	if ( ! is_multisite() || wp_using_ext_object_cache() || wp_installing() )
		return;

	if ( empty($network_id) )
		$network_id = get_current_network_id();

	$core_options = array('site_name', 'siteurl', 'active_sitewide_plugins', '_site_transient_timeout_theme_roots', '_site_transient_theme_roots', 'site_admins', 'can_compress_scripts', 'global_terms_enabled', 'ms_files_rewriting' );

	$core_options_in = "'" . implode("', '", $core_options) . "'";
	$options = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->sitemeta WHERE meta_key IN ($core_options_in) AND site_id = %d", $network_id ) );

	foreach ( $options as $option ) {
		$key = $option->meta_key;
		$cache_key = "{$network_id}:$key";
		$option->meta_value = maybe_unserialize( $option->meta_value );

		wp_cache_set( $cache_key, $option->meta_value, 'site-options' );
	}
}

// refactored. function update_option( $option, $value, $autoload = null ) {}
// :
// refactored. function delete_option( $option ) {}

/**
 * Delete a transient.
 *
 * @since 2.8.0
 *
 * @param string $transient Transient name. Expected to not be SQL-escaped.
 * @return bool true if successful, false otherwise
 */
function delete_transient( $transient ) {

	/**
	 * Fires immediately before a specific transient is deleted.
	 *
	 * The dynamic portion of the hook name, `$transient`, refers to the transient name.
	 *
	 * @since 3.0.0
	 *
	 * @param string $transient Transient name.
	 */
	do_action( "delete_transient_{$transient}", $transient );

	if ( wp_using_ext_object_cache() ) {
		$result = wp_cache_delete( $transient, 'transient' );
	} else {
		$option_timeout = '_transient_timeout_' . $transient;
		$option = '_transient_' . $transient;
		$result = delete_option( $option );
		if ( $result )
			delete_option( $option_timeout );
	}

	if ( $result ) {

		/**
		 * Fires after a transient is deleted.
		 *
		 * @since 3.0.0
		 *
		 * @param string $transient Deleted transient name.
		 */
		do_action( 'deleted_transient', $transient );
	}

	return $result;
}

/**
 * Get the value of a transient.
 *
 * If the transient does not exist, does not have a value, or has expired,
 * then the return value will be false.
 *
 * @since 2.8.0
 *
 * @param string $transient Transient name. Expected to not be SQL-escaped.
 * @return mixed Value of transient.
 */
function get_transient( $transient ) {

	/**
	 * Filters the value of an existing transient.
	 *
	 * The dynamic portion of the hook name, `$transient`, refers to the transient name.
	 *
	 * Passing a truthy value to the filter will effectively short-circuit retrieval
	 * of the transient, returning the passed value instead.
	 *
	 * @since 2.8.0
	 * @since 4.4.0 The `$transient` parameter was added
	 *
	 * @param mixed  $pre_transient The default value to return if the transient does not exist.
	 *                              Any value other than false will short-circuit the retrieval
	 *                              of the transient, and return the returned value.
	 * @param string $transient     Transient name.
	 */
	$pre = apply_filters( "pre_transient_{$transient}", false, $transient );
	if ( false !== $pre )
		return $pre;

	if ( wp_using_ext_object_cache() ) {
		$value = wp_cache_get( $transient, 'transient' );
	} else {
		$transient_option = '_transient_' . $transient;
		if ( ! wp_installing() ) {
			// If option is not in alloptions, it is not autoloaded and thus has a timeout
			$alloptions = wp_load_alloptions();
			if ( !isset( $alloptions[$transient_option] ) ) {
				$transient_timeout = '_transient_timeout_' . $transient;
				$timeout = get_option( $transient_timeout );
				if ( false !== $timeout && $timeout < time() ) {
					delete_option( $transient_option  );
					delete_option( $transient_timeout );
					$value = false;
				}
			}
		}

		if ( ! isset( $value ) )
			$value = get_option( $transient_option );
	}

	/**
	 * Filters an existing transient's value.
	 *
	 * The dynamic portion of the hook name, `$transient`, refers to the transient name.
	 *
	 * @since 2.8.0
	 * @since 4.4.0 The `$transient` parameter was added
	 *
	 * @param mixed  $value     Value of transient.
	 * @param string $transient Transient name.
	 */
	return apply_filters( "transient_{$transient}", $value, $transient );
}

/**
 * Set/update the value of a transient.
 *
 * You do not need to serialize values. If the value needs to be serialized, then
 * it will be serialized before it is set.
 *
 * @since 2.8.0
 *
 * @param string $transient  Transient name. Expected to not be SQL-escaped. Must be
 *                           172 characters or fewer in length.
 * @param mixed  $value      Transient value. Must be serializable if non-scalar.
 *                           Expected to not be SQL-escaped.
 * @param int    $expiration Optional. Time until expiration in seconds. Default 0 (no expiration).
 * @return bool False if value was not set and true if value was set.
 */
function set_transient( $transient, $value, $expiration = 0 ) {

	$expiration = (int) $expiration;

	/**
	 * Filters a specific transient before its value is set.
	 *
	 * The dynamic portion of the hook name, `$transient`, refers to the transient name.
	 *
	 * @since 3.0.0
	 * @since 4.2.0 The `$expiration` parameter was added.
	 * @since 4.4.0 The `$transient` parameter was added.
	 *
	 * @param mixed  $value      New value of transient.
	 * @param int    $expiration Time until expiration in seconds.
	 * @param string $transient  Transient name.
	 */
	$value = apply_filters( "pre_set_transient_{$transient}", $value, $expiration, $transient );

	/**
	 * Filters the expiration for a transient before its value is set.
	 *
	 * The dynamic portion of the hook name, `$transient`, refers to the transient name.
	 *
	 * @since 4.4.0
	 *
	 * @param int    $expiration Time until expiration in seconds. Use 0 for no expiration.
	 * @param mixed  $value      New value of transient.
	 * @param string $transient  Transient name.
	 */
	$expiration = apply_filters( "expiration_of_transient_{$transient}", $expiration, $value, $transient );

	if ( wp_using_ext_object_cache() ) {
		$result = wp_cache_set( $transient, $value, 'transient', $expiration );
	} else {
		$transient_timeout = '_transient_timeout_' . $transient;
		$transient_option = '_transient_' . $transient;
		if ( false === get_option( $transient_option ) ) {
			$autoload = 'yes';
			if ( $expiration ) {
				$autoload = 'no';
				add_option( $transient_timeout, time() + $expiration, '', 'no' );
			}
			$result = add_option( $transient_option, $value, '', $autoload );
		} else {
			// If expiration is requested, but the transient has no timeout option,
			// delete, then re-create transient rather than update.
			$update = true;
			if ( $expiration ) {
				if ( false === get_option( $transient_timeout ) ) {
					delete_option( $transient_option );
					add_option( $transient_timeout, time() + $expiration, '', 'no' );
					$result = add_option( $transient_option, $value, '', 'no' );
					$update = false;
				} else {
					update_option( $transient_timeout, time() + $expiration );
				}
			}
			if ( $update ) {
				$result = update_option( $transient_option, $value );
			}
		}
	}

	if ( $result ) {

		/**
		 * Fires after the value for a specific transient has been set.
		 *
		 * The dynamic portion of the hook name, `$transient`, refers to the transient name.
		 *
		 * @since 3.0.0
		 * @since 3.6.0 The `$value` and `$expiration` parameters were added.
		 * @since 4.4.0 The `$transient` parameter was added.
		 *
		 * @param mixed  $value      Transient value.
		 * @param int    $expiration Time until expiration in seconds.
		 * @param string $transient  The name of the transient.
		 */
		do_action( "set_transient_{$transient}", $value, $expiration, $transient );

		/**
		 * Fires after the value for a transient has been set.
		 *
		 * @since 3.0.0
		 * @since 3.6.0 The `$value` and `$expiration` parameters were added.
		 *
		 * @param string $transient  The name of the transient.
		 * @param mixed  $value      Transient value.
		 * @param int    $expiration Time until expiration in seconds.
		 */
		do_action( 'setted_transient', $transient, $value, $expiration );
	}
	return $result;
}

/**
 * Deletes all expired transients.
 *
 * The multi-table delete syntax is used to delete the transient record
 * from table a, and the corresponding transient_timeout record from table b.
 *
 * @since 4.9.0
 *
 * @param bool $force_db Optional. Force cleanup to run against the database even when an external object cache is used.
 */
function delete_expired_transients( $force_db = false ) {
	global $wpdb;

	if ( ! $force_db && wp_using_ext_object_cache() ) {
		return;
	}

	$wpdb->query( $wpdb->prepare(
		"DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
			WHERE a.option_name LIKE %s
			AND a.option_name NOT LIKE %s
			AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
			AND b.option_value < %d",
		$wpdb->esc_like( '_transient_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_' ) . '%',
		time()
	) );

	if ( ! is_multisite() ) {
		// non-Multisite stores site transients in the options table.
		$wpdb->query( $wpdb->prepare(
			"DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
				WHERE a.option_name LIKE %s
				AND a.option_name NOT LIKE %s
				AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
				AND b.option_value < %d",
			$wpdb->esc_like( '_site_transient_' ) . '%',
			$wpdb->esc_like( '_site_transient_timeout_' ) . '%',
			time()
		) );
	} elseif ( is_multisite() && is_main_site() && is_main_network() ) {
		// Multisite stores site transients in the sitemeta table.
		$wpdb->query( $wpdb->prepare(
			"DELETE a, b FROM {$wpdb->sitemeta} a, {$wpdb->sitemeta} b
				WHERE a.meta_key LIKE %s
				AND a.meta_key NOT LIKE %s
				AND b.meta_key = CONCAT( '_site_transient_timeout_', SUBSTRING( a.meta_key, 17 ) )
				AND b.meta_value < %d",
			$wpdb->esc_like( '_site_transient_' ) . '%',
			$wpdb->esc_like( '_site_transient_timeout_' ) . '%',
			time()
		) );
	}
}

/**
 * Saves and restores user interface settings stored in a cookie.
 *
 * Checks if the current user-settings cookie is updated and stores it. When no
 * cookie exists (different browser used), adds the last saved cookie restoring
 * the settings.
 *
 * @since 2.7.0
 */
function wp_user_settings() {

	if ( ! is_admin() || wp_doing_ajax() ) {
		return;
	}

	if ( ! $user_id = get_current_user_id() ) {
		return;
	}

	if ( ! is_user_member_of_blog() ) {
		return;
	}

	$settings = (string) get_user_option( 'user-settings', $user_id );

	if ( isset( $_COOKIE['wp-settings-' . $user_id] ) ) {
		$cookie = preg_replace( '/[^A-Za-z0-9=&_]/', '', $_COOKIE['wp-settings-' . $user_id] );

		// No change or both empty
		if ( $cookie == $settings )
			return;

		$last_saved = (int) get_user_option( 'user-settings-time', $user_id );
		$current = isset( $_COOKIE['wp-settings-time-' . $user_id]) ? preg_replace( '/[^0-9]/', '', $_COOKIE['wp-settings-time-' . $user_id] ) : 0;

		// The cookie is newer than the saved value. Update the user_option and leave the cookie as-is
		if ( $current > $last_saved ) {
			update_user_option( $user_id, 'user-settings', $cookie, false );
			update_user_option( $user_id, 'user-settings-time', time() - 5, false );
			return;
		}
	}

	// The cookie is not set in the current browser or the saved value is newer.
	$secure = ( 'https' === parse_url( admin_url(), PHP_URL_SCHEME ) );
	setcookie( 'wp-settings-' . $user_id, $settings, time() + YEAR_IN_SECONDS, SITECOOKIEPATH, null, $secure );
	setcookie( 'wp-settings-time-' . $user_id, time(), time() + YEAR_IN_SECONDS, SITECOOKIEPATH, null, $secure );
	$_COOKIE['wp-settings-' . $user_id] = $settings;
}

/**
 * Retrieve user interface setting value based on setting name.
 *
 * @since 2.7.0
 *
 * @param string $name    The name of the setting.
 * @param string $default Optional default value to return when $name is not set.
 * @return mixed the last saved user setting or the default value/false if it doesn't exist.
 */
function get_user_setting( $name, $default = false ) {
	$all_user_settings = get_all_user_settings();

	return isset( $all_user_settings[$name] ) ? $all_user_settings[$name] : $default;
}

/**
 * Add or update user interface setting.
 *
 * Both $name and $value can contain only ASCII letters, numbers and underscores.
 *
 * This function has to be used before any output has started as it calls setcookie().
 *
 * @since 2.8.0
 *
 * @param string $name  The name of the setting.
 * @param string $value The value for the setting.
 * @return bool|null True if set successfully, false if not. Null if the current user can't be established.
 */
function set_user_setting( $name, $value ) {
	if ( headers_sent() ) {
		return false;
	}

	$all_user_settings = get_all_user_settings();
	$all_user_settings[$name] = $value;

	return wp_set_all_user_settings( $all_user_settings );
}

/**
 * Delete user interface settings.
 *
 * Deleting settings would reset them to the defaults.
 *
 * This function has to be used before any output has started as it calls setcookie().
 *
 * @since 2.7.0
 *
 * @param string $names The name or array of names of the setting to be deleted.
 * @return bool|null True if deleted successfully, false if not. Null if the current user can't be established.
 */
function delete_user_setting( $names ) {
	if ( headers_sent() ) {
		return false;
	}

	$all_user_settings = get_all_user_settings();
	$names = (array) $names;
	$deleted = false;

	foreach ( $names as $name ) {
		if ( isset( $all_user_settings[$name] ) ) {
			unset( $all_user_settings[$name] );
			$deleted = true;
		}
	}

	if ( $deleted ) {
		return wp_set_all_user_settings( $all_user_settings );
	}

	return false;
}

/**
 * Retrieve all user interface settings.
 *
 * @since 2.7.0
 *
 * @global array $_updated_user_settings
 *
 * @return array the last saved user settings or empty array.
 */
function get_all_user_settings() {
	global $_updated_user_settings;

	if ( ! $user_id = get_current_user_id() ) {
		return array();
	}

	if ( isset( $_updated_user_settings ) && is_array( $_updated_user_settings ) ) {
		return $_updated_user_settings;
	}

	$user_settings = array();

	if ( isset( $_COOKIE['wp-settings-' . $user_id] ) ) {
		$cookie = preg_replace( '/[^A-Za-z0-9=&_-]/', '', $_COOKIE['wp-settings-' . $user_id] );

		if ( strpos( $cookie, '=' ) ) { // '=' cannot be 1st char
			parse_str( $cookie, $user_settings );
		}
	} else {
		$option = get_user_option( 'user-settings', $user_id );

		if ( $option && is_string( $option ) ) {
			parse_str( $option, $user_settings );
		}
	}

	$_updated_user_settings = $user_settings;
	return $user_settings;
}

/**
 * Private. Set all user interface settings.
 *
 * @since 2.8.0
 * @access private
 *
 * @global array $_updated_user_settings
 *
 * @param array $user_settings User settings.
 * @return bool|null False if the current user can't be found, null if the current
 *                   user is not a super admin or a member of the site, otherwise true.
 */
function wp_set_all_user_settings( $user_settings ) {
	global $_updated_user_settings;

	if ( ! $user_id = get_current_user_id() ) {
		return false;
	}

	if ( ! is_user_member_of_blog() ) {
		return;
	}

	$settings = '';
	foreach ( $user_settings as $name => $value ) {
		$_name = preg_replace( '/[^A-Za-z0-9_-]+/', '', $name );
		$_value = preg_replace( '/[^A-Za-z0-9_-]+/', '', $value );

		if ( ! empty( $_name ) ) {
			$settings .= $_name . '=' . $_value . '&';
		}
	}

	$settings = rtrim( $settings, '&' );
	parse_str( $settings, $_updated_user_settings );

	update_user_option( $user_id, 'user-settings', $settings, false );
	update_user_option( $user_id, 'user-settings-time', time(), false );

	return true;
}

/**
 * Delete the user settings of the current user.
 *
 * @since 2.7.0
 */
function delete_all_user_settings() {
	if ( ! $user_id = get_current_user_id() ) {
		return;
	}

	update_user_option( $user_id, 'user-settings', '', false );
	setcookie( 'wp-settings-' . $user_id, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH );
}

// refactored. function get_site_option( $option, $default = false, $deprecated = true ) {}
// :
// refactored. function update_network_option( $network_id, $option, $value ) {}

/**
 * Delete a site transient.
 *
 * @since 2.9.0
 *
 * @param string $transient Transient name. Expected to not be SQL-escaped.
 * @return bool True if successful, false otherwise
 */
function delete_site_transient( $transient ) {

	/**
	 * Fires immediately before a specific site transient is deleted.
	 *
	 * The dynamic portion of the hook name, `$transient`, refers to the transient name.
	 *
	 * @since 3.0.0
	 *
	 * @param string $transient Transient name.
	 */
	do_action( "delete_site_transient_{$transient}", $transient );

	if ( wp_using_ext_object_cache() ) {
		$result = wp_cache_delete( $transient, 'site-transient' );
	} else {
		$option_timeout = '_site_transient_timeout_' . $transient;
		$option = '_site_transient_' . $transient;
		$result = delete_site_option( $option );
		if ( $result )
			delete_site_option( $option_timeout );
	}
	if ( $result ) {

		/**
		 * Fires after a transient is deleted.
		 *
		 * @since 3.0.0
		 *
		 * @param string $transient Deleted transient name.
		 */
		do_action( 'deleted_site_transient', $transient );
	}

	return $result;
}

// refactored. function get_site_transient( $transient ) {}
// refactored. function set_site_transient( $transient, $value, $expiration = 0 ) {}

/**
 * Register default settings available in WordPress.
 *
 * The settings registered here are primarily useful for the REST API, so this
 * does not encompass all settings available in WordPress.
 *
 * @since 4.7.0
 */
function register_initial_settings() {
	register_setting( 'general', 'blogname', array(
		'show_in_rest' => array(
			'name' => 'title',
		),
		'type'         => 'string',
		'description'  => __( 'Site title.' ),
	) );

	register_setting( 'general', 'blogdescription', array(
		'show_in_rest' => array(
			'name' => 'description',
		),
		'type'         => 'string',
		'description'  => __( 'Site tagline.' ),
	) );

	if ( ! is_multisite() ) {
		register_setting( 'general', 'siteurl', array(
			'show_in_rest' => array(
				'name'    => 'url',
				'schema'  => array(
					'format' => 'uri',
				),
			),
			'type'         => 'string',
			'description'  => __( 'Site URL.' ),
		) );
	}

	if ( ! is_multisite() ) {
		register_setting( 'general', 'admin_email', array(
			'show_in_rest' => array(
				'name'    => 'email',
				'schema'  => array(
					'format' => 'email',
				),
			),
			'type'         => 'string',
			'description'  => __( 'This address is used for admin purposes, like new user notification.' ),
		) );
	}

	register_setting( 'general', 'timezone_string', array(
		'show_in_rest' => array(
			'name' => 'timezone',
		),
		'type'         => 'string',
		'description'  => __( 'A city in the same timezone as you.' ),
	) );

	register_setting( 'general', 'date_format', array(
		'show_in_rest' => true,
		'type'         => 'string',
		'description'  => __( 'A date format for all date strings.' ),
	) );

	register_setting( 'general', 'time_format', array(
		'show_in_rest' => true,
		'type'         => 'string',
		'description'  => __( 'A time format for all time strings.' ),
	) );

	register_setting( 'general', 'start_of_week', array(
		'show_in_rest' => true,
		'type'         => 'integer',
		'description'  => __( 'A day number of the week that the week should start on.' ),
	) );

	register_setting( 'general', 'WPLANG', array(
		'show_in_rest' => array(
			'name' => 'language',
		),
		'type'         => 'string',
		'description'  => __( 'WordPress locale code.' ),
		'default'      => 'en_US',
	) );

	register_setting( 'writing', 'use_smilies', array(
		'show_in_rest' => true,
		'type'         => 'boolean',
		'description'  => __( 'Convert emoticons like :-) and :-P to graphics on display.' ),
		'default'      => true,
	) );

	register_setting( 'writing', 'default_category', array(
		'show_in_rest' => true,
		'type'         => 'integer',
		'description'  => __( 'Default post category.' ),
	) );

	register_setting( 'writing', 'default_post_format', array(
		'show_in_rest' => true,
		'type'         => 'string',
		'description'  => __( 'Default post format.' ),
	) );

	register_setting( 'reading', 'posts_per_page', array(
		'show_in_rest' => true,
		'type'         => 'integer',
		'description'  => __( 'Blog pages show at most.' ),
		'default'      => 10,
	) );

	register_setting( 'discussion', 'default_ping_status', array(
		'show_in_rest' => array(
			'schema'   => array(
				'enum' => array( 'open', 'closed' ),
			),
		),
		'type'         => 'string',
		'description'  => __( 'Allow link notifications from other blogs (pingbacks and trackbacks) on new articles.' ),
	) );

	register_setting( 'discussion', 'default_comment_status', array(
		'show_in_rest' => array(
			'schema'   => array(
				'enum' => array( 'open', 'closed' ),
			),
		),
		'type'         => 'string',
		'description'  => __( 'Allow people to post comments on new articles.' ),
	) );

}

/**
 * Register a setting and its data.
 *
 * @since 2.7.0
 * @since 4.7.0 `$args` can be passed to set flags on the setting, similar to `register_meta()`.
 *
 * @global array $new_whitelist_options
 * @global array $wp_registered_settings
 *
 * @param string $option_group A settings group name. Should correspond to a whitelisted option key name.
 * 	Default whitelisted option key names include "general," "discussion," and "reading," among others.
 * @param string $option_name The name of an option to sanitize and save.
 * @param array  $args {
 *     Data used to describe the setting when registered.
 *
 *     @type string   $type              The type of data associated with this setting.
 *                                       Valid values are 'string', 'boolean', 'integer', and 'number'.
 *     @type string   $description       A description of the data attached to this setting.
 *     @type callable $sanitize_callback A callback function that sanitizes the option's value.
 *     @type bool     $show_in_rest      Whether data associated with this setting should be included in the REST API.
 *     @type mixed    $default           Default value when calling `get_option()`.
 * }
 */
function register_setting( $option_group, $option_name, $args = array() ) {
	global $new_whitelist_options, $wp_registered_settings;

	$defaults = array(
		'type'              => 'string',
		'group'             => $option_group,
		'description'       => '',
		'sanitize_callback' => null,
		'show_in_rest'      => false,
	);

	// Back-compat: old sanitize callback is added.
	if ( is_callable( $args ) ) {
		$args = array(
			'sanitize_callback' => $args,
		);
	}

	/**
	 * Filters the registration arguments when registering a setting.
	 *
	 * @since 4.7.0
	 *
	 * @param array  $args         Array of setting registration arguments.
	 * @param array  $defaults     Array of default arguments.
	 * @param string $option_group Setting group.
	 * @param string $option_name  Setting name.
	 */
	$args = apply_filters( 'register_setting_args', $args, $defaults, $option_group, $option_name );
	$args = wp_parse_args( $args, $defaults );

	if ( ! is_array( $wp_registered_settings ) ) {
		$wp_registered_settings = array();
	}

	if ( 'misc' == $option_group ) {
		_deprecated_argument( __FUNCTION__, '3.0.0',
			/* translators: %s: misc */
			sprintf( __( 'The "%s" options group has been removed. Use another settings group.' ),
				'misc'
			)
		);
		$option_group = 'general';
	}

	if ( 'privacy' == $option_group ) {
		_deprecated_argument( __FUNCTION__, '3.5.0',
			/* translators: %s: privacy */
			sprintf( __( 'The "%s" options group has been removed. Use another settings group.' ),
				'privacy'
			)
		);
		$option_group = 'reading';
	}

	$new_whitelist_options[ $option_group ][] = $option_name;
	if ( ! empty( $args['sanitize_callback'] ) ) {
		add_filter( "sanitize_option_{$option_name}", $args['sanitize_callback'] );
	}
	if ( array_key_exists( 'default', $args ) ) {
		add_filter( "default_option_{$option_name}", 'filter_default_option', 10, 3 );
	}

	$wp_registered_settings[ $option_name ] = $args;
}

/**
 * Unregister a setting.
 *
 * @since 2.7.0
 * @since 4.7.0 `$sanitize_callback` was deprecated. The callback from `register_setting()` is now used instead.
 *
 * @global array $new_whitelist_options
 *
 * @param string   $option_group      The settings group name used during registration.
 * @param string   $option_name       The name of the option to unregister.
 * @param callable $deprecated        Deprecated.
 */
function unregister_setting( $option_group, $option_name, $deprecated = '' ) {
	global $new_whitelist_options, $wp_registered_settings;

	if ( 'misc' == $option_group ) {
		_deprecated_argument( __FUNCTION__, '3.0.0',
			/* translators: %s: misc */
			sprintf( __( 'The "%s" options group has been removed. Use another settings group.' ),
				'misc'
			)
		);
		$option_group = 'general';
	}

	if ( 'privacy' == $option_group ) {
		_deprecated_argument( __FUNCTION__, '3.5.0',
			/* translators: %s: privacy */
			sprintf( __( 'The "%s" options group has been removed. Use another settings group.' ),
				'privacy'
			)
		);
		$option_group = 'reading';
	}

	$pos = array_search( $option_name, (array) $new_whitelist_options[ $option_group ] );
	if ( $pos !== false ) {
		unset( $new_whitelist_options[ $option_group ][ $pos ] );
	}
	if ( '' !== $deprecated ) {
		_deprecated_argument( __FUNCTION__, '4.7.0',
			/* translators: 1: $sanitize_callback, 2: register_setting() */
			sprintf( __( '%1$s is deprecated. The callback from %2$s is used instead.' ),
				'<code>$sanitize_callback</code>',
				'<code>register_setting()</code>'
			)
		);
		remove_filter( "sanitize_option_{$option_name}", $deprecated );
	}

	if ( isset( $wp_registered_settings[ $option_name ] ) ) {
		// Remove the sanitize callback if one was set during registration.
		if ( ! empty( $wp_registered_settings[ $option_name ]['sanitize_callback'] ) ) {
			remove_filter( "sanitize_option_{$option_name}", $wp_registered_settings[ $option_name ]['sanitize_callback'] );
		}

		unset( $wp_registered_settings[ $option_name ] );
	}
}

/**
 * Retrieves an array of registered settings.
 *
 * @since 4.7.0
 *
 * @return array List of registered settings, keyed by option name.
 */
function get_registered_settings() {
	global $wp_registered_settings;

	if ( ! is_array( $wp_registered_settings ) ) {
		return array();
	}

	return $wp_registered_settings;
}

/**
 * Filter the default value for the option.
 *
 * For settings which register a default setting in `register_setting()`, this
 * function is added as a filter to `default_option_{$option}`.
 *
 * @since 4.7.0
 *
 * @param mixed $default Existing default value to return.
 * @param string $option Option name.
 * @param bool $passed_default Was `get_option()` passed a default value?
 * @return mixed Filtered default value.
 */
function filter_default_option( $default, $option, $passed_default ) {
	if ( $passed_default ) {
		return $default;
	}

	$registered = get_registered_settings();
	if ( empty( $registered[ $option ] ) ) {
		return $default;
	}

	return $registered[ $option ]['default'];
}
