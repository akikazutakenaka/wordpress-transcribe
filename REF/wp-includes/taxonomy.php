<?php
/**
 * Core Taxonomy API
 *
 * @package    WordPress
 * @subpackage Taxonomy
 */

/**
 * Retrieves a list of registered taxonomy names or objects.
 *
 * @since  3.0.0
 * @global array $wp_taxonomies The registered taxonomies.
 *
 * @param  array  $args     Optional.
 *                          An array of `key => value` arguments to match against the taxonomy objects.
 *                          Default empty array.
 * @param  string $output   Optional.
 *                          The type of output to return in the array.
 *                          Accepts either taxonomy 'names' or 'objects'.
 *                          Default 'names'.
 * @param  array  $operator Optional.
 *                          The logical operation to perform.
 *                          Accepts 'and' or 'or'.
 *                          'or' means only one element from the array needs to match; 'and' means all elements must match.
 *                          Default 'and'.
 * @return array  A list of taxonomy names or objects.
 */
function get_taxonomies( $args = array(), $output = 'names', $operator = 'and' )
{
	global $wp_taxonomies;

	$field = 'names' == $output
		? 'name'
		: FALSE;

	return wp_filter_object_list( $wp_taxonomies, $args, $operator, $field );
}

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
 * Whether the taxonomy object is hierarchical.
 *
 * Checks to make sure that the taxonomy is an object first.
 * Then Gets the object, and finally returns the hierarchical value in the object.
 *
 * A false return value might also mean that the taxonomy does not exist.
 *
 * @since 2.3.0
 *
 * @param  string $taxonomy Name of taxonomy object.
 * @return bool   Whether the taxonomy is hierarchical.
 */
function is_taxonomy_hierarchical( $taxonomy )
{
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return FALSE;
	}

	$taxonomy = get_taxonomy( $taxonomy );
	return $taxonomy->hierarchical;
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
 * Get all Term data from database by Term field and data.
 *
 * Warning: $value is not escaped for 'name' $field.
 * You must do it yourself, if required.
 *
 * The default $field is 'id', therefore it is possible to also use null for field, but not recommended that you do so.
 *
 * If $value does not exist, the return value will be false.
 * If $taxonomy exists and $field and $value combinations exist, the Term will be returned.
 *
 * This function will always return the first term that matches the `$field`-`$value`-`$taxonomy` combination specified in the parameters.
 * If your query is likely to match more than one term (as is likely to be the case when `$field` is 'name', for example), consider using get_terms() instead; that way, you will get all matching terms, and can provide your own logic for deciding which one was intended.
 *
 * @todo  Better formatting for DocBlock.
 * @since 2.3.0
 * @since 4.4.0 `$taxonomy` is optional if `$field` is 'term_taxonomy_id'.
 *              Converted to return a WP_Term object if `$output` is `OBJECT`.
 * @see   sanitize_term_field() The $context param lists the available values for get_term_by() $filter param.
 *
 * @param  string              $field    Either 'slug', 'name', 'id' (term_id), or 'term_taxonomy_id'.
 * @param  string|int          $value    Search for this term value.
 * @param  string              $taxonomy Taxonomy name.
 *                                       Optional, if `$field` is 'term_taxonomy_id'.
 * @param  string              $output   Optional.
 *                                       The required return type.
 *                                       One of OBJECT, ARRAY_A, or ARRAY_N, which correspond to a WP_Term object, an associative array, or a numeric array, respectively.
 *                                       Default OBJECT.
 * @param  string              $filter   Optional, default is raw or no WordPress defined filter will applied.
 * @return WP_Term|array|false WP_Term instance (or array) on success.
 *                             Will return false if `$taxonomy` does not exist or `$term` was not found.
 */
function get_term_by( $field, $value, $taxonomy = '', $output = OBJECT, $filter = 'raw' )
{
	// 'term_taxonomy_id' lookups don't require taxonomy checks.
	if ( 'term_taxonomy_id' !== $field && ! taxonomy_exists( $taxonomy ) ) {
		return FALSE;
	}

	// No need to perform a query for empty 'slug' or 'name'.
	if ( 'slug' === $field || 'name' === $field ) {
		$value = ( string ) $value;

		if ( 0 === strlen( $value ) ) {
			return FALSE;
		}
	}

	if ( 'id' === $field || 'term_id' === $field ) {
		$term = get_term( ( int ) $value, $taxonomy, $output, $filter );

		if ( is_wp_error( $term ) || NULL === $term ) {
			$term = FALSE;
		}

		return $term;
	}

	$args = array(
		'get'                    => 'all',
		'number'                 => 1,
		'taxonomy'               => $taxonomy,
		'update_term_meta_cache' => FALSE,
		'orderby'                => 'none',
		'suppress_filter'        => TRUE
	);

	switch ( $field ) {
		case 'slug':
			$args['slug'] = $value;
			break;

		case 'name':
			$args['name'] = $value;
			break;

		case 'term_taxonomy_id':
			$args['term_taxonomy_id'] = $value;
			unset( $args['taxonomy'] );
			break;

		default:
			return FALSE;
	}

	$terms = get_terms( $args );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return FALSE;
	}

	$term = array_shift( $terms );

	// In the case of 'term_taxonomy_id', override the provided `$taxonomy` with whatever we find in the db.
	if ( 'term_taxonomy_id' === $field ) {
		$taxonomy = $term->taxonomy;
	}

	return get_term( $term, $taxonomy, $output, $filter );
}

