<?php
/**
 * Core Translation API
 *
 * @package WordPress
 * @subpackage i18n
 * @since 1.2.0
 */

// refactored. function get_locale() {}
// :
// refactored. function translate( $text, $domain = 'default' ) {}

/**
 * Remove last item on a pipe-delimited string.
 *
 * Meant for removing the last item in a string, such as 'Role name|User role'. The original
 * string will be returned if no pipe '|' characters are found in the string.
 *
 * @since 2.8.0
 *
 * @param string $string A pipe-delimited string.
 * @return string Either $string or everything before the last pipe.
 */
function before_last_bar( $string ) {
	$last_bar = strrpos( $string, '|' );
	if ( false === $last_bar ) {
		return $string;
	} else {
		return substr( $string, 0, $last_bar );
	}
}

// refactored. function translate_with_gettext_context( $text, $context, $domain = 'default' ) {}
// refactored. function __( $text, $domain = 'default' ) {}

/**
 * Retrieve the translation of $text and escapes it for safe use in an attribute.
 *
 * If there is no translation, or the text domain isn't loaded, the original text is returned.
 *
 * @since 2.8.0
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 *                       Default 'default'.
 * @return string Translated text on success, original text on failure.
 */
function esc_attr__( $text, $domain = 'default' ) {
	return esc_attr( translate( $text, $domain ) );
}

/**
 * Retrieve the translation of $text and escapes it for safe use in HTML output.
 *
 * If there is no translation, or the text domain isn't loaded, the original text
 * is escaped and returned..
 *
 * @since 2.8.0
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 *                       Default 'default'.
 * @return string Translated text
 */
function esc_html__( $text, $domain = 'default' ) {
	return esc_html( translate( $text, $domain ) );
}

// refactored. function _e( $text, $domain = 'default' ) {}

/**
 * Display translated text that has been escaped for safe use in an attribute.
 *
 * @since 2.8.0
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 *                       Default 'default'.
 */
function esc_attr_e( $text, $domain = 'default' ) {
	echo esc_attr( translate( $text, $domain ) );
}

/**
 * Display translated text that has been escaped for safe use in HTML output.
 *
 * @since 2.8.0
 *
 * @param string $text   Text to translate.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 *                       Default 'default'.
 */
function esc_html_e( $text, $domain = 'default' ) {
	echo esc_html( translate( $text, $domain ) );
}

// refactored. function _x( $text, $context, $domain = 'default' ) {}

/**
 * Display translated string with gettext context.
 *
 * @since 3.0.0
 *
 * @param string $text    Text to translate.
 * @param string $context Context information for the translators.
 * @param string $domain  Optional. Text domain. Unique identifier for retrieving translated strings.
 *                        Default 'default'.
 * @return string Translated context string without pipe.
 */
function _ex( $text, $context, $domain = 'default' ) {
	echo _x( $text, $context, $domain );
}

/**
 * Translate string with gettext context, and escapes it for safe use in an attribute.
 *
 * @since 2.8.0
 *
 * @param string $text    Text to translate.
 * @param string $context Context information for the translators.
 * @param string $domain  Optional. Text domain. Unique identifier for retrieving translated strings.
 *                        Default 'default'.
 * @return string Translated text
 */
function esc_attr_x( $text, $context, $domain = 'default' ) {
	return esc_attr( translate_with_gettext_context( $text, $context, $domain ) );
}

/**
 * Translate string with gettext context, and escapes it for safe use in HTML output.
 *
 * @since 2.9.0
 *
 * @param string $text    Text to translate.
 * @param string $context Context information for the translators.
 * @param string $domain  Optional. Text domain. Unique identifier for retrieving translated strings.
 *                        Default 'default'.
 * @return string Translated text.
 */
function esc_html_x( $text, $context, $domain = 'default' ) {
	return esc_html( translate_with_gettext_context( $text, $context, $domain ) );
}

