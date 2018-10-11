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
	if ( ! $meta_type || ! is_numeric( $object_id ) )
		return FALSE;

	$object_id = absint( $object_id );

	if ( ! $object_id )
		return FALSE;

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

	if ( NULL !== $check )
		return ( $single && is_array( $check ) ) ? $check[0] : $check;

	$meta_cache = wp_cache_get( $object_id, $meta_type . '_meta' );

	if ( ! $meta_cache ) {
		$meta_cache = update_meta_cache( $meta_type, [$object_id] );
// @NOW 019 -> wp-includes/meta.php
	}
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

	if ( ! $meta_type || ! $object_ids )
		return FALSE;

	$table = _get_meta_table( $meta_type );

	if ( ! $table )
		return FALSE;

	$column = sanitize_key( $meta_type . '_id' );
// @NOW 020 -> wp-includes/formatting.php
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

	if ( empty( $wpdb->$table_name ) )
		return FALSE;

	return $wpdb->$table_name;
}
