<?php
/**
 * Network API: WP_Network_Query class
 *
 * @package    WordPress
 * @subpackage Multisite
 * @since      4.6.0
 */

/**
 * Core class used for querying networks.
 *
 * @since 4.6.0
 * @see   WP_Network_Query::__construct() for accepted arguments.
 */
class WP_Network_Query
{
	/**
	 * SQL for database query.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	public $request;

	/**
	 * SQL query clauses.
	 *
	 * @since 4.6.0
	 *
	 * @var array
	 */
	protected $sql_clauses = [
		'select'  => '',
		'from'    => '',
		'where'   => [],
		'groupby' => '',
		'orderby' => '',
		'limits'  => ''
	];

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
	 * List of networks located by the query.
	 *
	 * @since 4.6.0
	 *
	 * @var array
	 */
	public $networks;

	/**
	 * The amount of found networks for the current query.
	 *
	 * @since 4.6.0
	 *
	 * @var int
	 */
	public $found_networks = 0;

	/**
	 * The number of pages.
	 *
	 * @since 4.6.0
	 *
	 * @var int
	 */
	public $max_num_pages = 0;

// @NOW 024
}
