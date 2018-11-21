<?php
/**
 * Taxonomy API: Core category-specific template tags
 *
 * @package    WordPress
 * @subpackage Template
 * @since      1.2.0
 */

/**
 * Retrieve category parents with separator.
 *
 * @since 1.2.0
 * @since 4.8.0 The `$visited` parameter was deprecated and renamed to `$deprecated`.
 *
 * @param  int             $id         Category ID.
 * @param  bool            $link       Optional, default is false.
 *                                     Whether to format with link.
 * @param  string          $separator  Optional, default is '/'.
 *                                     How to separate categories.
 * @param  bool            $nicename   Optional, default is false.
 *                                     Whether to use nice name for display.
 * @param  array           $deprecated Not used.
 * @return string|WP_Error A list of category parents on success, WP_Error on failure.
 */
function get_category_parents( $id, $link = FALSE, $separator = '/', $nicename = FALSE, $deprecated = array() )
{
	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '4.8.0' );
	}

	$format = $nicename
		? 'slug'
		: 'name';

	$args = array(
		'separator' => $separator,
		'link'      => $link,
		'format'    => $format
	);
	return get_term_parents_list( $id, 'category', $args );
}

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
	}

	/**
	 * Filters the array of categories to return for a post.
	 *
	 * @since 3.1.0
	 * @since 4.4.0 Added `$id` parameter.
	 *
	 * @param array $categories An array of categories to return for the post.
	 * @param int   $id         ID of the post.
	 */
	return apply_filters( 'get_the_categories', $categories, $id );
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

/**
 * Retrieve term parents with separator.
 *
 * @since 4.8.0
 *
 * @param  int             $term_id  Term ID.
 * @param  string          $taxonomy Taxonomy name.
 * @param  string|array    $args {
 *     Array of optional arguments.
 *
 *     @type string $format    Use term names or slugs for display.
 *                             Accepts 'name' or 'slug'.
 *                             Default 'name'.
 *     @type string $separator Separator for between the terms.
 *                             Default '/'.
 *     @type bool   $link      Whether to format as a link.
 *                             Default true.
 *     @type bool   $inclusive Include the term to get the parents for.
 *                             Default true.
 * }
 * @return string|WP_Error A list of term parents on success, WP_Error or empty string on failure.
 */
function get_term_parents_list( $term_id, $taxonomy, $args = array() )
{
	$list = '';
	$term = get_term( $term_id, $taxonomy );

	if ( is_wp_error( $term ) ) {
		return $term;
	}

	if ( ! $term ) {
		return $list;
	}

	$term_id = $term->term_id;
	$defaults = array(
		'format'    => 'name',
		'separator' => '/',
		'link'      => TRUE,
		'inclusive' => TRUE
	);
	$args = wp_parse_args( $args, $defaults );

	foreach ( array( 'link', 'inclusive' ) as $bool ) {
		$args[ $bool ] = wp_validate_boolean( $args[ $bool ] );
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
