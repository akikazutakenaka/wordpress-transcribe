<?php
/**
 * Rewrite API: WP_Rewrite class
 *
 * @package    WordPress
 * @subpackage Rewrite
 * @since      1.5.0
 */

/**
 * Core class used to implement a rewrite component API.
 *
 * The WordPress Rewrite class writes the rewrite module rules to the .htaccess file.
 * It also handles parsing the request to get the correct setup for the WordPress Query class.
 *
 * The Rewrite along with WP class function as a front controller for WordPress.
 * You can add rules to trigger your page view and processing using this component.
 * The full functionality of a front controller does not exist, meaning you can't define how the template files load based on the rewrite rules.
 *
 * @since 1.5.0
 */
class WP_Rewrite
{
	/**
	 * Permalink structure for posts.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $permalink_structure;

	/**
	 * Whether to add trailing slashes.
	 *
	 * @since 2.2.0
	 *
	 * @var bool
	 */
	public $use_trailing_slashes;

	/**
	 * Base for the author permalink structure (example.com/$author_base/authorname).
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	var $author_base = 'author';

	/**
	 * Permalink structure for author archives.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	var $author_structure;

	/**
	 * Permalink structure for date archives.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	var $date_structure;

	/**
	 * Permalink structure for pages.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	var $page_structure;

	/**
	 * Base of the search permalink structure (example.com/$search_base/query).
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	var $search_base = 'search';

	/**
	 * Permalink structure for searches.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	var $search_structure;

	/**
	 * Comments permalink base.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	var $comments_base = 'comments';

	/**
	 * Pagination permalink base.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	public $pagination_base = 'page';

	/**
	 * Comments pagination permalink base.
	 *
	 * @since 4.2.0
	 *
	 * @var string
	 */
	var $comments_pagination_base = 'comment-page';

	/**
	 * Feed permalink base.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	var $feed_base = 'feed';

	/**
	 * Comments feed permalink structure.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	var $comment_feed_structure;

	/**
	 * Feed request permalink structure.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	var $feed_structure;

	/**
	 * The static portion of the post permalink structure.
	 *
	 * If the permalink structure is "/archive/%post_id%" then the front is "/archive/".
	 * If the permalink structure is "/%year%/%postname%/" then the front is "/".
	 *
	 * @since 1.5.0
	 * @see   WP_Rewrite::init()
	 *
	 * @var string
	 */
	public $front;

	/**
	 * The prefix for all permalink structures.
	 *
	 * If PATHINFO/index permalinks are in use then the root is the value of `WP_Rewrite::$index` with a trailing slash appended.
	 * Otherwise the root will be empty.
	 *
	 * @since 1.5.0
	 * @see   WP_Rewrite::init()
	 * @see   WP_Rewrite::using_index_permalinks()
	 *
	 * @var string
	 */
	public $root = '';

	/**
	 * The name of the index file which is the entry point to all requests.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $index = 'index.php';

	/**
	 * Variable name to use for regex matches in the rewritten query.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	var $matches = '';

	/**
	 * Rewrite rules to match against the request to find the redirect or query.
	 *
	 * @since 1.5.0
	 *
	 * @var array
	 */
	var $rules;

	/**
	 * Additional rules added external to the rewrite class.
	 *
	 * Those not generated by the class, see add_rewrite_rule().
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	var $extra_rules = array();

	/**
	 * Additional rules that belong at the beginning to match first.
	 *
	 * Those not generated by the class, see add_rewrite_rule().
	 *
	 * @since 2.3.0
	 *
	 * @var array
	 */
	var $extra_rules_top = array();

	/**
	 * Rules that don't redirect to WordPress' index.php.
	 *
	 * These rules are written to the mod_rewrite portion of the .htaccess, and are added by add_external_rule().
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	var $non_wp_rules = array();

	/**
	 * Extra permalink structures, e.g. categories, added by add_permastruct().
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	var $extra_permastructs = array();

	/**
	 * Endpoints (like /trackback/) added by add_rewrite_endpoint().
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	var $endpoints;

	/**
	 * Whether to write every mod_rewrite rule for WordPress into the .htaccess file.
	 *
	 * This is off by default, turning it on might print a lot of rewrite rules to the .htaccess file.
	 *
	 * @since 2.0.0
	 * @see   WP_Rewrite::mod_rewrite_rules()
	 *
	 * @var bool
	 */
	public $use_verbose_rules = FALSE;

	/**
	 * Could post permalinks be confused with those of pages?
	 *
	 * If the first rewrite tag in the post permalink structure is one that could also match a page name (e.g. %postname% or %author%) then this flag is set to true.
	 * Prior to WordPress 3.3 this flag indicated that every page would have a set of rules added to the top of the rewrite rules array.
	 * Now it tells WP::parse_request() to check if a URL matching the page permastruct is actually a page before accepting it.
	 *
	 * @since 2.5.0
	 * @see   WP_Rewrite::init()
	 *
	 * @var bool
	 */
	public $use_verbose_page_rules = TRUE;

