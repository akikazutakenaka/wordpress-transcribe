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
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/class-wp-query.php
 * <- wp-includes/class-wp-query.php
 * <- wp-includes/class-wp-query.php
 * @NOW 012: wp-includes/class-wp-tax-query.php
 */
}
