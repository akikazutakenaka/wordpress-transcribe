<?php
/**
 * Meta API: WP_Meta_Query class
 *
 * @package WordPress
 * @subpackage Meta
 * @since 4.4.0
 */

/**
 * Core class used to implement meta queries for the Meta API.
 *
 * Used for generating SQL clauses that filter a primary query according to metadata keys and values.
 *
 * WP_Meta_Query is a helper that allows primary query classes, such as WP_Query and WP_User_Query,
 *
 * to filter their results by object metadata, by generating `JOIN` and `WHERE` subclauses to be attached
 * to the primary SQL query string.
 *
 * @since 3.2.0
 */
class WP_Meta_Query {
	// refactored. public $queries = array();
	// :
	// refactored. public function get_cast_for_type( $type = '' ) {}

	/**
	 * Generates SQL clauses to be appended to a main query.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type              Type of meta, eg 'user', 'post'.
	 * @param string $primary_table     Database table where the object being filtered is stored (eg wp_users).
	 * @param string $primary_id_column ID column for the filtered object in $primary_table.
	 * @param object $context           Optional. The main query object.
	 * @return false|array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	public function get_sql( $type, $primary_table, $primary_id_column, $context = null ) {
		if ( ! $meta_table = _get_meta_table( $type ) ) {
			return false;
		}

		$this->table_aliases = array();

		$this->meta_table     = $meta_table;
		$this->meta_id_column = sanitize_key( $type . '_id' );

		$this->primary_table     = $primary_table;
		$this->primary_id_column = $primary_id_column;

		$sql = $this->get_sql_clauses();

		/*
		 * If any JOINs are LEFT JOINs (as in the case of NOT EXISTS), then all JOINs should
		 * be LEFT. Otherwise posts with no metadata will be excluded from results.
		 */
		if ( false !== strpos( $sql['join'], 'LEFT JOIN' ) ) {
			$sql['join'] = str_replace( 'INNER JOIN', 'LEFT JOIN', $sql['join'] );
		}

