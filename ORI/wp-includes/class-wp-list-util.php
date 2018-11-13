<?php
/**
 * WordPress List utility class
 *
 * @package WordPress
 * @since 4.7.0
 */

/**
 * List utility.
 *
 * Utility class to handle operations on an array of objects.
 *
 * @since 4.7.0
 */
class WP_List_Util {
	// refactored. private $input = array();
	// :
	// refactored. public function __construct( $input ) {}

	/**
	 * Returns the original input array.
	 *
	 * @since 4.7.0
	 *
	 * @return array The input array.
	 */
	public function get_input() {
		return $this->input;
	}

	// refactored. public function get_output() {}
	// :
	// refactored. public function pluck( $field, $index_key = null ) {}

	/**
	 * Sorts the list, based on one or more orderby arguments.
	 *
	 * @since 4.7.0
	 *
	 * @param string|array $orderby       Optional. Either the field name to order by or an array
	 *                                    of multiple orderby fields as $orderby => $order.
	 * @param string       $order         Optional. Either 'ASC' or 'DESC'. Only used if $orderby
	 *                                    is a string.
	 * @param bool         $preserve_keys Optional. Whether to preserve keys. Default false.
	 * @return array The sorted array.
	 */
	public function sort( $orderby = array(), $order = 'ASC', $preserve_keys = false ) {
		if ( empty( $orderby ) ) {
			return $this->output;
		}

		if ( is_string( $orderby ) ) {
			$orderby = array( $orderby => $order );
		}

		foreach ( $orderby as $field => $direction ) {
			$orderby[ $field ] = 'DESC' === strtoupper( $direction ) ? 'DESC' : 'ASC';
		}

		$this->orderby = $orderby;

		if ( $preserve_keys ) {
			uasort( $this->output, array( $this, 'sort_callback' ) );
		} else {
			usort( $this->output, array( $this, 'sort_callback' ) );
		}

		$this->orderby = array();

		return $this->output;
	}

	/**
	 * Callback to sort the list by specific fields.
	 *
	 * @since 4.7.0
	 *
	 * @see WP_List_Util::sort()
	 *
	 * @param object|array $a One object to compare.
	 * @param object|array $b The other object to compare.
	 * @return int 0 if both objects equal. -1 if second object should come first, 1 otherwise.
	 */
	private function sort_callback( $a, $b ) {
		if ( empty( $this->orderby ) ) {
			return 0;
		}

		$a = (array) $a;
		$b = (array) $b;

		foreach ( $this->orderby as $field => $direction ) {
			if ( ! isset( $a[ $field ] ) || ! isset( $b[ $field ] ) ) {
				continue;
			}

			if ( $a[ $field ] == $b[ $field ] ) {
				continue;
			}

			$results = 'DESC' === $direction ? array( 1, -1 ) : array( -1, 1 );

			if ( is_numeric( $a[ $field ] ) && is_numeric( $b[ $field ] ) ) {
				return ( $a[ $field ] < $b[ $field ] ) ? $results[0] : $results[1];
			}

			return 0 > strcmp( $a[ $field ], $b[ $field ] ) ? $results[0] : $results[1];
		}

		return 0;
	}
}