	/**
	 * Rewrite tags that can be used in permalink structures.
	 *
	 * These are translated into the regular expressions stored in `WP_Rewrite::$rewritereplace` and are rewritten to the query variables listed in WP_Rewrite::$queryreplace.
	 *
	 * Additional tags can be added with add_rewrite_tag().
	 *
	 * @since 1.5.0
	 *
	 * @var array
	 */
	var $rewritecode = array( '%year%', '%monthnum%', '%day%', '%hour%', '%minute%', '%second%', '%postname%', '%post_id%', '%author%', '%pagename%', '%search%' );

	/**
	 * Regular expressions to be substituted into rewrite rules in place of rewrite tags, see WP_Rewrite::$rewritecode.
	 *
	 * @since 1.5.0
	 *
	 * @var array
	 */
	var $rewritereplace = array( '([0-9]{4})', '([0-9]{1,2})', '([0-9]{1,2})', '([0-9]{1,2})', '([0-9]{1,2})', '([0-9]{1,2})', '([^/]+)', '([0-9]+)', '([^/]+)', '([^/]+?)', '(.+)' );

	/**
	 * Query variables that rewrite tags map to, see WP_Rewrite::$rewritecode.
	 *
	 * @since 1.5.0
	 *
	 * @var array
	 */
	var $queryreplace = array( 'year=', 'monthnum=', 'day=', 'hour=', 'minute=', 'second=', 'name=', 'p=', 'author_name=', 'pagename=', 's=' );

	/**
	 * Supported default feeds.
	 *
	 * @since 1.5.0
	 *
	 * @var array
	 */
	public $feeds = array( 'feed', 'rdf', 'rss', 'rss2', 'atom' );

	/**
	 * Determines whether permalinks are being used.
	 *
	 * This can be either rewrite module or permalink in the HTTP query string.
	 *
	 * @since 1.5.0
	 *
	 * @return bool True, if permalinks are enabled.
	 */
	public function using_permalinks()
	{
		return ! empty( $this->permalink_structure );
	}

	/**
	 * Retrieves the feed permalink structure.
	 *
	 * The permalink structure is root property, feed base, and finally '/%feed%'.
	 * Will set the feed_structure property and then return it without attempting to set the value again.
	 *
	 * @since 1.5.0
	 *
	 * @return string|false False if not found.
	 *                      Permalink structure string.
	 */
	public function get_feed_permastruct()
	{
		if ( isset( $this->feed_structure ) ) {
			return $this->feed_structure;
		}

		if ( empty( $this->permalink_structure ) ) {
			$this->feed_structure = '';
			return FALSE;
		}

		$this->feed_structure = $this->root . $this->feed_base . '/%feed%';
		return $this->feed_structure;
	}

	/**
	 * Retrieves the comment feed permalink structure.
	 *
	 * The permalink structure is root property, comment base property, feed base and finally '/%feed%'.
	 * Will set the comment_feed_structure property and then return it without attempting to set the value again.
	 *
	 * @since 1.5.0
	 *
	 * @return string|false False if not found.
	 *                      Permalink structure string.
	 */
	public function get_comment_feed_permastruct()
	{
		if ( isset( $this->comment_feed_structure ) ) {
			return $this->comment_feed_structure;
		}

		if ( empty( $this->permalink_structure ) ) {
			$this->comment_feed_structure = '';
			return FALSE;
		}

		$this->comment_feed_structure = $this->root . $this->comments_base . '/' . $this->feed_base . '/%feed%';
		return $this->comment_feed_structure;
	}

	/**
	 * Sets up the object's properties.
	 *
	 * The 'use_verbose_page_rules' object property will be set to true if the permalink structure begins with one of the following: '%postname%', '%category%', '%tag%', or '%author%'.
	 *
	 * @since 1.5.0
	 */
	public function init()
	{
		$this->extra_rules = $this->non_wp_rules = $this->endpoints = array();
		$this->permalink_structure = get_option( 'permalink_structure' );
		$this->front = substr( $this->permalink_structure, 0, strpos( $this->permalink_structure, '%' ) );
		$this->root = '';

		if ( $this->using_index_permalinks() ) {
			$this->root = $this->index . '/';
		}

		unset( $this->author_structure );
		unset( $this->date_structure );
		unset( $this->page_structure );
		unset( $this->search_structure );
		unset( $this->feed_structure );
		unset( $this->comment_feed_structure );
		$this->use_trailing_slashes = '/' == substr( $this->permalink_structure, -1, 1 );

		// Enable generic rules for pages if permalink structure doesn't begin with a wildcard.
		$this->use_verbose_page_rules = preg_match( "/^[^%]*%(?:postname|category|tag|author)%/", $this->permalink_structure );
	}

	/**
	 * Constructor - Calls init(), which runs setup.
	 *
	 * @since 1.5.0
	 */
	public function __construct()
	{
		$this->init();
	}
}
