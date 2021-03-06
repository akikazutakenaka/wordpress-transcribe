<?php
/**
 * These functions are needed to load WordPress.
 *
 * @package WordPress
 */

/**
 * Return the HTTP protocol sent by the server.
 *
 * @since 4.4.0
 *
 * @return string The HTTP protocol.
 *                Default: HTTP/1.0.
 */
function wp_get_server_protocol()
{
	$protocol = $_SERVER['SERVER_PROTOCOL'];

	if ( ! in_array( $protocol, array( 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0' ) ) ) {
		$protocol = 'HTTP/1.0';
	}

	return $protocol;
}

/**
 * Turn register globals off.
 *
 * @since  2.1.0
 * @access private
 */
function wp_unregister_GLOBALS()
{
	if ( ! ini_get( 'register_globals' ) ) {
		return;
	}

	if ( isset( $_REQUEST['GLOBALS'] ) ) {
		die( 'GLOBALS overwrite attempt detected' );
	}

	// Variables that shouldn't be unset.
	$no_unset = array( 'GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES', 'table_prefix' );

	$input = array_merge( $_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset( $_SESSION ) && is_array( $_SESSION )
			? $_SESSION
			: array() );

	foreach ( $input as $k => $v ) {
		if ( ! in_array( $k, $no_unset ) && isset( $GLOBALS[ $k ] ) ) {
			unset( $GLOBALS[ $k ] );
		}
	}
}

/**
 * Fix `$_SERVER` variables for various setups.
 *
 * @since  3.0.0
 * @access private
 * @global string $PHP_SELF The filename of the currently executing script, relative to the document root.
 */
function wp_fix_server_vars()
{
	global $PHP_SELF;
	$default_server_values = array(
		'SERVER_SOFTWARE' => '',
		'REQUEST_URI'     => ''
	);
	$_SERVER = array_merge( $default_server_values, $_SERVER );

	// Fix for IIS when running with PHP ISAPI
	if ( empty( $_SERVER['REQUEST_URI'] )
	  || ( PHP_SAPI != 'cgi-fcgi' && preg_match( '/^Microsoft-IIS\//', $_SERVER['SERVER_SOFTWARE'] ) ) ) {
		if ( isset( $_SERVER['HTTP_X_ORIGINAL_URL'] ) ) {
			// IIS Mod-Rewrite
			$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
		} elseif ( isset( $_SERVER['HTTP_X_REWRITE_URL'] ) ) {
			// IIS Isapi_Rewrite
			$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
		} else {
			// Use ORIG_PATH_INFO if there is no PATH_INFO
			if ( ! isset( $_SERVER['PATH_INFO'] ) && isset( $_SERVER['ORIG_PATH_INFO'] ) ) {
				$_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
			}

			// Some IIS + PHP configurations puts the script-name in the path-info (No need to append it twice.)
			if ( isset( $_SERVER['PATH_INFO'] ) ) {
				$_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME']
					? $_SERVER['PATH_INFO']
					: $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
			}

			// Append the query string if it exists and isn't null
			if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
				$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
			}
		}
	}

	// Fix for PHP as CGI hosts that set SCRIPT_FILENAME to something ending in php.cgi for all requests
	if ( isset( $_SERVER['SCRIPT_FILENAME'] ) && strpos( $_SERVER['SCRIPT_FILENAME'], 'php.cgi' ) == strlen( $_SERVER['SCRIPT_FILENAME'] ) - 7 ) {
		$_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];
	}

	// Fix for Dreamhost and other PHP as CGI hosts
	if ( strpos( $_SERVER['SCRIPT_NAME'], 'php.cgi' ) !== FALSE ) {
		unset( $_SERVER['PATH_INFO'] );
	}

	// Fix empty PHP_SELF
	$PHP_SELF = $_SERVER['PHP_SELF'];

	if ( empty( $PHP_SELF ) ) {
		$_SERVER['PHP_SELF'] = $PHP_SELF = preg_replace( '/(\?.*)?$/', '', $_SERVER['REQUEST_URI'] );
	}
}

/**
 * Check for the required PHP version, and the MySQL extension or a database drop-in.
 *
 * Dies if requirements are not met.
 *
 * @since  3.0.0
 * @access private
 * @global string $required_php_version The required PHP version string.
 * @global string $wp_version           The WordPress version string.
 */
