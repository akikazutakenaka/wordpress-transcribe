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

	$_post = $post instanceof WP_Post
		? $post
		: ( is_object( $post )
			? ( empty( $post->filter )
				? new WP_Post( sanitize_post( $post, 'raw' ) )
				: ( 'raw' == $post->filter
					? new WP_Post( $post )
					: WP_Post::get_instance( $post->ID ) ) )
			: WP_Post::get_instance( $post ) );

	if ( ! $_post ) {
		return NULL;
	}

	$_post = $_post->filter( $filter );
// @NOW 007
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
		}

		$post->filter = $context;
	} elseif ( is_array( $post ) ) {
		// Check if post already filtered for this context.
		if ( isset( $post['filter'] ) && $context == $post['filter'] ) {
			return $post;
		}

		if ( ! isset( $post['ID'] ) ) {
			$post['ID'] = 0;
		}

		foreach ( array_keys( $post ) as $field ) {
			$post[ $field ] = sanitize_post_field( $field, $post[ $field ], $post['ID'], $context );
		}

		$post['filter'] = $context;
	}

	return $post;
}

/**
 * Sanitize post field based on context.
 *
 * Possible context values are: 'raw', 'edit', 'db', 'display', 'attribute' and 'js'.
 * The 'display' context is used by default.
 * 'attribute' and 'js' contexts are treated like 'display' when calling filters.
 *
 * @since 2.3.0
 * @since 4.4.0 Like `sanitize_post()`, `$context` defaults to 'display'.
 *
 * @param  string $field   The Post Object field name.
 * @param  mixed  $value   The Post Object value.
 * @param  int    $post_id Post ID.
 * @param  string $context Optional.
 *                         How to sanitize post fields.
 *                         Looks for 'raw', 'edit', 'db', 'display', 'attribute' and 'js'.
 *                         Default 'display'.
 * @return mixed  Sanitized value.
 */
function sanitize_post_field( $field, $value, $post_id, $context = 'display' )
{
	$int_fields = array( 'ID', 'post_parent', 'menu_order' );

	if ( in_array( $field, $int_fields ) ) {
		$value = ( int ) $value;
	}

	// Fields which contain arrays of integers.
	$array_int_fields = array( 'ancestors' );

	if ( in_array( $field, $array_int_fields ) ) {
		$value = array_map( 'absint', $value );
		return $value;
	}

	if ( 'raw' == $context ) {
		return $value;
	}

	$prefixed = FALSE;

	if ( FALSE !== strpos( $field, 'post_' ) ) {
		$prefixed = TRUE;
		$field_no_prefix = str_replace( 'post_', '', $field );
	}

	if ( 'edit' == $context ) {
		$format_to_edit = array( 'post_content', 'post_excerpt', 'post_title', 'post_password' );

		if ( $prefixed ) {
			/**
			 * Filters the value of a specific post field to edit.
			 *
			 * The dynamic portion of the hook name, `$field`, refers to the post field name.
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value   Value of the post field.
			 * @param int   $post_id Post ID.
			 */
			$value = apply_filters( "edit_{$field}", $value, $post_id );

			/**
			 * Filters the value of a specific post field to edit.
			 *
			 * The dynamic portion of the hook name, `$field_no_prefix`, refers to the post field name.
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value   Value of the post field.
			 * @param int   $post_id Post ID.
			 */
			$value = apply_filters( "{$field_no_prefix}_edit_pre", $value, $post_id );
		} else {
			$value = apply_filters( "edit_post_{$field}", $value, $post_id );
		}

		$value = in_array( $field, $format_to_edit )
			? ( 'post_content' == $field
				? format_to_edit( $value, user_can_richedit() )
				: format_to_edit( $value ) )
			: esc_attr( $value );
	} elseif ( 'db' == $context ) {
		if ( $prefixed ) {
			/**
			 * Filters the value of a specific post field before saving.
			 *
			 * The dynamic portion of the hook name, `$field`, refers to the post field name.
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value Value of the post field.
			 */
			$value = apply_filters( "pre_{$field}", $value );

			/**
			 * Filters the value of a specific field before saving.
			 *
			 * The dynamic portion of the hook name, `$field_no_prefix`, refers to the post field name.
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value Value of the post field.
			 */
			$value = apply_filters( "{$field_no_prefix}_save_pre", $value );
		} else {
			$value = apply_filters( "pre_post_{$field}", $value );

			/**
			 * Filters the value of a specific post field before saving.
			 *
			 * The dynamic portion of the hook name, `$field`, refers to the post field name.
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value Value of the post field.
			 */
			$value = apply_filters( "{$field}_pre", $value );
		}
	} else {
		// Use display filters by default.
		if ( $prefixed ) {
			/**
			 * Filters the value of a specific post field for display.
			 *
			 * The dynamic portion of the hook name, `$field`, refers to the post field name.
			 *
			 * @since 2.3.0
			 *
			 * @param mixed  $value   Value of the prefixed post field.
			 * @param int    $post_id Post ID.
			 * @param string $context Context for how to sanitize the field.
			 *                        Possible values include 'raw', 'edit', 'db', 'display', 'attribute', and 'js'.
			 */
			$value = apply_filters( "{$field}", $value, $post_id, $context );
		} else {
			$value = apply_filters( "post_{$field}", $value, $post_id, $context );
		}

		if ( 'attribute' == $context ) {
			$value = esc_attr( $value );
		} elseif ( 'js' == $context ) {
			$value = esc_js( $value );
		}
	}

	return $value;
}

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
