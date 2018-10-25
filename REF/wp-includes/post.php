<?php
/**
 * Core Post API
 *
 * @package    WordPress
 * @subpackage Post
 */

/**
 * Update a post with new post data.
 *
 * The date does not have to be set for drafts.
 * You can set the date and it will not be overridden.
 *
 * @since 1.0.0
 *
 * @param  array|object $postarr  Optional.
 *                                Post data.
 *                                Arrays are expected to be escaped, objects are not.
 *                                Default array.
 * @param  bool         $wp_error Optional.
 *                                Allow return of WP_Error on failure.
 *                                Default false.
 * @return int|WP_Error The value 0 or WP_Error on failure.
 *                      The post ID on success.
 */
function wp_update_post( $postarr = array(), $wp_error = FALSE )
{
	if ( is_object( $postarr ) ) {
		// Non-escaped post was passed.
		$postarr = get_object_vars( $postarr );
		$postarr = wp_slash( $postarr );
// @NOW 006
	}
}

/**
 * Check the given subset of the post hierarchy for hierarchy loops.
 *
 * Prevents loops from forming and breaks those that it finds.
 * Attached to the {@see 'wp_insert_post_parent'} filter.
 *
 * @since 3.1.0
 * @see   wp_find_hierarchy_loop()
 *
 * @param  int $post_parent ID of the parent for the post we're checking.
 * @param  int $post_ID     ID of the post we're checking.
 * @return int The new post_parent for the post, 0 otherwise.
 */
function wp_check_post_hierarchy_for_loops( $post_parent, $post_ID )
{
	// Nothing fancy here - bail.
	if ( ! $post_parent ) {
		return 0;
	}

	// New post can't cause a loop.
	if ( empty( $post_ID ) ) {
		return $post_parent;
	}

	// Can't be its own parent.
	if ( $post_parent == $post_ID ) {
		return 0;
	}

	// Now look for larger loops.
	if ( ! $loop = wp_find_hierarchy_loop( 'wp_get_post_parent_id', $post_ID, $post_parent ) ) {
		return $post_parent; // No loop
	}

	// Setting $post_parent to the given value causes a loop.
	if ( isset( $loop[ $post_ID ] ) ) {
		return 0;
	}

	/**
	 * There's a loop, but it doesn't contain $post_ID.
	 * Break the loop.
	 */
	foreach ( array_keys( $loop ) as $loop_member ) {
		wp_update_post( array(
				'ID'          => $loop_member,
				'post_parent' => 0
			) );
// @NOW 005 -> wp-includes/post.php
	}
}
