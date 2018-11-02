<?php
/**
 * Taxonomy API: WP_Term_Query class.
 *
 * @package    WordPress
 * @subpackage Taxonomy
 * @since      4.6.0
 */

/**
 * Class used for querying terms.
 *
 * @since 4.6.0
 * @see   WP_Term_Query::__construct() for accepted arguments.
 */
class WP_Term_Query
{
	/**
	 * SQL string used to perform database query.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	public $request;

	/**
	 * Metadata query container.
	 *
	 * @since 4.6.0
	 *
	 * @var object WP_Meta_Query
	 */
	public $meta_query = FALSE;

	/**
	 * Metadata query clauses.
	 *
	 * @since 4.6.0
	 *
	 * @var array
	 */
	protected $meta_query_clauses;

	/**
	 * SQL query clauses.
	 *
	 * @since 4.6.0
	 *
	 * @var array
	 */
	protected $sql_clauses = array(
		'select'  => '',
		'from'    => '',
		'where'   => array(),
		'orderby' => '',
		'limits'  => ''
	);

	/**
	 * Query vars set by the user.
	 *
	 * @since 4.6.0
	 *
	 * @var array
	 */
	public $query_vars;

	/**
	 * Default values for query vars.
	 *
	 * @since 4.6.0
	 *
	 * @var array
	 */
	public $query_var_defaults;

	/**
	 * List of terms located by the query.
	 *
	 * @since 4.6.0
	 *
	 * @var array
	 */
	public $terms;

