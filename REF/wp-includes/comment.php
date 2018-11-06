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
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/class-wp-user.php
 * <- wp-includes/capabilities.php
 * <- wp-includes/meta.php
 * @NOW 010: wp-includes/comment.php
 */
}
