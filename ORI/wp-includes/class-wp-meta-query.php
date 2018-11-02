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
	// refactored. public function get_sql_for_clause( &$clause, $parent_query, $clause_key = '' ) {}

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