/**
 * Translates and retrieves the singular or plural form based on the supplied number.
 *
 * Used when you want to use the appropriate form of a string based on whether a
 * number is singular or plural.
 *
 * Example:
 *
 *     printf( _n( '%s person', '%s people', $count, 'text-domain' ), number_format_i18n( $count ) );
 *
 * @since 2.8.0
 *
 * @param string $single The text to be used if the number is singular.
 * @param string $plural The text to be used if the number is plural.
 * @param int    $number The number to compare against to use either the singular or plural form.
 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
 *                       Default 'default'.
 * @return string The translated singular or plural form.
 */
function _n( $single, $plural, $number, $domain = 'default' ) {
	$translations = get_translations_for_domain( $domain );
	$translation  = $translations->translate_plural( $single, $plural, $number );

	/**
	 * Filters the singular or plural form of a string.
	 *
	 * @since 2.2.0
	 *
	 * @param string $translation Translated text.
	 * @param string $single      The text to be used if the number is singular.
	 * @param string $plural      The text to be used if the number is plural.
	 * @param string $number      The number to compare against to use either the singular or plural form.
	 * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
	 */
	return apply_filters( 'ngettext', $translation, $single, $plural, $number, $domain );
}

/**
 * Translates and retrieves the singular or plural form based on the supplied number, with gettext context.
 *
 * This is a hybrid of _n() and _x(). It supports context and plurals.
 *
 * Used when you want to use the appropriate form of a string with context based on whether a
 * number is singular or plural.
 *
 * Example of a generic phrase which is disambiguated via the context parameter:
 *
 *     printf( _nx( '%s group', '%s groups', $people, 'group of people', 'text-domain' ), number_format_i18n( $people ) );
 *     printf( _nx( '%s group', '%s groups', $animals, 'group of animals', 'text-domain' ), number_format_i18n( $animals ) );
 *
 * @since 2.8.0
 *
 * @param string $single  The text to be used if the number is singular.
 * @param string $plural  The text to be used if the number is plural.
 * @param int    $number  The number to compare against to use either the singular or plural form.
 * @param string $context Context information for the translators.
 * @param string $domain  Optional. Text domain. Unique identifier for retrieving translated strings.
 *                        Default 'default'.
 * @return string The translated singular or plural form.
 */
function _nx($single, $plural, $number, $context, $domain = 'default') {
	$translations = get_translations_for_domain( $domain );
	$translation  = $translations->translate_plural( $single, $plural, $number, $context );

	/**
	 * Filters the singular or plural form of a string with gettext context.
	 *
	 * @since 2.8.0
	 *
	 * @param string $translation Translated text.
	 * @param string $single      The text to be used if the number is singular.
	 * @param string $plural      The text to be used if the number is plural.
	 * @param string $number      The number to compare against to use either the singular or plural form.
	 * @param string $context     Context information for the translators.
	 * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
	 */
	return apply_filters( 'ngettext_with_context', $translation, $single, $plural, $number, $context, $domain );
}

/**
 * Registers plural strings in POT file, but does not translate them.
 *
 * Used when you want to keep structures with translatable plural
 * strings and use them later when the number is known.
 *
 * Example:
 *
 *     $message = _n_noop( '%s post', '%s posts', 'text-domain' );
 *     ...
 *     printf( translate_nooped_plural( $message, $count, 'text-domain' ), number_format_i18n( $count ) );
 *
 * @since 2.5.0
 *
 * @param string $singular Singular form to be localized.
 * @param string $plural   Plural form to be localized.
 * @param string $domain   Optional. Text domain. Unique identifier for retrieving translated strings.
 *                         Default null.
 * @return array {
 *     Array of translation information for the strings.
 *
 *     @type string $0        Singular form to be localized. No longer used.
 *     @type string $1        Plural form to be localized. No longer used.
 *     @type string $singular Singular form to be localized.
 *     @type string $plural   Plural form to be localized.
 *     @type null   $context  Context information for the translators.
 *     @type string $domain   Text domain.
 * }
 */
function _n_noop( $singular, $plural, $domain = null ) {
	return array( 0 => $singular, 1 => $plural, 'singular' => $singular, 'plural' => $plural, 'context' => null, 'domain' => $domain );
}

