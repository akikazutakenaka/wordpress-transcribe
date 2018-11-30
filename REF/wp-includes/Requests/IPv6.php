<?php
/**
 * Class to validate and to work with IPv6 addresses
 *
 * @package    Requests
 * @subpackage Utilities
 */

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
 * <-......: wp-includes/class-requests.php: Requests::set_defaults( &string $url, &array $headers, &array|null $data, &string $type, &array $options )
 * <-......: wp-includes/Requests/Cookie/Jar.php: Requests_Cookie_Jar::register( Requests_Hooker $hooks )
 * <-......: wp-includes/Requests/Cookie/Jar.php: Requests_Cookie_Jar::before_request( string $url, &array $headers, &array $data, &string $type, &array $options )
 * <-......: wp-includes/Requests/IRI.php: Requests_IRI::set_iri( string $iri )
 * <-......: wp-includes/Requests/IRI.php: Requests_IRI::set_authority( string $authority )
 * <-......: wp-includes/Requests/IRI.php: Requests_IRI::set_host( string $host )
 * @NOW 020: wp-includes/Requests/IPv6.php: Requests_IPv6
 */
