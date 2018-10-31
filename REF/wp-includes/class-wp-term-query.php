<?php
/**
 * Taxonomy API: WP_Term_Query class.
 *
 * @package    WordPress
 * @subpackage Taxonomy
 * @since      4.6.0
 */

/**
 * Class used for querying terms.
 *
 * @since 4.6.0
 * @see   WP_Term_Query::__construct() for accepted arguments.
 */
class WP_Term_Query
{
	/**
	 * SQL string used to perform database query.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	public $request;

	/**
	 * Metadata query container.
	 *
	 * @since 4.6.0
	 *
	 * @var object WP_Meta_Query
	 */
	public $meta_query = FALSE;

	/**
	 * Metadata query clauses.
	 *
	 * @since 4.6.0
	 *
	 * @var array
	 */
	protected $meta_query_clauses;

	/**
	 * SQL query clauses.
	 *
	 * @since 4.6.0
	 *
	 * @var array
	 */
	protected $sql_clauses = array(
		'select'  => '',
		'from'    => '',
		'where'   => array(),
		'orderby' => '',
		'limits'  => ''
	);

	/**
	 * Query vars set by the user.
	 *
	 * @since 4.6.0
	 *
	 * @var array
	 */
	public $query_vars;

	/**
	 * Default values for query vars.
	 *
	 * @since 4.6.0
	 *
	 * @var array
	 */
	public $query_var_defaults;

	/**
	 * List of terms located by the query.
	 *
	 * @since 4.6.0
	 *
	 * @var array
	 */
	public $terms;

// wp-includes/taxonomy.php -> @NOW 012
}
