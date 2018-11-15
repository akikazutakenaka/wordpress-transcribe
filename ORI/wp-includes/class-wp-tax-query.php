<?php
/**
 * Taxonomy API: WP_Tax_Query class
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 4.4.0
 */

/**
 * Core class used to implement taxonomy queries for the Taxonomy API.
 *
 * Used for generating SQL clauses that filter a primary query according to object
 * taxonomy terms.
 *
 * WP_Tax_Query is a helper that allows primary query classes, such as WP_Query, to filter
 * their results by object metadata, by generating `JOIN` and `WHERE` subclauses to be
 * attached to the primary SQL query string.
 *
 * @since 3.1.0
 */
class WP_Tax_Query {
	// refactored. public $queries = array();
	// :
	// refactored. public function get_sql( $primary_table, $primary_id_column ) {}

	/**
	 * Generate SQL clauses to be appended to a main query.
	 *
	 * Called by the public WP_Tax_Query::get_sql(), this method
	 * is abstracted out to maintain parity with the other Query classes.
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

	// refactored. protected function get_sql_for_query( &$query, $depth = 0 ) {}
	// :
	// refactored. public function transform_query( &$query, $resulting_field ) {}
}
