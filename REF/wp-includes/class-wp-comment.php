<?php
/**
 * Comment API: WP_Comment class
 *
 * @package    WordPress
 * @subpackage Comments
 * @since      4.4.0
 */

/**
 * Core class used to organize comments as instantiated objects with defined members.
 *
 * @since 4.4.0
 */
final class WP_Comment
{
	/**
	 * Comment ID.
	 *
	 * @since 4.4.0
	 *
	 * @var int
	 */
	public $comment_ID;

	/**
	 * ID of the post the comment is associated with.
	 *
	 * @since 4.4.0
	 *
	 * @var int
	 */
	public $comment_post_ID = 0;

	/**
	 * Comment author name.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $comment_author = '';

	/**
	 * Comment author email address.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $comment_author_email = '';

	/**
	 * Comment author URL.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $comment_author_url = '';

	/**
	 * Comment author IP address (IPv4 format).
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $comment_author_IP = '';

	/**
	 * Comment date in YYYY-MM-DD HH:MM:SS format.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $comment_date = '0000-00-00 00:00:00';

	/**
	 * Comment GMT date in YYYY-MM-DD HH:MM:SS format.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $comment_date_gmt = '0000-00-00 00:00:00';

	/**
	 * Comment content.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $comment_content;

	/**
	 * Comment karma count.
	 *
	 * @since 4.4.0
	 *
	 * @var int
	 */
	public $comment_karma = 0;

	/**
	 * Comment approval status.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $comment_approved = '1';

	/**
	 * Comment author HTTP user agent.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $comment_agent = '';

	/**
	 * Comment type.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $comment_type = '';

	/**
	 * Parent comment ID.
	 *
	 * @since 4.4.0
	 *
	 * @var int
	 */
	public $comment_parent = 0;

	/**
	 * Comment author ID.
	 *
	 * @since 4.4.0
	 *
	 * @var int
	 */
	public $user_id = 0;

	/**
	 * Comment children.
	 *
	 * @since 4.4.0
	 *
	 * @var array
	 */
	protected $children;

	/**
	 * Whether children have been populated for this comment object.
	 *
	 * @since 4.4.0
	 *
	 * @var bool
	 */
	protected $populated_children = FALSE;

	/**
	 * Post fields.
	 *
	 * @since 4.4.0
	 *
	 * @var array
	 */
	protected $post_fields = array( 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'comment_status', 'ping_status', 'post_name', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 'post_content_filtered', 'post_parent', 'guid', 'menu_order', 'post_type', 'post_mime_type', 'comment_count' );

	/**
	 * Retrieves a WP_Comment instance.
	 *
	 * @since  4.4.0
	 * @static
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param  int              $id Comment ID.
	 * @return WP_Comment|false Comment object, otherwise false.
	 */
	public static function get_instance( $id )
	{
		global $wpdb;
		$comment_id = ( int ) $id;

		if ( ! $comment_id ) {
			return FALSE;
		}

		$_comment = wp_cache_get( $comment_id, 'comment' );

		if ( ! $_comment ) {
			$_comment = $wpdb->get_row( $wpdb->prepare( <<<EOQ
SELECT *
FROM $wpdb->comments
WHERE comment_ID = %d
LIMIT 1
EOQ
					, $comment_id ) );

			if ( ! $_comment ) {
				return FALSE;
			}

			wp_cache_add( $_comment->comment_ID, $_comment, 'comment' );
		}

		return new WP_Comment( $_comment );
	}

	/**
	 * Constructor.
	 *
	 * Populates properties with object vars.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_Comment $comment Comment object.
	 */
	public function __construct( $comment )
	{
		foreach ( get_object_vars( $comment ) as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * Convert object to array.
	 *
	 * @since 4.4.0
	 *
	 * @return array Object as array.
	 */
	public function to_array()
	{
		return get_object_vars( $this );
	}
}