/**
 * Merge all term children into a single array of their IDs.
 *
 * This recursive function will merge all of the children of $term into the same array of term IDs.
 * Only useful for taxonomies which are hierarchical.
 *
 * Will return an empty array if $term does not exist in $taxonomy.
 *
 * @since 2.3.0
 *
 * @param  int            $term_id  ID of Term to get children.
 * @param  string         $taxonomy Taxonomy Name.
 * @return array|WP_Error List of Term IDs.
 *                        WP_Error returned if `$taxonomy` does not exist.
 */
function get_term_children( $term_id, $taxonomy )
{
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
	}

	$term_id = intval( $term_id );
	$terms = _get_term_hierarchy( $taxonomy );

	if ( ! isset( $terms[ $term_id ] ) ) {
		return array();
	}

	$children = $terms[ $term_id ];

	foreach ( ( array ) $terms[ $term_id ] as $child ) {
		if ( $term_id == $child ) {
			continue;
		}

		if ( isset( $terms[ $child ] ) ) {
			$children = array_merge( $children, get_term_children( $child, $taxonomy ) );
		}
	}

	return $children;
}

/**
 * Retrieve the terms in a given taxonomy or list of taxonomies.
 *
 * You can fully inject any customizations to the query before it is sent, as well as control the output with a filter.
 *
 * The {@see 'get_terms'} filter will be called when the cache has the term and will pass the found term along with the array of $taxonomies and array of $args.
 * This filter is also called before the array of terms is passed and will pass the array of terms, along with the $taxonomies and $args.
 *
 * The {@see 'list_terms_exclusions'} filter passes the compiled exclusions along with the $args.
 *
 * The {@see 'get_terms_orderby'} filter passes the `ORDER BY` clause for the query along with the $args array.
 *
 * Prior to 4.5.0, the first parameter of `get_terms()` was a taxonomy or list of taxonomies:
 *
 *     $terms = get_terms( 'post_tag', array( 'hide_empty' => FALSE ) );
 *
 * Since 4.5.0, taxonomies should be passed via the 'taxonomy' argument in the `$args` array:
 *
 *     $terms = get_terms( array(
 *             'taxonomy'   => 'post_tag',
 *             'hide_empty' => FALSE
 *         ) );
 *
 * @since    2.3.0
 * @since    4.2.0 Introduced 'name' and 'childless' parameters.
 * @since    4.4.0 Introduced the ability to pass 'term_id' as an alias of 'id' for the `orderby` parameter.
 *                 Introduced the 'meta_query' and 'update_term_meta_cache' parameters.
 *                 Converted to return a list of WP_Term objects.
 * @since    4.5.0 Changed the function signature so that the `$args` array can be provided as the first parameter.
 *                 Introduced 'meta_key' and 'meta_value' parameters.
 *                 Introduced the ability to order results by metadata.
 * @since    4.8.0 Introduced 'suppress_filter' parameter.
 * @internal The `$deprecated` parameter is parsed for backward compatibility only.
 *
 * @param  string|array       $args       Optional.
 *                                        Array or string of arguments.
 *                                        See WP_Term_Query::__construct() for information on accepted arguments.
 *                                        Default empty.
 * @param  array              $deprecated Argument array, when using the legacy function parameter format.
 *                                        If present, this parameter will be interpreted as `$args`, and the first function parameter will be parsed as a taxonomy or array of taxonomies.
 * @return array|int|WP_Error List of WP_Term instances and their children.
 *                            Will return WP_Error, if any of $taxonomies do not exist.
 */
