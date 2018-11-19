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

/**
 * Gets the default comment status for a post type.
 *
 * @since 4.3.0
 *
 * @param  string $post_type    Optional.
 *                              Post type.
 *                              Default 'post'.
 * @param  string $comment_type Optional.
 *                              Comment type.
 *                              Default 'comment'.
 * @return string Expected return value is 'open' or 'closed'.
 */
function get_default_comment_status( $post_type = 'post', $comment_type = 'comment' )
{
	switch ( $comment_type ) {
		case 'pingback':
		case 'trackback':
			$supports = 'trackbacks';
			$option = 'ping';
			break;

		default:
			$supports = 'comments';
			$option = 'comment';
	}

	// Set the status.
	$status = 'page' === $post_type
		? 'closed'
		: ( post_type_supports( $post_type, $supports )
			? get_option( "default_{$option}_status" )
			: 'closed' );

	/**
	 * Filters the default comment status for the given post type.
	 *
	 * @since 4.3.0
	 *
	 * @param string $status       Default status for the given post type, either 'open' or 'closed'.
	 * @param string $post_type    Post type.
	 *                             Default is `post`.
	 * @param string $comment_type Type of comment.
	 *                             Default is `comment`.
	 */
	return apply_filters( 'get_default_comment_status', $status, $post_type, $comment_type );
}

/**
 * Queues comments for metadata lazy-loading.
 *
 * @since 4.5.0
 *
 * @param array $comments Array of comment objects.
 */
function wp_queue_comments_for_comment_meta_lazyload( $comments )
{
	// Don't use `wp_list_pluck()` to avoid by-reference manipulation.
	$comment_ids = array();

	if ( is_array( $comments ) ) {
		foreach ( $comments as $comment ) {
			if ( $comment instanceof WP_Comment ) {
				$comment_ids[] = $comment->comment_ID;
			}
		}
	}

	if ( $comment_ids ) {
		$lazyloader = wp_metadata_lazyloader();
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/class-wp-query.php
 * @NOW 010: wp-includes/comment.php
 * -> wp-includes/meta.php
 */
	}
}
