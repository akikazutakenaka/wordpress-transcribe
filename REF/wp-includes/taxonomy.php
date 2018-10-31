<?php
/**
 * Core Taxonomy API
 *
 * @package    WordPress
 * @subpackage Taxonomy
 */

/**
 * Return the names or objects of the taxonomies which are registered for the requested object or object type, such as a post object or post type name.
 *
 * Example:
 *
 *     $taxonomies = get_object_taxonomies( 'post' );
 *
 * This results in:
 *
 *     Array( 'category', 'post_tag' );
 *
 * @since  2.3.0
 * @global array $wp_taxonomies The registered taxonomies.
 *
 * @param  array|string|WP_Post $object Name of the type of taxonomy object, or an object (row from posts).
 * @param  string               $output Optional.
 *                                      The type of output to return in the array.
 *                                      Accepts either taxonomy 'names' or 'objects'.
 *                                      Default 'names'.
 * @return array                The names of all taxonomy of $object_type.
 */
function get_object_taxonomies( $object, $output = 'names' )
{
	global $wp_taxonomies;

	if ( is_object( $object ) ) {
		if ( $object->post_type == 'attachment' ) {
			return get_attachment_taxonomies( $object, $output );
		}

		$object = $object->post_type;
	}

	$object = ( array ) $object;
	$taxonomies = array();

	foreach ( ( array ) $wp_taxonomies as $tax_name => $tax_obj ) {
		if ( array_intersect( $object, ( array ) $tax_obj->object_type ) ) {
			if ( 'names' == $output ) {
				$taxonomies[] = $tax_name;
			} else {
				$taxonomies[ $tax_name ] = $tax_obj;
			}
		}
	}

	return $taxonomies;
}

/**
 * Retrieves the taxonomy object of $taxonomy.
 *
 * The get_taxonomy function will first check that the parameter string given is a taxonomy object and if it is, it will return it.
 *
 * @since  2.3.0
 * @global array $wp_taxonomies The registered taxonomies.
 *
 * @param  string            $taxonomy Name of taxonomy object to return.
 * @return WP_Taxonomy|false The Taxonomy Object or false if $taxonomy doesn't exist.
 */
function get_taxonomy( $taxonomy )
{
	global $wp_taxonomies;

	return ! taxonomy_exists( $taxonomy )
		? FALSE
		: $wp_taxonomies[ $taxonomy ];
}

/**
 * Checks that the taxonomy name exists.
 *
 * Formerly is_taxonomy(), introduced in 2.3.0.
 *
 * @since  3.0.0
 * @global array $wp_taxonomies The registered taxonomies.
 *
 * @param  string $taxonomy Name of taxonomy object.
 * @return bool   Whether the taxonomy exists.
 */
function taxonomy_exists( $taxonomy )
{
	global $wp_taxonomies;
	return isset( $wp_taxonomies[ $taxonomy ] );
}

/**
 * Get all Term data from database by Term ID.
 *
 * The usage of the get_term function is to apply filters to a term object.
 * It is possible to get a term object from the database before applying the filters.
 *
 * $term ID must be part of $taxonomy, to get from the database.
 * Failure, might be able to be captured by the hooks.
 * Failure would be the same value as $wpdb returns for the get_row method.
 *
 * There are two hooks, one is specifically for each term, named 'get_term', and the second is for the taxonomy name, 'term_$taxonomy'.
 * Both hooks gets the term object, and the taxonomy name as parameters.
 * Both hooks are expected to return a Term object.
 *
 * {@see 'get_term'} hook - Takes two parameters the term Object and the taxonomy name.
 * Must return term object.
 * Used in get_term() as a catch-all filter for every $term.
 *
 * {@see 'get_$taxonomy'} hook - Takes two parameters the term Object and the taxonomy name.
 * Must return term object.
 * $taxonomy will be the taxonomy name, so for example, if 'category', it would be 'get_category' as the filter name.
 * Useful for custom taxonomies or plugging into default taxonomies.
 *
 * @todo  Better formatting for DocBlock
 * @since 2.3.0
 * @since 4.4.0 Converted to return a WP_Term object if `$output` is `OBJECT`.
 *              The `$taxonomy` parameter was made optional.
 * @see   sanitize_term_field() The $context param lists the available values for get_term_by() $filter param.
 *
 * @param  int|WP_Term|object          $term     If integer, term data will be fetched from the database, or from the cache if available.
 *                                               If stdClass object (as in the results of a database query), will apply filters and return a `WP_Term` object corresponding to the `$term` data.
 *                                               If `WP_Term`, will return `$term`.
 * @param  string                      $taxonomy Optional.
 *                                               Taxonomy name that $term is part of.
 * @param  string                      $output   Optional.
 *                                               The required return type.
 *                                               One of OBJECT, ARRAY_A, or ARRAY_N, which correspond to a WP_Term object, an associative array, or a numeric array, respectively.
 *                                               Default OBJECT.
 * @param  string                      $filter   Optional, default is raw or no WordPress defined filter will applied.
 * @return array|WP_Term|WP_Error|null Object of the type specified by `$output` on success.
 *                                     When `$output` is 'OBJECT', a WP_Term instance is returned.
 *                                     If taxonomy does not exist, a WP_Error is returned.
 *                                     Returns null for miscellaneous failure.
 */