	/**
	 * Constructor.
	 *
	 * Sets up the term query, based on the query vars passed.
	 *
	 * @since 4.6.0
	 * @since 4.6.0 Introduced 'term_taxonomy_id' parameter.
	 * @since 4.7.0 Introduced 'object_ids' parameter.
	 * @since 4.9.0 Added 'slug__in' support for 'orderby'.
	 *
	 * @param string|array $query {
	 *     Optional.
	 *     Array or query string of term query parameters.
	 *     Default empty.
	 *
	 *     @type string|array $taxonomy               Taxonomy name, or array of taxonomies, to which results should be limited.
	 *     @type int|array    $object_ids             Optional.
	 *                                                Object ID, or array of object IDs.
	 *                                                Results will be limited to terms associated with these objects.
	 *     @type string       $orderby                Field(s) to order terms by.
	 *                                                Accepts term fields ('name', 'slug', 'term_group', 'term_id', 'id', 'description', 'parent'), 'count' for term taxonomy count, 'include' to match the 'order' of the $include param, 'slug__in' to match the 'order' of the $slug param, 'meta_value', 'meta_value_num', the value of `$meta_key`, the array keys of `$meta_query`, or 'none' to omit the ORDER BY clause.
	 *                                                Defaults to 'name'.
	 *     @type string       $order                  Whether to order terms in ascending or descending order.
	 *                                                Accepts 'ASC' (ascending) or 'DESC' (descending).
	 *                                                Default 'ASC'.
	 *     @type bool|int     $hide_empty             Whether to hide terms not assigned to any posts.
	 *                                                Accepts 1|true or 0|false.
	 *                                                Default 1|true.
	 *     @type array|string $include                Array or comma/space-separated string of term ids to include.
	 *                                                Default empty array.
	 *     @type array|string $exclude                Array or comma/space-separated string of term ids to exclude.
	 *                                                If $include is non-empty, $exclude is ignored.
	 *                                                Default empty array.
	 *     @type array|string $exclude_tree           Array or comma/space-separated string of term ids to exclude along with all of their descendant terms.
	 *                                                If $include is non-empty, $exclude_tree is ignored.
	 *                                                Default empty array.
	 *     @type int|string   $number                 Maximum number of terms to return.
	 *                                                Accepts ''|0 (all) or any positive number.
	 *                                                Default ''|0 (all).
	 *                                                Note that $number may not return accurate results when coupled with $object_ids.
	 *                                                See #41796 for details.
	 *     @type int          $offset                 The number by which to offset the terms query.
	 *                                                Default empty.
	 *     @type string       $fields                 Term fields to query for.
	 *                                                Accepts 'all' (returns an array of complete term objects), 'all_with_object_id' (returns an array of term objects with the 'object_id' param; only works when the `$fields` parameter is 'object_ids'), 'ids' (returns an array of ids), 'tt_ids' (returns an array of term taxonomy ids), 'id=>parent' (returns an associative array with ids as keys, parent term IDs as values), 'names' (returns an array of term names), 'count' (returns the number of matching terms), 'id=>name' (returns an associative array with ids as keys, term names as values), or 'id=>slug' (returns an associative array with ids as keys, term slugs as values).
	 *                                                Default 'all'.
	 *     @type bool         $count                  Whether to return a term count (true) or array of term objects (false).
	 *                                                Will take precedence over `$fields` if true.
	 *                                                Default false.
	 *     @type string|array $name                   Optional.
	 *                                                Name or array of names to return term(s) for.
	 *                                                Default empty.
	 *     @type string|array $slug                   Optional.
	 *                                                Slug or array of slugs to return term(s) for.
	 *                                                Default empty.
	 *     @type int|array    $term_taxonomy_id       Optional.
	 *                                                Term taxonomy ID, or array of term taxonomy IDs, to match when querying terms.
	 *     @type bool         $hierarchical           Whether to include terms that have non-empty descendants (even if $hide_empty is set to true).
	 *                                                Default true.
	 *     @type string       $search                 Search criteria to match terms.
	 *                                                Will be SQL-formatted with wildcards before and after.
	 *                                                Default empty.
	 *     @type string       $name__like             Retrieve terms with criteria by which a term is LIKE `$name__like`.
	 *                                                Default empty.
	 *     @type string       $description__like      Retrieve terms where the description is LIKE `$description__like`.
	 *                                                Default empty.
	 *     @type bool         $pad_counts             Whether to pad the quantity of a term's children in the quantity of each term's "count" object variable.
	 *                                                Default false.
	 *     @type string       $get                    Whether to return terms regardless of ancestry or whether the terms are empty.
	 *                                                Accepts 'all' or empty (disabled).
	 *                                                Default empty.
	 *     @type int          $child_of               Term ID to retrieve child terms of.
	 *                                                If multiple taxonomies are passed, $child_of is ignored.
	 *                                                Default 0.
	 *     @type int|string   $parent                 Parent term ID to retrieve direct-child terms of.
	 *                                                Default empty.
	 *     @type bool         $childless              True to limit results to terms that have no children.
	 *                                                This parameter has no effect on non-hierarchical taxonomies.
	 *                                                Default false.
	 *     @type string       $cache_domain           Unique cache key to be produced when this query is stored in an object cache.
	 *                                                Default is 'core'.
	 *     @type bool         $update_term_meta_cache Whether to prime meta caches for matched terms.
	 *                                                Default true.
	 *     @type array        $meta_query             Optional.
	 *                                                Meta query clauses to limit retrieved terms by.
	 *                                                See `WP_Meta_Query`.
	 *                                                Default empty.
	 *     @type string       $meta_key               Limit terms to those matching a specific metadata key.
	 *                                                Can be used in conjunction with `$meta_value`.
	 *                                                Default empty.
	 *     @type string       $meta_value             Limit terms to those matching a specific metadata value.
	 *                                                Usually used in conjunction with `$meta_key`.
	 *                                                Default empty.
	 *     @type string       $meta_type              Type of object metadata is for (e.g., comment, post, or user).
	 *                                                Default empty.
	 *     @type string       $meta_compare           Comparison operator to test the 'meta_value'.
	 *                                                Default empty.
	 * }
	 */
	public function __construct( $query = '' )
	{
		$this->query_var_defaults = array(
			'taxonomy'               => NULL,
			'object_ids'             => NULL,
			'orderby'                => 'name',
			'order'                  => 'ASC',
			'hide_empty'             => TRUE,
			'include'                => array(),
			'exclude'                => array(),
			'exclude_tree'           => array(),
			'number'                 => '',
			'offset'                 => '',
			'fields'                 => 'all',
			'count'                  => FALSE,
			'name'                   => '',
			'slug'                   => '',
			'term_taxonomy_id'       => '',
			'hierarchical'           => TRUE,
			'search'                 => '',
			'name__like'             => '',
			'description__like'      => '',
			'pad_counts'             => FALSE,
			'get'                    => '',
			'child_of'               => 0,
			'parent'                 => '',
			'childless'              => FALSE,
			'cache_domain'           => 'core',
			'update_term_meta_cache' => TRUE,
			'meta_query'             => '',
			'meta_key'               => '',
			'meta_value'             => '',
			'meta_type'              => '',
			'meta_compare'           => ''
		);

		if ( ! empty( $query ) ) {
			$this->query( $query );
		}
	}

