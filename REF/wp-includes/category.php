<?php
/**
 * Taxonomy API: Core category-specific functionality
 *
 * @package    WordPress
 * @subpackage Taxonomy
 */

/**
 * Update category structure to old pre 2.3 from new taxonomy structure.
 *
 * This function was added for the taxonomy support to update the new category structure with the old category one.
 * This will maintain compatibility with plugins and themes which depend on the old key or property names.
 *
 * The parameter should only be passed a variable and not create the array or object inline to the parameter.
 * The reason for this is that parameter is passed by reference and PHP will fail unless it has the variable.
 *
 * There is no return value, because everything is updated on the variable you pass to it.
 * This is one of the features with using pass by reference in PHP.
 *
 * @since  2.3.0
 * @since  4.4.0 The `$category` parameter now also accepts a WP_Term object.
 * @access private
 *
 * @param array|object|WP_Term $category Category Row object or array.
 */
function _make_cat_compat( &$category )
{
	if ( is_object( $category ) && ! is_wp_error( $category ) ) {
		$category->cat_ID = $category->term_id;
		$category->category_count = $category->count;
		$category->category_description = $category->description;
		$category->cat_name = $category->name;
		$category->category_nicename = $category->slug;
		$category->category_parent = $category->parent;
	} elseif ( is_array( $category ) && isset( $category['term_id'] ) ) {
		$category['cat_ID'] = &$category['term_id'];
		$category['category_count'] = &$category['count'];
		$category['category_description'] = &$category['description'];
		$category['cat_name'] = &$category['name'];
		$category['category_nicename'] = &$category['slug'];
		$category['category_parent'] = &$category['parent'];
	}
}
