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
		$cache_key = "get_network_ids:$key:$last_changed";
		$cache_value = wp_cache_get( $cache_key, 'networks' );

		if ( FALSE === $cache_value ) {
			$network_ids = $this->get_network_ids();
// @NOW 025 -> wp-includes/class-wp-network-query.php
		}
	}

	/**
	 * Used internally to get a list of network IDs matching the query vars.
	 *
	 * @since  4.6.0
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return int|array A single count of network IDs if a count query.
	 *                   An array of network IDs if a full query.
	 */
	protected function get_network_ids()
	{
		global $wpdb;
		$order = $this->parse_order( $this->query_vars['order'] );

		// Disable ORDER BY with 'none', an empty array, or boolean false.
		if ( in_array( $this->query_vars['orderby'], ['none', [], FALSE], TRUE ) ) {
			$orderby = '';
		} elseif ( ! empty( $this->query_vars['orderby'] ) ) {
			$ordersby = is_array( $this->query_vars['orderby'] )
				? $this->query_vars['orderby']
				: preg_split( '/[,\s]/', $this->query_vars['orderby'] );
			$orderby_array = [];

			foreach ( $ordersby as $_key => $_value ) {
				if ( ! $_value ) {
					continue;
				}

				if ( is_int( $_key ) ) {
					$_orderby = $_value;
					$_order = $order;
				} else {
					$_orderby = $_key;
					$_order = $_value;
				}

				$parsed = $this->parse_orderby( $_orderby );

				if ( ! $parsed ) {
					continue;
				}

				if ( 'network__in' === $_orderby ) {
					$orderby_array[] = $parsed;
					continue;
				}

				$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
			}

			$orderby = implode( ', ', $orderby_array );
		} else {
			$orderby = "$wpdb->site.id $order";
		}

		$number = absint( $this->query_vars['number'] );
		$offset = absint( $this->query_vars['offset'] );

		if ( ! empty( $number ) ) {
			$limits = $offset
				. 'LIMIT ' . $offset . ',' . $number
				: 'LIMIT ' . $number;
		}

		$fields = $this->query_vars['count'] ? 'COUNT(*)' : "$wpdb->site.id";

		// Parse network IDs for an IN clause.
		if ( ! empty( $this->query_vars['network__in'] ) ) {
			$this->sql_clauses['where']['network__in'] = "$wpdb->site.id IN ( " . implode( ',', wp_parse_id_list( $this->query_vars['network__in'] ) ) . ')';
		}

		// Parse network IDs for a NOT IN clause.
		if ( ! empty( $this->query_vars['network__not_in'] ) ) {
			$this->sql_clauses['where']['network__not_in'] = "$wpdb->site.id NOT IN ( " . implode( ',', wp_parse_id_list( $this->query_vars['network__not_in'] ) );
		}

		if ( ! empty( $this->query_vars['domain'] ) ) {
			$this->sql_clauses['where']['domain'] = $wpdb->prepare( "$wpdb->site.domain = %s", $this->query_vars['domain'] );
		}

		// Parse network domain for an IN clause.
		if ( is_array( $this->query_vars['domain__in'] ) ) {
			$this->sql_clauses['where']['domain__in'] = "$wpdb->site.domain IN ( " . implode( "', '", $wpdb->_escape( $this->query_vars['domain__in'] ) ) . "' )";
		}

		// Parse network domain for a NOT IN clause.
		if ( is_array( $this->query_vars['domain__not_in'] ) ) {
			$this->sql_clauses['where']['domain__not_in'] = "$wpdb->site.domain NOT IN ( '" . implode( "', '", $wpdb->_escape( $this->query_vars['domain__not_in'] ) ) . "' )";
		}

		if ( ! empty( $this->query_vars['path'] ) ) {
			$this->sql_clauses['where']['path'] = $wpdb->prepare( "$wpdb->site.path = %s", $this->query_vars['path'] );
		}

		// Parse network path for an IN clause.
		if ( is_array( $this->query_vars['path__in'] ) ) {
			$this->sql_clauses['where']['path__in'] = "$wpdb->site.path IN ( '" . implode( "', '", $wpdb->_escape( $this->query_vars['path__in'] ) ) . "' )";
		}

		// Parse network path for a NOT IN clause.
		if ( is_array( $this->query_vars['path__not_in'] ) ) {
			$this->sql_clauses['where']['path__not_in'] = "$wpdb->site.path NOT IN ( '" . implode( "', '", $wpdb->_escape( $this->query_vars['path__not_in'] ) ) . "' )";
		}

		// Falsey search strings are ignored.
		if ( strlen( $this->query_vars['search'] ) ) {
			$this->sql_clauses['where']['search'] = $this->get_search_sql( $this->query_vars['search'], ["$wpdb->site.domain", "$wpdb->site.path"] );
// @NOW 026 -> wp-includes/class-wp-network-query.php
		}
	}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns.
	 *
	 * @since  4.6.0
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param  string $string  Search string.
	 * @param  array  $columns Columns to search.
	 * @return string Search SQL.
	 */
	protected function get_search_sql( $string, $columns )
	{
		global $wpdb;
		$like = '%' . $wpdb->esc_like( $string ) . '%';
// @NOW 027 -> wp-includes/wp-db.php
	}

	/**
	 * Parses and sanitizes 'orderby' keys passed to the network query.
	 *
	 * @since  4.6.0
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param  string       $orderby Alias for the field to order by.
	 * @return string|false Value to used in the ORDER clause.
	 *                      False otherwise.
	 */
	protected function parse_orderby( $orderby )
	{
		global $wpdb;
		$allowed_keys = ['id', 'domain', 'path'];
		$parsed = FALSE;

		if ( $orderby == 'network__in' ) {
			$network__in = implode( ',', array_map( 'absint', $this->query_vars['network__in'] ) );
			$parsed = "FIELD( {$wpdb->site}.id, $network__in )";
		} elseif ( $orderby == 'domain_length' || $orderby == 'path_length' ) {
			$field = substr( $orderby, 0, -7 );
			$parsed = "CHAR_LENGTH($wpdb->site.$field)";
		} elseif ( in_array( $orderby, $allowed_keys ) ) {
			$parsed = "$wpdb->site.$orderby";
		}

		return $parsed;
	}

	/**
	 * Parses an 'order' query variable and cast it to 'ASC' or 'DESC' as necessary.
	 *
	 * @since 4.6.0
	 *
	 * @param  string $order The 'order' query variable.
	 * @return string The sanitized 'order' query variable.
	 */
	protected function parse_order( $order )
	{
		return ( ! is_string( $order ) || empty( $order ) )
			? 'ASC'
			: ( 'ASC' === strtoupper( $order )
				? 'ASC'
				: 'DESC' );
	}
}
