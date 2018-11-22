<?php
/**
 * WordPress Post Template Functions.
 *
 * Gets content for the current post in the loop.
 *
 * @package    WordPress
 * @subpackage Template
 */

/**
 * Retrieves the Post Global Unique Identifier (guid).
 *
 * The guid will appear to be a link, but should not be used as an link to the post.
 * The reason you should not use it as a link, is because of moving the blog across domains.
 *
 * @since 1.5.0
 *
 * @param  int|WP_Post $post Optional.
 *                           Post ID or post object.
 *                           Default is global $post.
 * @return string
 */
function get_the_guid( $post = 0 )
{
	$post = get_post( $post );

	$guid = isset( $post->guid )
		? $post->guid
		: '';

	$id = isset( $post->ID )
		? $post->ID
		: 0;

	/**
	 * Filters the Global Unique Identifier (guid) of the post.
	 *
	 * @since 1.5.0
	 *
	 * @param string $guid Global Unique Identifier (guid) of the post.
	 * @param int    $id   The post ID.
	 */
	return apply_filters( 'get_the_guid', $guid, $id );
}
