<?php
/**
 * HTTP Proxy connection interface
 *
 * @package    Requests
 * @subpackage Proxy
 * @since      1.6
 */

/**
 * HTTP Proxy connection interface.
 *
 * Provides a handler for connection via an HTTP proxy.
 *
 * @package    Requests
 * @subpackage Proxy
 * @since      1.6
 */
class Requests_Proxy_HTTP implements Requests_Proxy
{
	/**
	 * Proxy host and port.
	 *
	 * Notation: "host:port" (e.g. 127.0.0.1:8080 or someproxy.com:3128)
	 *
	 * @var string
	 */
	public $proxy;

	/**
	 * Username.
	 *
	 * @var string
	 */
	public $user;

	/**
	 * Password.
	 *
	 * @var string
	 */
	public $pass;

	/**
	 * Do we need to authenticate?
	 * (i.e. username & password have been provided)
	 *
	 * @var bool
	 */
	public $use_authentication;

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
 * @NOW 013: wp-includes/Requests/Proxy/HTTP.php: Requests_Proxy_HTTP::__construct( [array|null $args = NULL] )
 */
}
