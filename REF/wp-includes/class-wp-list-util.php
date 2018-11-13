<?php
/**
 * WordPress List utility class
 *
 * @package WordPress
 * @since   4.7.0
 */

/**
 * List utility.
 *
 * Utility class to handle operations on an array of objects.
 *
 * @since 4.7.0
 */
class WP_List_Util
{
	/**
	 * The input array.
	 *
	 * @since 4.7.0
	 *
	 * @var array
	 */
	private $input = array();

	/**
	 * The output array.
	 *
	 * @since 4.7.0
	 *
	 * @var array
	 */
	private $output = array();

	/**
	 * Temporary arguments for sorting.
	 *
	 * @since 4.7.0
	 *
	 * @var array
	 */
	private $orderby = array();

	/**
	 * Constructor.
	 *
	 * Sets the input array.
	 *
	 * @since 4.7.0
	 *
	 * @param array $input Array to perform operations on.
	 */
	public function __construct( $input )
	{
		$this->output = $this->input = $input;
	}

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
 * @NOW 012: wp-includes/class-wp-list-util.php
 */

	/**
	 * Filters the list, based on a set of key => value arguments.
	 *
	 * @since 4.7.0
	 *
	 * @param  array  $args     Optional.
	 *                          An array of key => value arguments to match against each object.
	 *                          Default empty array.
	 * @param  string $operator Optional.
	 *                          The logical operation to perform.
	 *                          'AND' means all elements from the array must match.
	 *                          'OR' means only one element needs to match.
	 *                          'NOT' means no elements may match.
	 *                          Default 'AND'.
	 * @return array  Array of found values.
	 */
	public function filter( $args = array(), $operator = 'AND' )
	{
		if ( empty( $args ) ) {
			return $this->output;
		}

		$operator = strtoupper( $operator );

		if ( ! in_array( $operator, array( 'AND', 'OR', 'NOT' ), TRUE ) ) {
			return array();
		}

		$count = count( $args );
		$filtered = array();

		foreach ( $this->output as $key => $obj ) {
			$to_match = ( array ) $obj;
			$matched = 0;

			foreach ( $args as $m_key => $m_value ) {
				if ( array_key_exists( $m_key, $to_match ) && $m_value == $to_match[ $m_key ] ) {
					$matched++;
				}
			}

			if ( 'AND' == $operator && $matched == $count
			  || 'OR' == $operator && $matched > 0
			  || 'NOT' == $operator && 0 == $matched ) {
				$filtered[ $key ] = $obj;
			}
		}

		$this->output = $filtered;
		return $this->output;
	}

	/**
	 * Plucks a certain field out of each object in the list.
	 *
	 * This has the same functionality and prototype of array_column() (PHP 5.5) but also supports objects.
	 *
	 * @since 4.7.0
	 *
	 * @param  int|string $field     Field from the object to place instead of the entire object.
	 * @param  int|string $index_key Optional.
	 *                               Field from the object to use as keys for the new array.
	 *                               Default null.
	 * @return array      Array of found values.
	 *                    If `$index_key` is set, an array of found values with keys corresponding to `$index_key`.
	 *                    If `$index_key` is null, array keys from the original `$list` will be preserved in the results.
	 */
	public function pluck( $field, $index_key = NULL )
	{
		if ( ! $index_key ) {
			/**
			 * This is simple.
			 * Could at some point wrap array_column() if we knew we had an array of arrays.
			 */
			foreach ( $this->output as $key => $value ) {
				$this->output[ $key ] = is_object( $value )
					? $value->$field
					: $value[ $field ];
			}

			return $this->output;
		}

		/**
		 * When index_key is not set for a particular item, push the value to the end of the stack.
		 * This is how array_column() behaves.
		 */
		$newlist = array();

		foreach ( $this->output as $value ) {
			if ( is_object( $value ) ) {
				if ( isset( $value->$index_key ) ) {
					$newlist[ $value->$index_key ] = $value->$field;
				} else {
					$newlist[] = $value->$field;
				}
			} else {
				if ( isset( $value[ $index_key ] ) ) {
					$newlist[ $value[ $index_key ] ] = $value[ $field ];
				} else {
					$newlist[] = $value[ $field ];
				}
			}
		}

		$this->output = $newlist;
		return $this->output;
	}
}
