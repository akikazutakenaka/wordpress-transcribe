<?php
/**
 * Core Taxonomy API
 *
 * @package    WordPress
 * @subpackage Taxonomy
 */

// @NOW 010

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
// @NOW 009 -> wp-includes/taxonomy.php
}
