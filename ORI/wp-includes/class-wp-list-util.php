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

	// refactored. private function sort_callback( $a, $b ) {}
}
