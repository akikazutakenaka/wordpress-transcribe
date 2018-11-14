<?php
/**
 * Class for generating SQL clauses that filter a primary query according to date.
 *
 * WP_Date_Query is a helper that allows primary query classes, such as WP_Query, to filter their results by date columns, by generating `WHERE` subclauses to be attached to the primary SQL query string.
 *
 * Attempting to filter by an invalid date value (e.g. month=13) will generate SQL that will return no results.
 * In their cases, a _doing_it_wrong() error notice is also thrown.
 * See WP_Date_Query::validate_date_values().
 *
 * @link  https://codex.wordpress.org/Function_Reference/WP_Query Codex page.
 * @since 3.7.0
 */
class WP_Date_Query
{
	/**
	 * Array of date queries.
	 *
	 * See WP_Date_Query::__construct() for information on date query arguments.
	 *
	 * @since 3.7.0
	 *
	 * @var array
	 */
	public $queries = array();

	/**
	 * The default relation between top-level queries.
	 * Can be either 'AND' or 'OR'.
	 *
	 * @since 3.7.0
	 *
	 * @var string
	 */
	public $relation = 'AND';

	/**
	 * The column to query against.
	 * Can be changed via the query arguments.
	 *
	 * @since 3.7.0
	 *
	 * @var string
	 */
	public $column = 'post_date';

	/**
	 * The value comparison operator.
	 * Can be changed via the query arguments.
	 *
	 * @since 3.7.0
	 *
	 * @var array
	 */
	public $compare = '=';

	/**
	 * Supported time-related parameter keys.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	public $time_keys = array( 'after', 'before', 'year', 'month', 'monthnum', 'week', 'w', 'dayofyear', 'day', 'dayofweek', 'dayofweek_iso', 'hour', 'minute', 'second' );

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
 * @NOW 010: wp-includes/date.php
 */
}