	/**
	 * Parse arguments passed to the term query with default query parameters.
	 *
	 * @since 4.6.0
	 *
	 * @param string|array $query WP_Term_Query arguments.
	 *                            See WP_Term_Query::__construct().
	 */
	public function parse_query( $query = '' )
	{
		if ( empty( $query ) ) {
			$query = $this->query_vars;
		}

		$taxonomies = isset( $query['taxonomy'] )
			? ( array ) $query['taxonomy']
			: NULL;

		/**
		 * Filters the terms query default arguments.
		 *
		 * Use {@see 'get_terms_args'} to filter the passed arguments.
		 *
		 * @since 4.4.0
		 *
		 * @param array $defaults   An array of default get_terms() arguments.
		 * @param array $taxonomies An array of taxonomies.
		 */
		$this->query_var_defaults = apply_filters( 'get_terms_defaults', $this->query_var_defaults, $taxonomies );

		$query = wp_parse_args( $query, $this->query_var_defaults );
		$query['number'] = absint( $query['number'] );
		$query['offset'] = absint( $query['offset'] );

		// 'parent' overrides 'child_of'.
		if ( 0 < intval( $query['parent'] ) ) {
			$query['child_of'] = FALSE;
		}

		if ( 'all' == $query['get'] ) {
			$query['childless']    = FALSE;
			$query['child_of']     = 0;
			$query['hide_empty']   = 0;
			$query['hierarchical'] = FALSE;
			$query['pad_counts']   = FALSE;
		}

		$query['taxonomy'] = $taxonomies;
		$this->query_vars = $query;

		/**
		 * Fires after term query vars have been parsed.
		 *
		 * @since 4.6.0
		 *
		 * @param WP_Term_Query $this Current instance of WP_Term_Query.
		 */
		do_action( 'parse_term_query', $this );
	}

	/**
	 * Sets up the query for retrieving terms.
	 *
	 * @since 4.6.0
	 *
	 * @param  string|array $query Array or URL query string of parameters.
	 * @return array|int    List of terms, or number of terms when 'count' is passed as a query var.
	 */
	public function query( $query )
	{
		$this->query_vars = wp_parse_args( $query );
		return $this->get_terms();
	}

	/**
	 * Get terms, based on query_vars.
	 *
	 * @since  4.6.0
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return array List of terms.
	 */
	public function get_terms()
	{
		global $wpdb;
		$this->parse_query( $this->query_vars );
		$args = &$this->query_vars;

		// Set up meta_query so it's available to 'pre_get_terms'.
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars( $args );

		/**
		 * Fires before terms are retrieved.
		 *
		 * @since 4.6.0
		 *
		 * @param WP_Term_Query $this Current instance of WP_Term_Query.
		 */
		do_action( 'pre_get_terms', $this );

		$taxonomies = ( array ) $args['taxonomy'];

		// Save queries by not crawling the tree in the case of multiple taxes or a flat tax.
		$has_hierarchical_tax = FALSE;

		if ( $taxonomies ) {
			foreach ( $taxonomies as $_tax ) {
				if ( is_taxonomy_hierarchical( $_tax ) ) {
					$has_hierarchical_tax = TRUE;
				}
			}
		}

		if ( ! $has_hierarchical_tax ) {
			$args['hierarchical'] = FALSE;
			$args['pad_counts']   = FALSE;
		}

		// 'parent' overrides 'child_of'.
		if ( 0 < intval( $args['parent'] ) ) {
			$args['child_of'] = FALSE;
		}

		if ( 'all' == $args['get'] ) {
			$args['childless']    = FALSE;
			$args['child_of']     = 0;
			$args['hide_empty']   = 0;
			$args['hierarchical'] = FALSE;
			$args['pad_counts']   = FALSE;
		}

		/**
		 * Filters the terms query arguments.
		 *
		 * @since 3.1.0
		 *
		 * @param array $args       An array of get_terms() arguments.
		 * @param array $taxonomies An array of taxonomies.
		 */
		$args = apply_filters( 'get_terms_args', $args, $taxonomies );

		// Avoid the query if the queried parent/child_of term has no descendants.
		$child_of = $args['child_of'];
		$parent   = $args['parent'];

		$_parent = $child_of
			? $child_of
			: ( $parent
				? $parent
				: FALSE );

		if ( $_parent ) {
			$in_hierarchy = FALSE;

			foreach ( $taxonomies as $_tax ) {
				$hierarchy = _get_term_hierarchy( $_tax );

				if ( isset( $hierarchy[ $_parent ] ) ) {
					$in_hierarchy = TRUE;
				}
			}

			if ( ! $in_hierarchy ) {
				return array();
			}
		}

		// 'term_order' is a legal sort order only when joining the relationship table.
		$_orderby = $this->query_vars['orderby'];

		if ( 'term_order' === $_orderby && empty( $this->query_vars['object_ids'] ) ) {
			$_orderby = 'term_id';
		}

		$orderby = $this->parse_orderby( $_orderby );
// wp-includes/taxonomy.php -> @NOW 012 -> self
	}

