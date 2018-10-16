<?php
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

	if ( ! in_array( $scheme, ['http', 'https', 'relative'] ) ) {
		$scheme = ( is_ssl() && ! is_admin() && 'wp-login.php' !== $pagenow )
			? 'https'
			: parse_url( $url, PHP_URL_SCHEME );
// @NOW 009 -> wp-includes/load.php
	}
}
