<?php
/**
 * Query API: WP_Query class
 *
 * @package    WordPress
 * @subpackage Query
 * @since      4.7.0
 */

/**
 * The WordPress Query class.
 *
 * @link  https://codex.wordpress.org/Function_Reference/WP_Query Codex page.
 * @since 1.5.0
 * @since 4.5.0 Removed the `$comments_popup` property.
 */
class WP_Query
{
	/**
	 * Query vars set by the user.
	 *
	 * @since 1.5.0
	 *
	 * @var array
	 */
	public $query;

	/**
	 * Query vars, after parsing.
	 *
	 * @since 1.5.0
	 *
	 * @var array
	 */
	public $query_vars = array();

	/**
	 * Taxonomy query, as passed to get_tax_sql().
	 *
	 * @since 3.1.0
	 *
	 * @var object WP_Tax_Query
	 */
	public $tax_query;

	/**
	 * Metadata query container.
	 *
	 * @since 3.2.0
	 *
	 * @var object WP_Meta_Query
	 */
	public $meta_query = FALSE;

	/**
	 * Date query container.
	 *
	 * @since 3.7.0
	 *
	 * @var object WP_Date_Query
	 */
	public $date_query = FALSE;

	/**
	 * Holds the data for a single object that is queried.
	 *
	 * Holds the contents of a post, page, category, attachment.
	 *
	 * @since 1.5.0
	 *
	 * @var object|array
	 */
	public $queried_object;

	/**
	 * The ID of the queried object.
	 *
	 * @since 1.5.0
	 *
	 * @var int
	 */
	public $queried_object_id;

	/**
	 * Get post database query.
	 *
	 * @since 2.0.1
	 *
	 * @var string
	 */
	public $request;

	/**
	 * List of posts.
	 *
	 * @since 1.5.0
	 *
	 * @var array
	 */
	public $posts;

	/**
	 * The amount of posts for the current query.
	 *
	 * @since 1.5.0
	 *
	 * @var int
	 */
	public $post_count = 0;

	/**
	 * Index of the current item in the loop.
	 *
	 * @since 1.5.0
	 *
	 * @var int
	 */
	public $current_post = -1;

	/**
	 * Whether the loop has started and the caller is in the loop.
	 *
	 * @since 2.0.0
	 *
	 * @var bool
	 */
	public $in_the_loop = FALSE;

	/**
	 * The current post.
	 *
	 * @since 1.5.0
	 *
	 * @var WP_Post
	 */
	public $post;

	/**
	 * The list of comments for current post.
	 *
	 * @since 2.2.0
	 *
	 * @var array
	 */
	public $comments;

	/**
	 * The amount of comments for the posts.
	 *
	 * @since 2.2.0
	 *
	 * @var int
	 */
	public $comment_count = 0;

	/**
	 * The index of the comment in the comment loop.
	 *
	 * @since 2.2.0
	 *
	 * @var int
	 */
	public $current_comment = -1;

	/**
	 * Current comment ID.
	 *
	 * @since 2.2.0
	 *
	 * @var int
	 */
	public $comment;

	/**
	 * The amount of found posts for the current query.
	 *
	 * If limit clause was not used, equals $post_count.
	 *
	 * @since 2.1.0
	 *
	 * @var int
	 */
	public $found_posts = 0;

	/**
	 * The amount of pages.
	 *
	 * @since 2.1.0
	 *
	 * @var int
	 */
	public $max_num_pages = 0;

	/**
	 * The amount of commenet pages.
	 *
	 * @since 2.7.0
	 *
	 * @var int
	 */
	public $max_num_comment_pages = 0;

	/**
	 * Signifies whether the current query is for a single post.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_single = FALSE;

	/**
	 * Signifies whether the current query is for a preview.
	 *
	 * @since 2.0.0
	 *
	 * @var bool
	 */
	public $is_preview = FALSE;

	/**
	 * Signifies whether the current query is for a page.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_page = FALSE;

	/**
	 * Signifies whether the current query is for an archive.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_archive = FALSE;

	/**
	 * Signifies whether the current query is for a date archive.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_date = FALSE;

	/**
	 * Signifies whether the current query is for a year archive.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_year = FALSE;

	/**
	 * Signifies whether the current query is for a month archive.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_month = FALSE;

	/**
	 * Signifies whether the current query is for a day archive.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_day = FALSE;

	/**
	 * Signifies whether the current query is for a specific time.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_time = FALSE;

	/**
	 * Signifies whether the current query is for an author archive.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_author = FALSE;

	/**
	 * Signifies whether the current query is for a category archive.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_category = FALSE;

	/**
	 * Signifies whether the current query is for a tag archive.
	 *
	 * @since 2.3.0
	 *
	 * @var bool
	 */
	public $is_tag = FALSE;

	/**
	 * Signifies whether the current query is for a taxonomy archive.
	 *
	 * @since 2.5.0
	 *
	 * @var bool
	 */
	public $is_tax = FALSE;

	/**
	 * Signifies whether the current query is for a search.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_search = FALSE;

	/**
	 * Signifies whether the current query is for a feed.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_feed = FALSE;

	/**
	 * Signifies whether the current query is for a comment feed.
	 *
	 * @since 2.2.0
	 *
	 * @var bool
	 */
	public $is_comment_feed = FALSE;

