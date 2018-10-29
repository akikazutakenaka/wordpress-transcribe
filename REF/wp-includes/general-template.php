<?php
/**
 * General template tags that can go anywhere in a template.
 *
 * @package    WordPress
 * @subpackage Template
 */

/**
 * Retrieves information about the current site.
 *
 * Possible values for `$show` include:
 *
 * - 'name' - Site title (set in Settings > General)
 * - 'description' - Site tagline (set in Settings > General)
 * - 'wpurl' - The WordPress address (URL) (set in Settings > General)
 * - 'url' - The Site address (URL) (set in Settings > General)
 * - 'admin_email' - Admin email (set in Settings > General)
 * - 'charset' - The "Encoding for pages and feeds" (set in Settings > General)
 * - 'version' - The current WordPress version
 * - 'html_type' - The content-type (default: "text/html").
 *                 Themes and plugins can override the default value using the {@see 'pre_option_html_type'} filter.
 * - 'text_direction' - The text direction determined by the site's language.
 *                      is_rtl() should be used instead.
 * - 'language' - Language code for the current site.
 * - 'stylesheet_url' - URL to the stylesheet for the active theme.
 *                      An active child theme will take precedence over this value.
 * - 'stylesheet_directory' - Directory path for the active theme.
 *                            An active child theme will take precedence over this value.
 * - 'template_url' / 'template_directory' - URL of the active theme's directory.
 *                                           An active child theme will NOT take precedence over this value.
 * - 'pingback_url' - The pingback XML-RPC file URL (xmlrpc.php).
 * - 'atom_url' - The Atom feed URL (/feed/atom).
 * - 'rdf_url' - The RDF/RSS 1.0 feed URL (/feed/rfd).
 * - 'rss_url' - The RSS 0.92 feed URL (/feed/rss).
 * - 'rss2_url' - The RSS 2.0 feed URL (/feed).
 * - 'comments_atom_url' - The comments Atom feed URL (/comments/feed).
 * - 'comments_rss2_url' - The comments RSS 2.0 feed URL (/comments/feed).
 *
 * Some `$show` values are deprecated and will be removed in future versions.
 * These options will trigger the _deprecated_argument() function.
 *
 * Deprecated arguments include:
 *
 * - 'siteurl' - Use 'url' instead.
 * - 'home' - Use 'url' instead.
 *
 * @since  0.71
 * @global string $wp_version
 *
 * @param  string $show   Optional.
 *                        Site info to retrieve.
 *                        Default empty (site name).
 * @param  string $filter Optional.
 *                        How to filter what is retrieved.
 *                        Default 'raw'.
 * @return string Mostly string values, might be empty.
 */
