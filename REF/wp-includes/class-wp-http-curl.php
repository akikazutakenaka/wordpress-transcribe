<?php
/**
 * HTTP API: WP_Http_Curl class
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      4.4.0
 */

/**
 * Core class used to integrate Curl as an HTTP transport.
 *
 * HTTP request method uses Curl extension to retrieve the url.
 *
 * Requires the Curl extension to be installed.
 *
 * @since 2.7.0
 */
class WP_Http_Curl
{
	/**
	 * Temporary header storage for during requests.
	 *
	 * @since 3.2.0
	 *
	 * @var string
	 */
	private $headers = '';

	/**
	 * Temporary body storage for during requests.
	 *
	 * @since 3.6.0
	 *
	 * @var string
	 */
	private $body = '';

	/**
	 * The maximum amount of data to receive from the remote server.
	 *
	 * @since 3.6.0
	 *
	 * @var int
	 */
	private $max_body_length = FALSE;

	/**
	 * The file resource used for streaming to file.
	 *
	 * @since 3.6.0
	 *
	 * @var resource
	 */
	private $stream_handle = FALSE;

	/**
	 * The total bytes written in the current request.
	 *
	 * @since 4.1.0
	 *
	 * @var int
	 */
	private $bytes_written_total = 0;

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
 * <-......: wp-includes/class-wp-http.php: _get_first_available_transport( array $args [, string $url = NULL] )
 * @NOW 013: wp-includes/class-wp-http-curl.php: WP_Http_Curl::test( [array $args = array()] )
 */
}
