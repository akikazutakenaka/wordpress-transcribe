<?php
/**
 * Core Metadata API
 *
 * Functions for retrieving and manipulating metadata of various WordPress object types.
 * Metadata for an object is a represented by a simple key-value pair.
 * Objects may contain multiple metadata entries that share the same key and differ only in their value.
 *
 * @package    WordPress
 * @subpackage Meta
 */

/**
 * Retrieve metadata for the specified object.
 *
 * @since 2.9.0
 *
 * @param  string $meta_type Type of object metadata is for (e.g., comment, post, or user).
 * @param  int    $object_id ID of the object metadata is for.
 * @param  string $meta_key  Optional.
 *                           Metadata key.
 *                           If not specified, retrieve all metadata for the specified object.
 * @param  bool   $single    Optional, default is false.
 *                           If true, return only the first value of the specified meta_key.
 *                           This parameter has no effect if meta_key is not specified.
 * @return mixed  Single metadata value, or array of values.
 */
function get_metadata( $meta_type, $object_id, $meta_key = '', $single = FALSE )
{
	if ( ! $meta_type || ! is_numeric( $object_id ) ) {
		return FALSE;
	}

	$object_id = absint( $object_id );

	if ( ! $object_id ) {
		return FALSE;
	}

	/**
	 * Filters whether to retrieve metadata of a specific type.
	 *
	 * The dynamic portion of the hook, `$meta_type`, refers to the meta object type (comment, post, or user).
	 * Returning a non-null value will effectively short-circuit the function.
	 *
	 * @since 3.1.0
	 *
	 * @param null|array|string $value     The value get_metadata() should return - a single metadata value, or an array of values.
	 * @param int               $object_id Object ID.
	 * @param string            $meta_key  Meta key.
	 * @param bool              $single    Whether to return only the first value of the specified $meta_key.
	 */
	$check = apply_filters( "get_{$meta_type}_metadata", NULL, $object_id, $meta_key, $single );

	if ( NULL !== $check ) {
		return ( $single && is_array( $check ) ) ? $check[0] : $check;
	}

	$meta_cache = wp_cache_get( $object_id, $meta_type . '_meta' );

	if ( ! $meta_cache ) {
		$meta_cache = update_meta_cache( $meta_type, [ $object_id ] );
		$meta_cache = $meta_cache[ $object_id ];
	}

	if ( ! $meta_key ) {
		return $meta_cache;
	}

	if ( isset( $meta_cache[ $meta_key ] ) ) {
		return $single
			? maybe_unserialize( $meta_cache[ $meta_key ][0] )
			: array_map( 'maybe_unserialize', $meta_cache[ $meta_key ] );
	}

	return $single ? '' : [];
}

/**
 * Update the metadata cache for the specified objects.
 *
 * @since  2.9.0
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param  string      $meta_type  Type of object metadata is for (e.g., comment, post, or user).
 * @param  int|array   $object_ids Array or comma delimited list of object IDs to update cache for.
 * @return array|false Metadata cache for the specified objects, or false in failure.
 */
function update_meta_cache( $meta_type, $object_ids )
{
	global $wpdb;

	if ( ! $meta_type || ! $object_ids ) {
		return FALSE;
	}

	$table = _get_meta_table( $meta_type );

	if ( ! $table ) {
		return FALSE;
	}

	$column = sanitize_key( $meta_type . '_id' );

	if ( ! is_array( $object_ids ) ) {
		$object_ids = preg_replace( '|[^0-9,]|', '', $object_ids );
		$object_ids = explode( ',', $object_ids );
	}

	$object_ids = array_map( 'intval', $object_ids );
	$cache_key = $meta_type . '_meta';
	$ids = [];
	$cache = [];

	foreach ( $object_ids as $id ) {
		$cached_object = wp_cache_get( $id, $cache_key );

		if ( FALSE === $cached_object ) {
			$ids[] = $id;
		} else {
			$cache[ $id ] = $cached_object;
		}
	}

	if ( empty( $ids ) ) {
		return $cache;
	}

	// Get meta info
	$id_list = join( ',', $ids );
	$id_column = 'user' == $meta_type ? 'umeta_id' : 'meta_id';
	$meta_list = $wpdb->get_results( <<<EOQ
SELECT $column, meta_key, meta_value
FROM $table
WHERE $column IN ( $id_list )
ORDER BY $id_column ASC
EOQ
		, ARRAY_A );

	if ( ! empty( $meta_list ) ) {
		foreach ( $meta_list as $metarow ) {
			$mpid = intval( $metarow[ $column ] );
			$mkey = $metarow['meta_key'];
			$mval = $metarow['meta_value'];

			// Force subkeys to be array type:
			if ( ! isset( $cache[ $mpid ] ) || ! is_array( $cache[ $mpid ] ) ) {
				$cache[ $mpid ] = [];
			}

			if ( ! isset( $cache[ $mpid ][ $mkey ] ) || ! is_array( $cache[ $mpid ][ $mkey ] ) ) {
				$cache[ $mpid ][ $mkey ] = [];
			}

			// Add a value to the current pid/key:
			$cache[ $mpid ][ $mkey ][] = $mval;
		}
	}

	foreach ( $ids as $id ) {
		if ( ! isset( $cache[ $id ] ) ) {
			$cache[ $id ] = [];
		}

		wp_cache_add( $id, $cache[ $id ], $cache_key );
	}

	return $cache;
}

/**
 * Retrieve the name of the metadata table for the specified object type.
 *
 * @since  2.9.0
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param  string       $type Type of object to get metadata table for (e.g., comment, post, or user).
 * @return string|false Metadata table name, or false if no metadata table exists.
 */
function _get_meta_table( $type )
{
	global $wpdb;
	$table_name = $type . 'meta';

	if ( empty( $wpdb->$table_name ) ) {
		return FALSE;
	}

	return $wpdb->$table_name;
}