/**
 * Registers plural strings with gettext context in POT file, but does not translate them.
 *
 * Used when you want to keep structures with translatable plural
 * strings and use them later when the number is known.
 *
 * Example of a generic phrase which is disambiguated via the context parameter:
 *
 *     $messages = array(
 *      	'people'  => _nx_noop( '%s group', '%s groups', 'people', 'text-domain' ),
 *      	'animals' => _nx_noop( '%s group', '%s groups', 'animals', 'text-domain' ),
 *     );
 *     ...
 *     $message = $messages[ $type ];
 *     printf( translate_nooped_plural( $message, $count, 'text-domain' ), number_format_i18n( $count ) );
 *
 * @since 2.8.0
 *
 * @param string $singular Singular form to be localized.
 * @param string $plural   Plural form to be localized.
 * @param string $context  Context information for the translators.
 * @param string $domain   Optional. Text domain. Unique identifier for retrieving translated strings.
 *                         Default null.
 * @return array {
 *     Array of translation information for the strings.
 *
 *     @type string $0        Singular form to be localized. No longer used.
 *     @type string $1        Plural form to be localized. No longer used.
 *     @type string $2        Context information for the translators. No longer used.
 *     @type string $singular Singular form to be localized.
 *     @type string $plural   Plural form to be localized.
 *     @type string $context  Context information for the translators.
 *     @type string $domain   Text domain.
 * }
 */
function _nx_noop( $singular, $plural, $context, $domain = null ) {
	return array( 0 => $singular, 1 => $plural, 2 => $context, 'singular' => $singular, 'plural' => $plural, 'context' => $context, 'domain' => $domain );
}

/**
 * Translates and retrieves the singular or plural form of a string that's been registered
 * with _n_noop() or _nx_noop().
 *
 * Used when you want to use a translatable plural string once the number is known.
 *
 * Example:
 *
 *     $message = _n_noop( '%s post', '%s posts', 'text-domain' );
 *     ...
 *     printf( translate_nooped_plural( $message, $count, 'text-domain' ), number_format_i18n( $count ) );
 *
 * @since 3.1.0
 *
 * @param array  $nooped_plural Array with singular, plural, and context keys, usually the result of _n_noop() or _nx_noop().
 * @param int    $count         Number of objects.
 * @param string $domain        Optional. Text domain. Unique identifier for retrieving translated strings. If $nooped_plural contains
 *                              a text domain passed to _n_noop() or _nx_noop(), it will override this value. Default 'default'.
 * @return string Either $single or $plural translated text.
 */
function translate_nooped_plural( $nooped_plural, $count, $domain = 'default' ) {
	if ( $nooped_plural['domain'] )
		$domain = $nooped_plural['domain'];

	if ( $nooped_plural['context'] )
		return _nx( $nooped_plural['singular'], $nooped_plural['plural'], $count, $nooped_plural['context'], $domain );
	else
		return _n( $nooped_plural['singular'], $nooped_plural['plural'], $count, $domain );
}

// refactored. function load_textdomain( $domain, $mofile ) {}

/**
 * Unload translations for a text domain.
 *
 * @since 3.0.0
 *
 * @global array $l10n          An array of all currently loaded text domains.
 * @global array $l10n_unloaded An array of all text domains that have been unloaded again.
 *
 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
 * @return bool Whether textdomain was unloaded.
 */
function unload_textdomain( $domain ) {
	global $l10n, $l10n_unloaded;

	$l10n_unloaded = (array) $l10n_unloaded;

	/**
	 * Filters whether to override the text domain unloading.
	 *
	 * @since 3.0.0
	 *
	 * @param bool   $override Whether to override the text domain unloading. Default false.
	 * @param string $domain   Text domain. Unique identifier for retrieving translated strings.
	 */
	$plugin_override = apply_filters( 'override_unload_textdomain', false, $domain );

	if ( $plugin_override ) {
		$l10n_unloaded[ $domain ] = true;

		return true;
	}

	/**
	 * Fires before the text domain is unloaded.
	 *
	 * @since 3.0.0
	 *
	 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
	 */
	do_action( 'unload_textdomain', $domain );

	if ( isset( $l10n[$domain] ) ) {
		unset( $l10n[$domain] );

		$l10n_unloaded[ $domain ] = true;

		return true;
	}

	return false;
}

