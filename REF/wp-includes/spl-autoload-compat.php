<?php
/**
 * Polyfill for SPL autoload feature.
 * This file is separate to prevent compiler notices on the deprecated __autoload() function.
 *
 * See https://core.trac.wordpress.org/ticket/41134
 *
 * @package PHP
 * @access  private
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
 * <-......: wp-includes/http.php: wp_http_supports( [array $capabilities = array() [, string $url = NULL]] )
 * <-......: wp-includes/http.php: _wp_http_get_object()
 * <-......: wp-includes/class-http.php
 * @NOW 015: wp-includes/spl-autoload-compat.php: spl_autoload_register( callable $autoload_function [, bool $throw = TRUE [, bool $prepend = FALSE]] )
 */
