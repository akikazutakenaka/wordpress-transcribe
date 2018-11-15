<?php
/**
 * Taxonomy API: WP_Tax_Query class
 *
 * @package    WordPress
 * @subpackage Taxonomy
 * @since      4.4.0
 */

/**
 * Core class used to implement taxonomy queries for the Taxonomy API.
 *
 * Used for generating SQL clauses that filter a primary query according to object taxonomy terms.
 *
 * WP_Tax_Query is a helper that allows primary query classes, such as WP_Query, to filter their results by object metadata, by generating `JOIN` and `WHERE` subclauses to be attached to the primary SQL query string.
 *
 * @since 3.1.0
 */
class WP_Tax_Query
{
	/**
	 * Array of taxonomy queries.
	 *
	 * See WP_Tax_Query::__construct() for information on tax query arguments.
	 *
	 * @since 3.1.0
	 *
	 * @var array
	 */
	public $queries = array();

	/**
	 * The relation between the queries.
	 * Can be one of 'AND' or 'OR'.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	public $relation;

	/**
	 * Standard response when the query should not return any rows.
	 *
	 * @since  3.2.0
	 * @static
	 *
	 * @var string
	 */
	private static $no_results = array(
		'join'  => array( '' ),
		'where' => array( '0 = 1' )
	);

	/**
	 * A flat list of table aliases used in the JOIN clauses.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	protected $table_aliases = array();

	/**
	 * Terms and taxonomies fetched by this query.
	 *
	 * We store this data in a flat array because they are referenced in a number of places by WP_Query.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	public $queried_terms = array();

	/**
	 * Database table that where the metadata's objects are stored (e.g. $wpdb->users).
	 *
	 * @since 4.1.0
	 *
	 * @var string
	 */
	public $primary_table;

	/**
	 * Column in 'primary_table' that represents the ID of the object.
	 *
	 * @since 4.1.0
	 *
	 * @var string
	 */
	public $primary_id_column;

	/**
	 * Constructor.
	 *
	 * @since 3.1.0
	 * @since 4.1.0 Added support for `$operator` 'NOT EXISTS' and 'EXISTS' values.
	 *
	 * @param array $tax_query {
	 *     Array of taxonomy query clauses.
	 *
	 *     @type string $relation Optional.
	 *                            The MySQL keyword used to join the clauses of the query.
	 *                            Accepts 'AND', or 'OR'.
	 *                            Default 'AND'.
	 *     @type array {
	 *         Optional.
	 *         An array of first-order clause parameters, or another fully-formed tax query.
	 *
	 *         @type string           $taxonomy         Taxonomy being queried.
	 *                                                  Optional when field=term_taxonomy_id.
	 *         @type string|int|array $terms            Term or terms to filter by.
	 *         @type string           $field            Field to match $terms against.
	 *                                                  Accepts 'term_id', 'slug', 'name', or 'term_taxonomy_id'.
	 *                                                  Default: 'term_id'.
	 *         @type string           $operator         MySQL operator to be used with $terms in the WHERE clause.
	 *                                                  Accepts 'AND', 'IN', 'NOT IN', 'EXISTS', 'NOT EXISTS'.
	 *         @type bool             $include_children Optional.
	 *                                                  Whether to include child terms.
	 *                                                  Requires a $taxonomy.
	 *                                                  Default: true.
	 *     }
	 * }
	 */
	public function __construct( $tax_query )
	{
		$this->relation = isset( $tax_query['relation'] )
			? $this->sanitize_relation( $tax_query['relation'] )
			: 'AND';

		$this->queries = $this->sanitize_query( $tax_query );
	}

