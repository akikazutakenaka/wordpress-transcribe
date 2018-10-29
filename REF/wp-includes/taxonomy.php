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
// wp-includes/category-template.php -> @NOW 010 -> self
}

// self -> @NOW 011

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
