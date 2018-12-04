<?php
/**
 * Dependencies API: WP_Styles class
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
 * <-......: wp-includes/functions.wp-styles.php: wp_enqueue_style( string $handle [, string $src = '' [, array $deps = array() [, string|bool|null $ver = FALSE [, string $media = 'all']]]] )
 * <-......: wp-includes/functions.wp-styles.php: wp_styles()
 * @NOW 009: wp-includes/class.wp-styles.css: WP_Styles
 */