function get_term( $term, $taxonomy = '', $output = OBJECT, $filter = 'raw' )
{
	if ( empty( $term ) ) {
		return new WP_Error( 'invalid_term', __( 'Empty Term.' ) );
	}

	if ( $taxonomy && ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
	}

	$_term = $term instanceof WP_Term
		? $term
		: ( is_object( $term )
			? ( empty( $term->filter ) || 'raw' === $term->filter
				? new WP_Term( sanitize_term( $term, $taxonomy, 'raw' ) )
				: WP_Term::get_instance( $term->term_id ) )
			: WP_Term::get_instance( $term, $taxonomy ) );

	if ( is_wp_error( $_term ) ) {
		return $_term;
	} elseif ( ! $_term ) {
		return NULL;
	}

	/**
	 * Filters a term.
	 *
	 * @since 2.3.0
	 * @since 4.4.0 `$_term` can now also be a WP_Term object.
	 *
	 * @param int|WP_Term $_term    Term object or ID.
	 * @param string      $taxonomy The taxonomy slug.
	 */
	$_term = apply_filters( 'get_term', $_term, $taxonomy );

	/**
	 * Filters a taxonomy.
	 *
	 * The dynamic portion of the filter name, `$taxonomy`, refers to the taxonomy slug.
	 *
	 * @since 2.3.0
	 * @since 4.4.0 `$_term` can now also be a WP_Term object.
	 *
	 * @param int|WP_Term $_term    Term object or ID.
	 * @param string      $taxonomy The taxonomy slug.
	 */
	$_term = apply_filters( "get_{$taxonomy}", $_term, $taxonomy );

	// Bail if a filter callback has changed the type of the `$_term` object.
	if ( ! ( $_term instanceof WP_Term ) ) {
		return $_term;
	}

	// Sanitize term, according to the specified filter.
	$_term->filter( $filter );

	return $output == ARRAY_A
		? $_term->to_array()
		: ( $output == ARRAY_N
			? array_values( $_term->to_array() )
			: $_term );
}

/**
 * Updates metadata cache for list of term IDs.
 *
 * Performs SQL query to retrieve all metadata for the terms matching `$term_ids` and stores them in the cache.
 * Subsequent calls to `get_term_meta()` will not need to query the database.
 *
 * @since 4.4.0
 *
 * @param  array       $term_ids List of term IDs.
 * @return array|false Returns false if there is nothing to update.
 *                     Returns an array of metadata on success.
 */
function update_termmeta_cache( $term_ids )
{
	// Bail if term meta table is not installed.
	if ( get_option( 'db_version' ) < 34370 ) {
		return;
	}

	return update_meta_cache( 'term', $term_ids );
}

