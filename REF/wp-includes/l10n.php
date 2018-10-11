<?php
/**
 * Core Translation API
 *
 * @package    WordPress
 * @subpackage i18n
 * @since      1.2.0
 */

/**
 * Retrieves the locale of a user.
 *
 * If the user has a locale set to a non-empty string then it will be returned.
 * Otherwise it returns the locale of get_locale().
 *
 * @since 4.7.0
 *
 * @param  int|WP_User $user_id User's ID or a WP_User object.
 *                              Defaults to current user.
 * @return string      The locale of the user.
 */
function get_user_locale( $user_id = 0 )
{
	$user = FALSE;

	if ( 0 === $user_id && function_exists( 'wp_get_current_user' ) )
		$user = wp_get_current_user();
// @NOW 013 -> wp-includes/user.php
}

/**
 * Retrieve the translation of $text.
 *
 * If there is no translation, or the text domain isn't loaded, the original text is returned.
 *
 * *Note:* Don't use translate() directly, use __() or related functions.
 *
 * @since 2.2.0
 *
 * @param  string $text   Text to translate.
 * @param  string $domain Optional.
 *                        Text domain.
 *                        Unique identifier for retrieving translated strings.
 *                        Default 'default'.
 * @return string Translated text.
 */
function translate( $text, $domain = 'default' )
{
	$translations = get_translations_for_domain( $domain );
// @NOW 008 -> wp-includes/l10n.php
}

/**
 * Retrieve the translation of $text.
 *
 * If there is no translation, or text domain isn't loaded, the original text is returned.
 *
 * @since 2.1.0
 *
 * @param  string $text   Text to translate.
 * @param  string $domain Optional.
 *                        Text domain.
 *                        Unique identifier for retrieving translated strings.
 *                        Default 'default'.
 * @return string Translated text.
 */
function __( $text, $domain = 'default' )
{
	return translate( $text, $domain );
}

/**
 * Load a .mo file into the text domain $domain.
 *
 * If the text domain already exists, the translations will be merged.
 * If both sets have the same string, the translation from the original value will be taken.
 *
 * On success, the .mo file will be placed in the $l10n global by $domain and will be a MO object.
 *
 * @since  1.5.0
 * @global array $l10n          An array of all currently loaded text domains.
 * @global array $l10n_unloaded An array of all text domains that have been unloaded again.
 *
 * @param  string $domain Text domain.
 *                        Unique identifier for retrieving translated strings.
 *
 * @param  string $mofile Path to the .mo file.
 * @return bool   True on success, false on failure.
 */
function load_textdomain( $domain, $mofile )
{
	global $l10n, $l10n_unloaded;
	$l10n_unloaded = ( array ) $l10n_unloaded;

	/**
	 * Filters whether to override the .mo file loading.
	 *
	 * @since 2.9.0
	 *
	 * @param bool   $override Whether to override the .mo file loading.
	 *                         Default false.
	 * @param string $domain   Text domain.
	 *                         Unique identifier for retrieving translated strings.
	 * @param string $mofile   Path to the MO file.
	 */
	$plugin_override = apply_filters( 'override_load_textdomain', FALSE, $domain, $mofile );

	if ( TRUE == $plugin_override ) {
		unset( $l10n_unloaded[$domain] );
		return TRUE;
	}

	/**
	 * Fires before the MO translation file is loaded.
	 *
	 * @since 2.9.0
	 *
	 * @param string $domain Text domain.
	 *                       Unique identifier for retrieving translated strings.
	 * @param string $mofile Path to the .mo file.
	 */
	do_action( 'load_textdomain', $domain, $mofile );

	/**
	 * Filters MO file path for loading translations for a specific text domain.
	 *
	 * @since 2.9.0
	 *
	 * @param string $mofile Path to the MO file.
	 * @param string $domain Text domain.
	 *                       Unique identifier for retrieving translated strings.
	 */
	$mofile = apply_filters( 'load_textdomain_mofile', $mofile, $domain );

	if ( ! is_readable( $mofile ) )
		return FALSE;

	$mo = new MO();

	if ( ! $mo->import_from_file( $mofile ) )
		return FALSE;

	if ( isset( $l10n[$domain] ) )
		$mo->merge_with( $l10n[$domain] );

	unset( $l10n_unloaded[$domain] );
	$l10n[$domain] = &$mo;
	return TRUE;
}

