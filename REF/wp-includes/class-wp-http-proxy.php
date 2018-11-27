<?php
/**
 * HTTP API: WP_HTTP_Proxy class
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      4.4.0
 */

/**
 * Core class used to implement HTTP API proxy support.
 *
 * There are caveats to proxy support.
 * It requires that defines be made in the wp-config.php file to enable proxy support.
 * There are also a few filters that plugins can hook into for some of the constants.
 *
 * Please note that only BASIC authentication is supported by most transports.
 * cURL MAY support more methods (such as NTLM authentication) depending on your environment.
 *
 * The constants are as follows:
 *
 * 1. WP_PROXY_HOST         - Enable proxy support and host for connecting.
 * 2. WP_PROXY_PORT         - Proxy port for connection.
 *                            No default, must be defined.
 * 3. WP_PROXY_USERNAME     - Proxy username, if it requires authentication.
 * 4. WP_PROXY_PASSWORD     - Proxy password, if it requires authentication.
 * 5. WP_PROXY_BYPASS_HOSTS - Will prevent the hosts in this list from going through the proxy.
 *                            You do not need to have localhost and the site host in this list, because they will not be passed through the proxy.
 *                            The list should be presented in a comma separated list, wildcards using * are supported, eg. *.wordpress.org.
 *
 * An example can be as seen below.
 *
 *     define( 'WP_PROXY_HOST', '192.168.84.101' );
 *     define( 'WP_PROXY_PORT', '8080' );
 *     define( 'WP_PROXY_BYPASS_HOSTS', 'localhost, www.example.com, *.wordpress.org' );
 *
 * @link  https://core.trac.wordpress.org/ticket/4011 Proxy support ticket in WordPress.
 * @link  https://core.trac.wordpress.org/ticket/14636 Allow wildcard domains in WP_PROXY_BYPASS_HOSTS
 * @since 2.8.0
 */
class WP_HTTP_Proxy
{
	/**
	 * Whether proxy connection should be used.
	 *
	 * @since 2.8.0
	 * @uses  WP_PROXY_HOST
	 * @uses  WP_PROXY_PORT
	 *
	 * @return bool
	 */
	public function is_enabled()
	{
		return defined( 'WP_PROXY_HOST' ) && defined( 'WP_PROXY_PORT' );
	}

/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/post.php: wp_check_post_hierarchy_for_loops( int $post_parent, int $post_ID )
 * <-......: wp-includes/post.php: wp_insert_post( array $postarr [, bool $wp_error = FALSE] )
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::get_page_templates( [WP_Post|null $post = NULL [, string $post_type = 'page']] )
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::get_post_templates()
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::translate_header( string $header, string $value )
 * <-......: wp-admin/includes/theme.php: get_theme_feature_list( [bool $api = TRUE] )
 * <-......: wp-admin/includes/theme.php: themes_api( string $action [, array|object $args = array()] )
 * <-......: wp-includes/class-http.php: WP_Http::request( string $url [, string|array $args = array()] )
 * @NOW 013: wp-includes/class-wp-http-proxy.php: WP_HTTP_Proxy::host()
 */

	/**
	 * Whether URL should be sent through the proxy server.
	 *
	 * We want to keep localhost and the site URL from being sent through the proxy server, because some proxies can not handle this.
	 * We also have the constant available for defining other hosts that won't be sent through the proxy.
	 *
	 * @since     2.8.0
	 * @staticvar array|null $bypass_hosts
	 * @staticvar array      $wildcard_regex
	 *
	 * @param  string $uri URI to check.
	 * @return bool   True, to send through the proxy and false if, the proxy should not be used.
	 */
	public function send_through_proxy( $uri )
	{
		/**
		 * parse_url() only handles http, https type URLs, and will emit E_WARNING on failure.
		 * This will be displayed on sites, which is not reasonable.
		 */
		$check = @ parse_url( $uri );

		// Malformed URL, can not process, but this could mean ssl, so let through anyway.
		if ( $check === FALSE ) {
			return TRUE;
		}

		$home = parse_url( get_option( 'siteurl' ) );

		/**
		 * Filters whether to preempt sending the request through the proxy server.
		 *
		 * Returning false will bypass the proxy; returning true will send the request through the proxy.
		 * Returning null bypasses the filter.
		 *
		 * @since 3.5.0
		 *
		 * @param null   $override Whether to override the request result.
		 *                         Default null.
		 * @param string $uri      URL to check.
		 * @param array  $check    Associative array result of parsing the URI.
		 * @param array  $home     Associative array result of parsing the site URL.
		 */
		$result = apply_filters( 'pre_http_send_through_proxy', NULL, $uri, $check, $home );

		if ( ! is_null( $result ) ) {
			return $result;
		}

		if ( 'localhost' == $check['host']
		  || isset( $home['host'] ) && $home['host'] == $check['host'] ) {
			return FALSE;
		}

		if ( ! defined( 'WP_PROXY_BYPASS_HOSTS' ) ) {
			return TRUE;
		}

		static $bypass_hosts = NULL;
		static $wildcard_regex = array();

		if ( NULL === $bypass_hosts ) {
			$bypass_hosts = preg_split( '|,\s*|', WP_PROXY_BYPASS_HOSTS );

			if ( FALSE !== strpos( WP_PROXY_BYPASS_HOSTS, '*' ) ) {
				$wildcard_regex = array();

				foreach ( $bypass_hosts as $host ) {
					$wildcard_regex[] = str_replace( '\*', '.+', preg_quote( $host, '/' ) );
				}

				$wildcard_regex = '/^(' . implode( '|', $wildcard_regex ) . ')$/i';
			}
		}

		return ! empty( $wildcard_regex )
			? ! preg_match( $wildcard_regex, $check['host'] )
			: ! in_array( $check['host'], $bypass_hosts );
	}
}