	/**
	 * Signifies whether the current query is for trackback endpoint call.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_trackback = FALSE;

	/**
	 * Signifies whether the current query is for the site homepage.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_home = FALSE;

	/**
	 * Signifies whether the current query couldn't find anything.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_404 = FALSE;

	/**
	 * Signifies whether the current query is for an embed.
	 *
	 * @since 4.4.0
	 *
	 * @var bool
	 */
	public $is_embed = FALSE;

	/**
	 * Signifies whether the current query is for a paged result and not for the first page.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_paged = FALSE;

	/**
	 * Signifies whether the current query is for an administrative interface page.
	 *
	 * @since 1.5.0
	 *
	 * @var bool
	 */
	public $is_admin = FALSE;

	/**
	 * Signifies whether the current query is for an attachment page.
	 *
	 * @since 2.0.0
	 *
	 * @var bool
	 */
	public $is_attachment = FALSE;

	/**
	 * Signifies whether the current query is for an existing single post of any post type (post, attachment, page, custom post types).
	 *
	 * @since 2.1.0
	 *
	 * @var bool
	 */
	public $is_singular = FALSE;

	/**
	 * Signifies whether the current query is for the robots.txt file.
	 *
	 * @since 2.1.0
	 *
	 * @var bool
	 */
	public $is_robots = FALSE;

	/**
	 * Signifies whether the current query is for the page_for_posts page.
	 *
	 * Basically, the homepage if the option isn't set for the static homepage.
	 *
	 * @since 2.1.0
	 *
	 * @var bool
	 */
	public $is_posts_page = FALSE;

	/**
	 * Signifies whether the current query is for a post type archive.
	 *
	 * @since 3.1.0
	 *
	 * @var bool
	 */
	public $is_post_type_archive = FALSE;

	/**
	 * Stores the ->query_vars state like md5( serialize( $this->query_vars ) ) so we know whether we have to re-parse because something has changed.
	 *
	 * @since 3.1.0
	 *
	 * @var bool|string
	 */
	private $query_vars_hash = FALSE;

	/**
	 * Whether query vars have changed since the initial parse_query() call.
	 * Used to catch modifications to query vars made via pre_get_posts hooks.
	 *
	 * @since 3.1.1
	 */
	private $query_vars_changed = TRUE;

	/**
	 * Set if post thumbnails are cached.
	 *
	 * @since 3.2.0
	 *
	 * @var bool
	 */
	public $thumbnails_cached = FALSE;

	/**
	 * Cached list of search stopwords.
	 *
	 * @since 3.7.0
	 *
	 * @var array
	 */
	private $stopwords;

	private $compat_fields = array( 'query_vars_hash', 'query_vars_changed' );
	private $compat_methods = array( 'init_query_flags', 'parse_tax_query' );

	/**
	 * Resets query flags to false.
	 *
	 * The query flags are what page info WordPress was able to figure out.
	 *
	 * @since 2.0.0
	 */
	private function init_query_flags()
	{
		$this->is_single = FALSE;
		$this->is_preview = FALSE;
		$this->is_page = FALSE;
		$this->is_archive = FALSE;
		$this->is_date = FALSE;
		$this->is_year = FALSE;
		$this->is_month = FALSE;
		$this->is_day = FALSE;
		$this->is_time = FALSE;
		$this->is_author = FALSE;
		$this->is_category = FALSE;
		$this->is_tag = FALSE;
		$this->is_tax = FALSE;
		$this->is_search = FALSE;
		$this->is_feed = FALSE;
		$this->is_comment_feed = FALSE;
		$this->is_trackback = FALSE;
		$this->is_home = FALSE;
		$this->is_404 = FALSE;
		$this->is_paged = FALSE;
		$this->is_admin = FALSE;
		$this->is_attachment = FALSE;
		$this->is_singular = FALSE;
		$this->is_robots = FALSE;
		$this->is_posts_page = FALSE;
		$this->is_post_type_archive = FALSE;
	}

	/**
	 * Initiates object properties and sets default values.
	 *
	 * @since 1.5.0
	 */
	public function init()
	{
		unset( $this->posts );
		unset( $this->query );
		$this->query_vars = array();
		unset( $this->queried_object );
		unset( $this->queried_object_id );
		$this->post_count = 0;
		$this->current_post = -1;
		$this->in_the_loop = FALSE;
		unset( $this->request );
		unset( $this->post );
		unset( $this->comments );
		unset( $this->comment );
		$this->comment_count = 0;
		$this->current_comment = -1;
		$this->found_posts = 0;
		$this->max_num_pages = 0;
		$this->max_num_comment_pages = 0;
		$this->init_query_flags();
	}

/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * @NOW 009: wp-includes/class-wp-query.php
 */

	/**
	 * Sets up the WordPress query by parsing query string.
	 *
	 * @since 1.5.0
	 *
	 * @param  string|array $query URL query string or array of query arguments.
	 * @return array        List of posts.
	 */
	public function query( $query )
	{
		$this->init();
		$this->query = $this->query_vars = wp_parse_args( $query );
		return $this->get_posts();
	}

	/**
	 * Constructor.
	 *
	 * Sets up the WordPress query, if parameter is not empty.
	 *
	 * @since 1.5.0
	 *
	 * @param string|array $query URL query string or array of vars.
	 */
	public function __construct( $query = '' )
	{
		if ( ! empty( $query ) ) {
			$this->query( $query );
		}
	}
}