/**
 * Sanitize Term all fields.
 *
 * Relies on sanitize_term_field() to sanitize the term.
 * The difference is that this function will sanitize <strong>all</strong> fields.
 * The context is based on sanitize_term_field().
 *
 * The $term is expected to be either an array or an object.
 *
 * @since 2.3.0
 *
 * @param  array|object $term     The term to check.
 * @param  string       $taxonomy The taxonomy name to use.
 * @param  string       $context  Optional.
 *                                Context in which to sanitize the term.
 *                                Accepts 'edit', 'db', 'display', 'attribute', or 'js'.
 *                                Default 'display'.
 * @return array|object Term with all fields sanitized.
 */
function sanitize_term( $term, $taxonomy, $context = 'display' )
{
	$fields = array( 'term_id', 'name', 'description', 'slug', 'count', 'parent', 'term_group', 'term_taxonomy_id', 'object_id' );
	$do_object = is_object( $term );

	$term_id = $do_object
		? $term->term_id
		: ( isset( $term['term_id'] )
			? $term['term_id']
			: 0 );

	foreach ( ( array ) $fields as $field ) {
		if ( $do_object ) {
			if ( isset( $term->$field ) ) {
				$term->$field = sanitize_term_field( $field, $term->$field, $term_id, $taxonomy, $context );
			}
		} else {
			if ( isset( $term[ $field ] ) ) {
				$term[ $field ] = sanitize_term_field( $field, $term[ $field ], $term_id, $taxonomy, $context );
			}
		}
	}

	if ( $do_object ) {
		$term->filter = $context;
	} else {
		$term['filter'] = $context;
	}

	return $term;
}

/**
 * Cleanse the field value in the term based on the context.
 *
 * Passing a term field value through the function should be assumed to have cleansed the value for whatever context the term field is going to be used.
 *
 * If no context or an unsupported context is given, then default filters will be applied.
 *
 * There are enough filters for each context to support a custom filtering without creating your own filter function.
 * Simply create a function that hooks into the filter you need.
 *
 * @since 2.3.0
 *
 * @param  string $field    Term field to sanitize.
 * @param  string $value    Search for this term value.
 * @param  int    $term_id  Term ID.
 * @param  string $taxonomy Taxonomy name.
 * @param  string $context  Context in which to sanitize the term field.
 *                          Accepts 'edit', 'db', 'display', 'attribute', or 'js'.
 * @return mixed  Sanitized field.
 */
