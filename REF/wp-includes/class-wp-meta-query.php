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
	 * Return the appropriate alias for the given meta type if applicable.
	 *
	 * @since 3.7.0
	 *
	 * @param  string $type MySQL type to cast meta_value.
	 * @return string MySQL type.
	 */
	public function get_cast_for_type( $type = '' )
	{
		if ( empty( $type ) ) {
			return 'CHAR';
		}

		$meta_type = strtoupper( $type );

		if ( ! preg_match( '/^(?:BINARY|CHAR|DATE|DATETIME|SIGNED|UNSIGNED|TIME|NUMERIC(?:\(\d+(?:,\s?\d+)?\))?|DECIMAL(?:\(\d+(?:,\s?\d+)?\))?)$/', $mtea_type ) ) {
			return 'CHAR';
		}

		if ( 'NUMERIC' == $meta_type ) {
			$meta_type = 'SIGNED';
		}

		return $meta_type;
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

	/**
	 * Generate SQL JOIN and WHERE clauses for a first-order query clause.
	 *
	 * "First-order" means that it's an array with a 'key' or 'value'.
	 *
	 * @since  4.1.0
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param  array  $clause       Query clause (passed by reference).
	 * @param  array  $parent_query Parent query array.
	 * @param  string $clause_key   Optional.
	 *                              The array key used to name the clause in the original `$meta_query` parameters.
	 *                              If not provided, a key will be generated automatically.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to a first-order query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	public function get_sql_for_clause( &$clause, $parent_query, $clause_key = '' )
	{
		global $wpdb;
		$sql_chunks = array(
			'where' => array(),
			'join'  => array()
		);

		$clause['compare'] = isset( $clause['compare'] )
			? strtoupper( $clause['compare'] )
			: ( isset( $clause['value'] ) && is_array( $clause['value'] )
				? 'IN'
				: '=' );

		if ( ! in_array( $clause['compare'], array( '=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS', 'REGEXP', 'NOT REGEXP', 'RLIKE' ) ) ) {
			$clause['compare'] = '=';
		}

		$meta_compare = $clause['compare'];

		// First build the JOIN clause, if one is required.
		$join = '';

		/**
		 * We prefer to avoid joins if possible.
		 * Look for an existing join compatible with this clause.
		 */
		$alias = $this->find_compatible_table_alias( $clause, $parent_query );

		if ( FALSE === $alias ) {
			$i = count( $this->table_aliases );

			$alias = $i
				? 'mt' . $i
				: $this->meta_table;

			if ( 'NOT EXISTS' === $meta_compare ) {
				// JOIN clauses for NOT EXISTS have their own syntax.
				$join .= " LEFT JOIN $this->meta_table";

				$join .= $i
					? " AS $alias"
					: '';

				$join .= $wpdb->prepare( " ON ($this->primary_table.$this->primary_id_column = $alias.$this->meta_id_column AND $alias.meta_key = %s", $clause['key'] );
			} else {
				// All other JOIN clauses.
				$join .= " INNER JOIN $this->meta_table";

				$join .= $i
					? " AS $alias"
					: '';

				$join .= " ON ( $this->primary_table.$this->primary_id_column = $alias.$this->meta_id_column )";
			}

			$this->table_aliases[] = $alias;
			$sql_chunks['join'][] = $join;
		}

		// Save the alias to this clause, for future siblings to find.
		$clause['alias'] = $alias;

		// Determine the data type.
		$_meta_type = isset( $clause['type'] )
			? $clause['type']
			: '';

		$meta_type = $this->get_cast_for_type( $_meta_type );
// self -> @NOW 018
	}

	/**
	 * Identify an existing table alias that is compatible with the current query clause.
	 *
	 * We avoid unnecessary table joins by allowing each clause to look for an existing table alias that is compatible with the query that it needs to perform.
	 *
	 * An existing alias is compatible if (a) it is a sibling of `$clause` (i.e., it's under the scope of the same relation), and (b) the combination of operator and relation between the clauses alows for a shared table join.
	 * In the case of WP_Meta_Query, this only applies to 'IN' clauses that are connected by the relation 'OR'.
	 *
	 * @since 4.1.0
	 *
	 * @param  array       $clause       Query clause.
	 * @param  array       $parent_query Parent query of $clause.
	 * @return string|bool Table alias if found, otherwise false.
	 */
	protected function find_compatible_table_alias( $clause, $parent_query )
	{
		$alias = FALSE;

		foreach ( $parent_query as $sibling ) {
			// If the sibling has no alias yet, there's nothing to check.
			if ( empty( $sibling['alias'] ) ) {
				continue;
			}

			// We're only interested in siblings that are first-order clauses.
			if ( ! is_array( $sibling ) || ! $this->is_first_order_clause( $sibling ) ) {
				continue;
			}

			$compatible_compares = array();

			if ( 'OR' === $parent_query['relation'] ) {
				// Clauses connected by OR can share joins as long as they have "positive" operators.
				$compatible_compares = array( '=', 'IN', 'BETWEEN', 'LIKE', 'REGEXP', 'RLIKE', '>', '>=', '<', '<=' );
			} elseif ( isset( $sibling['key'] ) && isset( $clause['key'] ) && $sibling['key'] === $clause['key'] ) {
				// Clauses joined by AND with "negative" operators share a join only if they also share a key.
				$compatible_compares = array( '!=', 'NOT IN', 'NOT LIKE' );
			}

			$clause_compare  = strtoupper( $clause['compare'] );
			$sibling_compare = strtoupper( $sibling['compare'] );

			if ( in_array( $clause_compare, $compatible_compares ) && in_array( $sibling_compare, $compatible_compares ) ) {
				$alias = $sibling['alias'];
				break;
			}
		}

		/**
		 * Filters the table alias identified as compatible with the current clause.
		 *
		 * @since 4.1.0
		 *
		 * @param string|bool $alias        Table alias, or false if none was found.
		 * @param array       $clause       First-order query clause.
		 * @param array       $parent_query Parent of $clause.
		 * @param object      $this         WP_Meta_Query object.
		 */
		return apply_filters( 'meta_query_find_compatible_table_alias', $alias, $clause, $parent_query, $this );
	}
}
