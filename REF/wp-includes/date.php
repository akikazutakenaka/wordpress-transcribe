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
	 * Constructor.
	 *
	 * Time-related parameters that normally require integer values ('year', 'month', 'week', 'dayofyear', 'day', 'dayofweek', 'dayofweek_iso', 'hour', 'minute', 'second') accept arrays of integers for some values of 'compare'.
	 * When 'compare' is 'IN' or 'NOT IN', arrays are accepted; when 'compare' is 'BETWEEN' or 'NOT BETWEEN', arrays of two valid values are required.
	 * See individual argument descriptions for accepted values.
	 *
	 * @since 3.7.0
	 * @since 4.0.0 The $inclusive logic was updated to include all times within the date range.
	 * @since 4.1.0 Introduced 'dayofweek_iso' time type parameter.
	 *
	 * @param array $date_query {
	 *     Array of date query clauses.
	 *
	 *     @type array {
	 *         @type string $column   Optional.
	 *                                The column to query against.
	 *                                If undefined, inherits the value of the `$default_column` parameter.
	 *                                Accepts 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt', 'comment_date', 'comment_date_gmt'.
	 *                                Default 'post_date'.
	 *         @type string $compare  Optional.
	 *                                The comparison operator.
	 *                                Accepts '=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'.
	 *                                Default '='.
	 *         @type string $relation Optional.
	 *                                The boolean relationship between the date queries.
	 *                                Accepts 'OR' or 'AND'.
	 *                                Default 'OR'.
	 *         @type array {
	 *             Optional.
	 *             An array of first-order clause parameters, or another fully-formed date query.
	 *
	 *             @type string|array $before {
	 *                 Optional.
	 *                 Date to retrieve posts before.
	 *                 Accepts `strtotime()`-compatible string, or array of 'year', 'month', 'day' values.
	 *
	 *                 @type string $year  The four-digit year.
	 *                                     Default empty.
	 *                                     Accepts any four-digit year.
	 *                 @type string $month Optional with passing array.
	 *                                     The month of the year.
	 *                                     Default (string:empty)|(array:1).
	 *                                     Accepts numbers 1-12.
	 *                 @type string $day   Optional when passing array.
	 *                                     The day of the month.
	 *                                     Default (string:empty)|(array:1).
	 *                                     Accepts numbers 1-31.
	 *             }
	 *             @type string|array $after {
	 *                 Optional.
	 *                 Date to retrieve posts after.
	 *                 Accepts `strtotime()`-compatible string, or array of 'year', 'month', 'day' values.
	 *
	 *                 @type string $year  The four-digit year.
	 *                                     Default empty.
	 *                                     Accepts any four-digit year.
	 *                 @type string $month Optional with passing array.
	 *                                     The month of the year.
	 *                                     Default (string:empty)|(array:1).
	 *                                     Accepts numbers 1-12.
	 *                 @type string $day   Optional when passing array.
	 *                                     The day of the month.
	 *                                     Default (string:empty)|(array:1).
	 *                                     Accepts numbers 1-31.
	 *             }
	 *             @type string       $column        Optional.
	 *                                               Used to add a clause comparing a column other than the column specified in the top-level `$column` parameter.
	 *                                               Accepts 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt', 'comment_date', 'comment_date_gmt'.
	 *                                               Default is the value of top-level `$column`.
	 *             @type string       $compare       Optional.
	 *                                               The comparison operator.
	 *                                               Accepts '=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'.
	 *                                               'IN', 'NOT IN', 'BETWEEN', and 'NOT BETWEEN' comparisons support arrays in som time-related parameters.
	 *                                               Default '='.
	 *             @type bool         $inclusive     Optional.
	 *                                               Include results from date specified in 'before' or 'after'.
	 *                                               Default false.
	 *             @type int|array    $year          Optional.
	 *                                               The four-digit year number.
	 *                                               Accepts any four-digit year or an array of years if `$compare` supports it.
	 *                                               Default empty.
	 *             @type int|array    $month         Optional.
	 *                                               The two-digit month number.
	 *                                               Accepts numbers 1-12 or an array of valid numbers if `$compare` supports it.
	 *                                               Default empty.
	 *             @type int|array    $week          Optional.
	 *                                               The week number of the year.
	 *                                               Accepts numbers 0-53 or an array of valid numbers if `$compare` supports it.
	 *                                               Default empty.
	 *             @type int|array    $dayofyear     Optional.
	 *                                               The day number of the year.
	 *                                               Accepts numbers 1-366 or an array of valid numbers if `$compare` supports it.
	 *             @type int|array    $day           Optional.
	 *                                               The day of the month.
	 *                                               Accepts numbers 1-31 or an array of valid numbers if `$compare` supports it.
	 *                                               Default empty.
	 *             @type int|array    $dayofweek     Optional.
	 *                                               The day number of the week.
	 *                                               Accepts numbers 1-7 (1 is Sunday) or an array of valid numbers if `$compare` supports it.
	 *                                               Default empty.
	 *             @type int|array    $dayofweek_iso Optional.
	 *                                               The day number of the week (ISO).
	 *                                               Accepts numbers 1-7 (1 is Monday) or an array of valid numbers if `$compare` supports it.
	 *                                               Default empty.
	 *             @type int|array    $hour          Optional.
	 *                                               The hour of the day.
	 *                                               Accepts numbers 0-23 or an array of valid numbers if `$compare` supports it.
	 *                                               Default empty.
	 *             @type int|array    $minute        Optional.
	 *                                               The minute of the hour.
	 *                                               Accepts numbers 0-60 or an array of valid numbers if `$compare` supports it.
	 *                                               Default empty.
	 *             @type int|array    $second        Optional.
	 *                                               The second of the minute.
	 *                                               Accepts numbers 0-60 or an array of valid numbers if `$compare` supports it.
	 *                                               Default empty.
	 *         }
	 *     }
	 * }
	 * @param array $default_column Optional.
	 *                              Default column to query against.
	 *                              Default 'post_date'.
	 *                              Accepts 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt', 'comment_date', 'comment_date_gmt'.
	 */
	public function __construct( $date_query, $default_column = 'post_date' )
	{
		$this->relation = isset( $date_query['relation'] && 'OR' === strtoupper( $date_query['relation'] ) )
			? 'OR'
			: 'AND';

		if ( ! is_array( $date_query ) ) {
			return;
		}

		// Support for passing time-based keys in the top level of the $date_query array.
		if ( ! isset( $date_query[0] ) && ! empty( $date_query ) ) {
			$date_query = array( $date_query );
		}

		if ( empty( $date_query ) ) {
			return;
		}

		$date_query['column'] = ! empty( $date_query['column'] )
			? esc_sql( $date_query['column'] )
			: esc_sql( $default_column );

		$this->column = $this->validate_column( $this->column );
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

	/**
	 * Validate a column name parameter.
	 *
	 * Column names without a table prefix (like 'post_date') are checked against a whitelist of known tables, and then, if found, have a table prefix (such as 'wp_posts.') prepended.
	 * Prefixed column names (such as 'wp_posts.post_date') bypass this whitelist check, and are only sanitized to remove illegal characters.
	 *
	 * @since 3.7.0
	 *
	 * @param  string $column The user-supplied column name.
	 * @return string A validated column name value.
	 */
	public function validate_column( $column )
	{
		global $wpdb;
		$valid_columns = array( 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt', 'comment_date', 'comment_date_gmt', 'user_registered', 'registered', 'last_updated' );

		// Attempt to detect a table prefix.
		if ( FALSE === strpos( $column, '.' ) ) {
			/**
			 * Filters the list of valid date query columns.
			 *
			 * @since 3.7.0
			 * @since 4.1.0 Added 'user_registered' to the default recognized columns.
			 *
			 * @param array $valid_columns An array of valid date columns.
			 *                             Defaults are 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt', 'comment_date', 'comment_date_gmt', 'user_registered'.
			 */
			if ( ! in_array( $column, apply_filters( 'date_query_valid_columns', $valid_columns ) ) ) {
				$column = 'post_date';
			}

			$known_columns = array(
				$wpdb->posts    = array( 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt' ),
				$wpdb->comments = array( 'comment_date', 'comment_date_gmt' ),
				$wpdb->users    = array( 'user_registered' ),
				$wpdb->blogs    = array( 'registered', 'last_updated' )
			);

			// If it's a known column name, add the appropriate table prefix.
			foreach ( $known_columns as $table_name => $table_columns ) {
				if ( in_array( $column, $table_columns ) ) {
					$column = $table_name . '.' . $column;
					break;
				}
			}
		}

		// Remove unsafe characters.
		return preg_replace( '/[^a-zA-Z0-9_$\.]/', '', $column );
	}
}
