<?php
/**
 * Core Comment API
 *
 * @package    WordPress
 * @subpackage Comment
 */

/**
 * Retrieves comment data given a comment ID or comment object.
 *
 * If an object is passed then the comment data will be cached and then returned after being passed through a filter.
 * If the comment is empty, then the global comment variable will be used, if it is set.
 *
 * @since  2.0.0
 * @global WP_Comment $comment
 *
 * @param  WP_Comment|string|int $comment Comment to retrieve.
 * @param  string                $output  Optional.
 *                                        The required return type.
 *                                        One of OBJECT, ARRAY_A, or ARRAY_N, which correspond to a WP_Comment object, an associative array, or a numeric array, respectively.
 *                                        Default OBJECT.
 * @return WP_Comment|array|null Depends on $output value.
 */
function get_comment( &$comment = NULL, $output = OBJECT )
{
	if ( empty( $comment ) && isset( $GLOBALS['comment'] ) ) {
		$comment = $GLOBALS['comment'];
	}

	$_comment = $comment instanceof WP_Comment
		? $comment
		: ( is_object( $comment )
			? new WP_Comment( $comment )
			: WP_Comment::get_instance( $comment ) );

	if ( ! $_comment ) {
		return NULL;
	}

	/**
	 * Fires after a comment is retrieved.
	 *
	 * @since 2.3.0
	 *
	 * @param mixed $_comment Comment data.
	 */
	$_comment = apply_filters( 'get_comment', $_comment );

	return $output == OBJECT
		? $_comment
		: ( $output == ARRAY_A
			? $_comment->to_array()
			: ( $output == ARRAY_N
				? array_values( $_comment->to_array() )
				: $_comment ) );
}