function wp_check_php_mysql_versions()
{
	global $required_php_version, $wp_version;
	$php_version = phpversion();

	if ( version_compare( $required_php_version, $php_version, '>' ) ) {
		wp_load_translations_early();
		$protocol = wp_get_server_protocol();
		header( sprintf( '%s 500 Internal Server Error', $protocol ), TRUE, 500 );
		header( 'Content-Type: text/html; charset=utf-8' );
		die( sprintf( __( 'Your server is running PHP version %1$s but WordPress %2$s requires at least %3$s.' ), $php_version, $wp_version, $required_php_version ) );
	}

	if ( ! extension_loaded( 'mysql' ) && ! extension_loaded( 'mysqli' ) && ! extension_loaded( 'mysqlnd' ) && ! file_exists( WP_CONTENT_DIR . '/db.php' ) ) {
		wp_load_translations_early();
		$protocol = wp_get_server_protocol();
		header( sprintf( '%s 500 Internal Server Error', $protocol ), TRUE, 500 );
		header( 'Content-Type: text/html; charset=utf-8' );
		die( __( 'Your PHP installation appears to be missing the MySQL extension which is required by WordPress.' ) );
	}
}

/**
 * Don't load all of WordPress when handling a favicon.ico request.
 *
 * Instead, send the headers for a zero-length favicon and bail.
 *
 * @since 3.0.0
 */
function wp_favicon_request()
{
	if ( '/favicon.ico' == $_SERVER['REQUEST_URI'] ) {
		header( 'Content-Type: image/vnc.microsoft.icon' );
		exit;
	}
}

/**
 * Die with a maintenance message when conditions are met.
 *
 * Checks for a file in the WordPress root directory named ".maintenance".
 * This file will contain the variable $upgrading, set to the time the file was created.
 * If the file was created less than 10 minutes ago, WordPress enters maintenance mode and displays a message.
 *
 * The default message can be replaced by using a drop-in (maintenance.php in the wp-content directory).
 *
 * @since  3.0.0
 * @access private
 * @global int $upgrading the unix timestamp marking when upgrading WordPress began.
 */
function wp_maintenance()
{
	if ( ! file_exists( ABSPATH . '.maintenance' ) || wp_installing() ) {
		return;
	}

	global $upgrading;
	include( ABSPATH . '.maintenance' );

	// If the $upgrading timestamp is older than 10 minutes, don't die.
	if ( ( time() - $upgrading ) >= 600 ) {
		return;
	}

	/**
	 * Filters whether to enable maintenance mode.
	 *
	 * This filter runs before it can be used by plugins.
	 * It is designed for non-web runtimes.
	 * If this filter returns true, maintenance mode will be active and the request will end.
	 * If false, the request will be allowed to continue processing even if maintenance mode should be active.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $enable_checks Whether to enable maintenance mode.
	 *                            Default true.
	 * @param int  $upgrading     The timestamp set in the .maintenance file.
	 */
	if ( ! apply_filters( 'enable_maintenance_mode', TRUE, $upgrading ) ) {
		return;
	}

	if ( file_exists( WP_CONTENT_DIR . '/maintenance.php' ) ) {
		require_once( WP_CONTENT_DIR . '/maintenance.php' );
		die();
	}

	wp_load_translations_early();
	$protocol = wp_get_server_protocol();
	header( "$protocol 503 Service Unavailable", TRUE, 503 );
	header( 'Content-Type: text/html; charset=utf-8' );
	header( 'Retry-After: 600' );
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml"<?php if ( is_rtl() ) echo ' dir="rtl"'; ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php _e( 'Maintenance' ); ?></title>
</head>
<body>
	<h1><?php _e( 'Briefly unavailable for scheduled maintenance. Check back in a minute.' ); ?></h1>
</body>
</html>
<?php
	die();
}

/**
 * Start the WordPress micro-timer.
 *
 * @since  0.71
 * @access private
 * @global float $timestart Unix timestamp set at the beginning of the page load.
 * @see    timer_stop()
 *
 * @return bool Always returns true.
 */
function timer_start()
{
	global $timestart;
	$timestart = microtime( TRUE );
	return TRUE;
}