	/**
	 * Ensure the 'tax_query' argument passed to the class constructor is well-formed.
	 *
	 * Ensures that each query-level clause has a 'relation' key, and that each first-order clause contains all the necessary keys from `$defaults`.
	 *
	 * @since 4.1.0
	 *
	 * @param  array $queries Array of queries clauses.
	 * @return array Sanitized array of query clauses.
	 */
	public function sanitize_query( $queries )
	{
		$cleaned_query = array();
		$defaults = array(
			'taxonomy'         => '',
			'terms'            => array(),
			'field'            => 'term_id',
			'operator'         => 'IN',
			'include_children' => TRUE
		);

		foreach ( $queries as $key => $query ) {
			if ( 'relation' === $key ) {
				$cleaned_query['relation'] = $this->sanitize_relation( $query );
			} elseif ( self::is_first_order_clause( $query ) ) {
				$cleaned_clause = array_merge( $defaults, $query );
				$cleaned_clause['terms'] = ( array ) $cleaned_clause['terms'];
				$cleaned_query[] = $cleaned_clause;

				// Keep a copy of the clause in the flate $queried_terms array, for use in WP_Query.
				if ( ! empty( $cleaned_clause['taxonomy'] ) && 'NOT IN' !== $cleaned_clause['operator'] ) {
					$taxonomy = $cleaned_clause['taxonomy'];

					if ( ! isset( $this->queried_terms[ $taxonomy ] ) ) {
						$this->queried_terms[ $taxonomy ] = array();
					}

					// Backward compatibility: Only store the first 'terms' and 'field' found for a given taxonomy.
					if ( ! empty( $cleaned_clause['terms'] ) && ! isset( $this->queried_terms[ $taxonomy ]['terms'] ) ) {
						$this->queried_terms[ $taxonomy ]['terms'] = $cleaned_clause['terms'];
					}

					if ( ! empty( $cleaned_clause['field'] ) && ! isset( $this->queried_terms[ $taxonomy ]['field'] ) ) {
						$this->queried_terms[ $taxonomy ]['field'] = $cleaned_clause['field'];
					}
				}
			} elseif ( is_array( $query ) ) {
				// Otherwise, it's a nested query, so we recurse.
				$cleaned_subquery = $this->sanitize_query( $query );

				if ( ! empty( $cleaned_subquery ) ) {
					// All queries with children must have a relation.
					if ( ! isset( $cleaned_subquery['relation'] ) ) {
						$cleaned_subquery['relation'] = 'AND';
					}

					$cleaned_query[] = $cleaned_subquery;
				}
			}
		}

		return $cleaned_query;
	}

	/**
	 * Sanitize a 'relation' operator.
	 *
	 * @since 4.1.0
	 *
	 * @param  string $relation Raw relation key from the query argument.
	 * @return string Sanitized relation ('AND' or 'OR').
	 */
	public function sanitize_relation( $relation )
	{
		return 'OR' === strtoupper( $relation )
			? 'OR'
			: 'AND';
	}

	/**
	 * Determine whether a clause is first-order.
	 *
	 * A "first-order" clause is one that contains any of the first-order clause keys ('terms', 'taxonomy', 'include_children', 'field', 'operator').
	 * An empty clause also counts as a first-order clause, for backward compatibility.
	 * Any clause that doesn't meet this is determined, by process of elimination, to be a higher-order query.
	 *
	 * @since  4.1.0
	 * @static
	 *
	 * @param  array $query Tax query arguments.
	 * @return bool  Whether the query clause is a first-order clause.
	 */
	protected static function is_first_order_clause( $query )
	{
		return is_array( $query )
		    && ( empty( $query ) || array_key_exists( 'terms', $query ) || array_key_exists( 'taxonomy', $query ) || array_key_exists( 'include_children', $query ) || array_key_exists( 'field', $query ) || array_key_exists( 'operator', $query ) );
	}

