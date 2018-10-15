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
	// :
	// refactored. protected function parse_order( $order ) {}
}