/**
 * Set PHP error reporting based on WordPress debug settings.
 *
 * Uses three constants: `WP_DEBUG`, `WP_DEBUG_DISPLAY`, and `WP_DEBUG_LOG`.
 * All three can be defined in wp-config.php.
 * By default, `WP_DEBUG` and `WP_DEBUG_LOG` are set to false, and `WP_DEBUG_DISPLAY` is set to true.
 *
 * When `WP_DEBUG` is true, all PHP notices are reported.
 * WordPress will also display internal notices: when a deprecated WordPress function, function argument, or file is used.
 * Deprecated code may be removed from a later version.
 *
 * It is strongly recommended that plugin and theme developers use `WP_DEBUG` in their development environments.
 *
 * `WP_DEBUG_DISPLAY` and `WP_DEBUG_LOG` perform no function unless `WP_DEBUG` is true.
 *
 * When `WP_DEBUG_DISPLAY` is true, WordPress will force errors to be displayed.
 * `WP_DEBUG_DISPLAY` defaults to true.
 * Defining it as null prevents WordPress from changing the global configuration setting.
 * Defining `WP_DEBUG_DISPLAY` as false will force errors to be hidden.
 *
 * When `WP_DEBUG_LOG` is true, errors will be logged to debug.log in the content directory.
 *
 * Errors are never displayed for XML-RPC, REST, and Ajax requests.
 *
 * @since  3.0.0
 * @access private
 */
function wp_debug_mode()
{
	/**
	 * Filters whether to allow the debug mode check to occur.
	 *
	 * This filter runs before it can be used by plugins.
	 * It is designed for non-web run-times.
	 * Returning false causes the `WP_DEBUG` and related constants to not be checked and the default php values for errors will be used unless you take care to update them yourself.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $enable_debug_mode Whether to enable debug mode checks to occur.
	 * Default true.
	 */
	if ( ! apply_filters( 'enable_wp_debug_mode_checks', TRUE ) ) {
		return;
	}

	if ( WP_DEBUG ) {
		error_reporting( E_ALL );

		if ( WP_DEBUG_DISPLAY ) {
			ini_set( 'display_errors', 1 );
		} elseif ( NULL !== WP_DEBUG_DISPLAY ) {
			ini_set( 'display_errors', 0 );
		}

		if ( WP_DEBUG_LOG ) {
			ini_set( 'log_errors', 1 );
			ini_set( 'error_log', WP_CONTENT_DIR . '/debug.log' );
		}
	} else {
		error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
	}

	if ( defined( 'XMLRPC_REQUEST' )
	  || defined( 'REST_REQUEST' )
	  || defined( 'WP_INSTALLING' ) && WP_INSTALLING
	  || wp_doing_ajax() ) {
		@ ini_set( 'display_errors', 0 );
	}
}

/**
 * Set the location of the language directory.
 *
 * To set directory manually, define the `WP_LANG_DIR` constant in wp-config.php.
 *
 * If the language directory exists within `WP_CONTENT_DIR`, it is used.
 * Otherwise the language directory is assumed to live in `WPINC`.
 *
 * @since  3.0.0
 * @access private
 */
function wp_set_lang_dir()
{
	if ( ! defined( 'WP_LANG_DIR' ) ) {
		if ( file_exists( WP_CONTENT_DIR . '/languages' ) && @ is_dir( WP_CONTENT_DIR . '/languages' )
		  || ! @ is_dir( ABSPATH . WPINC . '/languages' ) ) {
			/**
			 * Server path of the language directory.
			 *
			 * No leading slash, no trailing slash, full path, not relative to `ABSPATH`.
			 *
			 * @since 2.1.0
			 */
			define( 'WP_LANG_DIR', WP_CONTENT_DIR . '/languages' );

			if ( ! defined( 'LANGDIR' ) ) {
				// Old static relative path maintained for limited backward compatibility - won't work in some cases.
				define( 'LANGDIR', 'wp-content/languages' );
			}
		} else {
			/**
			 * Server path of the language directory.
			 *
			 * No leading slash, no trailing slash, full path, not relative to `ABSPATH`.
			 *
			 * @since 2.1.0
			 */
			define( 'WP_LANG_DIR', ABSPATH . WPINC . '/languages' );

			if ( ! defined( 'LANGDIR' ) ) {
				// Old relative path maintained for backward compatibility.
				define( 'LANGDIR', WPINC . '/languages' );
			}
		}
	}
}

