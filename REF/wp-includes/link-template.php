<?php
/**
 * WordPress Link Template Functions
 *
 * @package    WordPress
 * @subpackage Template
 */

/**
 * Retrieves the permalink for the feed type.
 *
 * @since  1.5.0
 * @global WP_Rewrite $wp_rewrite
 *
 * @param  string $feed Optional.
 *                      Feed type.
 *                      Default empty.
 * @return string The feed permalink.
 */
function get_feed_link( $feed = '' )
{
	global $wp_rewrite;
	$permalink = $wp_rewrite->get_feed_permastruct();

	if ( '' != $permalink ) {
		if ( FALSE !== strpos( $feed, 'comments_' ) ) {
			$feed = str_replace( 'comments_', '', $feed );
			$permalink = $wp_rewrite->get_comment_feed_permastruct();
// @NOW 009
		}
	}
}

/**
 * Retrieves the URL for the current site where the front end is accessible.
 *
 * Returns the 'home' option with the appropriate protocol.
 * The protocol will be 'https' if is_ssl() evaluates to true; otherwise, it will be the same as the 'home' option.
 * If `$scheme` is 'http' or 'https', is_ssl() is overridden.
 *
 * @since 3.0.0
 *
 * @param  string      $path   Optional.
 *                             Path relative to the home URL.
 *                             Default empty.
 * @param  string|null $scheme Optional.
 *                             Scheme to give the home URL context.
 *                             Accepts 'http', 'https', 'relative', 'rest', or null.
 *                             Default null.
 * @return string      Home URL link with optional path appended.
 */
function home_url( $path = '', $scheme = NULL )
{
	return get_home_url( NULL, $path, $scheme );
}

/**
 * Retrieves the URL for a given site where the front end is accessible.
 *
 * Returns the 'home' option with the appropriate protocol.
 * The protocol will be 'https' if is_ssl() evaluates to true; otherwise, it will be the same as the 'home' option.
 * If `$scheme` is 'http' or 'https', is_ssl() is overridden.
 *
 * @since  3.0.0
 * @global $pagenow
 *
 * @param  int         $blog_id Optional.
 *                              Site ID.
 *                              Default null (current site).
 * @param  string      $path    Optional.
 *                              Path relative to the home URL.
 *                              Default empty.
 * @param  string|null $scheme  Optional.
 *                              Scheme to give the home URL context.
 *                              Accepts 'http', 'https', 'relative', 'rest', or null.
 *                              Default null.
 * @return string      Home URL link with optional path appended.
 */
function get_home_url( $blog_id = NULL, $path = '', $scheme = NULL )
{
	global $pagenow;
	$orig_scheme = $scheme;

	if ( empty( $blog_id ) || ! is_multisite() ) {
		$url = get_option( 'home' );
	} else {
		switch_to_blog( $blog_id );
		$url = get_option( 'home' );
		restore_current_blog();
	}

	if ( ! in_array( $scheme, array( 'http', 'https', 'relative' ) ) ) {
		$scheme = is_ssl() && ! is_admin() && 'wp-login.php' !== $pagenow
			? 'https'
			: parse_url( $url, PHP_URL_SCHEME );
	}

	$url = set_url_scheme( $url, $scheme );

	if ( $path && is_string( $path ) ) {
		$url .= '/' . ltrim( $path, '/' );
	}

	/**
	 * Filters the home URL.
	 *
	 * @since 3.0.0
	 *
	 * @param string      $url         The complete home URL including scheme and path.
	 * @param string      $path        Path relative to the home URL.
	 *                                 Blank string if no path is specified.
	 * @param string|null $orig_scheme Scheme to give the home URL context.
	 *                                 Accepts 'http', 'https', 'relative', 'rest', or null.
	 * @param int|null    $blog_id     Site ID, or null for the current site.
	 */
	return apply_filters( 'home_url', $url, $path, $orig_scheme, $blog_id );
}

/**
 * Retrieves the URL for the current site where WordPress application files (e.g. wp-blog-header.php or the wp-admin/ folder) are accessible.
 *
 * Returns the 'site_url' option with the appropriate protocol, 'https' if is_ssl() and 'http' otherwise.
 * If $scheme is 'http' or 'https', is_ssl() is overridden.
 *
 * @since 3.0.0
 *
 * @param  string $path   Optional.
 *                        Path relative to the site URL.
 *                        Default empty.
 * @param  string $scheme Optional.
 *                        Scheme to give the site URL context.
 *                        See set_url_scheme().
 * @return string Site URL link with optional path appended.
 */