function get_terms( $args = array(), $deprecated = '' )
{
	$term_query = new WP_Term_Query();
	$defaults = array( 'suppress_filter' => FALSE );

	/**
	 * Legacy argument format ($taxonomy, $args) takes precedence.
	 *
	 * We detect legacy argument format by checking if
	 * (a) a second non-empty parameter is passed, or
	 * (b) the first parameter shares no keys with the default array (i.e. it's a list of taxonomies)
	 */
	$_args = wp_parse_args( $args );
	$key_intersect = array_intersect_key( $term_query->query_var_defaults, ( array ) $_args );
	$do_legacy_args = $deprecated || empty( $key_intersect );

	if ( $do_legacy_args ) {
		$taxonomies = ( array ) $args;
		$args = wp_parse_args( $deprecated, $defaults );
		$args['taxonomy'] = $taxonomies;
	} else {
		$args = wp_parse_args( $args, $defaults );

		if ( isset( $args['taxonomy'] ) && NULL !== $args['taxonomy'] ) {
			$args['taxonomy'] = ( array ) $args['taxonomy'];
		}
	}

	if ( ! empty( $args['taxonomy'] ) ) {
		foreach ( $args['taxonomy'] as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
			}
		}
	}

	// Don't pass suppress_filter to WP_Term_Query.
	$suppress_filter = $args['suppress_filter'];
	unset( $args['suppress_filter'] );

	$terms = $term_query->query( $args );

	// Count queries are not filtered, for legacy reasons.
	if ( ! is_array( $terms ) ) {
		return $terms;
	}

	if ( $suppress_filter ) {
		return $terms;
	}

	/**
	 * Filters the found terms.
	 *
	 * @since 2.3.0
	 * @since 4.6.0 Added the `$term_query` parameter.
	 *
	 * @param array         $terms      Array of found terms.
	 * @param array         $taxonomies An array of taxonomies.
	 * @param array         $args       An array of get_terms() arguments.
	 * @param WP_Term_Query $term_query The WP_Term_Query object.
	 */
	return apply_filters( 'get_terms', $terms, $term_query->query_vars['taxonomy'], $term_query->query_vars, $term_query );
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
 * Check if Term exists.
 *
 * Formerly is_term(), introduced in 2.3.0.
 *
 * @since  3.0.0
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param  int|string $term     The term to check.
 *                              Accepts term ID, slug, or name.
 * @param  string     $taxonomy The taxonomy name to use.
 * @param  int        $parent   Optional.
 *                              ID of parent term under which to confine the exists.
 * @return mixed      Returns null if the term does not exist.
 *                    Returns the term ID if no taxonomy is specified and the term ID exists.
 *                    Returns an array of the term ID and the term taxonomy ID the taxonomy is specified and the pairing exists.
 */