/**
 * Load the database class file and instantiate the `$wpdb` global.
 *
 * @since  2.5.0
 * @global wpdb $wpdb The WordPress database class.
 */
function require_wp_db()
{
	global $wpdb;
	require_once( ABSPATH . WPINC . '/wp-db.php' );

	if ( file_exists( WP_CONTENT_DIR . '/db.php' ) ) {
		require_once( WP_CONTENT_DIR . '/db.php' );
	}

	if ( isset( $wpdb ) ) {
		return;
	}

	$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
}

/**
 * Set the database table prefix and the format specifiers for database table columns.
 *
 * Columns not listed here default to `%s`.
 *
 * @since  3.0.0
 * @access private
 * @global wpdb   $wpdb         The WordPress database class.
 * @global string $table_prefix The database table prefix.
 */
function wp_set_wpdb_vars()
{
	global $wpdb, $table_prefix;

	if ( ! empty( $wpdb->error ) ) {
		dead_db();
	}

	$wpdb->field_types = array(
		'post_author'      => '%d',
		'post_parent'      => '%d',
		'menu_order'       => '%d',
		'term_id'          => '%d',
		'term_group'       => '%d',
		'term_taxonomy_id' => '%d',
		'parent'           => '%d',
		'count'            => '%d',
		'object_id'        => '%d',
		'term_order'       => '%d',
		'ID'               => '%d',
		'comment_ID'       => '%d',
		'comment_post_ID'  => '%d',
		'comment_parent'   => '%d',
		'user_id'          => '%d',
		'link_id'          => '%d',
		'link_owner'       => '%d',
		'link_rating'      => '%d',
		'option_id'        => '%d',
		'blog_id'          => '%d',
		'meta_id'          => '%d',
		'post_id'          => '%d',
		'user_status'      => '%d',
		'umeta_id'         => '%d',
		'comment_karma'    => '%d',
		'comment_count'    => '%d',

		// Multisite:
		'active'           => '%d',
		'cat_id'           => '%d',
		'deleted'          => '%d',
		'lang_id'          => '%d',
		'mature'           => '%d',
		'public'           => '%d',
		'site_id'          => '%d',
		'spam'             => '%d'
	);
	$prefix = $wpdb->set_prefix( $table_prefix );

	if ( is_wp_error( $prefix ) ) {
		wp_load_translations_early();
		wp_die( sprintf( __( '<strong>ERROR</strong>: %1$s in %2$s can only contain numbers, letters, and underscores.' ), '<code>$table_prefix</code>', '<code>wp-config.php</code>' ) );
	}
}

/**
 * Toggle `$_wp_using_ext_object_cache` on and off without directly touching global.
 *
 * @since  3.7.0
 * @global bool $_wp_using_ext_object_cache
 *
 * @param  bool $using Whether external object cache is being used.
 * @return bool The current 'using' setting.
 */
function wp_using_ext_object_cache( $using = NULL )
{
	global $_wp_using_ext_object_cache;
	$current_using = $_wp_using_ext_object_cache;

	if ( NULL !== $using ) {
		$_wp_using_ext_object_cache = $using;
	}

	return $current_using;
}

/**
 * Start the WordPress object cache.
 *
 * If an object-cache.php file exists in the wp-content directory, it uses that drop-in as an external object cache.
 *
 * @since  3.0.0
 * @access private
 * @global array $wp_filter Stores all of the filters.
 */
