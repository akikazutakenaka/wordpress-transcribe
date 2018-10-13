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

	/**
	 * Constructor.
	 *
	 * Sets up the network query, based on the query vars passed.
	 *
	 * @since 4.6.0
	 *
	 * @param string|array $query {
	 *     Optional.
	 *     Array or query string of network query parameters.
	 *     Default empty.
	 *
	 *     @type array        $network__in          Array of network IDs to include.
	 *                                              Default empty.
	 *     @type array        $network__not_in      Array of network IDs to exclude.
	 *                                              Default empty.
	 *     @type bool         $count                Whether to return a network count (true) or array of network objects.
	 *                                              Default false.
	 *     @type string       $fields               Network fields to return.
	 *                                              Accepts 'ids' (returns an array of network IDs) or empty (returns an array of complete network objects).
	 *                                              Default empty.
	 *     @type int          $number               Maximum number of networks to retrieve.
	 *                                              Default empty (no limit).
	 *     @type int          $offset               Number of networks to offset the query.
	 *                                              Used to build LIMIT clause.
	 *                                              Default 0.
	 *     @type bool         $no_found_rows        Whether to disable the `SQL_CALC_FOUND_ROWS` query.
	 *                                              Default true.
	 *     @type string|array $orderby              Network status or array of statuses.
	 *                                              Accepts 'id', 'domain', 'path', 'domain_length', 'path_length' and 'network__in'.
	 *                                              Also accepts false, an empty array, or 'none' to disable `ORDER BY` clause.
	 *                                              Default 'id'.
	 *     @type string       $order                How to order retrieved networks.
	 *                                              Accepts 'ASC', 'DESC'.
	 *                                              Default 'ASC'.
	 *     @type string       $domain               Limit results to those affiliated with a given domain.
	 *                                              Default empty.
	 *     @type array        $domain__in           Array of domains to include affiliated networks for.
	 *                                              Default empty.
	 *     @type array        $domain__not_in       Array of domains to exclude affiliated networks for.
	 *                                              Default empty.
	 *     @type string       $path                 Limit results to those affiliated with a given path.
	 *                                              Default empty.
	 *     @type array        $path__in             Array of paths to include affiliated networks for.
	 *                                              Default empty.
	 *     @type array        $path__not_in         Array of paths to exclude affiliated networks for.
	 *                                              Default empty.
	 *     @type string       $search               Search term(s) to retrieve matching networks for.
	 *                                              Default empty.
	 *     @type bool         $update_network_cache Whether to prime the cache for found networks.
	 *                                              Default true.
	 * }
	 */
	public function __construct( $query = '' )
	{
		$this->query_var_defaults = [
			'network__in'          => '',
			'network__not_in'      => '',
			'count'                => FALSE,
			'fields'               => '',
			'number'               => '',
			'offset'               => '',
			'no_found_rows'        => TRUE,
			'orderby'              => 'id',
			'order'                => 'ASC',
			'domain'               => '',
			'domain__in'           => '',
			'domain__not_in'       => '',
			'path'                 => '',
			'path__in'             => '',
			'path__not_in'         => '',
			'search'               => '',
			'update_network_cache' => TRUE
		];

		if ( ! empty( $query ) ) {
			$this->query( $query );
// @NOW 024 -> wp-includes/class-wp-network-query.php
		}
	}

	/**
	 * Parses arguments passed to the network query with default query parameters.
	 *
	 * @since 4.6.0
	 *
	 * @param string|array $query WP_Network_Query arguments.
	 *                            See WP_Network_Query::__construct().
	 */
	public function parse_query( $query = '' )
	{
		if ( empty( $query ) ) {
			$query = $this->query_vars;
		}

		$this->query_vars = wp_parse_args( $query, $this->query_var_defaults );

		/**
		 * Fires after the network query vars have been parsed.
		 *
		 * @since 4.6.0
		 *
		 * @param WP_Network_Query $this The WP_Network_Query instance (passed by reference).
		 */
		do_action_ref_array( 'parse_network_query', [&$this] );
	}

	/**
	 * Sets up the WordPress query for retrieving networks.
	 *
	 * @since 4.6.0
	 *
	 * @param  string|array $query Array or URL query string of parameters.
	 * @return array|int    List of WP_Network objects, a list of network ids when 'fields' is set to 'ids', or the number of networks when 'count' is passed as a query var.
	 */
	public function query( $query )
	{
		$this->query_vars = wp_parse_args( $query );
		return $this->get_networks();
	}

	/**
	 * Gets a list of networks matching the query vars.
	 *
	 * @since 4.6.0
	 *
	 * @return array|int List of WP_Network objects, a list of network ids when 'fields' is set to 'ids', or the number of networks when 'count' is passed as a query var.
	 */
	public function get_networks()
	{
		$this->parse_query();

		/**
		 * Fires before networks are retrieved.
		 *
		 * @since 4.6.0
		 *
		 * @param WP_Network_Query $this Current instance of WP_Network_Query (passed by reference).
		 */
		do_action_ref_array( 'pre_get_networks', [&$this] );

		/**
		 * $args can include anything.
		 * Only use the args defined in the query_var_defaults to compute the key.
		 */
		$_args = wp_array_slice_assoc( $this->query_vars, array_keys( $this->query_var_defaults ) );

		// Ignore the $fields argument as the queried result will be the same regardless.
		unset( $_args['fields'] );

		$key = md5( serialize( $_args ) );
		$last_changed = wp_cache_get_last_changed( 'networks' );
// @NOW 025
	}
}
