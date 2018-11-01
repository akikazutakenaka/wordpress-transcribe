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
// wp-includes/class-wp-term-query.php -> @NOW 013 -> self
			}
		}
	}

// self -> @NOW 014
}