function term_exists( $term, $taxonomy = '', $parent = NULL )
{
	global $wpdb;
	$select = <<<EOQ
SELECT term_id
FROM $wpdb->terms AS t
WHERE 
EOQ;
	$tax_select = <<<EOQ
SELECT tt.term_id, tt.term_taxonomy_id
FROM $wpdb->terms AS t
INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id
WHERE 
EOQ;

	if ( is_int( $term ) ) {
		if ( 0 == $term ) {
			return 0;
		}

		$where = 't.term_id = %d';

		return ! empty( $taxonomy )
			? $wpdb->get_row( $wpdb->prepare( $tax_select . $where . " AND tt.taxonomy = %s", $term, $taxonomy ), ARRAY_A )
			: $wpdb->get_var( $wpdb->prepare( $select . $where, $term ) );
	}

	$term = trim( wp_unslash( $term ) );
	$slug = sanitize_title( $term );
	$where = 't.slug = %s';
	$else_where = 't.name = %s';
	$where_fields = array( $slug );
	$else_where_fields = array( $term );
	$orderby = 'ORDER BY t.term_id ASC';
	$limit = 'LIMIT 1';

	if ( ! empty( $taxonomy ) ) {
		if ( is_numeric( $parent ) ) {
			$parent = ( int ) $parent;
			$where_fields[] = $parent;
			$else_where_parent[] = $parent;
			$where .= ' AND tt.parent = %d';
			$else_where .= ' AND tt.parent = %d';
		}

		$where_fields[] = $taxonomy;
		$else_where_fields[] = $taxonomy;

		if ( $result = $wpdb->get_row( $wpdb->prepare( <<<EOQ
SELECT tt.term_id, tt.term_taxonomy_id
FROM $wpdb->terms AS t
INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id
WHERE $where
  AND tt.taxonomy = %s
$orderby
$limit
EOQ
					, $where_fields ), ARRAY_A ) ) {
			return $result;
		}

		return $wpdb->get_row( $wpdb->prepare( <<<EOQ
SELECT tt.term_id, tt.term_taxonomy_id
FROM $wpdb->term AS t
INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id
WHERE $else_where
  AND tt.taxonomy = %s
$orderby
$limit
EOQ
				, $else_where_fields ), ARRAY_A );
	}

	if ( $result = $wpdb->get_var( $wpdb->prepare( <<<EOQ
SELECT term_id
FROM $wpdb->terms AS t
WHERE $where
$orderby
$limit
EOQ
				, $where_fields ) ) ) {
		return $result;
	}

	return $wpdb->get_var( $wpdb->prepare( <<<EOQ
SELECT term_id
FROM $wpdb->terms AS t
WHERE $else_where
$orderby
$limit
EOQ
			, $else_where_fields ) );
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

			if ( isset( $t->args ) && is_array( $t->args ) && $args != array_merge( $args, $t->args ) ) {
				unset( $taxonomies[ $index ] );
				$terms = array_merge( $terms, wp_get_object_terms( $object_ids, $taxonomy, array_merge( $args, $t->args ) ) );
			}
		}
	} else {
		$t = get_taxonomy( $taxonomies[0] );

		if ( isset( $t->args ) && is_array( $t->args ) ) {
			$args = array_merge( $args, $t->args );
		}
	}

	$args['taxonomy'] = $taxonomies;
	$args['object_ids'] = $object_ids;

	// Taxonomies registered without an 'args' param are handled here.
	if ( ! empty( $taxonomies ) ) {
		$terms_from_remaining_taxonomies = get_terms( $args );

		// Array keys should be preserved for values of $fields that use term_id for keys.
		$terms = ! empty( $args['fields'] ) && 0 === strpos( $args['fields'], 'id=>' )
			? $terms + $terms_from_remaining_taxonomies
			: array_merge( $terms, $terms_from_remaining_taxonomies );
	}

	/**
	 * Filters the terms for a given object or objects.
	 *
	 * @since 4.2.0
	 *
	 * @param array $terms      An array of terms for the given object or objects.
	 * @param array $object_ids Array of object IDs for which `$terms` were retrieved.
	 * @param array $taxonomies Array of taxonomies from which `$terms` were retrieved.
	 * @param array $args       An array of arguments for retrieving terms for the given object(s).
	 *                          See wp_get_object_terms() for details.
	 */
	$terms = apply_filters( 'get_object_terms', $terms, $object_ids, $taxonomies, $args );

	$object_ids = implode( ',', $object_ids );
	$taxonomies = "'" . implode( "', '", array_map( 'esc_sql', $taxonomies ) ) . "'";

	/**
	 * Filters the terms for a given object or objects.
	 *
	 * The `$taxonomies` parameter passed to this filter is formatted as a SQL fragment.
	 * The {@see 'get_object_terms'} filter is recommended as an alternative.
	 *
	 * @since 2.8.0
	 *
	 * @param array     $terms      An array of terms for the given object or objects.
	 * @param int|array $object_ids Object ID or array of IDs.
	 * @param string    $taxonomies SQL-formatted (comma-separated and quoted) list of taxonomy names.
	 * @param array     $args       An array of arguments for retrieving terms for the given object(s).
	 *                              See wp_get_object_terms() for details.
	 */
	return apply_filters( 'wp_get_object_terms', $terms, $object_ids, $taxonomies, $args );
}

/**
 * Add a new term to the database.
 *
 * A non-existent term is inserted in the following sequence:
 * 1. The term is added to the term table, then related to the taxonomy.
 * 2. If everything is correct, several actions are fired.
 * 3. The 'term_id_filter' is evaluated.
 * 4. The term cache is cleaned.
 * 5. Several more actions are fired.
 * 6. An array is returned containing the term_id and term_taxonomy_id.
 *
 * If the 'slug' argument is not empty, then it is checked to see if the term is invalid.
 * If it is not a valid, existing term, it is added and the term_id is given.
 *
 * If the taxonomy is hierarchical, and the 'parent' argument is not empty, the term is inserted and the term_id will be given.
 *
 * Error handling:
 * If $taxonomy does not exist or $term is empty, a WP_Error object will be returned.
 *
 * If the term already exists on the same hierarchical level, or the term slug and name are not unique, a WP_Error object will be returned.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @since  2.3.0
 *
 * @param  string         $term     The term to add or update.
 * @param  string         $taxonomy The taxonomy to which to add the term.
 * @param  array|string   $args {
 *     Optional.
 *     Array or string of arguments for inserting a term.
 *
 *     @type string $alias_of    Slug of the term to make this term an alias of.
 *                               Default empty string.
 *                               Accepts a term slug.
 *     @type string $description The term description.
 *                               Default empty string.
 *     @type int    $parent      The id of the parent term.
 *                               Default 0.
 *     @type string $slug        The term slug to use.
 *                               Default empty string.
 * }
 * @return array|WP_Error An array containing the `term_id` and `term_taxonomy_id`, WP_Error otherwise.
 */