function sanitize_term_field( $field, $value, $term_id, $taxonomy, $context )
{
	$int_fields = array( 'parent', 'term_id', 'count', 'term_group', 'term_taxonomy_id', 'object_id' );

	if ( in_array( $field, $int_fields ) ) {
		$value = ( int ) $value;

		if ( $value < 0 ) {
			$value = 0;
		}
	}

	if ( 'raw' == $context ) {
		return $value;
	}

	if ( 'edit' == $context ) {
		/**
		 * Filters a term field to edit before it is sanitized.
		 *
		 * The dynamic portion of the filter name, `$field`, refers to the term field.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed  $value    Value of the term field.
		 * @param int    $term_id  Term ID.
		 * @param string $taxonomy Taxonomy slug.
		 */
		$value = apply_filters( "edit_term_{$field}", $value, $term_id, $taxonomy );

		/**
		 * Filters the taxonomy field to edit before it is sanitized.
		 *
		 * The dynamic portions of the filter name, `$taxonomy` and `$field`, refer to the taxonomy slug and taxonomy field, respectively.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed $value   Value of the taxonomy field to edit.
		 * @param int   $term_id Term ID.
		 */
		$value = apply_filters( "edit_{$taxonomy}_{$field}", $value, $term_id );

		$value = 'description' == $field
			? esc_html( $value )
			: esc_attr( $value );
	} elseif ( 'db' == $context ) {
		/**
		 * Filters a term field value before it is sanitized.
		 *
		 * The dynamic portion of the filter name, `$field`, refers to the term field.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed  $value    Value of the term field.
		 * @param string $taxonomy Taxonomy slug.
		 */
		$value = apply_filters( "pre_term_{$field}", $value, $taxonomy );

		/**
		 * Filters a taxonomy field before it is sanitized.
		 *
		 * The dynamic portions of the filter name, `$taxonomy` and `$field`, refer to the taxonomy slug and field name, respectively.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed $value Value of the taxonomy field.
		 */
		$value = apply_filters( "pre_{$taxonomy}_{$field}", $value );

		// Back compat filters.
		if ( 'slug' == $field ) {
			/**
			 * Filters the category nicename before it is sanitized.
			 *
			 * Use the {@see 'pre_$taxonomy_$field'} hook instead.
			 *
			 * @since 2.0.3
			 *
			 * @param string $value The category nicename.
			 */
			$value = apply_filters( 'pre_category_nicename', $value );
		}
	} elseif ( 'rss' == $context ) {
		/**
		 * Filters the term field for use in RSS.
		 *
		 * The dynamic portion of the filter name, `$field`, refers to the term field.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed  $value    Value of the term field.
		 * @param string $taxonomy Taxonomy slug.
		 */
		$value = apply_filters( "term_{$field}_rss", $value, $taxonomy );

		/**
		 * Filters the taxonomy field for use in RSS.
		 *
		 * The dynamic portions of the hook name, `$taxonomy` and `$field`, refer to the taxonomy slug and field name, respectively.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed $value Value of the taxonomy field.
		 */
		$value = apply_filters( "{$taxonomy}_{$field}_rss", $value );
	} else {
		// Use display filters by default.

		/**
		 * Filters the term field sanitized for display.
		 *
		 * The dynamic portion of the filter name, `$field`, refers to the term field name.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed  $value    Value of the term field.
		 * @param int    $term_id  Term ID.
		 * @param string $taxonomy Taxonomy slug.
		 * @param string $context  Context to retrieve the term field value.
		 */
		$value = apply_filters( "term_{$field}", $value, $term_id, $taxonomy, $context );

		/**
		 * Filters the taxonomy field sanitized for display.
		 *
		 * The dynamic portions of the filter name, `$taxonomy` and `$field`, refer to the taxonomy slug and taxonomy name, respectively.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed  $value   Value of the taxonomy field.
		 * @param int    $term_id Term ID.
		 * @param string $context Context to retrieve the taxonomy field value.
		 */
		$value = apply_filters( "{$taxonomy}_{$field}", $value, $term_id, $context );
	}

	$value = 'attribute' == $context
		? esc_attr( $value )
		: esc_js( $value );

	return $value;
}

/**
 * Retrieves the terms associated with the given object(s), in the supplied taxonomies.
 *
 * @since 2.3.0
 * @since 4.2.0 Added support for 'taxonomy', 'parent', and 'term_taxonomy_id' values of `$orderby`.
 *              Introduced `$parent` argument.
 * @since 4.4.0 Introduced `$meta_query` and `$update_term_meta_cache` arguments.
 *              When `$fields` is 'all' or 'all_with_object_id', an array of `WP_Term` objects will be returned.
 * @since 4.7.0 Refactored to use WP_Term_Query, and to support any WP_Term_Query arguments.
 *
 * @param  int|array      $object_ids The ID(s) of the object(s) to retrieve.
 * @param  string|array   $taxonomies The taxonomies to retrieve terms from.
 * @param  array|string   $args       See WP_Term_Query::__construct() for supported arguments.
 * @return array|WP_Error The requested term data or empty array if no terms found.
 *                        WP_Error if any of the $taxonomies don't exist.
 */
function wp_get_object_terms( $object_ids, $taxonomies, $args = array() )
{
	if ( empty( $object_ids ) || empty( $taxonomies ) ) {
		return array();
	}

	if ( ! is_array( $taxonomies ) ) {
		$taxonomies = array( $taxonomies );
	}

	foreach ( $taxonomies as $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
		}
	}

	if ( ! is_array( $object_ids ) ) {
		$object_ids = array( $object_ids );
	}

	$object_ids = array_map( 'intval', $object_ids );
	$args = wp_parse_args( $args );

	/**
	 * Filter arguments for retrieving object terms.
	 *
	 * @since 4.9.0
	 *
	 * @param array        $args       An array of arguments for retrieving terms for the given object(s).
	 *                                 See {@see wp_get_object_terms()} for details.
	 * @param int|array    $object_ids Object ID or array of IDs.
	 * @param string|array $taxonomies The taxonomies to retrieving terms from.
	 */
	$args = apply_filters( 'wp_get_object_terms_args', $args, $object_ids, $taxonomies );

	// When one or more queried taxonomies is registered with an 'args' array, those params override the `$args` passed to this function.
	$terms = array();

	if ( count( $taxonomies ) > 1 ) {
		foreach ( $taxonomies as $index => $taxonomy ) {
			$t = get_taxonomy( $taxonomy );
// wp-includes/category-template.php -> @NOW 010
		}
	}
}

