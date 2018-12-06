<?php
/**
 * Core Translation API
 *
 * @package    WordPress
 * @subpackage i18n
 * @since      1.2.0
 */

/**
 * Retrieves the current locale.
 *
 * If the locale is set, then it will filter the locale in the {@see 'locale'} filter hook and return the value.
 *
 * If the locale is not set already, then the WPLANG constant is used if it is defined.
 * Then it is filtered through the {@see 'locale'} filter hook and the value for the locale global set and the locale is returned.
 *
 * The process to get the locale should only be done once, but the locale will always be filtered using the {@see 'locale'} hook.
 *
 * @since  1.5.0
 * @global string $locale
 * @global string $wp_local_package
 *
 * @return string The locale of the blog or from the {@see 'locale'} hook.
 */
function get_locale()
{
	global $locale, $wp_local_package;

	if ( isset( $locale ) ) {
		/**
		 * Filters the locale ID of the WordPress installation.
		 *
		 * @since 1.5.0
		 *
		 * @param string $locale The locale ID.
		 */
		return apply_filters( 'locale', $locale );
	}

	if ( isset( $wp_local_package ) ) {
		$locale = $wp_local_package;
	}

	// WPLANG was defined in wp-config.
	if ( defined( 'WPLANG' ) ) {
		$locale = WPLANG;
	}

	// If multisite, check options.
	if ( is_multisite() ) {
		// Don't check blog option when installing.
		if ( wp_installing() || FALSE === ( $ms_locale = get_option( 'WPLANG' ) ) ) {
			$ms_locale = get_site_option( 'WPLANG' );
		}

		if ( $ms_locale !== FALSE ) {
			$locale = $ms_locale;
		}
	} else {
		$db_locale = get_option( 'WPLANG' );

		if ( $db_locale !== FALSE ) {
			$locale = $db_locale;
		}
	}

	if ( empty( $locale ) ) {
		$locale = 'en_US';
	}

	// This filter is documented in wp-includes/l10n.php
	return apply_filters( 'locale', $locale );
}

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

	if ( 0 === $user_id && function_exists( 'wp_get_current_user' ) ) {
		$user = wp_get_current_user();
	} elseif ( $user_id instanceof WP_User ) {
		$user = $user_id;
	} elseif ( $user_id && is_numeric( $user_id ) ) {
		$user = get_user_by( 'id', $user_id );
	}

	if ( ! $user ) {
		return get_locale();
	}

	$locale = $user->locale;

	return $locale
		? $locale
		: get_locale();
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
	$translation  = $translations->translate( $text );

	/**
	 * Filters text with its translation.
	 *
	 * @since 2.0.11
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Text to translate.
	 * @param string $domain      Text domain.
	 *                            Unique identifier for retrieving translated strings.
	 */
	return apply_filters( 'gettext', $translation, $text, $domain );
}

/**
 * Retrieve the translation of $text in the context defined in $context.
 *
 * If there is no translation, or the text domain isn't loaded the original text is returned.
 *
 * *Note:* Don't use translate_with_gettext_context() directly, use _x() or related functions.
 *
 * @since 2.8.0
 *
 * @param  string $text    Text to translate.
 * @param  string $context Context information for the translators.
 * @param  string $domain  Optional.
 *                         Text domain.
 *                         Unique identifier for retrieving translated strings.
 *                         Default 'default'.
 * @return string Translated text on success, original text on failure.
 */
function translate_with_gettext_context( $text, $context, $domain = 'default' )
{
	$translations = get_translations_for_domain( $domain );
	$translation  = $translations->translate( $text, $context );

	/**
	 * Filters text with its translation based on context information.
	 *
	 * @since 2.8.0
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Text to translate.
	 * @param string $context     Context information for the translators.
	 * @param string $domain      Text domain.
	 *                            Unique identifier for retrieving translated strings.
	 */
	return apply_filters( 'gettext_with_context', $translation, $text, $context, $domain );
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
 * Display translated text.
 *
 * @since 1.2.0
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional.
 *                       Text domain.
 *                       Unique identifier for retrieving translated strings.
 *                       Default 'default'.
 */
function _e( $text, $domain = 'default' )
{
	echo translate( $text, $domain );
}

/**
 * Retrieve translated string with gettext context.
 *
 * Quite a few times, there will be collisions with similar translatable text found in more than two places, but with different translated context.
 *
 * By including the context in the pot file, translators can translate the two strings differently.
 *
 * @since 2.8.0
 *
 * @param  string $text    Text to translate.
 * @param  string $context Context information for the translators.
 * @param  string $domain  Optional.
 *                         Text domain.
 *                         Unique identifier for retrieving translated strings.
 *                         Default 'default'.
 * @return string Translated context string without pipe.
 */
function _x( $text, $context, $domain = 'default' )
{
	return translate_with_gettext_context( $text, $context, $domain );
}

/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/formatting.php: wp_trim_excerpt( [string $text = ''] )
 * <-......: wp-includes/post-template.php: get_the_content( [string $more_link_text = NULL [, bool $strip_teaser = FALSE]] )
 * <-......: wp-includes/post-template.php: get_the_password_form( [int|WP_Post $post = 0] )
 * @NOW 008: wp-includes/l10n.php: esc_attr_x( string $text, string $context [, string $domain = 'default'] )
 */

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
		unset( $l10n_unloaded[ $domain ] );
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

	if ( ! is_readable( $mofile ) ) {
		return FALSE;
	}

	$mo = new MO();

	if ( ! $mo->import_from_file( $mofile ) ) {
		return FALSE;
	}

	if ( isset( $l10n[ $domain ] ) ) {
		$mo->merge_with( $l10n[ $domain ] );
	}

	unset( $l10n_unloaded[ $domain ] );
	$l10n[ $domain ] = &$mo;
	return TRUE;
}