function wp_insert_term( $term, $taxonomy, $args = array() )
{
	global $wpdb;

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
	}

	/**
	 * Filters a term before it is sanitized and inserted into the database.
	 *
	 * @since 3.0.0
	 *
	 * @param string $term     The term to add or update.
	 * @param string $taxonomy Taxonomy slug.
	 */
	$term = apply_filters( 'pre_insert_term', $term, $taxonomy );

	if ( is_wp_error( $term ) ) {
		return $term;
	}

	if ( is_int( $term ) && 0 == $term ) {
		return new WP_Error( 'invalid_term_id', __( 'Invalid term ID.' ) );
	}

	if ( '' == trim( $term ) ) {
		return new WP_Error( 'empty_term_name', __( 'A name is required for this term.' ) );
	}

	$defaults = array(
		'alias_of'    => '',
		'description' => '',
		'parent'      => 0,
		'slug'        => ''
	);
	$args = wp_parse_args( $args, $defaults );

	if ( $args['parent'] > 0 && ! term_exists( ( int ) $args['parent'] ) ) {
		return new WP_Error( 'missing_parent', __( 'Parent term does not exist.' ) );
	}

	$args['name'] = $term;
	$args['taxonomy'] = $taxonomy;

	// Coerce null description to strings, to avoid database errors.
	$args['description'] = ( string ) $args['description'];

	$args = sanitize_term( $args, $taxonomy, 'db' );

	// Expected_slashed ($name)
	$name = wp_unslash( $args['name'] );
	$description = wp_unslash( $args['description'] );
	$parent = ( int ) $args['parent'];

	$slug_provided = ! empty( $args['slug'] );

	$slug = ! $slug_provided
		? sanitize_title( $name )
		: $args['slug'];

	$term_group = 0;

	if ( $args['alias_of'] ) {
		$alias = get_term_by( 'slug', $args['alias_of'], $taxonomy );

		if ( ! empty( $alias->term_group ) ) {
			// The alias we want is already in a group, so let's use that one.
			$group_group = $alias->term_group;
		} elseif ( ! empty( $alias->term_id ) ) {
			// The alias is not in a group, so we create a new one and add the alias to it.
			$term_group = $wpdb->get_var( <<<EOQ
SELECT MAX(term_group)
FROM $wpdb->terms
EOQ
				) + 1;

			wp_update_term( $alias->term_id, $taxonomy, array( 'term_group' => $term_group ) );
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/taxonomy.php
 * @NOW 008: wp-includes/taxonomy.php
 * -> wp-includes/taxonomy.php
 */
		}
	}
}

/**
 * Create Term and Taxonomy Relationships.
 *
 * Relates an object (post, link etc) to a term and taxonomy type.
 * Creates the term and taxonomy relationship if it doesn't already exist.
 * Creates a term if it doesn't exist (using the slug).
 *
 * A relationship means that the term is grouped in or belongs to the taxonomy.
 * A term has no meaning until it is given context by defining which taxonomy it exists under.
 *
 * @since  2.3.0
 * @global wpdb $wpdb The WordPress database abstraction object.
 *
 * @param  int              $object_id The object to relate to.
 * @param  string|int|array $terms     A single term slug, single term id, or array of either term slugs or ids.
 *                                     Will replace all existing related terms in this taxonomy.
 *                                     Passing an empty value will remove all related terms.
 * @param  string           $taxonomy  The context in which to relate the term to the object.
 * @param  bool             $append    Optional.
 *                                     If false will delete difference of terms.
 *                                     Default false.
 * @return array|WP_Error   Term taxonomy IDs of the affected terms.
 */
function wp_set_object_terms( $object_id, $terms, $taxonomy, $append = FALSE )
{
	global $wpdb;
	$object_id = ( int ) $object_id;

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
	}

	if ( ! is_array( $terms ) ) {
		$terms = array( $terms );
	}

	$old_tt_ids = ! $append
		? wp_get_object_terms( $object_id, $taxonomy, array(
				'fields'  => 'tt_ids',
				'orderby' => 'none'
			) )
		: array();

	$tt_ids = array();
	$term_ids = array();
	$new_tt_ids = array();

	foreach ( ( array ) $terms as $term ) {
		if ( ! strlen( trim( $term ) ) ) {
			continue;
		}

		if ( ! $term_info = term_exists( $term, $taxonomy ) ) {
			// Skip if a non-existent term ID is passed.
			if ( is_int( $term ) ) {
				continue;
			}

			$term_info = wp_insert_term( $term, $taxonomy );
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * @NOW 007: wp-includes/taxonomy.php
 * -> wp-includes/taxonomy.php
 */
		}
	}
}

/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/taxonomy.php
 * <- wp-includes/taxonomy.php
 * <- wp-includes/taxonomy.php
 * @NOW 010: wp-includes/taxonomy.php
 */