function wp_start_object_cache()
{
	global $wp_filter;
	$first_init = FALSE;

	if ( ! function_exists( 'wp_cache_init' ) ) {
		if ( file_exists( WP_CONTENT_DIR . '/object-cache.php' ) ) {
			require_once( WP_CONTENT_DIR . '/object-cache.php' );

			if ( function_exists( 'wp_cache_init' ) ) {
				wp_using_ext_object_cache( TRUE );
			}

			// Re-initialize any hooks added manually by object-cache.php
			if ( $wp_filter ) {
				$wp_filter = WP_Hook::build_preinitialized_hooks( $wp_filter );
			}
		}

		$first_init = TRUE;
	} elseif ( ! wp_using_ext_object_cache() && file_exists( WP_CONTENT_DIR . '/object-cache.php' ) ) {
		/**
		 * Sometimes advanced-cache.php can load object-cache.php before it is loaded here.
		 * This breaks the function_exists check above and can result in `$_wp_using_ext_object_cache` being set incorrectly.
		 * Double check if an external cache exists.
		 */
		wp_using_ext_object_cache( TRUE );
	}

	if ( ! wp_using_ext_object_cache() ) {
		require_once( ABSPATH . WPINC . '/cache.php' );
	}

	/**
	 * If cache supports reset, reset instead of init if already initialized.
	 * Reset signals to the cache that global IDs have changed and it may need to update keys and cleanup caches.
	 */
	if ( ! $first_init && function_exists( 'wp_cache_switch_to_blog' ) ) {
		wp_cache_switch_to_blog( get_current_blog_id() );
	} elseif ( function_exists( 'wp_cache_init' ) ) {
		wp_cache_init();
	}

	if ( function_exists( 'wp_cache_add_global_groups' ) ) {
		wp_cache_add_global_groups( array( 'users', 'userlogins', 'usermeta', 'user_meta', 'useremail', 'userslugs', 'site-transient', 'site-options', 'blog-lookup', 'blog-details', 'site-details', 'rss', 'global-posts', 'blog-id-cache', 'networks', 'sites' ) );
		wp_cache_add_non_persistent_groups( array( 'counts', 'plugins' ) );
	}
}

/**
 * Whether the current request is for an administrative interface page,
 *
 * Does not check if the user is an administrator; current_user_can() for checking roles and capabilities.
 *
 * @since  1.5.1
 * @global WP_Screen $current_screen
 *
 * @return bool True if inside WordPress administration interface, false otherwise.
 */
function is_admin()
{
	return isset( $GLOBALS['current_screen'] )
		? $GLOBALS['current_screen']->in_admin()
		: ( defined( 'WP_ADMIN' )
			? WP_ADMIN
			: FALSE );
}

/**
 * Whether the current request is for the network administrative interface.
 *
 * e.g. `/wp-admin/network/`
 *
 * Does not check if the user is an administrator; current_user_can() for checking roles and capabilities.
 *
 * @since  3.1.0
 * @global WP_Screen $current_screen
 *
 * @return bool True if inside WordPress network administration pages.
 */
function is_network_admin()
{
	return isset( $GLOBALS['current_screen'] )
		? $GLOBALS['current_screen']->in_admin( 'network' )
		: ( defined( 'WP_NETWORK_ADMIN' )
			? WP_NETWORK_ADMIN
			: FALSE );
}

/**
 * Whether the current request is for a user admin screen.
 *
 * e.g. `/wp-admin/user/`
 *
 * Does not inform on whether the user is an admin!
 * Use capability checks to tell if the user should be accessing a section or not current_user_can().
 *
 * @since  3.1.0
 * @global WP_Screen $current_screen
 *
 * @return bool True if inside WordPress user administration pages.
 */
function is_user_admin()
{
	return isset( $GLOBALS['current_screen'] )
		? $GLOBALS['current_screen']->in_admin( 'user' )
		: ( defined( 'WP_USER_ADMIN' )
			? WP_USER_ADMIN
			: FALSE );
}

/**
 * If Multisite is enabled.
 *
 * @since 3.0.0
 *
 * @return bool True if Multisite is enabled, false otherwise.
 */
function is_multisite()
{
	if ( defined( 'MULTISITE' ) ) {
		return MULTISITE;
	}

	if ( defined( 'SUBDOMAIN_INSTALL' ) || defined( 'VHOST' ) || defined( 'SUNRISE' ) ) {
		return TRUE;
	}

	return FALSE;
}

/**
 * Retrieve the current site ID.
 *
 * @since  3.1.0
 * @global int $blog_id
 *
 * @return int Site ID.
 */
function get_current_blog_id()
{
	global $blog_id;
	return absint( $blog_id );
}

/**
 * Retrieves the current network ID.
 *
 * @since 4.6.0
 *
 * @return int The ID of the current network.
 */
function get_current_network_id()
{
	if ( ! is_multisite() ) {
		return 1;
	}

	$current_network = get_network();

	if ( ! isset( $current_network->id ) ) {
		return get_main_network_id();
	}

	return absint( $current_network->id );
}