/**
 * Loads plugin and theme textdomains just-in-time.
 *
 * When a textdomain is encountered for the first time, we try to load the translation file from `wp-content/languages`, removing the need to call load_plugin_textdomain() or load_theme_textdomain().
 *
 * @since  4.6.0
 * @access private
 * @see    get_translations_for_domain()
 * @global array $l10n_unloaded An array of all text domains that have been unloaded again.
 *
 * @param  string $domain Text domain.
 *                        Unique identifier for retrieving translated strings.
 * @return bool   True when the textdomain is successfully loaded, false otherwise.
 */
function _load_textdomain_just_in_time( $domain )
{
	global $l10n_unloaded;
	$l10n_unloaded = ( array ) $l10n_unloaded;

	// Short-circuit if domain is 'default' which is reserved for core.
	if ( 'default' === $domain || isset( $l10n_unloaded[$domain] ) )
		return FALSE;

	$translation_path = _get_path_to_translation( $domain );
// @NOW 010 -> wp-includes/l10n.php
}

/**
 * Gets the path to a translation file for loading a textdomain just in time.
 *
 * Caches the retrieved results internally.
 *
 * @since  4.7.0
 * @access private
 * @see    _load_textdomain_just_in_time()
 *
 * @param  string       $domain Text domain.
 *                              Unique identifier for retrieving translated strings.
 * @param  bool         $reset  Whether to reset the internal cache.
 *                              Used by the switch to locale functionality.
 * @return string|false The path to the translation file or false if no translation file was found.
 */
function _get_path_to_translation( $domain, $reset = FALSE )
{
	static $available_translations = [];

	if ( TRUE === $reset )
		$available_translations = [];

	if ( ! isset( $available_translations[$domain] ) )
		$available_translations[$domain] = _get_path_to_translation_from_lang_dir( $domain );
// @NOW 011 -> wp-includes/l10n.php
}

/**
 * Gets the path to a translation file in the languages directory for the current locale.
 *
 * Holds a cached list of available .mo files to improve performance.
 *
 * @since  4.7.0
 * @access private
 * @see    _get_path_to_translation()
 *
 * @param  string       $domain Text domain.
 *                              Unique identifier for retrieving translated strings.
 * @return string|false The path to the translation file or false if no translation file was found.
 */
function _get_path_to_translation_from_lang_dir( $domain )
{
	static $cached_mofiles = NULL;

	if ( NULL === $cached_mofiles ) {
		$cached_mofiles = [];
		$locations = [WP_LANG_DIR . '/plugins', WP_LANG_DIR . '/themes'];

		foreach ( $locations as $location ) {
			$mofiles = glob( $location . '/*.mo' );

			if ( $mofiles )
				$cached_mofiles = array_merge( $cached_mofiles, $mofiles );
		}
	}

	$locale = is_admin() ? get_user_locale() : get_locale();
// @NOW 012 -> wp-includes/l10n.php
}

/**
 * Return the Translations instance for a text domain.
 *
 * If there isn't one, returns empty Translations instance.
 *
 * @since  2.8.0
 * @global array $l10n
 *
 * @param  string                         $domain Text domain.
 *                                                Unique identifier for retrieving translated strings.
 * @return Translations|NOOP_Translations A Translations instance.
 */
function get_translations_for_domain( $domain )
{
	global $l10n;

	if ( isset( $l10n[$domain] )
	  || ( _load_textdomain_just_in_time( $domain ) && isset( $l10n[$domain] ) ) ) {
// @NOW 009 -> wp-includes/l10n.php
	}
}