/**
 * Update term based on arguments provided.
 *
 * The $args will indiscriminately override all values with the same field name.
 * Care must be taken to not override important information need to update or update will fail (or perhaps create a new term, neither would be acceptable).
 *
 * Defaults will set 'alias_of', 'description', 'parent', and 'slug' if not defined in $args already.
 *
 * 'alias_of' will create a term group, if it doesn't already exist, and update it for the $term.
 *
 * If the 'slug' argument in $args is missing, then the 'name' in $args will be used.
 * It should also be noted that if you set 'slug' and it isn't unique then a WP_Error will be passed back.
 * If you don't pass any slug, then a unique one will be created for you.
 *
 * For what can be overrode in `$args`, check the term scheme can contain and stay away from the term keys.
 *
 * @since  2.3.0
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param  int            $term_id  The ID of the term.
 * @param  string         $taxonomy The context ini which to relate the term to the object.
 * @param  array|string   $args     Optional.
 *                                  Array of get_terms() arguments.
 *                                  Default empty array.
 * @return array|WP_Error Returns Term ID and Taxonomy Term ID.
 */
function wp_update_term( $term_id, $taxonomy, $args = array() )
{
	global $wpdb;

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
	}

	$term_id = ( int ) $term_id;

	// First, get all of the original args.
	$term = get_term( $term_id, $taxonomy );

	if ( is_wp_error( $term ) ) {
		return $term;
	}

	if ( ! $term ) {
		return new WP_Error( 'invalid_term', __( 'Empty Term.' ) );
	}

	$term = ( array ) $term->data;

	// Escape data pulled from DB.
	$term = wp_slash( $term );

	// Merge old and new args with new args overwriting old ones.
	$args = array_merge( $term, $args );

	$defaults = array(
		'alias_of'    => '',
		'description' => '',
		'parent'      => 0,
		'slug'        => ''
	);
	$args = wp_parse_args( $args, $defaults );
	$args = sanitize_term( $args, $taxonomy, 'db' );
	$parsed_args = $args;

	// Expected_slashed ($name)
	$name = wp_unslash( $args['name'] );
	$description = wp_unslash( $args['description'] );

	$parsed_args['name'] = $name;
	$parsed_args['description'] = $description;

	if ( '' == trim( $name ) ) {
		return new WP_Error( 'empty_term_name', __( 'A name is required for this term.' ) );
	}

	if ( $parsed_args['parent'] > 0 && ! term_exists( ( int ) $parsed_args['parent'] ) ) {
		return new WP_Error( 'missing_parent', __( 'Parent term does not exist.' ) );
	}

	$empty_slug = FALSE;

	if ( empty( $args['slug'] ) ) {
		$empty_slug = TRUE;
		$slug = sanitize_title( $name );
	} else {
		$slug = $args['slug'];
	}

	$parsed_args['slug'] = $slug;

	$term_group = isset( $parsed_args['term_group'] )
		? $parsed_args['term_group']
		: 0;

	if ( $args['alias_of'] ) {
		$alias = get_term_by( 'slug', $args['alias_of'], $taxonomy );

		if ( ! empty( $alias->term_group ) ) {
			// The alias we want is already in a group, so let's use that one.
			$term_group = $alias->term_group;
		} elseif ( ! empty( $alias->term_id ) ) {
			// The alias is not in a group, so we create a new one and add the alias to it.
			$term_group = $wpdb->get_var( <<<EOQ
SELECT MAX(term_group)
FROM $wpdb->terms
EOQ
				+ 1 );

			wp_update_term( $alias->term_id, $taxonomy, array( 'term_group' => $term_group ) );
		}

		$parsed_args['term_group'] = $term_group;
	}

	/**
	 * Filters the term parent.
	 *
	 * Hook to this filter to see if it will cause a hierarchy loop.
	 *
	 * @since 3.1.0
	 *
	 * @param int    $parent      ID of the parent term.
	 * @param int    $term_id     Term ID.
	 * @param string $taxonomy    Taxonomy slug.
	 * @param array  $parsed_args An array of potentially altered update arguments for the gievn term.
	 * @param array  $args        An array of update arguments for the given term.
	 */
	$parent = apply_filters( 'wp_update_term_parent', $args['parent'], $term_id, $taxonomy, $parsed_args, $args );

	// Check for duplicate slug
	$duplicate = get_term_by( 'slug', $slug, $taxonomy );

	if ( $duplicate && $duplicate->term_id != $term_id ) {
		/**
		 * If an empty slug was passed or the parent changed, reset the slug to something unique.
		 * Otherwise, bail.
		 */
		if ( $empty_slug || $parent != $term['parent'] ) {
			$slug = wp_unique_term_slug( $slug, ( object ) $args );
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/taxonomy.php
 * <- wp-includes/taxonomy.php
 * @NOW 009: wp-includes/taxonomy.php
 * -> wp-includes/taxonomy.php
 */
		}
	}
}

//
// Cache
//

