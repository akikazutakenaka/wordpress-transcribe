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
 * Delete metadata for the specified post.
 *
 * @since  2.9.0
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param  string $meta_type  Type of object metadata is for (e.g., comment, post, or user).
 * @param  int    $object_id  ID of the object metadata is for.
 * @param  string $meta_key   Metadata key.
 * @param  mixed  $meta_value Optional.
 *                            Metadata value.
 *                            Must be serializable if non-scalar.
 *                            If specified, only delete metadata entries with this value.
 *                            Otherwise, delete all entries with the specified meta_key.
 *                            Pass `null`, `false`, or an empty string to skip this check.
 *                            (For backward compatibility, it is not possible to pass an empty string to delete those entries with an empty string for a value.)
 * @param  bool   $delete_all Optional, default is false.
 *                            If true, delete matching metadata entries for all objects, ignoring the specified object_id.
 *                            Otherwise, only delete matching metadata entries for the specified object_id.
 * @return bool   Trhe on successful delete, false on failure.
 */
function delete_metadata( $meta_type, $object_id, $meta_key, $meta_value = '', $delete_all = FALSE )
{
	global $wpdb;

	if ( ! $meta_type
	  || ! $meta_key
	  || ! is_numeric( $object_id ) && ! $delete_all ) {
		return FALSE;
	}

	$object_id = absint( $object_id );

	if ( ! $object_id && ! $delete_all ) {
		return FALSE;
	}

	$table = _get_meta_table( $meta_type );

	if ( ! $table ) {
		return FALSE;
	}

	$type_column = sanitize_key( $meta_type . '_id' );

	$id_column = 'user' == $meta_type
		? 'umeta_id'
		: 'meta_id';

	// expected_slashed ($meta_key)
	$meta_key = wp_unslash( $meta_key );
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * @NOW 008: wp-includes/meta.php
 * -> wp-includes/formatting.php
 */
}

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
		return $single && is_array( $check )
			? $check[0]
			: $check;
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

	return $single
		? ''
		: array();
}

/**
 * Determine if a meta key is set for a given object.
 *
 * @since 3.3.0
 *
 * @param  string $meta_type Type of object metadata is for (e.g., comment, post, or user).
 * @param  int    $object_id ID of the object metadata is for.
 * @param  string $meta_key  Metadata key.
 * @return bool   True if the key is set, false if not.
 */
function metadata_exists( $meta_type, $object_id, $meta_key )
{
	if ( ! $meta_type || ! is_numeric( $object_id ) ) {
		return FALSE;
	}

	$object_id = absint( $object_id );

	if ( ! $object_id ) {
		return FALSE;
	}

	// This filter is documented in wp-includes/meta.php
	$check = apply_filters( "get_{$meta_type}_metadata", NULL, $object_id, $meta_key, TRUE );

	if ( NULL !== $check ) {
		return ( bool ) $check;
	}

	$meta_cache = wp_cache_get( $object_id, $meta_type . '_meta' );

	if ( ! $meta_cache ) {
		$meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
		$meta_cache = $meta_cache[ $object_id ];
	}

	return isset( $meta_cache[ $meta_key ] );
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
	$ids = array();
	$cache = array();

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

	$id_column = 'user' == $meta_type
		? 'umeta_id'
		: 'meta_id';

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
				$cache[ $mpid ] = array();
			}

			if ( ! isset( $cache[ $mpid ][ $mkey ] ) || ! is_array( $cache[ $mpid ][ $mkey ] ) ) {
				$cache[ $mpid ][ $mkey ] = array();
			}

			// Add a value to the current pid/key:
			$cache[ $mpid ][ $mkey ][] = $mval;
		}
	}

	foreach ( $ids as $id ) {
		if ( ! isset( $cache[ $id ] ) ) {
			$cache[ $id ] = array();
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

/**
 * Determine whether a meta key is protected.
 *
 * @since 3.1.3
 *
 * @param  string      $meta_key  Meta key
 * @param  string|null $meta_type
 * @return bool        True if the key is protected, false otherwise.
 */
function is_protected_meta( $meta_key, $meta_type = NULL )
{
	$protected = '_' == $meta_key[0];

	/**
	 * Filters whether a meta key is protected.
	 *
	 * @since 3.2.0
	 *
	 * @param bool   $protected Whether the key is protected.
	 * @param string $meta_key  Meta key.
	 * @param string $meta_type Meta type.
	 */
	return apply_filters( 'is_protected_meta', $protected, $meta_key, $meta_type );
}

/**
 * Filter out `register_meta()` args based on a whitelist.
 * `register_meta()` args may change over time, so requiring the whitelist to be explicitly turned off is a warranty seal of sorts.
 *
 * @access private
 * @since  4.6.0
 *
 * @param  array $args         Arguments from `register_meta()`.
 * @param  array $default_args Default arguments for `register_meta()`.
 * @return array Filtered arguments.
 */
function _wp_register_meta_args_whitelist( $args, $default_args )
{
	return array_intersect_key( $args, $default_args );
}

/**
 * Returns the object subtype for a given object ID of a specific type.
 *
 * @since 4.9.8
 *
 * @param  string $object_type Type of object to request metadata for.
 *                             (e.g. comment, post, term, user)
 * @param  int    $object_id   ID of the object to retrieve its subtype.
 * @return string The object subtype or an empty string if unspecified subtype.
 */
function get_object_subtype( $object_type, $object_id )
{
	$object_id      = ( int ) $object_id;
	$object_subtype = '';

	switch ( $object_type ) {
		case 'post':
			$post_type = get_post_type( $object_id );

			if ( ! empty( $post_type ) ) {
				$object_subtype = $post_type;
			}

			break;

		case 'term':
			$term = get_term( $object_id );

			if ( ! $term instanceof WP_Term ) {
				break;
			}

			$object_subtype = $term->taxonomy;
			break;

		case 'comment':
			$comment = get_comment( $object_id );

			if ( ! $comment ) {
				break;
			}

			$object_subtype = 'comment';
			break;

		case 'user':
			$user = get_user_by( 'id', $object_id );

			if ( ! $user ) {
				break;
			}

			$object_subtype = 'user';
			break;
	}

	/**
	 * Filters the object subtype identifier for a non standard object type.
	 *
	 * The dynamic portion of the hook, `$object_type`, refers to the object type (post, comment, term, or user).
	 *
	 * @since 4.9.8
	 *
	 * @param string $object_subtype Empty string to override.
	 * @param int    $object_id      ID of the object to get the subtype for.
	 */
	return apply_filters( "get_object_subtype_{$object_type}", $object_subtype, $object_id );
}
