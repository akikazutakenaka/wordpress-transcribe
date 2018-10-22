<?php
/**
 * Theme, template, and stylesheet functions.
 *
 * @package    WordPress
 * @subpackage Theme
 */

/**
 * Retrieve name of the current stylesheet.
 *
 * The theme name that the administrator has currently set the front end theme as.
 *
 * For all intents and purposes, the template name and the stylesheet name are going to be the same for most cases.
 *
 * @since 1.5.0
 *
 * @return string Stylesheet name.
 */
function get_stylesheet()
{
	/**
	 * Filters the name of current stylesheet.
	 *
	 * @since 1.5.0
	 *
	 * @param string $stylesheet Name of the current stylesheet.
	 */
	return apply_filters( 'stylesheet', get_option( 'stylesheet' ) );
}

/**
 * Retrieve stylesheet directory URI.
 *
 * @since 1.5.0
 *
 * @return string
 */
function get_stylesheet_directory_uri()
{
	$stylesheet = str_replace( '%2F', '/', rawurlencode( get_stylesheet() ) );
	$theme_root_uri = get_theme_root_uri( $stylesheet );
	$stylesheet_dir_uri = "$theme_root_uri/$stylesheet";

	/**
	 * Filters the stylesheet directory URI.
	 *
	 * @since 1.5.0
	 *
	 * @param string $stylesheet_dir_uri Stylesheet directory URI.
	 * @param string $stylesheet         Name of the activated theme's directory.
	 * @param string $theme_root_uri     Themes root URI.
	 */
	return apply_filters( 'stylesheet_directory_uri', $stylesheet_dir_uri, $stylesheet, $theme_root_uri );
}

/**
 * Retrieves the URI of current theme stylesheet.
 *
 * The stylesheet file name is 'style.css' which is appended to the stylesheet directory URI path.
 * See get_stylesheet_directory_uri().
 *
 * @since 1.5.0
 *
 * @return string
 */
function get_stylesheet_uri()
{
	$stylesheet_dir_uri = get_stylesheet_directory_uri();
// @NOW 009
}

/**
 * Retrieve theme roots.
 *
 * @since  2.9.0
 * @global array $wp_theme_directories
 *
 * @return array|string An array of theme roots keyed by template/stylesheet or a single theme root if all themes have the same root.
 */
function get_theme_roots()
{
	global $wp_theme_directories;

	if ( ! is_array( $wp_theme_directories ) || count( $wp_theme_directories ) <= 1 ) {
		return '/themes';
	}

	$theme_roots = get_site_transient( 'theme_roots' );

	if ( FALSE === $theme_roots ) {
		search_theme_directories( TRUE ); // Regenerate the transient.
		$theme_roots = get_site_transient( 'theme_roots' );
	}

	return $theme_roots;
}

/**
 * Search all registered theme directories for complete and valid themes.
 *
 * @since     2.9.0
 * @global    array $wp_theme_directories
 * @staticvar array $found_themes
 *
 * @param  bool        $force Optional.
 *                            Whether to force a new directory scan.
 *                            Defaults to false.
 * @return array|false Valid themes found.
 */
function search_theme_directories( $force = FALSE )
{
	global $wp_theme_directories;
	static $found_themes = NULL;

	if ( empty( $wp_theme_directories ) ) {
		return FALSE;
	}

	if ( ! $force && isset( $found_themes ) ) {
		return $found_themes;
	}

	$found_themes = array();
	$wp_theme_directories = ( array ) $wp_theme_directories;
	$relative_theme_roots = array();

	/**
	 * Set up maybe-relative, maybe-absolute array of theme directories.
	 * We always want to return absolute, but we need to cache relative to use in get_theme_root().
	 */
	foreach ( $wp_theme_directories as $theme_root ) {
		if ( 0 === strpos( $theme_root, WP_CONTENT_DIR ) ) {
			$relative_theme_roots[ str_replace( WP_CONTENT_DIR, '', $theme_root ) ] = $theme_root;
		} else {
			$relative_theme_roots[ $theme_root ] = $theme_root;
		}
	}

	/**
	 * Filters whether to get the cache of the registered theme directories.
	 *
	 * @since 3.4.0
	 *
	 * @param bool   $cache_expiration Whether to get the cache of the theme directories.
	 *                                 Default false.
	 * @param string $cache_directory  Directory to be searched for the cache.
	 */
	if ( $cache_expiration = apply_filters( 'wp_cache_theme_persistently', FALSE, 'search_theme_directories' ) ) {
		$cached_roots = get_site_transient( 'theme_roots' );

		if ( is_array( $cached_roots ) ) {
			foreach ( $cached_roots as $theme_dir => $theme_root ) {
				// A cached theme root is no longer around, so skip it.
				if ( ! isset( $relative_theme_roots[ $theme_root ] ) ) {
					continue;
				}

				$found_themes[ $theme_dir ] = array(
					'theme_file' => $theme_dir . '/style.css',
					'theme_root' => $relative_theme_roots[ $theme_root ] // Convert relative to absolute.
				);
			}

			return $found_themes;
		}

		if ( ! is_int( $cache_expiration ) ) {
			$cache_expiration = 1800; // Half hour
		}
	} else {
		$cache_expiration = 1800; // Half hour
	}

	// Loop the registered theme directories and extract all themes.
	foreach ( $wp_theme_directories as $theme_root ) {
		// Start with directories in the root of the current theme directory.
		$dirs = @ scandir( $theme_root );

		if ( ! $dirs ) {
			trigger_error( "$theme_root is not readable", E_USER_NOTICE );
			continue;
		}

		foreach ( $dirs as $dir ) {
			if ( ! is_dir( $theme_root . '/' . $dir ) || $dir[0] == '.' || $dir == 'CVS' ) {
				continue;
			}

			if ( file_exists( $theme_root . '/' . $dir . '/style.css' ) ) {
				/**
				 * wp-content/themes/a-single-theme
				 * wp-content/themes is $theme_root, a-single-theme is $dir
				 */
				$found_themes[ $dir ] = array(
					'theme_file' => $dir . '/style.css',
					'theme_root' => $theme_root
				);
			} else {
				$found_theme = FALSE;

				/**
				 * wp-content/themes/a-folder-of-themes/*
				 * wp-content/themes is $theme_root, a-folder-of-themes is $dir, then themes are $sub_dirs
				 */
				$sub_dirs = @ scandir( $theme_root . '/' . $dir );

				if ( ! $sub_dirs ) {
					trigger_error( "$theme_root/$dir is not readable", E_USER_NOTICE );
					continue;
				}

				foreach ( $sub_dirs as $sub_dir ) {
					if ( ! is_dir( $theme_root . '/' . $dir . '/' . $sub_dir ) || $dir[0] == '.' || $dir == 'CVS' ) {
						continue;
					}

					if ( ! file_exists( $theme_root . '/' . $dir . '/' . $sub_dir . '/style.css' ) ) {
						continue;
					}

					$found_themes[ $dir . '/' . $sub_dir ] = array(
						'theme_file' => $dir . '/' . $sub_dir . '/style.css',
						'theme_root' => $theme_root
					);
					$found_theme = TRUE;
				}

				/**
				 * Never mind the above, it's just a theme missing a style.css.
				 * Return it; WP_Theme will catch the error.
				 */
				if ( ! $found_theme ) {
					$found_themes[ $dir ] = array(
						'theme_file' => $dir . '/style.css',
						'theme_root' => $theme_root
					);
				}
			}
		}
	}

	asort( $found_themes );
	$theme_roots = array();
	$relative_theme_roots = array_flip( $relative_theme_roots );

	foreach ( $found_themes as $theme_dir => $theme_data ) {
		$theme_roots[ $theme_dir ] = $relative_theme_roots[ $theme_data['theme_root'] ]; // Convert absolute to relative.
	}

	if ( $theme_roots != get_site_transient( 'theme_roots' ) ) {
		set_site_transient( 'theme_roots', $theme_roots, $cache_expiration );
	}

	return $found_themes;
}

