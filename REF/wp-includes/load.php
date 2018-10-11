<?php
/**
 * These functions are needed to load WordPress.
 *
 * @package WordPress
 */

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
function wp_check_php_mysql_version()
{
	global $required_php_version, $wp_version;
	$php_version = phpversion();

	if ( version_compare( $required_php_version, $php_version, '>' ) ) {
		wp_load_translations_early();
// @NOW 004 -> wp-includes/load.php
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
 * If Multisite is enabled.
 *
 * @since 3.0.0
 *
 * @return bool True if Multisite is enabled, false otherwise.
 */
function is_multisite()
{
	if ( defined( 'MULTISITE' ) )
		return MULTISITE;

	if ( defined( 'SUBDOMAIN_INSTALL' ) || defined( 'VHOST' ) || defined( 'SUNRISE' ) )
		return TRUE;

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

	if ( $loaded )
		return;

	$loaded = TRUE;

	if ( function_exists( 'did_action' ) && did_action( 'init' ) )
		return;

	// We need $wp_local_package
	require ABSPATH . WPINC . '/version.php';

	// Translation and localization
	require_once ABSPATH . WPINC . '/pomo/mo.php';
	require_once ABSPATH . WPINC . '/l10n.php';
	require_once ABSPATH . WPINC . '/class-wp-locale.php';
	require_once ABSPATH . WPINC . '/class-wp-locale-switcher.php';

	// General libraries
	require_once ABSPATH . WPINC . '/plugin.php';

	$locales = $locations = [];

	while ( TRUE ) {
		if ( defined( 'WPLANG' ) ) {
			if ( '' == WPLANG )
				break;

			$locales[] = WPLANG;
		}

		if ( isset( $wp_local_package ) )
			$locales[] = $wp_local_package;

		if ( ! $locales )
			break;

		if ( defined( 'WP_LANG_DIR' ) && @is_dir( WP_LANG_DIR ) )
			$locations[] = WP_LANG_DIR;

		if ( defined( 'WP_CONTENT_DIR' ) && @is_dir( WP_CONTENT_DIR . '/languages' ) )
			$locations[] = WP_CONTENT_DIR . '/languages';

		if ( @is_dir( ABSPATH . 'wp-content/languages' ) )
			$locations[] = ABSPATH . 'wp-content/languages';

		if ( @is_dir( ABSPATH . WPINC . '/languages' ) )
			$locations[] = ABSPATH . WPINC . '/languages';

		if ( ! $locations )
			break;

		$locations = array_unique( $locations );

		foreach ( $locales as $locale )
			foreach ( $locations as $location )
				if ( file_exists( $location . '/' . $locale . '.mo' ) ) {
					load_textdomain( 'default', $location . '/' . $locale . '.mo' );

					if ( defined( 'WP_SETUP_CONFIG' ) && file_exists( $location . '/admin-' . $locale . '.mo' ) )
						load_textdomain( 'default', $location . '/admin-' . $locale . '.mo' );

					break 2;
				}

		break;
	}

	$wp_locale = new WP_Locale();
// @NOW 005 -> wp-includes/class-wp-locale.php
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

	if ( FALSE !== strpos( $value, 'g' ) )
		$bytes *= GB_IN_BYTES;
	elseif ( FALSE !== strpos( $value, 'm' ) )
		$bytes *= MB_IN_BYTES;
	elseif ( FALSE !== strpos( $value, 'k' ) )
		$bytes *= KB_IN_BYTES;

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
		if ( function_exists( 'ini_get_all' ) )
			$ini_all = ini_get_all();
	}

	// Bit operator to workaround https://bugs.php.net/bug.php?id=44936 which changes access level to 63 in PHP 5.2.6-5.2.17.
	if ( isset( $ini_all[$setting]['access'] )
	  && ( INI_ALL === ( $ini_all[$setting]['access'] & 7 ) || INI_USER === ( $ini_all[$setting]['access'] & 7 ) ) )
		return TRUE;

	// If we were unable to retrieve the details, fail gracefully to assume it's changeable.
	if ( ! is_array( $ini_all ) )
		return TRUE;

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
