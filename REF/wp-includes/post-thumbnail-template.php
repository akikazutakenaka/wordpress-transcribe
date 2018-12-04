<?php
/**
 * WordPress Post Thumbnail Template Functions.
 *
 * Support for post thumbnails.
 * Theme's functions.php must call add_theme_support( 'post-thumbnails' ) to use these.
 *
 * @package    WordPress
 * @subpackage Template
 */

/**
 * Check if post has an image attached.
 *
 * @since 2.9.0
 * @since 4.4.0 `$post` can be a post ID or WP_Post object.
 *
 * @param  int|WP_Post $post Optional.
 *                           Post ID or WP_Post object.
 *                           Default is global `$post`.
 * @return bool        Whether the post has an image attached.
 */
function has_post_thumbnail( $post = NULL )
{
	return ( bool ) get_post_thumbnail_id( $post );
}

/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/post-template.php: prepend_attachment( string $content )
 * @NOW 006: wp-includes/post-thumbnail-template.php: get_post_thumbnail_id( [ int|WP_Post $post = NULL] )
 */
