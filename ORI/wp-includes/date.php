<?php
/**
 * Class for generating SQL clauses that filter a primary query according to date.
 *
 * WP_Date_Query is a helper that allows primary query classes, such as WP_Query, to filter
 * their results by date columns, by generating `WHERE` subclauses to be attached to the
 * primary SQL query string.
 *
 * Attempting to filter by an invalid date value (eg month=13) will generate SQL that will
 * return no results. In these cases, a _doing_it_wrong() error notice is also thrown.
 * See WP_Date_Query::validate_date_values().
 *
 * @link https://codex.wordpress.org/Function_Reference/WP_Query Codex page.
 *
 * @since 3.7.0
 */
class WP_Date_Query {
	// refactored. public $queries = array();
	// :
	// refactored. protected function get_sql_for_query( $query, $depth = 0 ) {}

	/**
	 * Turns a single date clause into pieces for a WHERE clause.
	 *
	 * A wrapper for get_sql_for_clause(), included here for backward
	 * compatibility while retaining the naming convention across Query classes.
	 *
	 * @since  3.7.0
	 *
	 * @param  array $query Date query arguments.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_for_subquery( $query ) {
		return $this->get_sql_for_clause( $query, '' );
	}

	// refactored. protected function get_sql_for_clause( $query, $parent_query ) {}
	// :
	// refactored. public function build_time_query( $column, $compare, $hour = null, $minute = null, $second = null ) {}
}
