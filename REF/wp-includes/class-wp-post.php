<?php
/**
 * Post API: WP_Post class
 *
 * @package    WordPress
 * @subpackage Post
 * @since      4.4.0
 */

/**
 * Core class used to implement the WP_Post object.
 *
 * @since 3.5.0
 */
final class WP_Post
{
	/**
	 * Post ID.
	 *
	 * @since 3.5.0
	 *
	 * @var int
	 */
	public $ID;

	/**
	 * ID of post author.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $post_author = 0;

	/**
	 * The post's local publication time.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $post_date = '0000-00-00 00:00:00';

	/**
	 * The post's GMT publication time.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $post_date_gmt = '0000-00-00 00:00:00';

	/**
	 * The post's content.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $post_content = '';

	/**
	 * The post's title.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $post_title = '';

	/**
	 * The post's excerpt.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $post_excerpt = '';

	/**
	 * The post's status.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $post_status = 'publish';

	/**
	 * Whether comments are allowed.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $comment_status = 'open';

	/**
	 * Whether pings are allowed.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $ping_status = 'open';

	/**
	 * The post's password in plain text.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $post_password = '';

	/**
	 * The post's slug.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $post_name = '';

	/**
	 * URLs queued to be pinged.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $to_ping = '';

	/**
	 * URL that have been pinged.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $pinged = '';

	/**
	 * The post's local modified time.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $post_modified = '0000-00-00 00:00:00';

	/**
	 * The post's GMT modified time.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $post_modified_gmt = '0000-00-00 00:00:00';

	/**
	 * A utility DB field for post content.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $post_content_filtered = '';

	/**
	 * ID of a post's parent post.
	 *
	 * @since 3.5.0
	 *
	 * @var int
	 */
	public $post_parent = 0;

	/**
	 * The unique identifier for a post, not necessarily a URL, used as the feed GUID.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $guid = '';

	/**
	 * A field used for ordering posts.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $menu_order = 0;

	/**
	 * The post's type, like post or page.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $post_type = 'post';

	/**
	 * An attachment's mime type.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $post_mime_type = '';

	/**
	 * Cached comment count.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $comment_count = 0;

	/**
	 * Stores the post object's sanitization level.
	 *
	 * Does not correspond to a DB field.
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	public $filter;

	/**
	 * Retrieve WP_Post instance.
	 *
	 * @since  3.5.0
	 * @static
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param  int           $post_id Post ID.
	 * @return WP_Post|false Post object, false otherwise.
	 */
	public static function get_instance( $post_id )
	{
		global $wpdb;
		$post_id = ( int ) $post_id;

		if ( ! $post_id ) {
			return FALSE;
		}

		$_post = wp_cache_get( $post_id, 'posts' );

		if ( ! $_post ) {
			$_post = $wpdb->get_row( $wpdb->prepare( <<<EOQ
SELECT *
FROM $wpdb->posts
WHERE ID = %d
LIMIT 1
EOQ
					, $post_id ) );

			if ( ! $_post ) {
				return FALSE;
			}

			$_post = sanitize_post( $_post, 'raw' );
			wp_cache_add( $_post->ID, $_post, 'posts' );
		} elseif ( empty( $_post->filter ) ) {
			$_post = sanitize_post( $_post, 'raw' );
		}

		return new WP_Post( $_post );
	}

	/**
	 * Constructor.
	 *
	 * @since 3.5.0
	 *
	 * @param WP_Post|object $post Post object.
	 */
	public function __construct( $post )
	{
		foreach ( get_object_vars( $post ) as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * {@Missing Summary}
	 *
	 * @since 3.5.0
	 *
	 * @param  string                         $filter Filter.
	 * @return self|array|bool|object|WP_Post
	 */
	public function filter( $filter )
	{
		return $this->filter == $filter
			? $this
			: ( $filter == 'raw'
				? self::get_instance( $this->ID )
				: sanitize_post( $this, $filter ) );
	}

	/**
	 * Isset-er.
	 *
	 * @since 3.5.0
	 *
	 * @param  string $key Property to check if set.
	 * @return bool
	 */
	public function __isset( $key )
	{
		return 'ancestors' == $key || 'page_template' == $key || 'post_category' == $key || 'tags_input' == $key
			? TRUE
			: metadata_exists( 'post', $this->ID, $key );
	}

	/**
	 * Getter.
	 *
	 * @since 3.5.0
	 *
	 * @param  string $key Key to get.
	 * @return mixed
	 */
	public function __get( $key )
	{
		if ( 'page_template' == $key && $this->__isset( $key ) ) {
			return get_post_meta( $this->ID, '_wp_page_template', TRUE );
		}

		if ( 'post_category' == $key ) {
			if ( is_object_in_taxonomy( $this->post_type, 'category' ) ) {
// self -> @NOW 008 -> wp-includes/taxonomy.php
			}
		}
	}

	/**
	 * Convert object to array.
	 *
	 * @since 3.5.0
	 *
	 * @return array Object as array.
	 */
	public function to_array()
	{
		$post = get_object_vars( $this );

		foreach ( array( 'ancestors', 'page_template', 'post_category', 'tags_input' ) as $key ) {
			if ( $this->__isset( $key ) ) {
				$post[ $key ] = $this->__get( $key );
// wp-includes/post.php -> @NOW 007 -> self
			}
		}
	}
}