<?php
/**
 * These functions are needed to load WordPress.
 *
 * @package WordPress
 */

// refactored. function wp_get_server_protocol() {}
// :
// refactored. function timer_start() {}

/**
 * Retrieve or display the time from the page start to when function is called.
 *
 * @since 0.71
 *
 * @global float   $timestart Seconds from when timer_start() is called.
 * @global float   $timeend   Seconds from when function is called.
 *
 * @param int|bool $display   Whether to echo or return the results. Accepts 0|false for return,
 *                            1|true for echo. Default 0|false.
 * @param int      $precision The number of digits from the right of the decimal to display.
 *                            Default 3.
 * @return string The "second.microsecond" finished time calculation. The number is formatted
 *                for human consumption, both localized and rounded.
 */
function timer_stop( $display = 0, $precision = 3 ) {
	global $timestart, $timeend;
	$timeend = microtime( true );
	$timetotal = $timeend - $timestart;
	$r = ( function_exists( 'number_format_i18n' ) ) ? number_format_i18n( $timetotal, $precision ) : number_format( $timetotal, $precision );
	if ( $display )
		echo $r;
	return $r;
}

// refactored. function wp_debug_mode() {}
// :
// refactored. function wp_start_object_cache() {}

/**
 * Redirect to the installer if WordPress is not installed.
 *
 * Dies with an error message when Multisite is enabled.
 *
 * @since 3.0.0
 * @access private
 */
function wp_not_installed() {
	if ( is_multisite() ) {
		if ( ! is_blog_installed() && ! wp_installing() ) {
			nocache_headers();

			wp_die( __( 'The site you have requested is not installed properly. Please contact the system administrator.' ) );
		}
	} elseif ( ! is_blog_installed() && ! wp_installing() ) {
		nocache_headers();

		require( ABSPATH . WPINC . '/kses.php' );
		require( ABSPATH . WPINC . '/pluggable.php' );
		require( ABSPATH . WPINC . '/formatting.php' );

		$link = wp_guess_url() . '/wp-admin/install.php';

		wp_redirect( $link );
		die();
	}
}

/**
 * Retrieve an array of must-use plugin files.
 *
 * The default directory is wp-content/mu-plugins. To change the default
 * directory manually, define `WPMU_PLUGIN_DIR` and `WPMU_PLUGIN_URL`
 * in wp-config.php.
 *
 * @since 3.0.0
 * @access private
 *
 * @return array Files to include.
 */
function wp_get_mu_plugins() {
	$mu_plugins = array();
	if ( !is_dir( WPMU_PLUGIN_DIR ) )
		return $mu_plugins;
	if ( ! $dh = opendir( WPMU_PLUGIN_DIR ) )
		return $mu_plugins;
	while ( ( $plugin = readdir( $dh ) ) !== false ) {
		if ( substr( $plugin, -4 ) == '.php' )
			$mu_plugins[] = WPMU_PLUGIN_DIR . '/' . $plugin;
	}
	closedir( $dh );
	sort( $mu_plugins );

	return $mu_plugins;
}

/**
 * Retrieve an array of active and valid plugin files.
 *
 * While upgrading or installing WordPress, no plugins are returned.
 *
 * The default directory is wp-content/plugins. To change the default
 * directory manually, define `WP_PLUGIN_DIR` and `WP_PLUGIN_URL`
 * in wp-config.php.
 *
 * @since 3.0.0
 * @access private
 *
 * @return array Files.
 */
function wp_get_active_and_valid_plugins() {
	$plugins = array();
	$active_plugins = (array) get_option( 'active_plugins', array() );

	// Check for hacks file if the option is enabled
	if ( get_option( 'hack_file' ) && file_exists( ABSPATH . 'my-hacks.php' ) ) {
		_deprecated_file( 'my-hacks.php', '1.5.0' );
		array_unshift( $plugins, ABSPATH . 'my-hacks.php' );
	}

	if ( empty( $active_plugins ) || wp_installing() )
		return $plugins;

	$network_plugins = is_multisite() ? wp_get_active_network_plugins() : false;

	foreach ( $active_plugins as $plugin ) {
		if ( ! validate_file( $plugin ) // $plugin must validate as file
			&& '.php' == substr( $plugin, -4 ) // $plugin must end with '.php'
			&& file_exists( WP_PLUGIN_DIR . '/' . $plugin ) // $plugin must exist
			// not already included as a network plugin
			&& ( ! $network_plugins || ! in_array( WP_PLUGIN_DIR . '/' . $plugin, $network_plugins ) )
			)
		$plugins[] = WP_PLUGIN_DIR . '/' . $plugin;
	}
	return $plugins;
}

/**
 * Set internal encoding.
 *
 * In most cases the default internal encoding is latin1, which is
 * of no use, since we want to use the `mb_` functions for `utf-8` strings.
 *
 * @since 3.0.0
 * @access private
 */
