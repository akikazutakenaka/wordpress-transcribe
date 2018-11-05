<?php
/**
 * Taxonomy API: Core category-specific template tags
 *
 * @package    WordPress
 * @subpackage Template
 * @since      1.2.0
 */

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
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/class-wp-post.php
 * <- wp-includes/class-wp-post.php
 * @NOW 009: wp-includes/category-template.php
 */
	}
}
