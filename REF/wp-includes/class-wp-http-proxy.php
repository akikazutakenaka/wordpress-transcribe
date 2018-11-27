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
 * @NOW 013: wp-includes/class-wp-http-proxy.php: WP_HTTP_Proxy::send_through_proxy( string $uri )
 */
}