/**
 * Load the theme's translated strings.
 *
 * If the current locale exists as a .mo file in the theme's root directory, it will be included in the translated strings by the $domain.
 *
 * The .mo files must be named based on the locale exactly.
 *
 * @since 1.5.0
 * @since 4.6.0 The function now tries to load the .mo file from the languages directory first.
 *
 * @param  string $domain Text domain.
 *                        Unique identifier for retrieving translated strings.
 * @param  string $path   Optional.
 *                        Path to the directory containing the .mo file.
 *                        Default false.
 * @return bool   True when textdomain is successfully loaded, false otherwise.
 */
function load_theme_textdomain( $domain, $path = FALSE )
{
	/**
	 * Filters a theme's locale.
	 *
	 * @since 3.0.;0
	 *
	 * @param string $locale The theme's current locale.
	 * @param string $domain Text domain.
	 *                       Unique identifier for retrieving translated strings.
	 */
	$locale = apply_filters( 'theme_locale', is_admin()
			? get_user_locale()
			: get_locale(), $domain );

	$mofile = $domain . '-' . $locale . '.mo';

	// Try to load from the languages directory first.
	if ( load_textdomain( $domain, WP_LANG_DIR . '/themes/' . $mofile ) ) {
		return TRUE;
	}

	if ( ! $path ) {
		$path = get_template_directory();
	}

	return load_textdomain( $domain, $path . '/' . $locale . '.mo' );
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
	if ( 'default' === $domain || isset( $l10n_unloaded[ $domain ] ) ) {
		return FALSE;
	}

	$translation_path = _get_path_to_translation( $domain );

	if ( FALSE === $translation_path ) {
		return FALSE;
	}

	return load_textdomain( $domain, $translation_path );
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
	static $available_translations = array();

	if ( TRUE === $reset ) {
		$available_translations = array();
	}

	if ( ! isset( $available_translations[ $domain ] ) ) {
		$available_translations[ $domain ] = _get_path_to_translation_from_lang_dir( $domain );
	}

	return $available_translations[ $domain ];
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
		$cached_mofiles = array();
		$locations = array( WP_LANG_DIR . '/plugins', WP_LANG_DIR . '/themes' );

		foreach ( $locations as $location ) {
			$mofiles = glob( $location . '/*.mo' );

			if ( $mofiles ) {
				$cached_mofiles = array_merge( $cached_mofiles, $mofiles );
			}
		}
	}

	$locale = is_admin()
		? get_user_locale()
		: get_locale();

	$mofile = "{$domain}-{$locale}.mo";
	$path = WP_LANG_DIR . '/plugins/' . $mofile;

	if ( in_array( $path, $cached_mofiles ) ) {
		return $path;
	}

	$path = WP_LANG_DIR . '/themes/' . $mofile;

	if ( in_array( $path, $cached_mofiles ) ) {
		return $path;
	}

	return FALSE;
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

	if ( isset( $l10n[ $domain ] )
	  || ( _load_textdomain_just_in_time( $domain ) && isset( $l10n[ $domain ] ) ) ) {
		return $l10n[ $domain ];
	}

	static $noop_translations = NULL;

	if ( NULL === $noop_translations ) {
		$noop_translations = new NOOP_Translations;
	}

	return $noop_translations;
}

/**
 * Whether there are translations for the text domain.
 *
 * @since  3.0.0
 * @global array $l10n
 *
 * @param  string $domain Text domain.
 *                        Unique identifier for retrieving translated strings.
 * @return bool   Whether there are translations.
 */
function is_textdomain_loaded( $domain )
{
	global $l10n;
	return isset( $l10n[ $domain ] );
}

/**
 * Get all available languages based on the presence of *.mo files in a given directory.
 *
 * The default directory is WP_LANG_DIR.
 *
 * @since 3.0.0
 * @since 4.7.0 The results are now filterable with the {@see 'get_available_languages'} filter.
 *
 * @param  string $dir A directory to search for language files.
 *                     Default WP_LANG_DIR.
 * @return array  An array of language codes or an empty array if no languages are present.
 *                Language codes are formed by stripping the .mo extension from the language file names.
 */
function get_available_languages( $dir = NULL )
{
	$languages = array();

	$lang_files = glob( ( is_null( $dir )
			? WP_LANG_DIR
			: $dir ) . '/*.mo' );

	if ( $lang_files ) {
		foreach ( $lang_files as $lang_file ) {
			$lang_file = basename( $lang_file, '.mo' );

			if ( 0 !== strpos( $lang_file, 'continents-cities' ) && 0 !== strpos( $lang_file, 'ms-' ) && 0 !== strpos( $lang_file, 'admin-' ) ) {
				$languages[] = $lang_file;
			}
		}
	}

	/**
	 * Filters the list of available language codes.
	 *
	 * @since 4.7.0
	 *
	 * @param array  $languages An array of available language codes.
	 * @param string $dir       The directory where the language files were found.
	 */
	return apply_filters( 'get_available_languages', $languages, $dir );
}

/**
 * Checks if current locale is RTL.
 *
 * @since  3.0.0
 * @global WP_Locale $wp_locale
 *
 * @return bool Whether locale is RTL.
 */
function is_rtl()
{
	global $wp_locale;

	if ( ! $wp_locale instanceof WP_Locale ) {
		return FALSE;
	}

	return $wp_locale->is_rtl();
}