/**
 * Load default translated strings based on locale.
 *
 * Loads the .mo file in WP_LANG_DIR constant path from WordPress root.
 * The translated (.mo) file is named based on the locale.
 *
 * @see load_textdomain()
 *
 * @since 1.5.0
 *
 * @param string $locale Optional. Locale to load. Default is the value of get_locale().
 * @return bool Whether the textdomain was loaded.
 */
function load_default_textdomain( $locale = null ) {
	if ( null === $locale ) {
		$locale = is_admin() ? get_user_locale() : get_locale();
	}

	// Unload previously loaded strings so we can switch translations.
	unload_textdomain( 'default' );

	$return = load_textdomain( 'default', WP_LANG_DIR . "/$locale.mo" );

	if ( ( is_multisite() || ( defined( 'WP_INSTALLING_NETWORK' ) && WP_INSTALLING_NETWORK ) ) && ! file_exists(  WP_LANG_DIR . "/admin-$locale.mo" ) ) {
		load_textdomain( 'default', WP_LANG_DIR . "/ms-$locale.mo" );
		return $return;
	}

	if ( is_admin() || wp_installing() || ( defined( 'WP_REPAIRING' ) && WP_REPAIRING ) ) {
		load_textdomain( 'default', WP_LANG_DIR . "/admin-$locale.mo" );
	}

	if ( is_network_admin() || ( defined( 'WP_INSTALLING_NETWORK' ) && WP_INSTALLING_NETWORK ) )
		load_textdomain( 'default', WP_LANG_DIR . "/admin-network-$locale.mo" );

	return $return;
}

/**
 * Loads a plugin's translated strings.
 *
 * If the path is not given then it will be the root of the plugin directory.
 *
 * The .mo file should be named based on the text domain with a dash, and then the locale exactly.
 *
 * @since 1.5.0
 * @since 4.6.0 The function now tries to load the .mo file from the languages directory first.
 *
 * @param string $domain          Unique identifier for retrieving translated strings
 * @param string $deprecated      Optional. Use the $plugin_rel_path parameter instead. Default false.
 * @param string $plugin_rel_path Optional. Relative path to WP_PLUGIN_DIR where the .mo file resides.
 *                                Default false.
 * @return bool True when textdomain is successfully loaded, false otherwise.
 */
function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) {
	/**
	 * Filters a plugin's locale.
	 *
	 * @since 3.0.0
	 *
	 * @param string $locale The plugin's current locale.
	 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
	 */
	$locale = apply_filters( 'plugin_locale', is_admin() ? get_user_locale() : get_locale(), $domain );

	$mofile = $domain . '-' . $locale . '.mo';

	// Try to load from the languages directory first.
	if ( load_textdomain( $domain, WP_LANG_DIR . '/plugins/' . $mofile ) ) {
		return true;
	}

	if ( false !== $plugin_rel_path ) {
		$path = WP_PLUGIN_DIR . '/' . trim( $plugin_rel_path, '/' );
	} elseif ( false !== $deprecated ) {
		_deprecated_argument( __FUNCTION__, '2.7.0' );
		$path = ABSPATH . trim( $deprecated, '/' );
	} else {
		$path = WP_PLUGIN_DIR;
	}

	return load_textdomain( $domain, $path . '/' . $mofile );
}

/**
 * Load the translated strings for a plugin residing in the mu-plugins directory.
 *
 * @since 3.0.0
 * @since 4.6.0 The function now tries to load the .mo file from the languages directory first.
 *
 * @param string $domain             Text domain. Unique identifier for retrieving translated strings.
 * @param string $mu_plugin_rel_path Optional. Relative to `WPMU_PLUGIN_DIR` directory in which the .mo
 *                                   file resides. Default empty string.
 * @return bool True when textdomain is successfully loaded, false otherwise.
 */
