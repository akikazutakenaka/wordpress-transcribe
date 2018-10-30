<?php
/**
 * Taxonomy API: WP_Term class
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 4.4.0
 */

/**
 * Core class used to implement the WP_Term object.
 *
 * @since 4.4.0
 *
 * @property-read object $data Sanitized term data.
 */
final class WP_Term {
	// refactored. public $term_id;
	// :
	// refactored. public function to_array() {}

	/**
	 * Getter.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key Property to get.
	 * @return mixed Property value.
	 */
	public function __get( $key ) {
		switch ( $key ) {
			case 'data' :
				$data = new stdClass();
				$columns = array( 'term_id', 'name', 'slug', 'term_group', 'term_taxonomy_id', 'taxonomy', 'description', 'parent', 'count' );
				foreach ( $columns as $column ) {
					$data->{$column} = isset( $this->{$column} ) ? $this->{$column} : null;
				}

				return sanitize_term( $data, $data->taxonomy, 'raw' );
		}
	}
}
