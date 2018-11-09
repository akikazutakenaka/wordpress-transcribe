<?php
/**
 * Post revision functions.
 *
 * @package    WordPress
 * @subpackage Post_Revisions
 */

/**
 * Determines if the specified post is a revision.
 *
 * @since 2.6.0
 *
 * @param  int|WP_Post $post Post ID or post object.
 * @return false|int   False if not a revision, ID of revision's parent otherwise.
 */
function wp_is_post_revision( $post )
{
	return ( ! $post = wp_get_post_revision( $post ) )
		? FALSE
		: ( int ) $post->post_parent;
}

/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * @NOW 007: wp-includes/post.php
 */