function load_muplugin_textdomain( $domain, $mu_plugin_rel_path = '' ) {
	/** This filter is documented in wp-includes/l10n.php */
	$locale = apply_filters( 'plugin_locale', is_admin() ? get_user_locale() : get_locale(), $domain );

	$mofile = $domain . '-' . $locale . '.mo';

	// Try to load from the languages directory first.
	if ( load_textdomain( $domain, WP_LANG_DIR . '/plugins/' . $mofile ) ) {
		return true;
	}

	$path = WPMU_PLUGIN_DIR . '/' . ltrim( $mu_plugin_rel_path, '/' );

	return load_textdomain( $domain, $path . '/' . $mofile );
}

/**
 * Load the theme's translated strings.
 *
 * If the current locale exists as a .mo file in the theme's root directory, it
 * will be included in the translated strings by the $domain.
 *
 * The .mo files must be named based on the locale exactly.
 *
 * @since 1.5.0
 * @since 4.6.0 The function now tries to load the .mo file from the languages directory first.
 *
 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
 * @param string $path   Optional. Path to the directory containing the .mo file.
 *                       Default false.
 * @return bool True when textdomain is successfully loaded, false otherwise.
 */
function load_theme_textdomain( $domain, $path = false ) {
	/**
	 * Filters a theme's locale.
	 *
	 * @since 3.0.0
	 *
	 * @param string $locale The theme's current locale.
	 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
	 */
	$locale = apply_filters( 'theme_locale', is_admin() ? get_user_locale() : get_locale(), $domain );

	$mofile = $domain . '-' . $locale . '.mo';

	// Try to load from the languages directory first.
	if ( load_textdomain( $domain, WP_LANG_DIR . '/themes/' . $mofile ) ) {
		return true;
	}

	if ( ! $path ) {
		$path = get_template_directory();
	}

	return load_textdomain( $domain, $path . '/' . $locale . '.mo' );
}

/**
 * Load the child themes translated strings.
 *
 * If the current locale exists as a .mo file in the child themes
 * root directory, it will be included in the translated strings by the $domain.
 *
 * The .mo files must be named based on the locale exactly.
 *
 * @since 2.9.0
 *
 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
 * @param string $path   Optional. Path to the directory containing the .mo file.
 *                       Default false.
 * @return bool True when the theme textdomain is successfully loaded, false otherwise.
 */
function load_child_theme_textdomain( $domain, $path = false ) {
	if ( ! $path )
		$path = get_stylesheet_directory();
	return load_theme_textdomain( $domain, $path );
}

// refactored. function _load_textdomain_just_in_time( $domain ) {}
// :
// refactored. function get_translations_for_domain( $domain ) {}

/**
 * Whether there are translations for the text domain.
 *
 * @since 3.0.0
 *
 * @global array $l10n
 *
 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
 * @return bool Whether there are translations.
 */
function is_textdomain_loaded( $domain ) {
	global $l10n;
	return isset( $l10n[ $domain ] );
}

/**
 * Translates role name.
 *
 * Since the role names are in the database and not in the source there
 * are dummy gettext calls to get them into the POT file and this function
 * properly translates them back.
 *
 * The before_last_bar() call is needed, because older installations keep the roles
 * using the old context format: 'Role name|User role' and just skipping the
 * content after the last bar is easier than fixing them in the DB. New installations
 * won't suffer from that problem.
 *
 * @since 2.8.0
 *
 * @param string $name The role name.
 * @return string Translated role name on success, original name on failure.
 */
function translate_user_role( $name ) {
	return translate_with_gettext_context( before_last_bar($name), 'User role' );
}

// refactored. function get_available_languages( $dir = null ) {}

/**
 * Get installed translations.
 *
 * Looks in the wp-content/languages directory for translations of
 * plugins or themes.
 *
 * @since 3.7.0
 *
 * @param string $type What to search for. Accepts 'plugins', 'themes', 'core'.
 * @return array Array of language data.
 */
