<?php
/**
 * HTTP API: WP_Http class
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      2.7.0
 */

if ( ! class_exists( 'Requests' ) ) {
	require( ABSPATH . WPINC . '/class-requests.php' );
	Requests::register_autoloader();
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
 * <-......: wp-includes/http.php: wp_http_supports( [array $capabilities = array() [, string $url = NULL]] )
 * <-......: wp-includes/http.php: _wp_http_get_object()
 * @NOW 014: wp-includes/class-http.php
 * ......->: wp-includes/class-requests.php: Requests::register_autoloader()
 */
}