		/**
		 * Filters the meta query's generated SQL.
		 *
		 * @since 3.1.0
		 *
		 * @param array  $clauses           Array containing the query's JOIN and WHERE clauses.
		 * @param array  $queries           Array of meta queries.
		 * @param string $type              Type of meta.
		 * @param string $primary_table     Primary table.
		 * @param string $primary_id_column Primary column ID.
		 * @param object $context           The main query object.
		 */
		return apply_filters_ref_array( 'get_meta_sql', array( $sql, $this->queries, $type, $primary_table, $primary_id_column, $context ) );
	}

	/**
	 * Generate SQL clauses to be appended to a main query.
	 *
	 * Called by the public WP_Meta_Query::get_sql(), this method is abstracted
	 * out to maintain parity with the other Query classes.
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
	protected function get_sql_clauses() {
		/*
		 * $queries are passed by reference to get_sql_for_query() for recursion.
		 * To keep $this->queries unaltered, pass a copy.
		 */
		$queries = $this->queries;
		$sql = $this->get_sql_for_query( $queries );

		if ( ! empty( $sql['where'] ) ) {
			$sql['where'] = ' AND ' . $sql['where'];
		}

		return $sql;
	}

	/**
	 * Generate SQL clauses for a single query array.
	 *
	 * If nested subqueries are found, this method recurses the tree to
	 * produce the properly nested SQL.
	 *
	 * @since 4.1.0
	 *
	 * @param array $query Query to parse (passed by reference).
	 * @param int   $depth Optional. Number of tree levels deep we currently are.
	 *                     Used to calculate indentation. Default 0.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to a single query array.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_for_query( &$query, $depth = 0 ) {
		$sql_chunks = array(
			'join'  => array(),
			'where' => array(),
		);

		$sql = array(
			'join'  => '',
			'where' => '',
		);

		$indent = '';
		for ( $i = 0; $i < $depth; $i++ ) {
			$indent .= "  ";
		}

		foreach ( $query as $key => &$clause ) {
			if ( 'relation' === $key ) {
				$relation = $query['relation'];
			} elseif ( is_array( $clause ) ) {

				// This is a first-order clause.
				if ( $this->is_first_order_clause( $clause ) ) {
					$clause_sql = $this->get_sql_for_clause( $clause, $query, $key );

					$where_count = count( $clause_sql['where'] );
					if ( ! $where_count ) {
						$sql_chunks['where'][] = '';
					} elseif ( 1 === $where_count ) {
						$sql_chunks['where'][] = $clause_sql['where'][0];
					} else {
						$sql_chunks['where'][] = '( ' . implode( ' AND ', $clause_sql['where'] ) . ' )';
					}

					$sql_chunks['join'] = array_merge( $sql_chunks['join'], $clause_sql['join'] );
				// This is a subquery, so we recurse.
				} else {
					$clause_sql = $this->get_sql_for_query( $clause, $depth + 1 );

					$sql_chunks['where'][] = $clause_sql['where'];
					$sql_chunks['join'][]  = $clause_sql['join'];
				}
			}
		}

		// Filter to remove empties.
		$sql_chunks['join']  = array_filter( $sql_chunks['join'] );
		$sql_chunks['where'] = array_filter( $sql_chunks['where'] );

		if ( empty( $relation ) ) {
			$relation = 'AND';
		}

		// Filter duplicate JOIN clauses and combine into a single string.
		if ( ! empty( $sql_chunks['join'] ) ) {
			$sql['join'] = implode( ' ', array_unique( $sql_chunks['join'] ) );
		}

		// Generate a single WHERE clause with proper brackets and indentation.
		if ( ! empty( $sql_chunks['where'] ) ) {
			$sql['where'] = '( ' . "\n  " . $indent . implode( ' ' . "\n  " . $indent . $relation . ' ' . "\n  " . $indent, $sql_chunks['where'] ) . "\n" . $indent . ')';
		}

		return $sql;
	}

	/**
	 * Generate SQL JOIN and WHERE clauses for a first-order query clause.
	 *
	 * "First-order" means that it's an array with a 'key' or 'value'.
	 *
	 * @since 4.1.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param array  $clause       Query clause (passed by reference).
	 * @param array  $parent_query Parent query array.
	 * @param string $clause_key   Optional. The array key used to name the clause in the original `$meta_query`
	 *                             parameters. If not provided, a key will be generated automatically.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to a first-order query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	public function get_sql_for_clause( &$clause, $parent_query, $clause_key = '' ) {
		global $wpdb;

		$sql_chunks = array(
			'where' => array(),
			'join' => array(),
		);

		if ( isset( $clause['compare'] ) ) {
			$clause['compare'] = strtoupper( $clause['compare'] );
		} else {
			$clause['compare'] = isset( $clause['value'] ) && is_array( $clause['value'] ) ? 'IN' : '=';
		}

		if ( ! in_array( $clause['compare'], array(
			'=', '!=', '>', '>=', '<', '<=',
			'LIKE', 'NOT LIKE',
			'IN', 'NOT IN',
			'BETWEEN', 'NOT BETWEEN',
			'EXISTS', 'NOT EXISTS',
			'REGEXP', 'NOT REGEXP', 'RLIKE'
		) ) ) {
			$clause['compare'] = '=';
		}

		$meta_compare = $clause['compare'];

		// First build the JOIN clause, if one is required.
		$join = '';

		// We prefer to avoid joins if possible. Look for an existing join compatible with this clause.
		$alias = $this->find_compatible_table_alias( $clause, $parent_query );
		if ( false === $alias ) {
			$i = count( $this->table_aliases );
			$alias = $i ? 'mt' . $i : $this->meta_table;

			// JOIN clauses for NOT EXISTS have their own syntax.
			if ( 'NOT EXISTS' === $meta_compare ) {
				$join .= " LEFT JOIN $this->meta_table";
				$join .= $i ? " AS $alias" : '';
				$join .= $wpdb->prepare( " ON ($this->primary_table.$this->primary_id_column = $alias.$this->meta_id_column AND $alias.meta_key = %s )", $clause['key'] );

			// All other JOIN clauses.
			} else {
				$join .= " INNER JOIN $this->meta_table";
				$join .= $i ? " AS $alias" : '';
				$join .= " ON ( $this->primary_table.$this->primary_id_column = $alias.$this->meta_id_column )";
			}

			$this->table_aliases[] = $alias;
			$sql_chunks['join'][] = $join;
		}

		// Save the alias to this clause, for future siblings to find.
		$clause['alias'] = $alias;

		// Determine the data type.
		$_meta_type = isset( $clause['type'] ) ? $clause['type'] : '';
		$meta_type  = $this->get_cast_for_type( $_meta_type );
		$clause['cast'] = $meta_type;

		// Fallback for clause keys is the table alias. Key must be a string.
		if ( is_int( $clause_key ) || ! $clause_key ) {
			$clause_key = $clause['alias'];
		}

		// Ensure unique clause keys, so none are overwritten.
		$iterator = 1;
		$clause_key_base = $clause_key;
		while ( isset( $this->clauses[ $clause_key ] ) ) {
			$clause_key = $clause_key_base . '-' . $iterator;
			$iterator++;
		}

		// Store the clause in our flat array.
		$this->clauses[ $clause_key ] =& $clause;

		// Next, build the WHERE clause.

		// meta_key.
		if ( array_key_exists( 'key', $clause ) ) {
			if ( 'NOT EXISTS' === $meta_compare ) {
				$sql_chunks['where'][] = $alias . '.' . $this->meta_id_column . ' IS NULL';
			} else {
				$sql_chunks['where'][] = $wpdb->prepare( "$alias.meta_key = %s", trim( $clause['key'] ) );
			}
		}

		// meta_value.
		if ( array_key_exists( 'value', $clause ) ) {
			$meta_value = $clause['value'];

			if ( in_array( $meta_compare, array( 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ) ) ) {
				if ( ! is_array( $meta_value ) ) {
					$meta_value = preg_split( '/[,\s]+/', $meta_value );
				}
			} else {
				$meta_value = trim( $meta_value );
			}

			switch ( $meta_compare ) {
				case 'IN' :
				case 'NOT IN' :
					$meta_compare_string = '(' . substr( str_repeat( ',%s', count( $meta_value ) ), 1 ) . ')';
					$where = $wpdb->prepare( $meta_compare_string, $meta_value );
					break;

				case 'BETWEEN' :
				case 'NOT BETWEEN' :
					$meta_value = array_slice( $meta_value, 0, 2 );
					$where = $wpdb->prepare( '%s AND %s', $meta_value );
					break;

				case 'LIKE' :
				case 'NOT LIKE' :
					$meta_value = '%' . $wpdb->esc_like( $meta_value ) . '%';
					$where = $wpdb->prepare( '%s', $meta_value );
					break;

				// EXISTS with a value is interpreted as '='.
				case 'EXISTS' :
					$meta_compare = '=';
					$where = $wpdb->prepare( '%s', $meta_value );
					break;

				// 'value' is ignored for NOT EXISTS.
				case 'NOT EXISTS' :
					$where = '';
					break;

				default :
					$where = $wpdb->prepare( '%s', $meta_value );
					break;

			}

			if ( $where ) {
				if ( 'CHAR' === $meta_type ) {
					$sql_chunks['where'][] = "$alias.meta_value {$meta_compare} {$where}";
				} else {
					$sql_chunks['where'][] = "CAST($alias.meta_value AS {$meta_type}) {$meta_compare} {$where}";
				}
			}
		}

		/*
		 * Multiple WHERE clauses (for meta_key and meta_value) should
		 * be joined in parentheses.
		 */
		if ( 1 < count( $sql_chunks['where'] ) ) {
			$sql_chunks['where'] = array( '( ' . implode( ' AND ', $sql_chunks['where'] ) . ' )' );
		}

		return $sql_chunks;
	}

	/**
	 * Get a flattened list of sanitized meta clauses.
	 *
	 * This array should be used for clause lookup, as when the table alias and CAST type must be determined for
	 * a value of 'orderby' corresponding to a meta clause.
	 *
	 * @since 4.2.0
	 *
	 * @return array Meta clauses.
	 */
	public function get_clauses() {
		return $this->clauses;
	}

	// refactored. protected function find_compatible_table_alias( $clause, $parent_query ) {}

	/**
	 * Checks whether the current query has any OR relations.
	 *
	 * In some cases, the presence of an OR relation somewhere in the query will require
	 * the use of a `DISTINCT` or `GROUP BY` keyword in the `SELECT` clause. The current
	 * method can be used in these cases to determine whether such a clause is necessary.
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if the query contains any `OR` relations, otherwise false.
	 */
	public function has_or_relation() {
		return $this->has_or_relation;
	}
}