function wp_set_internal_encoding() {
	if ( function_exists( 'mb_internal_encoding' ) ) {
		$charset = get_option( 'blog_charset' );
		if ( ! $charset || ! @mb_internal_encoding( $charset ) )
			mb_internal_encoding( 'UTF-8' );
	}
}

/**
 * Add magic quotes to `$_GET`, `$_POST`, `$_COOKIE`, and `$_SERVER`.
 *
 * Also forces `$_REQUEST` to be `$_GET + $_POST`. If `$_SERVER`,
 * `$_COOKIE`, or `$_ENV` are needed, use those superglobals directly.
 *
 * @since 3.0.0
 * @access private
 */
function wp_magic_quotes() {
	// If already slashed, strip.
	if ( get_magic_quotes_gpc() ) {
		$_GET    = stripslashes_deep( $_GET    );
		$_POST   = stripslashes_deep( $_POST   );
		$_COOKIE = stripslashes_deep( $_COOKIE );
	}

	// Escape with wpdb.
	$_GET    = add_magic_quotes( $_GET    );
	$_POST   = add_magic_quotes( $_POST   );
	$_COOKIE = add_magic_quotes( $_COOKIE );
	$_SERVER = add_magic_quotes( $_SERVER );

	// Force REQUEST to be GET + POST.
	$_REQUEST = array_merge( $_GET, $_POST );
}

/**
 * Runs just before PHP shuts down execution.
 *
 * @since 1.2.0
 * @access private
 */
function shutdown_action_hook() {
	/**
	 * Fires just before PHP shuts down execution.
	 *
	 * @since 1.2.0
	 */
	do_action( 'shutdown' );

	wp_cache_close();
}

/**
 * Copy an object.
 *
 * @since 2.7.0
 * @deprecated 3.2.0
 *
 * @param object $object The object to clone.
 * @return object The cloned object.
 */
function wp_clone( $object ) {
	// Use parens for clone to accommodate PHP 4. See #17880
	return clone( $object );
}

// refactored. function is_admin() {}

/**
 * Whether the current request is for a site's admininstrative interface.
 *
 * e.g. `/wp-admin/`
 *
 * Does not check if the user is an administrator; current_user_can()
 * for checking roles and capabilities.
 *
 * @since 3.1.0
 *
 * @global WP_Screen $current_screen
 *
 * @return bool True if inside WordPress blog administration pages.
 */
function is_blog_admin() {
	if ( isset( $GLOBALS['current_screen'] ) )
		return $GLOBALS['current_screen']->in_admin( 'site' );
	elseif ( defined( 'WP_BLOG_ADMIN' ) )
		return WP_BLOG_ADMIN;

	return false;
}

// refactored. function is_network_admin() {}
// :
// refactored. function wp_doing_ajax() {}

/**
 * Determines whether the current request is a WordPress cron request.
 *
 * @since 4.8.0
 *
 * @return bool True if it's a WordPress cron request, false otherwise.
 */
function wp_doing_cron() {
	/**
	 * Filters whether the current request is a WordPress cron request.
	 *
	 * @since 4.8.0
	 *
	 * @param bool $wp_doing_cron Whether the current request is a WordPress cron request.
	 */
	return apply_filters( 'wp_doing_cron', defined( 'DOING_CRON' ) && DOING_CRON );
}

// refactored. function is_wp_error( $thing ) {}
// refactored. function wp_is_file_mod_allowed( $context ) {}

/**
 * Start scraping edited file errors.
 *
 * @since 4.9.0
 */
function wp_start_scraping_edited_file_errors() {
	if ( ! isset( $_REQUEST['wp_scrape_key'] ) || ! isset( $_REQUEST['wp_scrape_nonce'] ) ) {
		return;
	}
	$key = substr( sanitize_key( wp_unslash( $_REQUEST['wp_scrape_key'] ) ), 0, 32 );
	$nonce = wp_unslash( $_REQUEST['wp_scrape_nonce'] );

	if ( get_transient( 'scrape_key_' . $key ) !== $nonce ) {
		echo "###### wp_scraping_result_start:$key ######";
		echo wp_json_encode( array(
			'code' => 'scrape_nonce_failure',
			'message' => __( 'Scrape nonce check failed. Please try again.' ),
		) );
		echo "###### wp_scraping_result_end:$key ######";
		die();
	}
	register_shutdown_function( 'wp_finalize_scraping_edited_file_errors', $key );
}

/**
 * Finalize scraping for edited file errors.
 *
 * @since 4.9.0
 *
 * @param string $scrape_key Scrape key.
 */
function wp_finalize_scraping_edited_file_errors( $scrape_key ) {
	$error = error_get_last();
	echo "\n###### wp_scraping_result_start:$scrape_key ######\n";
	if ( ! empty( $error ) && in_array( $error['type'], array( E_CORE_ERROR, E_COMPILE_ERROR, E_ERROR, E_PARSE, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
		$error = str_replace( ABSPATH, '', $error );
		echo wp_json_encode( $error );
	} else {
		echo wp_json_encode( true );
	}
	echo "\n###### wp_scraping_result_end:$scrape_key ######\n";
}
