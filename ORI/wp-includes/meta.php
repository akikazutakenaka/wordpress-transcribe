<?php
/**
 * Core Metadata API
 *
 * Functions for retrieving and manipulating metadata of various WordPress object types. Metadata
 * for an object is a represented by a simple key-value pair. Objects may contain multiple
 * metadata entries that share the same key and differ only in their value.
 *
 * @package WordPress
 * @subpackage Meta
 */

// refactored. function add_metadata($meta_type, $object_id, $meta_key, $meta_value, $unique = false) {}
// :
// refactored. function metadata_exists( $meta_type, $object_id, $meta_key ) {}

/**
 * Get meta data by meta ID
 *
 * @since 3.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $meta_type Type of object metadata is for (e.g., comment, post, term, or user).
 * @param int    $meta_id   ID for a specific meta row
 * @return object|false Meta object or false.
 */
function get_metadata_by_mid( $meta_type, $meta_id ) {
	global $wpdb;

	if ( ! $meta_type || ! is_numeric( $meta_id ) || floor( $meta_id ) != $meta_id ) {
		return false;
	}

	$meta_id = intval( $meta_id );
	if ( $meta_id <= 0 ) {
		return false;
	}

	$table = _get_meta_table( $meta_type );
	if ( ! $table ) {
		return false;
	}

	$id_column = ( 'user' == $meta_type ) ? 'umeta_id' : 'meta_id';

	$meta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE $id_column = %d", $meta_id ) );

	if ( empty( $meta ) )
		return false;

	if ( isset( $meta->meta_value ) )
		$meta->meta_value = maybe_unserialize( $meta->meta_value );

	return $meta;
}

/**
 * Update meta data by meta ID
 *
 * @since 3.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $meta_type  Type of object metadata is for (e.g., comment, post, or user)
 * @param int    $meta_id    ID for a specific meta row
 * @param string $meta_value Metadata value
 * @param string $meta_key   Optional, you can provide a meta key to update it
 * @return bool True on successful update, false on failure.
 */
