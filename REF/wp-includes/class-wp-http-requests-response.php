<?php
/**
 * HTTP API: WP_HTTP_Requests_Response class
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      4.6.0
 */

/**
 * Core wrapper object for a Requests_Response for standardisation.
 *
 * @since 4.6.0
 * @see   WP_HTTP_Response
 */
class WP_HTTP_Requests_Response extends WP_HTTP_Response
{
	/**
	 * Requests Response object.
	 *
	 * @since 4.6.0
	 *
	 * @var Requests_Response
	 */
	protected $response;

	/**
	 * Filename the response was saved to.
	 *
	 * @since 4.6.0
	 *
	 * @var string|null
	 */
	protected $filename;

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
 * @NOW 013: wp-includes/class-wp-http-requests-response.php: WP_HTTP_Requests_Response
 * ......->: wp-includes/class-wp-http-response.php: WP_HTTP_Response::set_headers( array $headers )
 */
}
