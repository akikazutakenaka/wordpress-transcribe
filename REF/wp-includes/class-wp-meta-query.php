<?php
/**
 * Meta API: WP_Meta_Query class
 *
 * @package    WordPress
 * @subpackage Meta
 * @since      4.4.0
 */

/**
 * Core class used to implement meta queries for the Meta API.
 *
 * Used for generating SQL clauses that filter a primary query according to metadata keys and values.
 *
 * WP_Meta_Query is a helper that allows primary query classes, such as WP_Query and WP_User_Query, to filter their results by object metadata, by generating `JOIN` and `WHERE` subclauses to be attached to the primary SQL query string.
 *
 * @since 3.2.0
 */
class WP_Meta_Query
{
	/**
	 * Array of metadata queries.
	 *
	 * See WP_Meta_Query::__construct() for information on meta query arguments.
	 *
	 * @since 3.2.0
	 *
	 * @var array
	 */
	public $queries = array();

	/**
	 * The relation between the queries.
	 * Can be one of 'AND' or 'OR'.
	 *
	 * @since 3.2.0
	 *
	 * @var string
	 */
	public $relation;

	/**
	 * Database table to query for the metadata.
	 *
	 * @since 4.1.0
	 *
	 * @var string
	 */
	public $meta_table;

	/**
	 * Column in meta_table that represents the ID of the object the metadata belongs to.
	 *
	 * @since 4.1.0
	 *
	 * @var string
	 */
	public $meta_id_column;

	/**
	 * Database table that where the metadata's objects are stored (e.g. $wpdb->users).
	 *
	 * @since 4.1.0
	 *
	 * @var string
	 */
	public $primary_table;

	/**
	 * Column in primary_table that represents the ID of the object.
	 *
	 * @since 4.1.0
	 *
	 * @var string
	 */
	public $primary_id_column;

	/**
	 * A flat list of table aliases used in JOIN clauses.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	protected $table_aliases = array();

	/**
	 * A flat list of clauses, keyed by clause 'name'.
	 *
	 * @since 4.2.0
	 *
	 * @var array
	 */
	protected $clauses = array();

	/**
	 * Whether the query contains any OR relations.
	 *
	 * @since 4.3.0
	 *
	 * @var bool
	 */
	protected $has_or_relation = FALSE;

	/**
	 * Constructor.
	 *
	 * @since 3.2.0
	 * @since 4.2.0 Introduced support for naming query clauses by associative array keys.
	 *
	 * @param array $meta_query {
	 *     Array of meta query clauses.
	 *     When first-order clauses or sub-clauses use strings as their array keys, they may be referenced in the 'orderby' parameter of the parent query.
	 *
	 *     @type string $relation Optional.
	 *                            The MySQL keyword used to join the clauses of the query.
	 *                            Accepts 'AND', or 'OR'.
	 *                            Default 'AND'.
	 *     @type array {
	 *         Optional.
	 *         An array of first-order clause parameters, or another fully-formed meta query.
	 *
	 *         @type string $key     Meta key to filter by.
	 *         @type string $value   Meta value to filter by.
	 *         @type string $compare MySQL operator used for comparing the $value.
	 *                               Accepts '=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'REGEXP', 'NOT REGEXP', 'RLIKE', 'EXISTS' or 'NOT EXISTS'.
	 *                               Default is 'IN' when `$value` is an array, '=' otherwise.
	 *         @type string $type    MySQL data type that the meta_value column will be CAST to for comparisons.
	 *                               Accepts 'NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', or 'UNSIGNED'.
	 *                               Default is 'CHAR'.
	 *     }
	 * }
	 */
	public function __construct( $meta_query = FALSE )
	{
		if ( ! $meta_query ) {
			return;
		}

		$this->relation = isset( $meta_query['relation'] ) && strtoupper( $meta_query['relation'] ) == 'OR'
			? 'OR'
			: 'AND';

		$this->queries = $this->sanitize_query( $meta_query );
	}