function update_metadata_by_mid( $meta_type, $meta_id, $meta_value, $meta_key = false ) {
	global $wpdb;

	// Make sure everything is valid.
	if ( ! $meta_type || ! is_numeric( $meta_id ) || floor( $meta_id ) != $meta_id ) {
		return false;
	}

	$meta_id = intval( $meta_id );
	if ( $meta_id <= 0 ) {
		return false;
	}

	$table = _get_meta_table( $meta_type );
	if ( ! $table ) {
		return false;
	}

	$column = sanitize_key($meta_type . '_id');
	$id_column = 'user' == $meta_type ? 'umeta_id' : 'meta_id';

	// Fetch the meta and go on if it's found.
	if ( $meta = get_metadata_by_mid( $meta_type, $meta_id ) ) {
		$original_key = $meta->meta_key;
		$object_id = $meta->{$column};

		// If a new meta_key (last parameter) was specified, change the meta key,
		// otherwise use the original key in the update statement.
		if ( false === $meta_key ) {
			$meta_key = $original_key;
		} elseif ( ! is_string( $meta_key ) ) {
			return false;
		}

		$meta_subtype = get_object_subtype( $meta_type, $object_id );

		// Sanitize the meta
		$_meta_value = $meta_value;
		$meta_value  = sanitize_meta( $meta_key, $meta_value, $meta_type, $meta_subtype );
		$meta_value  = maybe_serialize( $meta_value );

		// Format the data query arguments.
		$data = array(
			'meta_key' => $meta_key,
			'meta_value' => $meta_value
		);

		// Format the where query arguments.
		$where = array();
		$where[$id_column] = $meta_id;

		/** This action is documented in wp-includes/meta.php */
		do_action( "update_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value );

		if ( 'post' == $meta_type ) {
			/** This action is documented in wp-includes/meta.php */
			do_action( 'update_postmeta', $meta_id, $object_id, $meta_key, $meta_value );
		}

		// Run the update query, all fields in $data are %s, $where is a %d.
		$result = $wpdb->update( $table, $data, $where, '%s', '%d' );
		if ( ! $result )
			return false;

		// Clear the caches.
		wp_cache_delete($object_id, $meta_type . '_meta');

		/** This action is documented in wp-includes/meta.php */
		do_action( "updated_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value );

		if ( 'post' == $meta_type ) {
			/** This action is documented in wp-includes/meta.php */
			do_action( 'updated_postmeta', $meta_id, $object_id, $meta_key, $meta_value );
		}

		return true;
	}

	// And if the meta was not found.
	return false;
}

/**
 * Delete meta data by meta ID
 *
 * @since 3.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $meta_type Type of object metadata is for (e.g., comment, post, term, or user).
 * @param int    $meta_id   ID for a specific meta row
 * @return bool True on successful delete, false on failure.
 */
function delete_metadata_by_mid( $meta_type, $meta_id ) {
	global $wpdb;

	// Make sure everything is valid.
	if ( ! $meta_type || ! is_numeric( $meta_id ) || floor( $meta_id ) != $meta_id ) {
		return false;
	}

	$meta_id = intval( $meta_id );
	if ( $meta_id <= 0 ) {
		return false;
	}

	$table = _get_meta_table( $meta_type );
	if ( ! $table ) {
		return false;
	}

	// object and id columns
	$column = sanitize_key($meta_type . '_id');
	$id_column = 'user' == $meta_type ? 'umeta_id' : 'meta_id';

	// Fetch the meta and go on if it's found.
	if ( $meta = get_metadata_by_mid( $meta_type, $meta_id ) ) {
		$object_id = $meta->{$column};

		/** This action is documented in wp-includes/meta.php */
		do_action( "delete_{$meta_type}_meta", (array) $meta_id, $object_id, $meta->meta_key, $meta->meta_value );

		// Old-style action.
		if ( 'post' == $meta_type || 'comment' == $meta_type ) {
			/**
			 * Fires immediately before deleting post or comment metadata of a specific type.
			 *
			 * The dynamic portion of the hook, `$meta_type`, refers to the meta
			 * object type (post or comment).
			 *
			 * @since 3.4.0
			 *
			 * @param int $meta_id ID of the metadata entry to delete.
			 */
			do_action( "delete_{$meta_type}meta", $meta_id );
		}

		// Run the query, will return true if deleted, false otherwise
		$result = (bool) $wpdb->delete( $table, array( $id_column => $meta_id ) );

		// Clear the caches.
		wp_cache_delete($object_id, $meta_type . '_meta');

		/** This action is documented in wp-includes/meta.php */
		do_action( "deleted_{$meta_type}_meta", (array) $meta_id, $object_id, $meta->meta_key, $meta->meta_value );

		// Old-style action.
		if ( 'post' == $meta_type || 'comment' == $meta_type ) {
			/**
			 * Fires immediately after deleting post or comment metadata of a specific type.
			 *
			 * The dynamic portion of the hook, `$meta_type`, refers to the meta
			 * object type (post or comment).
			 *
			 * @since 3.4.0
			 *
			 * @param int $meta_ids Deleted metadata entry ID.
			 */
			do_action( "deleted_{$meta_type}meta", $meta_id );
		}

		return $result;

	}

	// Meta id was not found.
	return false;
}

// refactored. function update_meta_cache($meta_type, $object_ids) {}
// refactored. function wp_metadata_lazyloader() {}

/**
 * Given a meta query, generates SQL clauses to be appended to a main query.
 *
 * @since 3.2.0
 *
 * @see WP_Meta_Query
 *
 * @param array $meta_query         A meta query.
 * @param string $type              Type of meta.
 * @param string $primary_table     Primary database table name.
 * @param string $primary_id_column Primary ID column name.
 * @param object $context           Optional. The main query object
 * @return array Associative array of `JOIN` and `WHERE` SQL.
 */
function get_meta_sql( $meta_query, $type, $primary_table, $primary_id_column, $context = null ) {
	$meta_query_obj = new WP_Meta_Query( $meta_query );
	return $meta_query_obj->get_sql( $type, $primary_table, $primary_id_column, $context );
}

// refactored. function _get_meta_table($type) {}
// :
// refactored. function sanitize_meta( $meta_key, $meta_value, $object_type, $object_subtype = '' ) {}

/**
 * Registers a meta key.
 *
 * It is recommended to register meta keys for a specific combination of object type and object subtype. If passing
 * an object subtype is omitted, the meta key will be registered for the entire object type, however it can be partly
 * overridden in case a more specific meta key of the same name exists for the same object type and a subtype.
 *
 * If an object type does not support any subtypes, such as users or comments, you should commonly call this function
 * without passing a subtype.
 *
 * @since 3.3.0
 * @since 4.6.0 {@link https://core.trac.wordpress.org/ticket/35658 Modified
 *              to support an array of data to attach to registered meta keys}. Previous arguments for
 *              `$sanitize_callback` and `$auth_callback` have been folded into this array.
 * @since 4.9.8 The `$object_subtype` argument was added to the arguments array.
 *
 * @param string $object_type    Type of object this meta is registered to.
 * @param string $meta_key       Meta key to register.
 * @param array  $args {
 *     Data used to describe the meta key when registered.
 *
 *     @type string $object_subtype    A subtype; e.g. if the object type is "post", the post type. If left empty,
 *                                     the meta key will be registered on the entire object type. Default empty.
 *     @type string $type              The type of data associated with this meta key.
 *                                     Valid values are 'string', 'boolean', 'integer', and 'number'.
 *     @type string $description       A description of the data attached to this meta key.
 *     @type bool   $single            Whether the meta key has one value per object, or an array of values per object.
 *     @type string $sanitize_callback A function or method to call when sanitizing `$meta_key` data.
 *     @type string $auth_callback     Optional. A function or method to call when performing edit_post_meta, add_post_meta, and delete_post_meta capability checks.
 *     @type bool   $show_in_rest      Whether data associated with this meta key can be considered public.
 * }
 * @param string|array $deprecated Deprecated. Use `$args` instead.
 *
 * @return bool True if the meta key was successfully registered in the global array, false if not.
 *                       Registering a meta key with distinct sanitize and auth callbacks will fire those
 *                       callbacks, but will not add to the global registry.
 */
function register_meta( $object_type, $meta_key, $args, $deprecated = null ) {
	global $wp_meta_keys;

	if ( ! is_array( $wp_meta_keys ) ) {
		$wp_meta_keys = array();
	}

	$defaults = array(
		'object_subtype'    => '',
		'type'              => 'string',
		'description'       => '',
		'single'            => false,
		'sanitize_callback' => null,
		'auth_callback'     => null,
		'show_in_rest'      => false,
	);

	// There used to be individual args for sanitize and auth callbacks
	$has_old_sanitize_cb = false;
	$has_old_auth_cb = false;

	if ( is_callable( $args ) ) {
		$args = array(
			'sanitize_callback' => $args,
		);

		$has_old_sanitize_cb = true;
	} else {
		$args = (array) $args;
	}

	if ( is_callable( $deprecated ) ) {
		$args['auth_callback'] = $deprecated;
		$has_old_auth_cb = true;
	}

	/**
	 * Filters the registration arguments when registering meta.
	 *
	 * @since 4.6.0
	 *
	 * @param array  $args        Array of meta registration arguments.
	 * @param array  $defaults    Array of default arguments.
	 * @param string $object_type Object type.
	 * @param string $meta_key    Meta key.
	 */
	$args = apply_filters( 'register_meta_args', $args, $defaults, $object_type, $meta_key );
	$args = wp_parse_args( $args, $defaults );

	$object_subtype = ! empty( $args['object_subtype'] ) ? $args['object_subtype'] : '';

	// If `auth_callback` is not provided, fall back to `is_protected_meta()`.
	if ( empty( $args['auth_callback'] ) ) {
		if ( is_protected_meta( $meta_key, $object_type ) ) {
			$args['auth_callback'] = '__return_false';
		} else {
			$args['auth_callback'] = '__return_true';
		}
	}

	// Back-compat: old sanitize and auth callbacks are applied to all of an object type.
	if ( is_callable( $args['sanitize_callback'] ) ) {
		if ( ! empty( $object_subtype ) ) {
			add_filter( "sanitize_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $args['sanitize_callback'], 10, 4 );
		} else {
			add_filter( "sanitize_{$object_type}_meta_{$meta_key}", $args['sanitize_callback'], 10, 3 );
		}
	}

	if ( is_callable( $args['auth_callback'] ) ) {
		if ( ! empty( $object_subtype ) ) {
			add_filter( "auth_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $args['auth_callback'], 10, 6 );
		} else {
			add_filter( "auth_{$object_type}_meta_{$meta_key}", $args['auth_callback'], 10, 6 );
		}
	}

	// Global registry only contains meta keys registered with the array of arguments added in 4.6.0.
	if ( ! $has_old_auth_cb && ! $has_old_sanitize_cb ) {
		unset( $args['object_subtype'] );

		$wp_meta_keys[ $object_type ][ $object_subtype ][ $meta_key ] = $args;

		return true;
	}

	return false;
}

/**
 * Checks if a meta key is registered.
 *
 * @since 4.6.0
 * @since 4.9.8 The `$object_subtype` parameter was added.
 *
 * @param string $object_type    The type of object.
 * @param string $meta_key       The meta key.
 * @param string $object_subtype Optional. The subtype of the object type.
 *
 * @return bool True if the meta key is registered to the object type and, if provided,
 *              the object subtype. False if not.
 */
function registered_meta_key_exists( $object_type, $meta_key, $object_subtype = '' ) {
	$meta_keys = get_registered_meta_keys( $object_type, $object_subtype );

	return isset( $meta_keys[ $meta_key ] );
}

/**
 * Unregisters a meta key from the list of registered keys.
 *
 * @since 4.6.0
 * @since 4.9.8 The `$object_subtype` parameter was added.
 *
 * @param string $object_type    The type of object.
 * @param string $meta_key       The meta key.
 * @param string $object_subtype Optional. The subtype of the object type.
 * @return bool True if successful. False if the meta key was not registered.
 */
function unregister_meta_key( $object_type, $meta_key, $object_subtype = '' ) {
	global $wp_meta_keys;

	if ( ! registered_meta_key_exists( $object_type, $meta_key, $object_subtype ) ) {
		return false;
	}

	$args = $wp_meta_keys[ $object_type ][ $object_subtype ][ $meta_key ];

	if ( isset( $args['sanitize_callback'] ) && is_callable( $args['sanitize_callback'] ) ) {
		if ( ! empty( $object_subtype ) ) {
			remove_filter( "sanitize_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $args['sanitize_callback'] );
		} else {
			remove_filter( "sanitize_{$object_type}_meta_{$meta_key}", $args['sanitize_callback'] );
		}
	}

	if ( isset( $args['auth_callback'] ) && is_callable( $args['auth_callback'] ) ) {
		if ( ! empty( $object_subtype ) ) {
			remove_filter( "auth_{$object_type}_meta_{$meta_key}_for_{$object_subtype}", $args['auth_callback'] );
		} else {
			remove_filter( "auth_{$object_type}_meta_{$meta_key}", $args['auth_callback'] );
		}
	}

	unset( $wp_meta_keys[ $object_type ][ $object_subtype ][ $meta_key ] );

	// Do some clean up
	if ( empty( $wp_meta_keys[ $object_type ][ $object_subtype ] ) ) {
		unset( $wp_meta_keys[ $object_type ][ $object_subtype ] );
	}
	if ( empty( $wp_meta_keys[ $object_type ] ) ) {
		unset( $wp_meta_keys[ $object_type ] );
	}

	return true;
}

/**
 * Retrieves a list of registered meta keys for an object type.
 *
 * @since 4.6.0
 * @since 4.9.8 The `$object_subtype` parameter was added.
 *
 * @param string $object_type    The type of object. Post, comment, user, term.
 * @param string $object_subtype Optional. The subtype of the object type.
 * @return array List of registered meta keys.
 */
function get_registered_meta_keys( $object_type, $object_subtype = '' ) {
	global $wp_meta_keys;

	if ( ! is_array( $wp_meta_keys ) || ! isset( $wp_meta_keys[ $object_type ] ) || ! isset( $wp_meta_keys[ $object_type ][ $object_subtype ] ) ) {
		return array();
	}

	return $wp_meta_keys[ $object_type ][ $object_subtype ];
}

/**
 * Retrieves registered metadata for a specified object.
 *
 * The results include both meta that is registered specifically for the
 * object's subtype and meta that is registered for the entire object type.
 *
 * @since 4.6.0
 *
 * @param string $object_type Type of object to request metadata for. (e.g. comment, post, term, user)
 * @param int    $object_id   ID of the object the metadata is for.
 * @param string $meta_key    Optional. Registered metadata key. If not specified, retrieve all registered
 *                            metadata for the specified object.
 * @return mixed A single value or array of values for a key if specified. An array of all registered keys
 *               and values for an object ID if not. False if a given $meta_key is not registered.
 */
function get_registered_metadata( $object_type, $object_id, $meta_key = '' ) {
	$object_subtype = get_object_subtype( $object_type, $object_id );

	if ( ! empty( $meta_key ) ) {
		if ( ! empty( $object_subtype ) && ! registered_meta_key_exists( $object_type, $meta_key, $object_subtype ) ) {
			$object_subtype = '';
		}

		if ( ! registered_meta_key_exists( $object_type, $meta_key, $object_subtype ) ) {
			return false;
		}

		$meta_keys     = get_registered_meta_keys( $object_type, $object_subtype );
		$meta_key_data = $meta_keys[ $meta_key ];

		$data = get_metadata( $object_type, $object_id, $meta_key, $meta_key_data['single'] );

		return $data;
	}

	$data = get_metadata( $object_type, $object_id );
	if ( ! $data ) {
		return array();
	}

	$meta_keys = get_registered_meta_keys( $object_type );
	if ( ! empty( $object_subtype ) ) {
		$meta_keys = array_merge( $meta_keys, get_registered_meta_keys( $object_type, $object_subtype ) );
	}

	return array_intersect_key( $data, $meta_keys );
}

// refactored. function _wp_register_meta_args_whitelist( $args, $default_args ) {}
// refactored. function get_object_subtype( $object_type, $object_id ) {}