function wp_get_installed_translations( $type ) {
	if ( $type !== 'themes' && $type !== 'plugins' && $type !== 'core' )
		return array();

	$dir = 'core' === $type ? '' : "/$type";

	if ( ! is_dir( WP_LANG_DIR ) )
		return array();

	if ( $dir && ! is_dir( WP_LANG_DIR . $dir ) )
		return array();

	$files = scandir( WP_LANG_DIR . $dir );
	if ( ! $files )
		return array();

	$language_data = array();

	foreach ( $files as $file ) {
		if ( '.' === $file[0] || is_dir( WP_LANG_DIR . "$dir/$file" ) ) {
			continue;
		}
		if ( substr( $file, -3 ) !== '.po' ) {
			continue;
		}
		if ( ! preg_match( '/(?:(.+)-)?([a-z]{2,3}(?:_[A-Z]{2})?(?:_[a-z0-9]+)?).po/', $file, $match ) ) {
			continue;
		}
		if ( ! in_array( substr( $file, 0, -3 ) . '.mo', $files ) )  {
			continue;
		}

		list( , $textdomain, $language ) = $match;
		if ( '' === $textdomain ) {
			$textdomain = 'default';
		}
		$language_data[ $textdomain ][ $language ] = wp_get_pomo_file_data( WP_LANG_DIR . "$dir/$file" );
	}
	return $language_data;
}

/**
 * Extract headers from a PO file.
 *
 * @since 3.7.0
 *
 * @param string $po_file Path to PO file.
 * @return array PO file headers.
 */
function wp_get_pomo_file_data( $po_file ) {
	$headers = get_file_data( $po_file, array(
		'POT-Creation-Date'  => '"POT-Creation-Date',
		'PO-Revision-Date'   => '"PO-Revision-Date',
		'Project-Id-Version' => '"Project-Id-Version',
		'X-Generator'        => '"X-Generator',
	) );
	foreach ( $headers as $header => $value ) {
		// Remove possible contextual '\n' and closing double quote.
		$headers[ $header ] = preg_replace( '~(\\\n)?"$~', '', $value );
	}
	return $headers;
}

/**
 * Language selector.
 *
 * @since 4.0.0
 * @since 4.3.0 Introduced the `echo` argument.
 * @since 4.7.0 Introduced the `show_option_site_default` argument.
 *
 * @see get_available_languages()
 * @see wp_get_available_translations()
 *
 * @param string|array $args {
 *     Optional. Array or string of arguments for outputting the language selector.
 *
 *     @type string   $id                           ID attribute of the select element. Default 'locale'.
 *     @type string   $name                         Name attribute of the select element. Default 'locale'.
 *     @type array    $languages                    List of installed languages, contain only the locales.
 *                                                  Default empty array.
 *     @type array    $translations                 List of available translations. Default result of
 *                                                  wp_get_available_translations().
 *     @type string   $selected                     Language which should be selected. Default empty.
 *     @type bool|int $echo                         Whether to echo the generated markup. Accepts 0, 1, or their
 *                                                  boolean equivalents. Default 1.
 *     @type bool     $show_available_translations  Whether to show available translations. Default true.
 *     @type bool     $show_option_site_default     Whether to show an option to fall back to the site's locale. Default false.
 * }
 * @return string HTML content
 */
