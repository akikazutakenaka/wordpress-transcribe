<?php
/**
 * Core Translation API
 *
 * @package    WordPress
 * @subpackage i18n
 * @since      1.2.0
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

	if ( ! $mo->import_from_file( $mofile ) ) {
		// @NOW 006 -> wp-includes/pomo/mo.php
	}
}