/**
 * Removes the taxonomy relationship to terms from the cache.
 *
 * Will remove the entire taxonomy relationship containing term `$object_id`.
 * The term IDs have to exist within the taxonomy `$object_type` for the deletion to take place.
 *
 * @since  2.3.0
 * @global bool $_wp_suspend_cache_invalidation
 * @see    get_object_taxonomies() for more on $object_type.
 *
 * @param int|array    $object_ids  Single or list of term object ID(s).
 * @param array|string $object_type The taxonomy object type.
 */
function clean_object_term_cache( $object_ids, $object_type )
{
	global $_wp_suspend_cache_invalidation;

	if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
		return;
	}

	if ( ! is_array( $object_ids ) ) {
		$object_ids = array( $object_ids );
	}

	$taxonomies = get_object_taxonomies( $object_type );

	foreach ( $object_ids as $id ) {
		foreach ( $taxonomies as $taxonomy ) {
			wp_cache_delete( $id, "{$taxonomy}_relationships" );
		}
	}

	/**
	 * Fires after the object term cache has been cleaned.
	 *
	 * @since 2.5.0
	 *
	 * @param array  $object_ids  An array of object IDs.
	 * @param string $object_type Object type.
	 */
	do_action( 'clean_object_term_cache', $object_ids, $object_type );
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
 * Updates the cache for the given term object ID(s).
 *
 * Note: Due to performance concerns, great care should be taken to only update term caches when necessary.
 * Processing time can increase exponentially depending on both the number of passed term IDs and the number of taxonomies those terms belong to.
 *
 * Caches will only be updated for terms not already cached.
 *
 * @since 2.3.0
 *
 * @param  string|array $object_ids  Comma-separated list or array of term object IDs.
 * @param  array|string $object_type The taxonomy object type.
 * @return void|false   False if all of the terms in `$object_ids` are already cached.
 */
function update_object_term_cache( $object_ids, $object_type )
{
	if ( empty( $object_ids ) ) {
		return;
	}

	if ( ! is_array( $object_ids ) ) {
		$object_ids = explode( ',', $object_ids );
	}

	$object_ids = array_map( 'intval', $object_ids );
	$taxonomies = get_object_taxonomies( $object_type );
	$ids = array();

	foreach ( ( array ) $object_ids as $id ) {
		foreach ( $taxonomies as $taxonomy ) {
			if ( FALSE === wp_cache_get( $id, "{$taxonomy}_relationships" ) ) {
				$ids[] = $id;
				break;
			}
		}
	}

	if ( empty( $ids ) ) {
		return FALSE;
	}

	$terms = wp_get_object_terms( $ids, $taxonomies, array(
			'fields'                 => 'all_with_object_id',
			'orderby'                => 'name',
			'update_term_meta_cache' => FALSE
		) );
	$object_terms = array();

	foreach ( ( array ) $terms as $term ) {
		$object_terms[ $term->object_id ][ $term->taxonomy ][] = $term->term_id;
	}

	foreach ( $ids as $id ) {
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! isset( $object_terms[ $id ][ $taxonomy ] ) ) {
				if ( ! isset( $object_terms[ $id ] ) ) {
					$object_terms[ $id ] = array();
				}

				$object_terms[ $id ][ $taxonomy ] = array();
			}
		}
	}

	foreach ( $object_terms as $id => $value ) {
		foreach ( $value as $taxonomy => $terms ) {
			wp_cache_add( $id, $terms, "{$taxonomy}_relationships" );
		}
	}
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

//
// Private
//

/**
 * Retrieves children of taxonomy as Term IDs.
 *
 * @ignore
 * @since  2.3.0
 *
 * @param  string $taxonomy Taxonomy name.
 * @return array  Empty if $taxonomy isn't hierarchical or returns children as Term IDs.
 */
function _get_term_hierarchy( $taxonomy )
{
	if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
		return array();
	}

	$children = get_option( "{$taxonomy}_children" );

	if ( is_array( $children ) ) {
		return $children;
	}

	$children = array();
	$terms = get_terms( $taxonomy, array(
			'get'     => 'all',
			'orderby' => 'id',
			'fields'  => 'id=>parent'
		) );

	foreach ( $terms as $term_id => $parent ) {
		if ( $parent > 0 ) {
			$children[ $parent ][] = $term_id;
		}
	}

	update_option( "{$taxonomy}_children", $children );
	return $children;
}