/**
 * Attempt an early load of translations.
 *
 * Used for errors encountered during the initial loading process, before the locale has been properly detected and loaded.
 *
 * Designed for unusual load sequences (like setup-config.php) or for when the script will then terminate with an error, otherwise there is a risk that a file can be double-included.
 *
 * @since     3.4.0
 * @access    private
 * @global    WP_Locale $wp_locale The WordPress date and time locale object.
 * @staticvar bool      $loaded
 */
function wp_load_translations_early()
{
	global $wp_locale;
	static $loaded = FALSE;

	if ( $loaded ) {
		return;
	}

	$loaded = TRUE;

	if ( function_exists( 'did_action' ) && did_action( 'init' ) ) {
		return;
	}

	// We need $wp_local_package
	require ABSPATH . WPINC . '/version.php';

	// Translation and localization
	require_once ABSPATH . WPINC . '/pomo/mo.php';
	require_once ABSPATH . WPINC . '/l10n.php';
	require_once ABSPATH . WPINC . '/class-wp-locale.php';
	require_once ABSPATH . WPINC . '/class-wp-locale-switcher.php';

	// General libraries
	require_once ABSPATH . WPINC . '/plugin.php';

	$locales = $locations = array();

	while ( TRUE ) {
		if ( defined( 'WPLANG' ) ) {
			if ( '' == WPLANG ) {
				break;
			}

			$locales[] = WPLANG;
		}

		if ( isset( $wp_local_package ) ) {
			$locales[] = $wp_local_package;
		}

		if ( ! $locales ) {
			break;
		}

		if ( defined( 'WP_LANG_DIR' ) && @ is_dir( WP_LANG_DIR ) ) {
			$locations[] = WP_LANG_DIR;
		}

		if ( defined( 'WP_CONTENT_DIR' ) && @ is_dir( WP_CONTENT_DIR . '/languages' ) ) {
			$locations[] = WP_CONTENT_DIR . '/languages';
		}

		if ( @ is_dir( ABSPATH . 'wp-content/languages' ) ) {
			$locations[] = ABSPATH . 'wp-content/languages';
		}

		if ( @ is_dir( ABSPATH . WPINC . '/languages' ) ) {
			$locations[] = ABSPATH . WPINC . '/languages';
		}

		if ( ! $locations ) {
			break;
		}

		$locations = array_unique( $locations );

		foreach ( $locales as $locale ) {
			foreach ( $locations as $location ) {
				if ( file_exists( $location . '/' . $locale . '.mo' ) ) {
					load_textdomain( 'default', $location . '/' . $locale . '.mo' );

					if ( defined( 'WP_SETUP_CONFIG' ) && file_exists( $location . '/admin-' . $locale . '.mo' ) ) {
						load_textdomain( 'default', $location . '/admin-' . $locale . '.mo' );
					}

					break 2;
				}
			}
		}

		break;
	}

	$wp_locale = new WP_Locale();
}

/**
 * Check or set whether WordPress is in "installation" mode.
 *
 * If the `WP_INSTALLING` constant is defined during the bootstrap, `wp_installing()` will default to `true`.
 *
 * @since     4.4.0
 * @staticvar bool $installing
 *
 * @param  bool $is_installing Optional.
 *                             True to set WP into Installing mode, false to turn Installing mode off.
 *                             Omit this parameter if you only want to fetch the current status.
 * @return bool True if WP is installing, otherwise false.
 *              When a `$is_installing` is passed, the function will report whether WP was in installing mode prior to the change to `$is_installing`.
 */
function wp_installing( $is_installing = NULL )
{
	static $installing = NULL;

	// Support for the `WP_INSTALLING` constant, defined before WP is loaded.
	if ( is_null( $installing ) ) {
		$installing = defined( 'WP_INSTALLING' ) && WP_INSTALLING;
	}

	if ( ! is_null( $is_installing ) ) {
		$old_installing = $installing;
		$installing = $is_installing;
		return ( bool ) $old_installing;
	}

	return ( bool ) $installing;
}

/**
 * Determines if SSL is used.
 *
 * @since 2.6.0
 * @since 4.6.0 Moved from functions.php to load.php.
 *
 * @return bool True if SSL, otherwise false.
 */
