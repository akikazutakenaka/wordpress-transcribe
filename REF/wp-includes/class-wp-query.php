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
	 * Fills in the query variables, which do not exist within the parameter.
	 *
	 * @since 2.1.0
	 * @since 4.4.0 Removed the `comments_popup` public query variable.
	 *
	 * @param  array $array Defined query variables.
	 * @return array Complete query variables with undefined ones filled in empty.
	 */
	public function fill_query_vars( $array )
	{
		$keys = array( 'error', 'm', 'p', 'post_parent', 'subpost', 'subpost_id', 'attachment', 'attachment_id', 'name', 'static', 'pagename', 'page_id', 'second', 'minute', 'hour', 'day', 'monthnum', 'year', 'w', 'category_name', 'tag', 'cat', 'tag_id', 'author', 'author_name', 'feed', 'tb', 'paged', 'meta_key', 'meta_value', 'preview', 's', 'sentence', 'title', 'fields', 'menu_order', 'embed' );

		foreach ( $keys as $key ) {
			if ( ! isset( $array[ $key ] ) ) {
				$array[ $key ] = '';
			}
		}

		$array_keys = array( 'category__in', 'category__not_in', 'category__and', 'post__in', 'post__not_in', 'post_name__in', 'tag__in', 'tag__not_in', 'tag__and', 'tag_slug__in', 'tag_slug__and', 'post_parent__in', 'post_parent__not_in', 'author__in', 'author__not_in' );

		foreach ( $array_keys as $key ) {
			if ( ! isset( $array[ $key ] ) ) {
				$array[ $key ] = array();
			}
		}

		return $array;
	}

	/**
	 * Parse a query string and set query type booleans.
	 *
	 * @since 1.5.0
	 * @since 4.2.0 Introduced the ability to order by specific clauses of a `$meta_query`, by passing the clause's array key to `$orderby`.
	 * @since 4.4.0 Introduced `$post_name__in` and `$title` parameters.
	 *              `$s` was updated to support excluded search terms, by prepending a hyphen.
	 * @since 4.5.0 Removed the `$comments_popup` parameter.
	 *              Introduced the `$comment_status` and `$ping_status` parameters.
	 *              Introduced `RAND(x)` syntax for `$orderby`, which allows an integer seed value to random sorts.
	 * @since 4.6.0 Added 'post_name__in' support for `$orderby`.
	 *              Introduced the `$lazy_load_term_meta` argument.
	 * @since 4.9.0 Introduced the `$comment_count` parameter.
	 *
	 * @param string|array $query {
	 *     Optional.
	 *     Array or string of Query parameters.
	 *
	 *     @type int          $attachment_id          Attachment post ID.
	 *                                                Used for 'attachment' post_type.
	 *     @type int|string   $author                 Author ID, or comma-separated list of IDs.
	 *     @type string       $author_name            User 'user_nicename'.
	 *     @type array        $author__in             An array of author IDs to query from.
	 *     @type array        $author__not_in         An array of author IDs not to query from.
	 *     @type bool         $cache_results          Whether to cache post information.
	 *                                                Default true.
	 *     @type int|string   $cat                    Category ID or comma-separated list of IDs (this or any children).
	 *     @type array        $category__and          An array of category IDs (AND in).
	 *     @type array        $category__in           An array of category IDs (OR in, no children).
	 *     @type array        $category__not_in       An array of category IDs (NOT in).
	 *     @type string       $category_name          Use category slug (not name, this or any children).
	 *     @type array|int    $comment_count          Filter results by comment count.
	 *                                                Provide an integer to match comment count exactly.
	 *                                                Provide an array with integer 'value' and 'compare' operator ('=', '!=', '>', '>=', '<', '<=') to compare against comment_count in a specific way.
	 *     @type string       $comment_status         Comment status.
	 *     @type int          $comments_per_page      The number of comments to return per page.
	 *                                                Default 'comments_per_page' option.
	 *     @type array        $date_query             An associative array of WP_Date_Query arguments.
	 *                                                See WP_Date_Query::__construct().
	 *     @type int          $day                    Day of the month.
	 *                                                Default empty.
	 *                                                Accepts numbers 1-31.
	 *     @type bool         $exact                  Whether to search by exact keyword.
	 *                                                Default false.
	 *     @type string|array $fields                 Which fields to return.
	 *                                                Single field or all fields (string), or array of fields.
	 *                                                'id=>parent' uses 'id' and 'post_parent'.
	 *                                                Default all fields.
	 *                                                Accepts 'ids', 'id=>parent'.
	 *     @type int          $hour                   Hour of the day.
	 *                                                Default empty.
	 *                                                Accepts numbers 0-23.
	 *     @type int|bool     $ignore_sticky_posts    Whether to ignore sticky posts or not.
	 *                                                Setting this to false excludes stickies from 'post__in'.
	 *                                                Accepts 1|true, 0|false.
	 *                                                Default 0|false.
	 *     @type int          $m                      Combination YearMonth.
	 *                                                Accepts any four-digit year and month numbers 1-12.
	 *                                                Default empty.
	 *     @type string       $meta_compare           Comparison operator to test the 'meta_value'.
	 *     @type string       $meta_key               Custom field key.
	 *     @type array        $meta_query             An associative array of WP_Meta_Query arguments.
	 *                                                See WP_Meta_Query.
	 *     @type string       $meta_value             Custom field value.
	 *     @type int          $meta_value_num         Custom field value number.
	 *     @type int          $menu_order             The menu order of the posts.
	 *     @type int          $monthnum               The two-digit month.
	 *                                                Default empty.
	 *                                                Accepts numbers 1-12.
	 *     @type string       $name                   Post slug.
	 *     @type bool         $nopaging               Show all posts (true) or paginate (false).
	 *                                                Default false.
	 *     @type bool         $no_found_rows          Whether to skip counting the total rows found.
	 *                                                Enabling can improve performance.
	 *                                                Default false.
	 *     @type int          $offset                 The number of posts to offset before retrieval.
	 *     @type string       $order                  Designates ascending or deescending order of posts.
	 *                                                Default 'DESC'.
	 *                                                Accepts 'ASC', 'DESC'.
	 *     @type string|array $orderby                Sort retrieved posts by parameter.
	 *                                                One or more options may be passed.
	 *                                                To use 'meta_value', or 'meta_value_num', 'meta_key=keyname' must be also defined.
	 *                                                To sort by a specific `$meta_query` clause, use that clause's array key.
	 *                                                Accepts 'none', 'name', 'author', 'date', 'title', 'modified', 'menu_order', 'parent', 'ID', 'rand', 'relevance', 'RAND(x)' (where 'x' is an integer seed value), 'comment_count', 'meta_value', 'meta_value_num', 'post__in', 'post_name__in', 'post_parent__in', and the array keys of `$meta_query`.
	 *                                                Default is 'date', except when a search is being performed, when the default is 'relevance'.
	 *     @type int          $p                      Post ID.
	 *     @type int          $page                   Show the number of posts that would show up on page X of a static front page.
	 *     @type int          $paged                  The number of the current page.
	 *     @type int          $page_id                Page ID.
	 *     @type string       $pagename               Page slug.
	 *     @type string       $perm                   Show posts if user has the appropriate capability.
	 *     @type string       $ping_status            Ping status.
	 *     @type array        $post__in               An array of post IDs to retrieve, sticky posts will be included.
	 *     @type string       $post_mime_type         The mime type of the post.
	 *                                                Used for 'attachment' post_type.
	 *     @type array        $post__not_in           An array of post IDs not to retrieve.
	 *                                                Note: a string of comma-separated IDs will NOT work.
	 *     @type int          $post_parent            Page ID to retrieve child pages for.
	 *                                                Use 0 to only retrieve top-level pages.
	 *     @type array        $post_parent__in        An array containing parent page IDs to query child pages from.
	 *     @type array        $post_parent__not_in    An array containing parent page IDs not to query child pages from.
	 *     @type string|array $post_type              A post type slug (string) or array of post type slugs.
	 *                                                Default 'any' if using 'tax_query'.
	 *     @type string|array $post_status            A post status (string) or array of post statuses.
	 *     @type int          $posts_per_page         The number of posts to query for.
	 *                                                Use -1 to request all posts.
	 *     @type int          $posts_per_archive_page The number of posts to query for by archive page.
	 *                                                Overrides 'posts_per_page' when is_archive(), or is_search() are true.
	 *     @type array        $post_name__in          An array of post slugs that results must match.
	 *     @type string       $s                      Search keyword(s).
	 *                                                Prepending a term with a hyphen will exclude posts matching that term.
	 *                                                E.g., 'pillow -sofa' will return posts containing 'pillow' but not 'sofa'.
	 *                                                The character used for exclusion can be modified using the 'wp_query_search_exclusion_prefix' filter.
	 *     @type int          $second                 Second of the minute.
	 *                                                Default empty.
	 *                                                Accepts numbers 0-60.
	 *     @type bool         $sentence               Whether to search by phrase.
	 *                                                Default false.
	 *     @type bool         $suppress_filters       Whether to suppress filters.
	 *                                                Default false.
	 *     @type string       $tag                    Tag slug.
	 *                                                Comma-separated (either), Plus-separated (all).
	 *     @type array        $tag__and               An array of tag ids (AND in).
	 *     @type array        $tag__in                An array of tag ids (OR in).
	 *     @type array        $tag__not_in            An array of tag ids (NOT in).
	 *     @type int          $tag_id                 Tag id or comma-separated list of IDs.
	 *     @type array        $tag_slug__and          An array of tag slugs (AND in).
	 *     @type array        $tag_slug__in           An array of tag slugs (OR in), unless 'ignore_sticky_posts' is true.
	 *                                                Note: a string comma-separated IDs will NOT work.
	 *     @type array        $tax_query              An associative array of WP_Tax_Query arguments.
	 *                                                See WP_Tax_Query->queries.
	 *     @type string       $title                  Post title.
	 *     @type bool         $update_post_meta_cache Whether to update the post meta cache.
	 *                                                Default true.
	 *     @type bool         $update_post_term_cache Whether to update the post term cache.
	 *                                                Default true.
	 *     @type bool         $lazy_load_term_meta    Whether to lazy-load term meta.
	 *                                                Setting to false will disable cache priming for term meta, so that each get_term_meta() call will hit the database.
	 *                                                Defaults to the value of `$update_post_term_cache`.
	 *     @type int          $w                      The week number of the year.
	 *                                                Default empty.
	 *                                                Accepts number 0-53.
	 *     @type int          $year                   The four-digit year.
	 *                                                Default empty.
	 *                                                Accepts any four-digit year.
	 * }
	 */
	public function parse_query( $query = '' )
	{
		if ( ! empty( $query ) ) {
			$this->init();
			$this->query = $this->query_vars = wp_parse_args( $query );
		} elseif ( ! isset( $this->query ) ) {
			$this->query = $this->query_vars;
		}

		$this->query_vars = $this->fill_query_vars( $this->query_vars );
		$qv = &$this->query_vars;
		$this->query_vars_changed = TRUE;

		if ( ! empty( $qv['robots'] ) ) {
			$this->is_robots = TRUE;
		}

		if ( ! is_scalar( $qv['p'] ) || $qv['p'] < 0 ) {
			$qv['p'] = 0;
			$qv['error'] = 404;
		} else {
			$qv['p'] = intval( $qv['p'] );
		}

		$qv['page_id'] = absint( $qv['page_id'] );
		$qv['year'] = absint( $qv['year'] );
		$qv['monthnum'] = absint( $qv['monthnum'] );
		$qv['day'] = absint( $qv['day'] );
		$qv['w'] = absint( $qv['w'] );

		$qv['m'] = is_scalar( $qv['m'] )
			? preg_replace( '|[^0-9]|', '', $qv['m'] )
			: '';

		$qv['paged'] = absint( $qv['paged'] );
		$qv['cat'] = preg_replace( '|[^0-9,-]|', '', $qv['cat'] ); // Comma separated list of positive or negative integers
		$qv['author'] = preg_replace( '|[^0-9,-]|', '', $qv['author'] ); // Comma separated list of positive or negative integers
		$qv['pagename'] = trim( $qv['pagename'] );
		$qv['name'] = trim( $qv['name'] );
		$qv['title'] = trim( $qv['title'] );

		if ( '' !== $qv['hour'] ) {
			$qv['hour'] = absint( $qv['hour'] );
		}

		if ( '' !== $qv['minute'] ) {
			$qv['minute'] = absint( $qv['minute'] );
		}

		if ( '' !== $qv['second'] ) {
			$qv['second'] = absint( $qv['second'] );
		}

		if ( '' !== $qv['menu_order'] ) {
			$qv['menu_order'] = absint( $qv['menu_order'] );
		}

		// Fairly insane upper bound for search string lengths.
		if ( ! is_scalar( $qv['s'] )
		  || ! empty( $qv['s'] ) && strlen( $qv['s'] ) > 1600 ) {
			$qv['s'] = '';
		}

		/**
		 * Compat.
		 * Map subpost to attachment.
		 */
		if ( '' != $qv['subpost'] ) {
			$qv['attachment'] = $qv['subpost'];
		}

		if ( '' != $qv['subpost_id'] ) {
			$qv['attachment_id'] = $qv['subpost_id'];
		}

		$qv['attachment_id'] = absint( $qv['attachment_id'] );

		if ( '' != $qv['attachment'] || ! empty( $qv['attachment_id'] ) ) {
			$this->is_single = TRUE;
			$this->is_attachment = TRUE;
		} elseif ( '' != $qv['name'] ) {
			$this->is_single = TRUE;
		} elseif ( $qv['p'] ) {
			$this->is_single = TRUE;
		} elseif ( '' !== $qv['hour'] && '' !== $qv['minute'] && '' !== $qv['second'] && '' != $qv['year'] && '' != $qv['monthnum'] && '' != $qv['day'] ) {
			// If year, month, day, hour, minute, and second are set, a single post is being queried.
			$this->is_single = TRUE;
		} elseif ( '' != $qv['static'] || '' != $qv['pagename'] || ! empty( $qv['page_id'] ) ) {
			$this->is_page = TRUE;
			$this->is_single = FALSE;
		} else {
			/**
			 * Look for archive queries.
			 * Dates, categories, authors, search, post type archives.
			 */
			if ( isset( $this->query['s'] ) ) {
				$this->is_search = TRUE;
			}

			if ( '' !== $qv['second'] ) {
				$this->is_time = TRUE;
				$this->is_date = TRUE;
			}

			if ( '' !== $qv['minute'] ) {
				$this->is_time = TRUE;
				$this->is_date = TRUE;
			}

			if ( '' !== $qv['hour'] ) {
				$this->is_time = TRUE;
				$this->is_date = TRUE;
			}

			if ( $qv['day'] ) {
				if ( ! $this->is_date ) {
					$date = sprintf( '%04d-%02d-%02d', $qv['year'], $qv['monthnum'], $qv['day'] );

					if ( $qv['monthnum'] && $qv['year'] && ! wp_checkdate( $qv['monthnum'], $qv['day'], $qv['year'], $date ) ) {
						$qv['error'] = '404';
					} else {
						$this->is_day = TRUE;
						$this->is_date = TRUE;
					}
				}
			}

			if ( $qv['monthnum'] ) {
				if ( ! $this->is_date ) {
					if ( 12 < $qv['monthnum'] ) {
						$qv['error'] = '404';
					} else {
						$this->is_month = TRUE;
						$this->is_date = TRUE;
					}
				}
			}

			if ( $qv['year'] ) {
				if ( ! $this->is_date ) {
					$this->is_year = TRUE;
					$this->is_date = TRUE;
				}
			}

			if ( $qv['m'] ) {
				$this->is_date = TRUE;

				if ( strlen( $qv['m'] ) > 9 ) {
					$this->is_time = TRUE;
				} elseif ( strlen( $qv['m'] ) > 7 ) {
					$this->is_day = TRUE;
				} elseif ( strlen( $qv['m'] ) > 5 ) {
					$this->is_month = TRUE;
				} else {
					$this->is_year = TRUE;
				}
			}

			if ( '' != $qv['w'] ) {
				$this->is_date = TRUE;
			}

			$this->query_vars_hash = FALSE;
			$this->parse_tax_query( $qv );
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
 * @NOW 010: wp-includes/class-wp-query.php
 */
		}
	}

	/**
	 * Parses various taxonomy related query vars.
	 *
	 * For BC, this method is not marked as protected.
	 * See [28987].
	 *
	 * @since 3.1.0
	 *
	 * @param array $q The query variables.
	 *                 Passed by reference.
	 */
	public function parse_tax_query( &$q )
	{
		$tax_query = ! empty( $q['tax_query'] ) && is_array( $q['tax_query'] )
			? $q['tax_query']
			: array();

		if ( ! empty( $q['taxonomy'] ) && ! empty( $q['term'] ) ) {
			$tax_query[] = array(
				'taxonomy' => $q['taxonomy'],
				'terms'    => array( $q['term'] ),
				'field'    => 'slug'
			);
		}

		foreach ( get_taxonomies( array(), 'objects' ) as $taxonomy => $t ) {
			if ( 'post_tag' == $taxonomy ) {
				continue; // Handled further down in the $q['tag'] block.
			}

			if ( $t->query_var && ! empty( $q[ $t->query_var ] ) ) {
				$tax_query_defaults = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug'
				);

				if ( isset( $t->rewrite['hierarchical'] ) && $t->rewrite['hierarchical'] ) {
					$q[ $t->query_var ] = wp_basename( $q[ $t->query_var ] );
				}

				$term = $q[ $t->query_var ];

				if ( is_array( $term ) ) {
					$term = implode( ',', $term );
				}

				if ( strpos( $term, '+' ) !== FALSE ) {
					$terms = preg_split( '/[+]+/', $term );

					foreach ( $terms as $term ) {
						tax_query[] = array_merge( $tax_query_defaults, array( 'terms' => array( $term ) ) );
					}
				} else {
					$tax_query[] = array_merge( $tax_query_defaults, array( 'terms' => preg_split( '/[,]+/', $term ) ) );
				}
			}
		}

		// If querystring 'cat' is an array, implode it.
		if ( is_array( $q['cat'] ) ) {
			$q['cat'] = implode( ',', $q['cat'] );
		}

		// Category stuff.
		if ( ! empty( $q['cat'] ) && ! $this->is_singular ) {
			$cat_in = $cat_not_in = array();
			$cat_array = preg_split( '/[,\s]+/', urldecode( $q['cat'] ) );
			$cat_array = array_map( 'intval', $cat_array );
			$q['cat'] = implode( ',', $cat_array );

			foreach ( $cat_array as $cat ) {
				if ( $cat > 0 ) {
					$cat_in[] = $cat;
				} elseif ( $cat < 0 ) {
					$cat_not_in[] = abs( $cat );
				}
			}

			if ( ! empty( $cat_in ) ) {
				$tax_query[] = array(
					'taxonomy'         => 'category',
					'terms'            => $cat_in,
					'field'            => 'term_id',
					'include_children' => TRUE
				);
			}

			if ( ! empty( $cat_not_in ) ) {
				$tax_query[] = array(
					'taxonomy'         => 'category',
					'terms'            => $cat_not_in,
					'field'            => 'term_id',
					'operator'         => 'NOT IN',
					'include_children' => TRUE
				);
			}

			unset( $cat_array, $cat_in, $cat_not_in );
		}

		if ( ! empty( $q['category__and'] ) && 1 === count( ( array ) $q['category__and'] ) ) {
			$q['category__and'] = ( array ) $q['category__and'];

			if ( ! isset( $q['category__in'] ) ) {
				$q['category__in'] = array();
			}

			$q['category__in'][] = absint( reset( $q['category__and'] ) );
			unset( $q['category__and'] );
		}

		if ( ! empty( $q['category__in'] ) ) {
			$q['category__in'] = array_map( 'absint', array_unique( ( array ) $q['category__in'] ) );
			$tax_query[] = array(
				'taxonomy'         => 'category',
				'terms'            => $q['category__in'],
				'field'            => 'term_id',
				'include_children' => FALSE
			);
		}

		if ( ! empty( $q['category__not_in'] ) ) {
			$q['category__not_in'] = array_map( 'absint', array_unique( ( array ) $q['category__not_in'] ) );
			$tax_query[] = array(
				'taxonomy'         => 'category',
				'terms'            => $q['category__not_in'],
				'operator'         => 'NOT IN',
				'include_children' => FALSE
			);
		}

		if ( ! empty( $q['category__and'] ) ) {
			$q['category__and'] = array_map( 'absint', array_unique( ( array ) $q['category__and'] ) );
			$tax_query[] = array(
				'taxonomy'         => 'category',
				'terms'            => $q['category__and'],
				'field'            => 'term_id',
				'operator'         => 'AND',
				'include_children' => FALSE
			);
		}

		// If querystring 'tag' is array, implode it.
		if ( is_array( $q['tag'] ) ) {
			$q['tag'] = implode( ',', $q['tag'] );
		}

		// Tag stuff.
		if ( '' != $q['tag'] && ! $this->is_singular && $this->query_vars_changed ) {
			if ( strpos( $q['tag'], ',' ) !== FALSE ) {
				$tags = preg_split( '/[,\r\n\t ]+/', $q['tag'] );

				foreach ( ( array ) $tags as $tag ) {
					$tag = sanitize_term_field( 'slug', $tag, 0, 'post_tag', 'db' );
					$q['tag_slug__in'][] = $tag;
				}
			} elseif ( preg_match( '/[+\r\n\t ]+/', $q['tag'] ) || ! empty( $q['cat'] ) ) {
				$tags = preg_split( '/[+\r\n\t ]+/', $q['tag'] );

				foreach ( ( array ) $tags as $tag ) {
					$tag = sanitize_term_field( 'slug', $tag, 0, 'post_tag', 'db' );
					$q['tag_slug__and'][] = $tag;
				}
			} else {
				$q['tag'] = sanitize_term_field( 'slug', $q['tag'], 0, 'post_tag', 'db' );
				$q['tag_slug__in'][] = $q['tag'];
			}
		}

		if ( ! empty( $q['tag_id'] ) ) {
			$q['tag_id'] = absint( $q['tag_id'] );
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'terms'    => $q['tag_id']
			);
		}

		if ( ! empty( $q['tag__in'] ) ) {
			$q['tag__in'] = array_map( 'absint', array_unique( ( array ) $q['tag__in'] ) );
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'terms'    => $q['tag__in']
			);
		}

		if ( ! empty( $q['tag__not_in'] ) ) {
			$q['tag__not_in'] = array_map( 'absint', array_unique( ( array ) $q['tag__not_in'] ) );
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'terms'    => $q['tag__not_in'],
				'operator' => 'NOT IN'
			);
		}

		if ( ! empty( $q['tag__and'] ) ) {
			$q['tag__and'] = array_map( 'absint', array_unique( ( array ) $q['tag__and'] ) );
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'terms'    => $q['tag__and'],
				'operator' => 'AND'
			);
		}

		if ( ! empty( $q['tag_slug__in'] ) ) {
			$q['tag_slug__in'] = array_map( 'sanitize_title_for_query', array_unique( ( array ) $q['tag_slug__in'] ) );
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'terms'    => $q['tag_slug__in'],
				'field'    => 'slug'
			);
		}

		if ( ! empty( $q['tag_slug__and'] ) ) {
			$q['tag_slug__and'] = array_map( 'sanitize_title_for_query', array_unique( ( array ) $q['tag_slug__and'] ) );
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'terms'    => $q['tag_slug__and'],
				'field'    => 'slug',
				'operator' => 'AND'
			);
		}

		$this->tax_query = new WP_Tax_Query( $tax_query );

		/**
		 * Fires after taxonomy-related query vars have been parsed.
		 *
		 * @since 3.7.0
		 *
		 * @param WP_Query $this The WP_Query instance.
		 */
		do_action( 'parse_tax_query', $this );
	}

	/**
	 * Retrieve the posts based on query variables.
	 *
	 * There are a few filters and actions that can be used to modify the post database query.
	 *
	 * @since 1.5.0
	 *
	 * @return array List of posts.
	 */
	public function get_posts()
	{
		global $wpdb;
		$this->parse_query();
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
 * -> wp-includes/class-wp-query.php
 */
	}

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