	/**
	 * Ensure the 'meta_query' argument passed to the class constructor is well-formed.
	 *
	 * Eliminates empty items and ensures that a 'relation' is set.
	 *
	 * @since 4.1.0
	 *
	 * @param  array $queries Array of query clauses.
	 * @return array Sanitized array of query clauses.
	 */
	public function sanitize_query( $queries )
	{
		$clean_queries = array();

		if ( ! is_array( $queries ) ) {
			return $clean_queries;
		}

		foreach ( $queries as $key => $query ) {
			if ( 'relation' === $key ) {
				$relation = $query;
			} elseif ( ! is_array( $query ) ) {
				continue;
			} elseif ( $this->is_first_order_clause( $query ) ) {
				if ( isset( $query['value'] ) && array() === $query['value'] ) {
					unset( $query['value'] );
				}

				$clean_queries[ $key ] = $query;
			} else {
				$cleaned_query = $this->sanitize_query( $query );

				if ( ! empty( $cleaned_query ) ) {
					$clean_queries[ $key ] = $cleaned_query;
				}
			}
		}

		if ( empty( $clean_queries ) ) {
			return $clean_queries;
		}

		// Sanitize the 'relation' key provided in the query.
		if ( isset( $relation ) && 'OR' === strtoupper( $relation ) ) {
			$clean_queries['relation'] = 'OR';
			$this->has_or_relation = TRUE;
		} elseif ( 1 === count( $clean_queries ) ) {
			/**
			 * If there is only a single clause, call the relation 'OR'.
			 * This value will not actually be used to join clauses, but it simplifies the logic around combining key-only queries.
			 */
			$clean_queries['relation'] = 'OR';
		} else {
			// Default to AND.
			$clean_queries['relation'] = 'AND';
		}

		return $clean_queries;
	}

	/**
	 * Determine whether a query clause is first-order.
	 *
	 * A first-order meta query clause is one that has either a 'key' or a 'value' array key.
	 *
	 * @since 4.1.0
	 *
	 * @param  array $query Meta query arguments.
	 * @return bool  Whether the query clause is a first-order clause.
	 */
	protected function is_first_order_clause( $query )
	{
		return isset( $query['key'] ) || isset( $query['value'] );
	}

	/**
	 * Constructs a meta query based on 'meta_*' query vars
	 *
	 * @since 3.2.0
	 *
	 * @param array $qv The query variables
	 */
	public function parse_query_vars( $qv )
	{
		$meta_query = array();

		// For orderby=meta_value to work correctly, simple query needs to be first (so that its table join is against an unaliased meta table) and needs to be its own clause (so it doesn't interfere with the logic of the rest of the meta_query).
		$primary_meta_query = array();

		foreach ( array( 'key', 'compare', 'type' ) as $key ) {
			if ( ! empty( $qv[ "meta_$key" ] ) ) {
				$primary_meta_query[ $key ] = $qv[ "meta_$key" ];
			}
		}

		// WP_Query sets 'meta_value' = '' by default.
		if ( isset( $qv['meta_value'] )
		  && '' !== $qv['meta_value']
		  && ( ! is_array( $qv['meta_value'] ) || $qv['meta_value'] ) ) {
			$primary_meta_query['value'] = $qv['meta_value'];
		}

		$existing_meta_query = isset( $qv['meta_query'] ) && is_array( $qv['meta_query'] )
			? $qv['meta_query']
			: array();

		if ( ! empty( $primary_meta_query ) && ! empty( $existing_meta_query ) ) {
			$meta_query = array(
				'relation' => 'AND',
				$primary_meta_query,
				$existing_meta_query
			);
		} elseif ( ! empty( $primary_meta_query ) ) {
			$meta_query = array( $primary_meta_query );
		} elseif ( ! empty( $existing_meta_query ) ) {
			$meta_query = $existing_meta_query;
		}

		$this->__construct( $meta_query );
	}

	/**
	 * Generates SQL clauses to be appended to a main query.
	 *
	 * @since 3.2.0
	 *
	 * @param  string      $type              Type of meta, e.g. 'user', 'post'.
	 * @param  string      $primary_table     Database table where the object being filtered is stored (e.g. wp_users).
	 * @param  string      $primary_id_column ID column for the filtered object in $primary_table.
	 * @param  object      $context           Optional.
	 *                                        The main query object.
	 * @return false|array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	public function get_sql( $type, $primary_table, $primary_id_column, $context = NULL )
	{
		if ( ! $meta_table = _get_meta_table( $type ) ) {
			return FALSE;
		}

		$this->table_aliases = array();
		$this->meta_table     = $meta_table;
		$this->meta_id_column = sanitize_key( $type . '_id' );
		$this->primary_table     = $primary_table;
		$this->primary_id_column = $primary_id_column;
		$sql = $this->get_sql_clauses();
// wp-includes/class-wp-term-query.php -> @NOW 015 -> self
	}

	/**
	 * Generate SQL clauses to be appended to a main query.
	 *
	 * Called by the public WP_Meta_Query::get_sql(), this method is abstracted out to maintain parity with the other Query classes.
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
// self -> @NOW 016 -> self
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
					// This is a first-order clause.
					$clause_sql = $this->get_sql_for_clause( $clause, $query, $key );
// self -> @NOW 017 -> self
				}
			}
		}
	}

// self -> @NOW 018
}