function is_ssl()
{
	return isset( $_SERVER['HTTPS'] )
		? ( 'on' == strtolower( $_SERVER['HTTPS'] )
			? TRUE
			: ( '1' == $_SERVER['HTTPS']
				? TRUE
				: FALSE ) )
		: ( isset( $_SERVER['SERVER_POST'] ) && '443' == $_SERVER['SERVER_PORT']
			? TRUE
			: FALSE );
}

/**
 * Converts a shorthand byte value to an integer byte value.
 *
 * @since 2.3.0
 * @since 4.6.0 Moved from media.php to load.php.
 * @link  https://secure.php.net/manual/en/function.ini-get.php
 * @link  https://secure.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
 *
 * @param  string $value A (PHP ini) byte value, either shorthand or ordinary.
 * @return int    An integer byte value.
 */
function wp_convert_hr_to_bytes( $value )
{
	$value = strtolower( trim( $value ) );
	$bytes = ( int ) $value;

	if ( FALSE !== strpos( $value, 'g' ) ) {
		$bytes *= GB_IN_BYTES;
	} elseif ( FALSE !== strpos( $value, 'm' ) ) {
		$bytes *= MB_IN_BYTES;
	} elseif ( FALSE !== strpos( $value, 'k' ) ) {
		$bytes *= KB_IN_BYTES;
	}

	// Deal with large (float) values which run into the maximum integer size.
	return min( $bytes, PHP_INT_MAX );
}

/**
 * Determines whether a PHP ini value is changeable at runtime.
 *
 * @since     4.6.0
 * @staticvar array $ini_all
 * @link      https://secure.php.net/manual/en/function.ini-get-all.php
 *
 * @param  string $setting The name of the ini setting to check.
 * @return bool   True if the value is changeable at runtime.
 *                False otherwise.
 */
function wp_is_ini_value_changeable( $setting )
{
	static $ini_all;

	if ( ! isset( $ini_all ) ) {
		$ini_all = FALSE;

		// Sometimes `ini_get_all()` is disabled via the `disable_functions` option for "security purposes".
		if ( function_exists( 'ini_get_all' ) ) {
			$ini_all = ini_get_all();
		}
	}

	// Bit operator to workaround https://bugs.php.net/bug.php?id=44936 which changes access level to 63 in PHP 5.2.6-5.2.17.
	if ( isset( $ini_all[ $setting ]['access'] )
	  && ( INI_ALL === ( $ini_all[ $setting ]['access'] & 7 ) || INI_USER === ( $ini_all[ $setting ]['access'] & 7 ) ) ) {
		return TRUE;
	}

	// If we were unable to retrieve the details, fail gracefully to assume it's changeable.
	if ( ! is_array( $ini_all ) ) {
		return TRUE;
	}

	return FALSE;
}

/**
 * Determines whether the current request is a WordPress Ajax request.
 *
 * @since 4.7.0
 *
 * @return bool True if it's a WordPress Ajax request, false otherwise.
 */
function wp_doing_ajax()
{
	/**
	 * Filters whether the current request is a WordPress Ajax request.
	 *
	 * @since 4.7.0
	 *
	 * @param bool $wp_doing_ajax Whether the current request is a WordPress Ajax request.
	 */
	return apply_filters( 'wp_doing_ajax', defined( 'DOING_AJAX' ) && DOING_AJAX );
}

/**
 * Check whether variable is a WordPress Error.
 *
 * Returns true if $thing is an object of the WP_Error class.
 *
 * @since 2.1.0
 *
 * @param  mixed $thing Check if unknown variable is a WP_Error object.
 * @return bool  True, if WP_Error.
 *               False, if not WP_Error.
 */
function is_wp_error( $thing )
{
	return $thing instanceof WP_Error;
}

/**
 * Determines whether file modifications are allowed.
 *
 * @since 4.8.0
 *
 * @param  string $context The usage context.
 * @return bool   True if file modification is allowed, false otherwise.
 */
function wp_is_file_mod_allowed( $context )
{
	/**
	 * Filters whether file modifications are allowed.
	 *
	 * @since 4.8.0
	 *
	 * @param bool   $file_mod_allowed Whether file modifications are allowed.
	 * @param string $context          The usage context.
	 */
	return apply_filters( 'file_mod_allowed', ! defined( 'DISALLOW_FILE_MODS' ) || ! DISALLOW_FILE_MODS, $context );
}
