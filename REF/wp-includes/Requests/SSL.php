<?php
/**
 * SSL utilities for Requests
 *
 * @package    Requests
 * @subpackage Utilities
 */

/**
 * SSL utilities for Requests.
 *
 * Collection of utilities for working with and verifying SSL certificates.
 *
 * @package    Requests
 * @subpackage Utilities
 */
class Requests_SSL
{
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
 * <-......: wp-includes/class-requests.php: Requests::request( string $url [, array $headers = array() [, array|null $data = array() [, string $type = self::GET [, array $options = array()]]]] )
 * <-......: wp-includes/Requests/Transport/fsockopen.php: Requests_Transport_fsockopen::request( string $url [, array $headers = array() [, string|array $data = array() [, array $options = array()]]] )
 * <-......: wp-includes/Requests/Transport/fsockopen.php: verify_certificate_from_context( string $host, resource $context )
 * @NOW 016: wp-includes/Requests/SSL.php: Requests_SSL::verity_certificate( string $host, array $cert )
 */
}