function get_bloginfo( $show = '', $filter = 'raw' )
{
	switch ( $show ) {
		case 'home': // DEPRECATED
		case 'siteurl': // DEPRECATED
			_deprecated_argument( __FUNCTION__, '2.2.0', sprintf( __( 'The %1$s option is deprecated for the family of %2$s functions. Use the %3$s option instead.' ), '<code>' . $show . '</code>', '<code>bloginfo()</code>', '<code>url</code>' ) );

		case 'url':
			$output = home_url();
			break;

		case 'wpurl':
			$output = site_url();
			break;

		case 'description':
			$output = get_option( 'blogdescription' );
			break;

		case 'rdf_url':
			$output = get_feed_link( 'rdf' );
			break;

		case 'rss_url':
			$output = get_feed_link( 'rss' );
			break;

		case 'rss2_url':
			$output = get_feed_link( 'rss2' );
			break;

		case 'atom_url':
			$output = get_feed_link( 'atom' );
			break;

		case 'comments_atom_url':
			$output = get_feed_link( 'comments_atom' );
			break;

		case 'comments_rss2_url':
			$output = get_feed_link( 'comments_rss2' );
			break;

		case 'pingback_url':
			$output = site_url( 'xmlrpc.php' );
			break;

		case 'stylesheet_uri':
			$output = get_stylesheet_uri();
			break;

		case 'stylesheet_directory':
			$output = get_stylesheet_directory_uri();
			break;

		case 'template_directory':
		case 'template_url':
			$output = get_template_directory_uri();
			break;

		case 'admin_email':
			$output = get_option( 'admin_email' );
			break;

		case 'charset':
			$output = get_option( 'blog_charset' );

			if ( '' == $output ) {
				$output = 'UTF-8';
			}

			break;

		case 'html_type':
			$output = get_option( 'html_type' );
			break;

		case 'version':
			global $wp_version;
			$output = $wp_version;
			break;

		case 'language':
			$output = __( 'html_lang_attribute' );

			if ( 'html_lang_attribute' === $output || preg_match( '/[^a-zA-Z0-9-]/', $output ) ) {
				$output = is_admin()
					? get_user_locale()
					: get_locale();

				$output = str_replace( '_', '-', $output );
			}

			break;

		case 'text_direction':
			_deprecated_argument( __FUNCTION__, '2.2.0', sprintf( __( 'The %1$s option is deprecated for the family of %2$s functions. Use the %3$s function instead.' ), '<code>' . $show . '</code>', '<code>bloginfo()</code>', '<code>is_rtl()</code>' ) );

			$output = function_exists( 'is_rtl' )
				? ( is_rtl()
					? 'rtl'
					: 'ltr' )
				: 'ltr';

			break;

		case 'name':
		default:
			$output = get_option( 'blogname' );
			break;
	}

	$url = TRUE;

	if ( strpos( $show, 'url' ) === FALSE && strpos( $show, 'directory' ) === FALSE && strpos( $show, 'home' ) === FALSE ) {
		$url = FALSE;
	}

	if ( 'display' == $filter ) {
		$output = $url
			? /**
			   * Filters the URL returned by get_bloginfo().
			   *
			   * @since 2.0.5
			   *
			   * @param mixed $output The URL returned by bloginfo().
			   * @param mixed $show   Type of information requested.
			   */
				apply_filters( 'bloginfo_url', $output, $show )
			: /**
			   * Filters the site information returned by get_bloginfo().
			   *
			   * @since 0.71
			   *
			   * @param mixed $output The requested non-URL site information.
			   * @param mixed $show   Type of information requested.
			   */
				apply_filters( 'bloginfo', $output, $show );
	}

	return $output;
}

/**
 * Whether the user can access the visual editor.
 *
 * Checks if the user can access the visual editor and that it's supported by the user's browser.
 *
 * @since  2.0.0
 * @global bool $wp_rich_edit Whether the user can access the visual editor.
 * @global bool $is_gecko     Whether the browser is Gecko-based.
 * @global bool $is_opera     Whether the browser is Opera.
 * @global bool $is_safari    Whether the browser is Safari.
 * @global bool $is_chrome    Whether the browser is Chrome.
 * @global bool $is_IE        Whether the browser is Internet Explorer.
 * @global bool $is_edge      Whether the browser is Microsoft Edge.
 *
 * @return bool True if the user can access the visual editor, false otherwise.
 */
function user_can_richedit()
{
	global $wp_rich_edit, $is_gecko, $is_opera, $is_safari, $is_chrome, $is_IE, $is_edge;

	if ( ! isset( $wp_rich_edit ) ) {
		$wp_rich_edit = FALSE;

		if ( get_user_option( 'rich_editing' ) == 'true' || ! is_user_logged_in() ) { // Default to 'true' for logged out users
			if ( $is_safari ) {
				$wp_rich_edit = ! wp_is_mobile()
				             || preg_match( '!AppleWebKit/(\d+)!', $_SERVER['HTTP_USER_AGENT'], $match ) && intval( $match[1] ) >= 534;
// @NOW 010
			}
		}
	}
}
