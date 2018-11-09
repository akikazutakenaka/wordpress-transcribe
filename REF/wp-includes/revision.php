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
 * Gets a post revision.
 *
 * @since 2.6.0
 *
 * @param  int|WP_Post        $post   The post ID or object.
 * @param  string             $output Optional.
 *                                    The required return type.
 *                                    One of OBJECT, ARRAY_A, or ARRAY_N, which correspond to a WP_Post object, an associative array, or a numeric array, respectively.
 *                                    Default OBJECT.
 * @param  string             $filter Optional sanitation filter.
 *                                    See sanitize_post().
 * @return WP_Post|array|null WP_Post (or array) on success, or null on failure.
 */
function wp_get_post_revision( &$post, $output = OBJECT, $filter = 'raw' )
{
	return ( ! $revision = get_post( $post, OBJECT, $filter ) )
		? $revision
		: ( 'revision' !== $revision->post_type
			? NULL
			: ( $output == OBJECT
				? $revision
				: ( $output == ARRAY_A
					? get_object_vars( $revision )
					: ( $output == ARRAY_N
						? array_values( get_object_vars( $revision ) )
						: $revision ) ) ) );
}
