<?php
/**
 * Taxonomy API: Core category-specific template tags
 *
 * @package    WordPress
 * @subpackage Template
 * @since      1.2.0
 */

/**
 * Retrieve post categories.
 *
 * This tag may be used outside The Loop by passing a post id as the parameter.
 *
 * Note: This function only returns results from the default "category" taxonomy.
 * For custom taxonomies use get_the_terms().
 *
 * @since 0.71
 *
 * @param  int   $id Optional, default to current post ID.
 *                   The post ID.
 * @return array Array of WP_Term objects, one for each category assigned to the post.
 */
function get_the_category( $id = FALSE )
{
	$categories = get_the_terms( $id, 'category' );

	if ( ! $categories || is_wp_error( $categories ) ) {
		$categories = array();
	}

	$categories = array_values( $categories );

	foreach ( array_keys( $categories ) as $key ) {
		_make_cat_compat( $categories[ $key ] );
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/link-template.php
 * @NOW 008: wp-includes/category-template.php
 */
	}
}

/**
 * Retrieve the terms of the taxonomy that are attached to the post.
 *
 * @since 2.5.0
 *
 * @param  int|object           $post     Post ID or object.
 * @param  string               $taxonomy Taxonomy name.
 * @return array|false|WP_Error Array of WP_Term objects on success, false if there are no terms or the post does not exist, WP_Error on failure.
 */
function get_the_terms( $post, $taxonomy )
{
	if ( ! $post = get_post( $post ) ) {
		return FALSE;
	}

	$terms = get_object_term_cache( $post->ID, $taxonomy );

	if ( FALSE === $terms ) {
		$terms = wp_get_object_terms( $post->ID, $taxonomy );

		if ( ! is_wp_error( $terms ) ) {
			$term_ids = wp_list_pluck( $terms, 'term_id' );
			wp_cache_add( $post->ID, $term_ids, $taxonomy . '_relationships' );
		}
	}

	/**
	 * Filters the list of terms attached to the given post.
	 *
	 * @since 3.1.0
	 *
	 * @param array|WP_Error $terms    List of attached terms, or WP_Error on failure.
	 * @param int            $post_id  Post ID.
	 * @param string         $taxonomy Name of the taxonomy.
	 */
	$terms = apply_filters( 'get_the_terms', $terms, $post->ID, $taxonomy );

	return empty( $terms )
		? FALSE
		: $terms;
}