	/**
	 * Generates SQL clauses to be appended to a main query.
	 *
	 * @since  3.1.0
	 * @static
	 *
	 * @param  string $primary_table     Database table where the object being filtered is stored (e.g. wp_users).
	 * @param  string $primary_id_column ID column for the filtered object in $primary_table.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	public function get_sql( $primary_table, $primary_id_column )
	{
		$this->primary_table = $primary_table;
		$this->primary_id_column = $primary_id_column;
		return $this->get_sql_clauses();
	}

	/**
	 * Generate SQL clauses to be appended to a main query.
	 *
	 * Called by the public WP_Tax_Query::get_sql(), this method is abstracted out to maintain parity with the other Query classes.
	 *
	 * @since 4.1.0
	 *
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_clauses()
	{
		/**
		 * $queries are passed by reference to get_sql_for_query() for recursion.
		 * To keep $this->queries unaltered, pass a copy.
		 */
		$queries = $this->queries;
		$sql = $this->get_sql_for_query( $queries );
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
 * @NOW 010: wp-includes/class-wp-tax-query.php
 * -> wp-includes/class-wp-tax-query.php
 */
	}

	/**
	 * Generate SQL clauses for a single query array.
	 *
	 * If nested subqueries are found, this method recurses the tree to produce the properly nested SQL.
	 *
	 * @since 4.1.0
	 *
	 * @param  array $query Query to parse (passed by reference).
	 * @param  int   $depth Optional.
	 *                      Number of tree levels deep we currently are.
	 *                      Used to calculate indentation.
	 *                      Default 0.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to a single query array.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_for_query( &$query, $depth = 0 )
	{
		$sql_chunks = array(
			'join'  => array(),
			'where' => array()
		);
		$sql = array(
			'join'  => '',
			'where' => ''
		);
		$indent = '';

		for ( $i = 0; $i < $depth; $i++ ) {
			$indent .= "  ";
		}

		foreach ( $query as $key => &$clause ) {
			if ( 'relation' === $key ) {
				$relation = $query['relation'];
			} elseif ( is_array( $clause ) ) {
				if ( $this->is_first_order_clause( $clause ) ) {
					$clause_sql = $this->get_sql_for_clause( $clause, $query );
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
 * <- wp-includes/class-wp-tax-query.php
 * @NOW 011: wp-includes/class-wp-tax-query.php
 * -> wp-includes/class-wp-tax-query.php
 */
				}
			}
		}
	}

	/**
	 * Generate SQL JOIN and WHERE clauses for a "first-order" query clause.
	 *
	 * @since  4.1.0
	 * @global wpdb $wpdb The WordPress database abstraction object.
	 *
	 * @param  array $clause       Query clause (passed by reference).
	 * @param  array $parent_query Parent query array.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to a first-order query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	public function get_sql_for_clause( &$clause, $parent_query )
	{
		global $wpdb;
		$sql = array(
			'where' => array(),
			'join'  => array()
		);
		$join = $where = '';
		$this->clean_query( $clause );
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
 * <- wp-includes/class-wp-tax-query.php
 * <- wp-includes/class-wp-tax-query.php
 * @NOW 012: wp-includes/class-wp-tax-query.php
 */
	}

	/**
	 * Validates a single query.
	 *
	 * @since 3.2.0
	 *
	 * @param array $query The single query.
	 *                     Passed by reference.
	 */
	private function clean_query( &$query )
	{
		if ( empty( $query['taxonomy'] ) ) {
			if ( 'term_taxonomy_id' !== $query['field'] ) {
				$query = new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
				return;
			}

			// So long as there are shared terms, include_children requires that a taxonomy is set.
			$query['include_children'] = FALSE;
		} elseif ( ! taxonomy_exists( $query['taxonomy'] ) ) {
			$query = new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
			return;
		}

		$query['terms'] = array_unique( ( array ) $query['terms'] );

		if ( is_taxonomy_hierarchical( $query['taxonomy'] ) && $query['include_children'] ) {
			$this->transform_query( $query, 'term_id' );

			if ( is_wp_error( $query ) ) {
				return;
			}

			$children = array();

			foreach ( $query['terms'] as $term ) {
				$children = array_merge( $children, get_term_children( $term, $query['taxonomy'] ) );
				$children[] = $term;
			}

			$query['terms'] = $children;
		}

		$this->transform_query( $query, 'term_taxonomy_id' );
	}

	/**
	 * Transforms a single query, from one field to another.
	 *
	 * Operates on the `$query` object by reference.
	 * In the case of error, `$query` is converted to a WP_Error object.
	 *
	 * @since  3.2.0
	 * @global wpdb $wpdb The WordPress database abstraction object.
	 *
	 * @param array  $query           The single query.
	 *                                Passed by reference.
	 * @param string $resulting_field The resulting field.
	 *                                Accepts 'slug', 'name', 'term_taxonomy_id', or 'term_id'.
	 *                                Default 'term_id'.
	 */
	public function transform_query( &$query, $resulting_field )
	{
		if ( empty( $query['terms'] ) ) {
			return;
		}

		if ( $query['field'] == $resulting_field ) {
			return;
		}

		$resulting_field = sanitize_key( $resulting_field );

		// Empty 'terms' always results in a null transformation.
		$terms = array_filter( $query['terms'] );

		if ( empty( $terms ) ) {
			$query['terms'] = array();
			$query['field'] = $resulting_field;
			return;
		}

		$args = array(
			'get'                    => 'all',
			'number'                 => 0,
			'taxonomy'               => $query['taxonomy'],
			'update_term_meta_cache' => FALSE,
			'orderby'                => 'none'
		);

		// Term query parameter name depends on the 'field' being searched on.
		switch ( $query['field'] ) {
			case 'slug':
				$args['slug'] = $terms;
				break;

			case 'name':
				$args['name'] = $terms;
				break;

			case 'term_taxonomy_id':
				$args['term_taxonomy_id'] = $terms;
				break;

			default:
				$args['include'] = wp_parse_id_list( $terms );
				break;
		}

		$term_query = new WP_Term_Query();
		$term_list  = $term_query->query( $args );

		if ( is_wp_error( $term_list ) ) {
			$query = $term_list;
			break;
		}

		if ( 'AND' == $query['operator'] && count( $term_list ) < count( $query['terms'] ) ) {
			$query = new WP_Error( 'inexistent_terms', __( 'Inexistent terms.' ) );
			return;
		}

		$query['terms'] = wp_list_pluck( $term_list, $resulting_field );
		$query['field'] = $resulting_field;
	}
}
