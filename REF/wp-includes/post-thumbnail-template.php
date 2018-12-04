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
 * Retrieve post thumbnail ID.
 *
 * @since 2.9.0
 * @since 4.4.0 `$post` can be a post ID or WP_Post object.
 *
 * @param  int|WP_Post $post Optional.
 *                           Post ID or WP_Post object.
 *                           Default is global `$post`.
 * @return string|int  Post thumbnail ID or empty string.
 */
function get_post_thumbnail_id( $post = NULL )
{
	$post = get_post( $post );

	return ! $post
		? ''
		: get_post_meta( $post->ID, '_thumbnail_id', TRUE );
}
