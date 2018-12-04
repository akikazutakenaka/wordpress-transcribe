<?php
/**
 * Dependencies API: _WP_Dependency class
 *
 * @since      4.7.0
 * @package    WordPress
 * @subpackage Dependencies
 */

/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/post-template.php: prepend_attachment( string $content )
 * <-......: wp-includes/media.php: wp_video_shortcode( array $attr [, string $content = ''] )
 * <-......: wp-includes/functions.wp-scripts.php: wp_enqueue_script( string $handle [, string $src = '' [, array $deps = array() [, string|bool|null $ver = FALSE [, bool $in_footer = FALSE]]]] )
 * <-......: wp-includes/class.wp-dependencies.php: WP_Dependencies::add( string $handle, string $src [, array $deps = array() [, string|bool|null $ver = FALSE [, mixed $args = NULL]]] )
 * @NOW 009: wp-includes/class-wp-dependency.php: _WP_Dependency
 */