/**
 * Retrieves the taxonomy relationship to the term object id.
 *
 * Upstream functions (like get_the_terms() and is_object_in_term()) are responsible for populating the object-term relationship cache.
 * The current function only fetched relationship data that is already in the cache.
 *
 * @since 2.3.0
 * @since 4.7.0 Returns a WP_Error object if get_term() returns an error for any of the matched terms.
 *
 * @param  int                 $id       Term object ID.
 * @param  string              $taxonomy Taxonomy name.
 * @return bool|array|WP_Error Array of `WP_Term` objects, if cached.
 *                             False if cache is empty for `$taxonomy` and `$id`.
 *                             WP_Error if get_term() returns an error object for any term.
 */
function get_object_term_cache( $id, $taxonomy )
{
	$_term_ids = wp_cache_get( $id, "{$taxonomy}_relationships" );

	// We leave the priming of relationship caches to upstream functions.
	if ( FALSE === $_term_ids ) {
		return FALSE;
	}

	// Backward compatibility for if a plugin is putting objects into the cache, rather than IDs.
	$term_ids = array();

	foreach ( $_term_ids as $term_id ) {
		if ( is_numeric( $term_id ) ) {
			$term_ids[] = intval( $term_id );
		} elseif ( isset( $term_id->term_id ) ) {
			$term_ids[] = intval( $term_id->term_id );
		}
	}

	// Fill the term objects.
	_prime_term_caches( $term_ids );

	$terms = array();

	foreach ( $term_ids as $term_id ) {
		$term = get_term( $term_id, $taxonomy );

		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$terms[] = $term;
	}

	return $terms;
}

/**
 * Updates Terms to Taxonomy in cache.
 *
 * @since 2.3.0
 *
 * @param array  $terms    List of term objects to change.
 * @param string $taxonomy Optional.
 *                         Update Term to this taxonomy in cache.
 *                         Default empty.
 */
function update_term_cache( $terms, $taxonomy = '' )
{
	foreach ( ( array ) $terms as $term ) {
		// Create a copy in case the array was passed by reference.
		$_term = clone $term;

		// Object ID should not be cached.
		unset( $_term->object_id );

		wp_cache_add( $term->term_id, $_term, 'terms' );
	}
}

/**
 * Adds any terms from the given IDs to the cache that do not already exist in cache.
 *
 * @since  4.6.0
 * @access private
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array $term_ids          Array of term IDs.
 * @param bool  $update_meta_cache Optional.
 *                                 Whether to update the meta cache.
 *                                 Default true.
 */
function _prime_term_caches( $term_ids, $update_meta_cache = TRUE )
{
	global $wpdb;
	$non_cached_ids = _get_non_cached_ids( $term_ids, 'terms' );

	if ( ! empty( $non_cached_ids ) ) {
		$fresh_terms = $wpdb->get_results( sprintf( <<<EOQ
SELECT t.*, tt.*
FROM $wpdb->terms AS t
INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
WHERE t.term_id IN ( %s )
EOQ
				, join( ",", array_map( 'intval', $non_cached_ids ) ) ) );
		update_term_cache( $fresh_terms, $update_meta_cache );

		if ( $update_meta_cache ) {
			update_termmeta_cache( $non_cached_ids );
		}
	}
}

/**
* Determine if the given object type is associated with the given taxonomy.
*
* @since 3.0.0
*
* @param  string $object_type Object type string.
* @param  string $taxonomy    Single taxonomy name.
* @return bool   True if object is associated with the taxonomy, otherwise false.
*/
function is_object_in_taxonomy( $object_type, $taxonomy )
{
	$taxonomies = get_object_taxonomies( $object_type );

	return empty( $taxonomies )
		? FALSE
		: in_array( $taxonomy, $taxonomies );
}