	/**
	 * Parse and sanitize 'orderby' keys passed to the term query.
	 *
	 * @since  4.6.0
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param  string       $orderby_raw Alias for the field to order by.
	 * @return string|false Value to used in the ORDER clause.
	 *                      False otherwise.
	 */
	protected function parse_orderby( $orderby_raw )
	{
		$_orderby = strtolower( $orderby_raw );
		$maybe_orderby_meta = FALSE;

		if ( in_array( $_orderby, array( 'term_id', 'name', 'slug', 'term_group' ), TRUE ) ) {
			$orderby = "t.$_orderby";
		} elseif ( in_array( $_orderby, array( 'count', 'parent', 'taxonomy', 'term_taxonomy_id', 'description' ), TRUE ) ) {
			$orderby = "tt.$_orderby";
		} elseif ( 'term_orderby' === $_orderby ) {
			$orderby = 'tr.term_order';
		} elseif ( 'include' == $_orderby && ! empty( $this->query_vars['include'] ) ) {
			$include = implode( ',', wp_parse_id_list( $this->query_vars['include'] ) );
			$orderby = "FIELD( t.term_id, $include )";
		} elseif ( 'slug__in' == $_orderby && ! empty( $this->query_vars['slug'] ) && is_array( $this->query_vars['slug'] ) ) {
			$slugs = implode( "', '", array_map( 'sanitize_title_for_query', $this->query_vars['slug'] ) );
			$orderby = "FIELD( t.slug, '" . $slugs . "')";
		} elseif ( 'none' == $_orderby ) {
			$orderby = '';
		} elseif ( empty( $_orderby ) || 'id' == $_orderby || 'term_id' === $_orderby ) {
			$orderby = 't.term_id';
		} else {
			$orderby = 't.name';

			// This may be a value of orderby related to meta.
			$maybe_orderby_meta = TRUE;
		}

		/**
		 * Filters the ORDERBY clause of the terms query.
		 *
		 * @since 2.8.0
		 *
		 * @param string $orderby    `ORDERBY` clause of the terms query.
		 * @param array  $args       An array of terms query arguments.
		 * @param array  $taxonomies An array of taxonomies.
		 */
		$orderby = apply_filters( 'get_terms_orderby', $orderby, $this->query_vars, $this->query_vars['taxonomy'] );

		// Run after the 'get_terms_orderby' filter for backward compatibility.
		if ( $maybe_orderby_meta ) {
			$maybe_orderby_meta = $this->parse_orderby_meta( $_orderby );
// self -> @NOW 013 -> self
		}
	}

	/**
	 * Generate the ORDER BY clause for an 'orderby' param that is potentially related to a meta query.
	 *
	 * @since 4.6.0
	 *
	 * @param  string $orderby_raw Raw 'orderby' value passed to WP_Term_Query.
	 * @return string ORDER BY clause.
	 */
	protected function parse_orderby_meta( $orderby_raw )
	{
		$orderby = '';

		// Tell the meta query to generate its SQL, so we have access to table aliases.
		$this->meta_query->get_sql( 'term', 't', 'term_id' );
// self -> @NOW 014
	}
}
