<?php
/**
 * Core Post API
 *
 * @package    WordPress
 * @subpackage Post
 */

/**
 * Retrieves post data given a post ID or post object.
 *
 * See sanitize_post() for optional $filter values.
 * Also, the parameter `$post`, must be given as a variable, since it is passed by reference.
 *
 * @since  1.5.1
 * @global WP_Post $post
 *
 * @param  int|WP_Post|null   $post   Optional.
 *                                    Post ID or post object.
 *                                    Defaults to global $post.
 * @param  string             $output Optional.
 *                                    The required return type.
 *                                    One of OBJECT, ARRAY_A, or ARRAY_N, which correspond to a WP_Post object, an associative array, or a numeric array, respectively.
 *                                    Default OBJECT.
 * @param  string             $filter Optional.
 *                                    Type of filter to apply.
 *                                    Accepts 'raw', 'edit', 'db', or 'display'.
 *                                    Default 'raw'.
 * @return WP_Post|array|null Type corresponding to $output on success or null on failure.
 *                            When $output is OBJECT, a `WP_Post` instance is returned.
 */
function get_post( $post = NULL, $output = OBJECT, $filter = 'raw' )
{
	if ( empty( $post ) && isset( $GLOBALS['post'] ) ) {
		$post = $GLOBALS['post'];
	}

	if ( $post instanceof WP_Post ) {
		$_post = $post;
	} elseif ( is_object( $post ) ) {
		if ( empty( $post->filter ) ) {
			$_post = sanitize_post( $post, 'raw' );
// @NOW 007 -> wp-includes/post.php
		}
	}
}

/**
 * Sanitize every post field.
 *
 * If the context is 'raw', then the post object or array will get minimal sanitization of the integer fields.
 *
 * @since 2.3.0
 * @see   sanitize_post_field()
 *
 * @param  object|WP_Post|array $post    The Post Object or Array.
 * @param  string               $context Optional.
 *                                       How to sanitize post fields.
 *                                       Accepts 'raw', 'edit', 'db', or 'display'.
 *                                       Default 'display'.
 * @param  object|WP_Post|array The now sanitized Post Object or Array (will be the same type as $post).
 */
function sanitize_post( $post, $context = 'display' )
{
	if ( is_object( $post ) ) {
		// Check if post already filtered for this context.
		if ( isset( $post->filter ) && $context == $post->filter ) {
			return $post;
		}

		if ( ! isset( $post->ID ) ) {
			$post->ID = 0;
		}

		foreach ( array_keys( get_object_vars( $post ) ) as $field ) {
			$post->$field = sanitize_post_field( $field, $post->$field, $post->ID, $context );
// @NOW 008 -> wp-includes/post.php
		}
	}
}

// @NOW 009

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
	}

	// First, get all of the original fields.
	$post = get_post( $postarr['ID'], ARRAY_A );
// @NOW 006 -> wp-includes/post.php
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
