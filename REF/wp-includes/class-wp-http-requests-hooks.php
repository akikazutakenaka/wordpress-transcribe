<?php
/**
 * HTTP API: Requests hook bridge class
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      4.7.0
 */

/**
 * Bridge to connect Requests internal hooks to WordPress actions.
 *
 * @since 4.7.0
 * @see   Requests_Hooks
 */
class WP_HTTP_Requests_Hooks extends Requests_Hooks
{
	/**
	 * Requested URL.
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * WordPress WP_HTTP request data.
	 *
	 * @var array Request data in WP_Http format.
	 */
	protected $request = array();

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
 * @NOW 013: wp-includes/class-wp-http-requests-hooks.php: WP_HTTP_Requests_Hooks::__construct()
 */
}