function site_url( $path = '', $scheme = NULL )
{
	return get_site_url( NULL, $path, $scheme );
}

/**
 * Retrieves the URL for a given site WHERE WordPress application files (e.g. wp-blog-header.php or the wp-admin/ folder) are accessible.
 *
 * Returns the 'site_url' option with the appropriate protocol, 'https' if is_ssl() and 'http' otherwise.
 * If `$scheme` is 'http' or 'https', `is_ssl()` is overridden.
 *
 * @since 3.0.0
 *
 * @param  int    $blog_id Optional.
 *                         Site ID.
 *                         Default null (current site).
 * @param  string $path    Optional.
 *                         Path relative to the site URL.
 *                         Default empty.
 * @param  string $scheme  Optional.
 *                         Scheme to give the site URL context.
 *                         Accepts 'http', 'https', 'login', 'login_post', 'admin' or 'relative'.
 *                         Default null.
 * @return string Site URL link with optional path appended.
 */
function get_site_url( $blog_id = NULL, $path = '', $scheme = NULL )
{
	if ( empty( $blog_id ) || ! is_multisite() ) {
		$url = get_option( 'siteurl' );
	} else {
		switch_to_blog( $blog_id );
		$url = get_option( 'siteurl' );
		restore_current_blog();
	}

	$url = set_url_scheme( $url, $scheme );

	if ( $path && is_string( $path ) ) {
		$url .= '/' . ltrim( $path, '/' );
	}

	/**
	 * Filters the site URL.
	 *
	 * @since 2.7.0
	 *
	 * @param string      $url     The complete site URL including scheme and path.
	 * @param string      $path    Path relative to the site URL.
	 *                             Blank string if no path is specified.
	 * @param string|null $scheme  Scheme to give the site URL context.
	 *                             Accepts 'http', 'https', 'login', 'login_post', 'admin', 'relative' or null.
	 * @param int|null    $blog_id Site ID, or null for the current site.
	 */
	return apply_filters( 'site_url', $url, $path, $scheme, $blog_id );
}

/**
 * Sets the scheme for a URL.
 *
 * @since 3.4.0
 * @since 4.4.0 The 'rest' scheme was added.
 *
 * @param  string      $url    Absolute URL that includes a scheme.
 * @param  string|null $scheme Optional.
 *                             Scheme to give $url.
 *                             Currently 'http', 'https', 'login', 'login_post', 'admin', 'relative', 'rest', 'rpc', or null.
 *                             Default null.
 * @return string      $url    URL with chosen scheme.
 */
function set_url_scheme( $url, $scheme = NULL )
{
	$orig_scheme = $scheme;

	if ( ! $scheme ) {
		$scheme = is_ssl()
			? 'https'
			: 'http';
	} elseif ( $scheme === 'admin' || $scheme === 'login' || $scheme === 'login_post' || $scheme === 'rpc' ) {
		$scheme = is_ssl() || force_ssl_admin()
			? 'https'
			: 'http';
	} elseif ( $scheme !== 'http' && $scheme !== 'https' && $scheme !== 'relative' ) {
		$scheme = is_ssl()
			? 'https'
			: 'http';
	}

	$url = trim( $url );

	if ( substr( $url, 0, 2 ) === '//' ) {
		$url = 'http:' . $url;
	}

	if ( 'relative' == $scheme ) {
		$url = ltrim( preg_replace( '#^\w+://[^/]*#', '', $url ) );

		if ( $url !== '' && $url[0] === '/' ) {
			$url = '/' . ltrim( $url, "/ \t\n\r\0\x0B" );
		}
	} else {
		$url = preg_replace( '#^\w+://#', $scheme . '://', $url );
	}

	/**
	 * Filters the resulting URL after setting the scheme.
	 *
	 * @since 3.4.0
	 *
	 * @param string      $url         The complete URL including scheme and path.
	 * @param string      $scheme      Scheme applied to the URL.
	 *                                 One of 'http', 'https', or 'relative'.
	 * @param string|null $orig_scheme Scheme requested for the URL.
	 *                                 One of 'http', 'https', 'login', 'login_post', 'admin', 'relative', 'rest', 'rpc', or null.
	 */
	return apply_filters( 'set_url_scheme', $url, $scheme, $orig_scheme );
}
