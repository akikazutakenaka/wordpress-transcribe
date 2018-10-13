<?php
/**
 * Network API: WP_Network_Query class
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 4.6.0
 */

/**
 * Core class used for querying networks.
 *
 * @since 4.6.0
 *
 * @see WP_Network_Query::__construct() for accepted arguments.
 */
class WP_Network_Query {
	// refactored. public $request;
	// :
	// refactored. public $max_num_pages = 0;

	/**
	 * Constructor.
	 *
	 * Sets up the network query, based on the query vars passed.
	 *
	 * @since 4.6.0
	 *
	 * @param string|array $query {
	 *     Optional. Array or query string of network query parameters. Default empty.
	 *
	 *     @type array        $network__in          Array of network IDs to include. Default empty.
	 *     @type array        $network__not_in      Array of network IDs to exclude. Default empty.
 	 *     @type bool         $count                Whether to return a network count (true) or array of network objects.
 	 *                                              Default false.
 	 *     @type string       $fields               Network fields to return. Accepts 'ids' (returns an array of network IDs)
 	 *                                              or empty (returns an array of complete network objects). Default empty.
 	 *     @type int          $number               Maximum number of networks to retrieve. Default empty (no limit).
 	 *     @type int          $offset               Number of networks to offset the query. Used to build LIMIT clause.
 	 *                                              Default 0.
 	 *     @type bool         $no_found_rows        Whether to disable the `SQL_CALC_FOUND_ROWS` query. Default true.
 	 *     @type string|array $orderby              Network status or array of statuses. Accepts 'id', 'domain', 'path',
 	 *                                              'domain_length', 'path_length' and 'network__in'. Also accepts false,
 	 *                                              an empty array, or 'none' to disable `ORDER BY` clause. Default 'id'.
 	 *     @type string       $order                How to order retrieved networks. Accepts 'ASC', 'DESC'. Default 'ASC'.
 	 *     @type string       $domain               Limit results to those affiliated with a given domain. Default empty.
 	 *     @type array        $domain__in           Array of domains to include affiliated networks for. Default empty.
 	 *     @type array        $domain__not_in       Array of domains to exclude affiliated networks for. Default empty.
 	 *     @type string       $path                 Limit results to those affiliated with a given path. Default empty.
 	 *     @type array        $path__in             Array of paths to include affiliated networks for. Default empty.
 	 *     @type array        $path__not_in         Array of paths to exclude affiliated networks for. Default empty.
 	 *     @type string       $search               Search term(s) to retrieve matching networks for. Default empty.
	 *     @type bool         $update_network_cache Whether to prime the cache for found networks. Default true.
	 * }
	 */
	public function __construct( $query = '' ) {
		$this->query_var_defaults = array(
			'network__in'          => '',
			'network__not_in'      => '',
			'count'                => false,
			'fields'               => '',
			'number'               => '',
			'offset'               => '',
			'no_found_rows'        => true,
			'orderby'              => 'id',
			'order'                => 'ASC',
			'domain'               => '',
			'domain__in'           => '',
			'domain__not_in'       => '',
			'path'                 => '',
			'path__in'             => '',
			'path__not_in'         => '',
			'search'               => '',
			'update_network_cache' => true,
		);

		if ( ! empty( $query ) ) {
			$this->query( $query );
		}
	}

	// refactored. public function parse_query( $query = '' ) {}
	// refactored. public function query( $query ) {}

	/**
	 * Gets a list of networks matching the query vars.
	 *
	 * @since 4.6.0
	 *
	 * @return array|int List of WP_Network objects, a list of network ids when 'fields' is set to 'ids',
	 *                   or the number of networks when 'count' is passed as a query var.
	 */
	public function get_networks() {
		$this->parse_query();

		/**
		 * Fires before networks are retrieved.
		 *
		 * @since 4.6.0
		 *
		 * @param WP_Network_Query $this Current instance of WP_Network_Query (passed by reference).
		 */
		do_action_ref_array( 'pre_get_networks', array( &$this ) );

		// $args can include anything. Only use the args defined in the query_var_defaults to compute the key.
		$_args = wp_array_slice_assoc( $this->query_vars, array_keys( $this->query_var_defaults ) );

		// Ignore the $fields argument as the queried result will be the same regardless.
		unset( $_args['fields'] );

		$key = md5( serialize( $_args ) );
		$last_changed = wp_cache_get_last_changed( 'networks' );

		$cache_key = "get_network_ids:$key:$last_changed";
		$cache_value = wp_cache_get( $cache_key, 'networks' );

		if ( false === $cache_value ) {
			$network_ids = $this->get_network_ids();
			if ( $network_ids ) {
				$this->set_found_networks();
			}

			$cache_value = array(
				'network_ids' => $network_ids,
				'found_networks' => $this->found_networks,
			);
			wp_cache_add( $cache_key, $cache_value, 'networks' );
		} else {
			$network_ids = $cache_value['network_ids'];
			$this->found_networks = $cache_value['found_networks'];
		}

		if ( $this->found_networks && $this->query_vars['number'] ) {
			$this->max_num_pages = ceil( $this->found_networks / $this->query_vars['number'] );
		}

		// If querying for a count only, there's nothing more to do.
		if ( $this->query_vars['count'] ) {
			// $network_ids is actually a count in this case.
			return intval( $network_ids );
		}

		$network_ids = array_map( 'intval', $network_ids );

		if ( 'ids' == $this->query_vars['fields'] ) {
			$this->networks = $network_ids;
			return $this->networks;
		}

		if ( $this->query_vars['update_network_cache'] ) {
			_prime_network_caches( $network_ids );
		}

		// Fetch full network objects from the primed cache.
		$_networks = array();
		foreach ( $network_ids as $network_id ) {
			if ( $_network = get_network( $network_id ) ) {
				$_networks[] = $_network;
			}
		}

		/**
		 * Filters the network query results.
		 *
		 * @since 4.6.0
		 *
		 * @param array            $_networks An array of WP_Network objects.
		 * @param WP_Network_Query $this      Current instance of WP_Network_Query (passed by reference).
		 */
		$_networks = apply_filters_ref_array( 'the_networks', array( $_networks, &$this ) );

		// Convert to WP_Network instances
		$this->networks = array_map( 'get_network', $_networks );

		return $this->networks;
	}

	// refactored. protected function get_network_ids() {}

	/**
	 * Populates found_networks and max_num_pages properties for the current query
	 * if the limit clause was used.
	 *
	 * @since 4.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	private function set_found_networks() {
		global $wpdb;

		if ( $this->query_vars['number'] && ! $this->query_vars['no_found_rows'] ) {
			/**
			 * Filters the query used to retrieve found network count.
			 *
			 * @since 4.6.0
			 *
			 * @param string           $found_networks_query SQL query. Default 'SELECT FOUND_ROWS()'.
			 * @param WP_Network_Query $network_query        The `WP_Network_Query` instance.
			 */
			$found_networks_query = apply_filters( 'found_networks_query', 'SELECT FOUND_ROWS()', $this );

			$this->found_networks = (int) $wpdb->get_var( $found_networks_query );
		}
	}

	// refactored. protected function get_search_sql( $string, $columns ) {}
	// :
	// refactored. protected function parse_order( $order ) {}
}
