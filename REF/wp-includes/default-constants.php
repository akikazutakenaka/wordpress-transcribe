<?php
/**
 * Defines constants and global variables that can be overridden, generally in wp-config.php.
 *
 * @package WordPress
 */

/**
 * Defines initial WordPress constants
 *
 * @see    wp_debug_mode()
 * @since  3.0.0
 * @global int    $blog_id    The current site ID.
 * @global string $wp_version The WordPress version string.
 */
function wp_initial_constants()
{
	global $blog_id;

	/**
	 * Constants for expressing human-readable data sizes in their respective number of bytes.
	 *
	 * @since 4.4.0
	 */
	define( 'KB_IN_BYTES', 1024 );
	define( 'MB_IN_BYTES', 1024 * KB_IN_BYTES );
	define( 'GB_IN_BYTES', 1024 * MB_IN_BYTES );
	define( 'TB_IN_BYTES', 1024 * GB_IN_BYTES );

	$current_limit     = @ini_get( 'memory_limit' );
	$current_limit_int = wp_convert_hr_to_bytes( $current_limit );

	// Define memory limits.
	if ( ! defined( 'WP_MEMORY_LIMIT' ) ) {
		if ( FALSE === wp_is_ini_value_changeable( 'memory_limit' ) ) {
			define( 'WP_MEMORY_LIMIT', $current_limit );
		} elseif ( is_multisite() ) {
			define( 'WP_MEMORY_LIMIT', '64M' );
		} else {
			define( 'WP_MEMORY_LIMIT', '40M' );
		}
	}

	if ( ! defined( 'WP_MAX_MEMORY_LIMIT' ) ) {
		if ( FALSE === wp_is_ini_value_changeable( 'memory_limit' ) ) {
			define( 'WP_MAX_MEMORY_LIMIT', $current_limit );
		} elseif ( -1 === $current_limit_int || $current_limit_int > 268435456 ) {
			define( 'WP_MAX_MEMORY_LIMIT', $current_limit );
		} else {
			define( 'WP_MAX_MEMORY_LIMIT', '256M' );
		}
	}

	// Set memory limits.
	$wp_limit_int = wp_convert_hr_to_bytes( WP_MEMORY_LIMIT );

	if ( -1 !== $current_limit_int
	  && ( -1 === $wp_limit_int || $wp_limit_int > $current_limit_int ) ) {
		@ini_set( 'memory_limit', WP_MEMORY_LIMIT );
	}

	if ( ! isset( $blog_id ) ) {
		$blog_id = 1;
	}

	if ( ! defined( 'WP_CONTENT_DIR' ) ) {
		define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' ); // No trailing slash, full paths only - WP_CONTENT_URL is defined further down
	}

	// Add define( 'WP_DEBUG', TRUE ); to wp-config.php to enable display of notices during development.
	if ( ! defined( 'WP_DEBUG' ) ) {
		define( 'WP_DEBUG', FALSE );
	}

	/**
	 * Add define( 'WP_DEBUG_DISPLAY', NULL ); to wp-config.php use the globally configured setting for display_errors and not force errors to be displayed.
	 * Use false to force display_errors off.
	 */
	if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
		define( 'WP_DEBUG_DISPLAY', TRUE );
	}

	// Add define( 'WP_DEBUG_LOG', TRUE ); to enable error logging to wp-content/debug.log.
	if ( ! defined( 'WP_DEBUG_LOG' ) ) {
		define( 'WP_DEBUG_LOG', FALSE );
	}

	if ( ! defined( 'WP_CACHE' ) ) {
		define( 'WP_CACHE', FALSE );
	}

	// Add define( 'SCRIPT_DEBUG', TRUE ); to wp-config.php to enable loading of non-minified, non-concatenated scripts and stylesheets.
	if ( ! defined( 'SCRIPT_DEBUG' ) ) {
		$develop_src = ( ! empty( $GLOBALS['wp_version'] ) )
			? ( FALSE !== strpos( $GLOBALS['wp_version'], '-src' ) )
			: FALSE;

		define( 'SCRIPT_DEBUG', $develop_src );
	}

	// Private
	if ( ! defined( 'MEDIA_TRASH' ) ) {
		define( 'MEDIA_TRASH', FALSE );
	}

	if ( ! defined( 'SHORTINIT' ) ) {
		define( 'SHORTINIT', FALSE );
	}

	// Constants for features added to WP that should short-circuit their plugin implementations.
	define( 'WP_FEATURE_BETTER_PASSWORDS', TRUE );

	/**
	 * Constants for expressing human-readable intervals in their respective number of seconds.
	 *
	 * Please not that these values are approximate and are provided for convenience.
	 * For example, MONTH_IN_SECONDS wrongly assumes every month has 30 days and YEAR_IN_SECONDS does not take leap years into account.
	 *
	 * If you need more accuracy please consider using the DateTime class (https://secure.php.net/manual/en/class.datetime.php).
	 *
	 * @since 3.5.0
	 * @since 4.4.0 Introduced `MONTH_IN_SECONDS`.
	 */
	define( 'MINUTE_IN_SECONDS', 60 );
	define( 'HOUR_IN_SECONDS',   60 * MINUTE_IN_SECONDS );
	define( 'DAY_IN_SECONDS',    24 * HOUR_IN_SECONDS );
	define( 'WEEK_IN_SECONDS',    7 * DAY_IN_SECONDS );
	define( 'MONTH_IN_SECONDS',  30 * DAY_IN_SECONDS );
	define( 'YEAR_IN_SECONDS',  365 * DAY_IN_SECONDS );
}