function wp_dropdown_languages( $args = array() ) {

	$parsed_args = wp_parse_args( $args, array(
		'id'           => 'locale',
		'name'         => 'locale',
		'languages'    => array(),
		'translations' => array(),
		'selected'     => '',
		'echo'         => 1,
		'show_available_translations' => true,
		'show_option_site_default'    => false,
	) );

	// Bail if no ID or no name.
	if ( ! $parsed_args['id'] || ! $parsed_args['name'] ) {
		return;
	}

	// English (United States) uses an empty string for the value attribute.
	if ( 'en_US' === $parsed_args['selected'] ) {
		$parsed_args['selected'] = '';
	}

	$translations = $parsed_args['translations'];
	if ( empty( $translations ) ) {
		require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
		$translations = wp_get_available_translations();
	}

	/*
	 * $parsed_args['languages'] should only contain the locales. Find the locale in
	 * $translations to get the native name. Fall back to locale.
	 */
	$languages = array();
	foreach ( $parsed_args['languages'] as $locale ) {
		if ( isset( $translations[ $locale ] ) ) {
			$translation = $translations[ $locale ];
			$languages[] = array(
				'language'    => $translation['language'],
				'native_name' => $translation['native_name'],
				'lang'        => current( $translation['iso'] ),
			);

			// Remove installed language from available translations.
			unset( $translations[ $locale ] );
		} else {
			$languages[] = array(
				'language'    => $locale,
				'native_name' => $locale,
				'lang'        => '',
			);
		}
	}

	$translations_available = ( ! empty( $translations ) && $parsed_args['show_available_translations'] );

	// Holds the HTML markup.
	$structure = array();

	// List installed languages.
	if ( $translations_available ) {
		$structure[] = '<optgroup label="' . esc_attr_x( 'Installed', 'translations' ) . '">';
	}

	// Site default.
	if ( $parsed_args['show_option_site_default'] ) {
		$structure[] = sprintf(
			'<option value="site-default" data-installed="1"%s>%s</option>',
			selected( 'site-default', $parsed_args['selected'], false ),
			_x( 'Site Default', 'default site language' )
		);
	}

	// Always show English.
	$structure[] = sprintf(
		'<option value="" lang="en" data-installed="1"%s>English (United States)</option>',
		selected( '', $parsed_args['selected'], false )
	);

	// List installed languages. 
	foreach ( $languages as $language ) {
		$structure[] = sprintf(
			'<option value="%s" lang="%s"%s data-installed="1">%s</option>',
			esc_attr( $language['language'] ),
			esc_attr( $language['lang'] ),
			selected( $language['language'], $parsed_args['selected'], false ),
			esc_html( $language['native_name'] )
		);
	}
	if ( $translations_available ) {
		$structure[] = '</optgroup>';
	}

	// List available translations.
	if ( $translations_available ) {
		$structure[] = '<optgroup label="' . esc_attr_x( 'Available', 'translations' ) . '">';
		foreach ( $translations as $translation ) {
			$structure[] = sprintf(
				'<option value="%s" lang="%s"%s>%s</option>',
				esc_attr( $translation['language'] ),
				esc_attr( current( $translation['iso'] ) ),
				selected( $translation['language'], $parsed_args['selected'], false ),
				esc_html( $translation['native_name'] )
			);
		}
		$structure[] = '</optgroup>';
	}

	// Combine the output string.
	$output  = sprintf( '<select name="%s" id="%s">', esc_attr( $parsed_args['name'] ), esc_attr( $parsed_args['id'] ) );
	$output .= join( "\n", $structure );
	$output .= '</select>';

	if ( $parsed_args['echo'] ) {
		echo $output;
	}

	return $output;
}

// refactored. function is_rtl() {}

/**
 * Switches the translations according to the given locale.
 *
 * @since 4.7.0
 *
 * @global WP_Locale_Switcher $wp_locale_switcher
 *
 * @param string $locale The locale.
 * @return bool True on success, false on failure.
 */
function switch_to_locale( $locale ) {
	/* @var WP_Locale_Switcher $wp_locale_switcher */
	global $wp_locale_switcher;

	return $wp_locale_switcher->switch_to_locale( $locale );
}

/**
 * Restores the translations according to the previous locale.
 *
 * @since 4.7.0
 *
 * @global WP_Locale_Switcher $wp_locale_switcher
 *
 * @return string|false Locale on success, false on error.
 */
function restore_previous_locale() {
	/* @var WP_Locale_Switcher $wp_locale_switcher */
	global $wp_locale_switcher;

	return $wp_locale_switcher->restore_previous_locale();
}

/**
 * Restores the translations according to the original locale.
 *
 * @since 4.7.0
 *
 * @global WP_Locale_Switcher $wp_locale_switcher
 *
 * @return string|false Locale on success, false on error.
 */
function restore_current_locale() {
	/* @var WP_Locale_Switcher $wp_locale_switcher */
	global $wp_locale_switcher;

	return $wp_locale_switcher->restore_current_locale();
}

/**
 * Whether switch_to_locale() is in effect.
 *
 * @since 4.7.0
 *
 * @global WP_Locale_Switcher $wp_locale_switcher
 *
 * @return bool True if the locale has been switched, false otherwise.
 */
function is_locale_switched() {
	/* @var WP_Locale_Switcher $wp_locale_switcher */
	global $wp_locale_switcher;

	return $wp_locale_switcher->is_switched();
}
