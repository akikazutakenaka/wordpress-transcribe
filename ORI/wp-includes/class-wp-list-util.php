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
	// refactored. private function sort_callback( $a, $b ) {}
}