/**
 * Get the subset of $terms that are descendants of $term_id.
 *
 * If `$terms` is an array of objects, then _get_term_children() returns an array of objects.
 * If `$terms` is an array of IDs, then _get_term_children() returns an array of IDs.
 *
 * @access private
 * @since  2.3.0
 *
 * @param  int            $term_id   The ancestor term: all returned terms should be descendants of `$term_id`.
 * @param  array          $terms     The set of terms - either an array of term objects or term IDs - from which those that are descendants of $term_id will be chosen.
 * @param  string         $taxonomy  The taxonomy which determines the hierarchy of the terms.
 * @param  array          $ancestors Optional.
 *                                   Term ancestors that have already been identified.
 *                                   Passed by reference, to keep track of found terms when recursing the hierarchy.
 *                                   The array of located ancestors is used to prevent infinite recursion loops.
 *                                   For performance, `term_ids` are used as array keys, with 1 as value.
 *                                   Default empty array.
 * @return array|WP_Error The subset of $terms that are descendants of $term_id.
 */
function _get_term_children( $term_id, $terms, $taxonomy, &$ancestors = array() )
{
	$empty_array = array();

	if ( empty( $terms ) ) {
		return $empty_array;
	}

	$term_list = array();
	$has_children = _get_term_hierarchy( $taxonomy );

	if ( 0 != $term_id && ! isset( $has_children[ $term_id ] ) ) {
		return $empty_array;
	}

	// Include the term itself in the ancestors array, so we can properly detect when a loop has occurred.
	if ( empty( $ancestors ) ) {
		$ancestors[ $term_id ] = 1;
	}

	foreach ( ( array ) $terms as $term ) {
		$use_id = FALSE;

		if ( ! is_object( $term ) ) {
			$term = get_term( $term, $taxonomy );

			if ( is_wp_error( $term ) ) {
				return $term;
			}

			$use_id = TRUE;
		}

		// Don't recurse if we've already identified the term as a child - this indicates a loop.
		if ( isset( $ancestors[ $term->term_id ] ) ) {
			continue;
		}

		if ( $term->parent == $term_id ) {
			$term_list[] = $use_id
				? $term->term_id
				: $term;

			if ( ! isset( $has_children[ $term->term_id ] ) ) {
				continue;
			}

			$ancestors[ $term->term_id ] = 1;

			if ( $children = _get_term_children( $term->term_id, $terms, $taxonomy, $ancestors ) ) {
				$term_list = array_merge( $term_list, $children );
			}
		}
	}

	return $term_list;
}

/**
 * Add count of children to parent count.
 *
 * Recalculates term counts by including items from child terms.
 * Assumes all relevant children are already in the $terms argument.
 *
 * @access private
 * @since  2.3.0
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array  $terms    List of term objects (passed by reference).
 * @param string $taxonomy Term context.
 */
function _pad_term_counts( &$terms, $taxonomy )
{
	global $wpdb;

	// This function only works for hierarchical taxonomies like post categories.
	if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
		return;
	}

	$term_hier = _get_term_hierarchy( $taxonomy );

	if ( empty( $term_hier ) ) {
		return;
	}

	$term_items = array();
	$terms_by_id = array();
	$term_ids = array();

	foreach ( ( array ) $terms as $key => $term ) {
		$terms_by_id[ $term->term_id ] = &$terms[ $key ];
		$term_ids[ $term->term_taxonomy_id ] = $term->term_id;
	}

	// Get the object and term ids and stick them in a lookup table.
	$tax_obj = get_taxonomy( $taxonomy );
	$object_types = esc_sql( $tax_obj->object_type );
	$results = $wpdb->get_results( "SELECT object_id, term_taxonomy_id FROM $wpdb->term_relationships INNER JOIN $wpdb->posts ON object_id = ID WHERE term_taxonomy_id IN (" . implode( ',', array_keys( $term_ids ) ) . ") AND post_type IN ('" . implode( "', '" $object_types ) . "') AND post_status = 'publish'" );

	foreach ( $results as $row ) {
		$id = $term_ids[ $row->term_taxonomy_id ];

		$term_items[ $id ][ $row->object_id ] = isset( $term_items[ $id ][ $row->object_id ] )
			? ++$term_items[ $id ][ $row->object_id ]
			: 1;
	}

	// Touch every ancestor's lookup row for each post in each term.
	foreach ( $term_ids as $term_id ) {
		$child = $term_id;
		$ancestors = array();

		while ( ! empty( $terms_by_id[ $child ] ) && $parent = $terms_by_id[ $child ]->parent ) {
			$ancestors[] = $child;

			if ( ! empty( $term_items[ $term_id ] ) ) {
				foreach ( $term_items[ $term_id ] as $item_id => $touches ) {
					$term_items[ $parent ][ $item_id ] = isset( $term_items[ $parent ][ $item_id ] )
						? ++$term_items[ $parent ][ $item_id ]
						: 1;
				}
			}

			$child = $parent;

			if ( in_array( $parent, $ancestors ) ) {
				break;
			}
		}
	}

	// Transfer the touched cells.
	foreach ( ( array ) $term_items as $id => $items ) {
		if ( isset( $terms_by_id[ $id ] ) ) {
			$terms_by_id[ $id ]->count = count( $items );
		}
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