/**
 * Retrieve URI for themes directory.
 *
 * Does not have trailing slash.
 *
 * @since  1.5.0
 * @global array $wp_theme_directories
 *
 * @param  string $stylesheet_or_template Optional.
 *                                        The stylesheet or template name of the theme.
 *                                        Default is to leverage the main theme root.
 * @param  string $theme_root             Optional.
 *                                        The theme root for which calculations will be based, preventing the need for a get_raw_theme_root() call.
 * @return string Themes URI.
 */
function get_theme_root_uri( $stylesheet_or_template = FALSE, $theme_root = FALSE )
{
	global $wp_theme_directories;

	if ( $stylesheet_or_template && ! $theme_root ) {
		$theme_root = get_raw_theme_root( $stylesheet_or_template );
	}

	$theme_root_uri = $stylesheet_or_template && $theme_root
		? ( in_array( $theme_root, ( array ) $wp_theme_directories )
			? ( 0 === strpos( $theme_root, WP_CONTENT_DIR )
				? content_url( str_replace( WP_CONTENT_DIR, '', $theme_root ) )
				: ( 0 === strpos( $theme_root, ABSPATH )
					? site_url( str_replace( ABSPATH, '', $theme_root ) )
					: ( 0 === strpos( $theme_root, WP_PLUGIN_DIR ) || 0 === strpos( $theme_root, WPMU_PLUGIN_DIR )
						? plugins_url( basename( $theme_root ), $theme_root )
						: $theme_root ) ) )
			: content_url( $theme_root ) )
		: content_url( 'themes' );

	/**
	 * Filters the URI for themes directory.
	 *
	 * @since 1.5.0
	 *
	 * @param string $theme_root_uri         The URI for themes directory.
	 * @param string $siteurl                WordPress web address which is set in General Option.
	 * @param string $stylesheet_or_template Stylesheet or template name of the theme.
	 */
	return apply_filters( 'theme_root_uri', $theme_root_uri, get_option( 'siteurl' ), $stylesheet_or_template );
}

/**
 * Get the raw theme root relative to the content directory with no filters applied.
 *
 * @since  3.1.0
 * @global array $wp_theme_directories
 *
 * @param  string $stylesheet_or_template The stylesheet or template name of the theme.
 * @param  bool   $skip_cache             Optional.
 *                                        Whether to skip the cache.
 *                                        Defaults to false, meaning the cache is used.
 * @return string Theme root.
 */
function get_raw_theme_root( $stylesheet_or_template, $skip_cache = FALSE )
{
	global $wp_theme_directories;

	if ( ! is_array( $wp_theme_directories ) || count( $wp_theme_directories ) <= 1 ) {
		return '/themes';
	}

	$theme_root = FALSE;

	// If requesting the root for the current theme, consult options to avoid calling get_theme_roots()
	if ( ! $skip_cache ) {
		if ( get_option( 'stylesheet' ) == $stylesheet_or_template ) {
			$theme_root = get_option( 'stylesheet_root' );
		} elseif ( get_option( 'templtae' ) == $stylesheet_or_template ) {
			$theme_root = get_option( 'template_root' );
		}
	}

	if ( empty( $theme_root ) ) {
		$theme_roots = get_theme_roots();

		if ( ! empty( $theme_roots[ $stylesheet_or_template ] ) ) {
			$theme_root = $theme_roots[ $stylesheet_or_template ];
		}
	}

	return $theme_root;
}
