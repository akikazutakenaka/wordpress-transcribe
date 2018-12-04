<?php
/**
 * Dependencies API: WP_Scripts class
 *
 * @since      2.6.0
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
 * <-......: wp-includes/functions.wp-scripts.php: wp_scripts()
 * @NOW 009: wp-includes/class.wp-scripts.php: WP_Scripts
 */
