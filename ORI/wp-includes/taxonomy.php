<?php
/**
 * Core Taxonomy API
 *
 * @package WordPress
 * @subpackage Taxonomy
 */

//
// Taxonomy Registration
//

/**
 * Creates the initial taxonomies.
 *
 * This function fires twice: in wp-settings.php before plugins are loaded (for
 * backward compatibility reasons), and again on the {@see 'init'} action. We must
 * avoid registering rewrite rules before the {@see 'init'} action.
 *
 * @since 2.8.0
 *
 * @global WP_Rewrite $wp_rewrite The WordPress rewrite class.
 */
function create_initial_taxonomies() {
	global $wp_rewrite;

	if ( ! did_action( 'init' ) ) {
		$rewrite = array( 'category' => false, 'post_tag' => false, 'post_format' => false );
	} else {

		/**
		 * Filters the post formats rewrite base.
		 *
		 * @since 3.1.0
		 *
		 * @param string $context Context of the rewrite base. Default 'type'.
		 */
		$post_format_base = apply_filters( 'post_format_rewrite_base', 'type' );
		$rewrite = array(
			'category' => array(
				'hierarchical' => true,
				'slug' => get_option('category_base') ? get_option('category_base') : 'category',
				'with_front' => ! get_option('category_base') || $wp_rewrite->using_index_permalinks(),
				'ep_mask' => EP_CATEGORIES,
			),
			'post_tag' => array(
				'hierarchical' => false,
				'slug' => get_option('tag_base') ? get_option('tag_base') : 'tag',
				'with_front' => ! get_option('tag_base') || $wp_rewrite->using_index_permalinks(),
				'ep_mask' => EP_TAGS,
			),
			'post_format' => $post_format_base ? array( 'slug' => $post_format_base ) : false,
		);
	}

	register_taxonomy( 'category', 'post', array(
		'hierarchical' => true,
		'query_var' => 'category_name',
		'rewrite' => $rewrite['category'],
		'public' => true,
		'show_ui' => true,
		'show_admin_column' => true,
		'_builtin' => true,
		'capabilities' => array(
			'manage_terms' => 'manage_categories',
			'edit_terms'   => 'edit_categories',
			'delete_terms' => 'delete_categories',
			'assign_terms' => 'assign_categories',
		),
		'show_in_rest' => true,
		'rest_base' => 'categories',
		'rest_controller_class' => 'WP_REST_Terms_Controller',
	) );

	register_taxonomy( 'post_tag', 'post', array(
	 	'hierarchical' => false,
		'query_var' => 'tag',
		'rewrite' => $rewrite['post_tag'],
		'public' => true,
		'show_ui' => true,
		'show_admin_column' => true,
		'_builtin' => true,
		'capabilities' => array(
			'manage_terms' => 'manage_post_tags',
			'edit_terms'   => 'edit_post_tags',
			'delete_terms' => 'delete_post_tags',
			'assign_terms' => 'assign_post_tags',
		),
		'show_in_rest' => true,
		'rest_base' => 'tags',
		'rest_controller_class' => 'WP_REST_Terms_Controller',
	) );

	register_taxonomy( 'nav_menu', 'nav_menu_item', array(
		'public' => false,
		'hierarchical' => false,
		'labels' => array(
			'name' => __( 'Navigation Menus' ),
			'singular_name' => __( 'Navigation Menu' ),
		),
		'query_var' => false,
		'rewrite' => false,
		'show_ui' => false,
		'_builtin' => true,
		'show_in_nav_menus' => false,
	) );

	register_taxonomy( 'link_category', 'link', array(
		'hierarchical' => false,
		'labels' => array(
			'name' => __( 'Link Categories' ),
			'singular_name' => __( 'Link Category' ),
			'search_items' => __( 'Search Link Categories' ),
			'popular_items' => null,
			'all_items' => __( 'All Link Categories' ),
			'edit_item' => __( 'Edit Link Category' ),
			'update_item' => __( 'Update Link Category' ),
			'add_new_item' => __( 'Add New Link Category' ),
			'new_item_name' => __( 'New Link Category Name' ),
			'separate_items_with_commas' => null,
			'add_or_remove_items' => null,
			'choose_from_most_used' => null,
			'back_to_items' => __( '&larr; Back to Link Categories' ),
		),
		'capabilities' => array(
			'manage_terms' => 'manage_links',
			'edit_terms'   => 'manage_links',
			'delete_terms' => 'manage_links',
			'assign_terms' => 'manage_links',
		),
		'query_var' => false,
		'rewrite' => false,
		'public' => false,
		'show_ui' => true,
		'_builtin' => true,
	) );

	register_taxonomy( 'post_format', 'post', array(
		'public' => true,
		'hierarchical' => false,
		'labels' => array(
			'name' => _x( 'Format', 'post format' ),
			'singular_name' => _x( 'Format', 'post format' ),
		),
		'query_var' => true,
		'rewrite' => $rewrite['post_format'],
		'show_ui' => false,
		'_builtin' => true,
		'show_in_nav_menus' => current_theme_supports( 'post-formats' ),
	) );
}

// refactored. function get_taxonomies( $args = array(), $output = 'names', $operator = 'and' ) {}
// :
// refactored. function is_taxonomy_hierarchical($taxonomy) {}

/**
 * Creates or modifies a taxonomy object.
 *
 * Note: Do not use before the {@see 'init'} hook.
 *
 * A simple function for creating or modifying a taxonomy object based on
 * the parameters given. If modifying an existing taxonomy object, note
 * that the `$object_type` value from the original registration will be
 * overwritten.
 *
 * @since 2.3.0
 * @since 4.2.0 Introduced `show_in_quick_edit` argument.
 * @since 4.4.0 The `show_ui` argument is now enforced on the term editing screen.
 * @since 4.4.0 The `public` argument now controls whether the taxonomy can be queried on the front end.
 * @since 4.5.0 Introduced `publicly_queryable` argument.
 * @since 4.7.0 Introduced `show_in_rest`, 'rest_base' and 'rest_controller_class'
 *              arguments to register the Taxonomy in REST API.
 *
 * @global array $wp_taxonomies Registered taxonomies.
 *
 * @param string       $taxonomy    Taxonomy key, must not exceed 32 characters.
 * @param array|string $object_type Object type or array of object types with which the taxonomy should be associated.
 * @param array|string $args        {
 *     Optional. Array or query string of arguments for registering a taxonomy.
 *
 *     @type array         $labels                An array of labels for this taxonomy. By default, Tag labels are
 *                                                used for non-hierarchical taxonomies, and Category labels are used
 *                                                for hierarchical taxonomies. See accepted values in
 *                                                get_taxonomy_labels(). Default empty array.
 *     @type string        $description           A short descriptive summary of what the taxonomy is for. Default empty.
 *     @type bool          $public                Whether a taxonomy is intended for use publicly either via
 *                                                the admin interface or by front-end users. The default settings
 *                                                of `$publicly_queryable`, `$show_ui`, and `$show_in_nav_menus`
 *                                                are inherited from `$public`.
 *     @type bool          $publicly_queryable    Whether the taxonomy is publicly queryable.
 *                                                If not set, the default is inherited from `$public`
 *     @type bool          $hierarchical          Whether the taxonomy is hierarchical. Default false.
 *     @type bool          $show_ui               Whether to generate and allow a UI for managing terms in this taxonomy in
 *                                                the admin. If not set, the default is inherited from `$public`
 *                                                (default true).
 *     @type bool          $show_in_menu          Whether to show the taxonomy in the admin menu. If true, the taxonomy is
 *                                                shown as a submenu of the object type menu. If false, no menu is shown.
 *                                                `$show_ui` must be true. If not set, default is inherited from `$show_ui`
 *                                                (default true).
 *     @type bool          $show_in_nav_menus     Makes this taxonomy available for selection in navigation menus. If not
 *                                                set, the default is inherited from `$public` (default true).
 *     @type bool          $show_in_rest          Whether to include the taxonomy in the REST API.
 *     @type string        $rest_base             To change the base url of REST API route. Default is $taxonomy.
 *     @type string        $rest_controller_class REST API Controller class name. Default is 'WP_REST_Terms_Controller'.
 *     @type bool          $show_tagcloud         Whether to list the taxonomy in the Tag Cloud Widget controls. If not set,
 *                                                the default is inherited from `$show_ui` (default true).
 *     @type bool          $show_in_quick_edit    Whether to show the taxonomy in the quick/bulk edit panel. It not set,
 *                                                the default is inherited from `$show_ui` (default true).
 *     @type bool          $show_admin_column     Whether to display a column for the taxonomy on its post type listing
 *                                                screens. Default false.
 *     @type bool|callable $meta_box_cb           Provide a callback function for the meta box display. If not set,
 *                                                post_categories_meta_box() is used for hierarchical taxonomies, and
 *                                                post_tags_meta_box() is used for non-hierarchical. If false, no meta
 *                                                box is shown.
 *     @type array         $capabilities {
 *         Array of capabilities for this taxonomy.
 *
 *         @type string $manage_terms Default 'manage_categories'.
 *         @type string $edit_terms   Default 'manage_categories'.
 *         @type string $delete_terms Default 'manage_categories'.
 *         @type string $assign_terms Default 'edit_posts'.
 *     }
 *     @type bool|array    $rewrite {
 *         Triggers the handling of rewrites for this taxonomy. Default true, using $taxonomy as slug. To prevent
 *         rewrite, set to false. To specify rewrite rules, an array can be passed with any of these keys:
 *
 *         @type string $slug         Customize the permastruct slug. Default `$taxonomy` key.
 *         @type bool   $with_front   Should the permastruct be prepended with WP_Rewrite::$front. Default true.
 *         @type bool   $hierarchical Either hierarchical rewrite tag or not. Default false.
 *         @type int    $ep_mask      Assign an endpoint mask. Default `EP_NONE`.
 *     }
 *     @type string        $query_var             Sets the query var key for this taxonomy. Default `$taxonomy` key. If
 *                                                false, a taxonomy cannot be loaded at `?{query_var}={term_slug}`. If a
 *                                                string, the query `?{query_var}={term_slug}` will be valid.
 *     @type callable      $update_count_callback Works much like a hook, in that it will be called when the count is
 *                                                updated. Default _update_post_term_count() for taxonomies attached
 *                                                to post types, which confirms that the objects are published before
 *                                                counting them. Default _update_generic_term_count() for taxonomies
 *                                                attached to other object types, such as users.
 *     @type bool          $_builtin              This taxonomy is a "built-in" taxonomy. INTERNAL USE ONLY!
 *                                                Default false.
 * }
 * @return WP_Error|void WP_Error, if errors.
 */
function register_taxonomy( $taxonomy, $object_type, $args = array() ) {
	global $wp_taxonomies;

	if ( ! is_array( $wp_taxonomies ) )
		$wp_taxonomies = array();

	$args = wp_parse_args( $args );

	if ( empty( $taxonomy ) || strlen( $taxonomy ) > 32 ) {
		_doing_it_wrong( __FUNCTION__, __( 'Taxonomy names must be between 1 and 32 characters in length.' ), '4.2.0' );
		return new WP_Error( 'taxonomy_length_invalid', __( 'Taxonomy names must be between 1 and 32 characters in length.' ) );
	}

	$taxonomy_object = new WP_Taxonomy( $taxonomy, $object_type, $args );
	$taxonomy_object->add_rewrite_rules();

	$wp_taxonomies[ $taxonomy ] = $taxonomy_object;

	$taxonomy_object->add_hooks();


	/**
	 * Fires after a taxonomy is registered.
	 *
	 * @since 3.3.0
	 *
	 * @param string       $taxonomy    Taxonomy slug.
	 * @param array|string $object_type Object type or array of object types.
	 * @param array        $args        Array of taxonomy registration arguments.
	 */
	do_action( 'registered_taxonomy', $taxonomy, $object_type, (array) $taxonomy_object );
}

/**
 * Unregisters a taxonomy.
 *
 * Can not be used to unregister built-in taxonomies.
 *
 * @since 4.5.0
 *
 * @global WP    $wp            Current WordPress environment instance.
 * @global array $wp_taxonomies List of taxonomies.
 *
 * @param string $taxonomy Taxonomy name.
 * @return bool|WP_Error True on success, WP_Error on failure or if the taxonomy doesn't exist.
 */
function unregister_taxonomy( $taxonomy ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
	}

	$taxonomy_object = get_taxonomy( $taxonomy );

	// Do not allow unregistering internal taxonomies.
	if ( $taxonomy_object->_builtin ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Unregistering a built-in taxonomy is not allowed.' ) );
	}

	global $wp_taxonomies;

	$taxonomy_object->remove_rewrite_rules();
	$taxonomy_object->remove_hooks();

	// Remove the taxonomy.
	unset( $wp_taxonomies[ $taxonomy ] );

	/**
	 * Fires after a taxonomy is unregistered.
	 *
	 * @since 4.5.0
	 *
	 * @param string $taxonomy Taxonomy name.
	 */
	do_action( 'unregistered_taxonomy', $taxonomy );

	return true;
}

/**
 * Builds an object with all taxonomy labels out of a taxonomy object.
 *
 * @since 3.0.0
 * @since 4.3.0 Added the `no_terms` label.
 * @since 4.4.0 Added the `items_list_navigation` and `items_list` labels.
 * @since 4.9.0 Added the `most_used` and `back_to_items` labels.
 *
 * @param WP_Taxonomy $tax Taxonomy object.
 * @return object {
 *     Taxonomy labels object. The first default value is for non-hierarchical taxonomies
 *     (like tags) and the second one is for hierarchical taxonomies (like categories).
 *
 *     @type string $name                       General name for the taxonomy, usually plural. The same
 *                                              as and overridden by `$tax->label`. Default 'Tags'/'Categories'.
 *     @type string $singular_name              Name for one object of this taxonomy. Default 'Tag'/'Category'.
 *     @type string $search_items               Default 'Search Tags'/'Search Categories'.
 *     @type string $popular_items              This label is only used for non-hierarchical taxonomies.
 *                                              Default 'Popular Tags'.
 *     @type string $all_items                  Default 'All Tags'/'All Categories'.
 *     @type string $parent_item                This label is only used for hierarchical taxonomies. Default
 *                                              'Parent Category'.
 *     @type string $parent_item_colon          The same as `parent_item`, but with colon `:` in the end.
 *     @type string $edit_item                  Default 'Edit Tag'/'Edit Category'.
 *     @type string $view_item                  Default 'View Tag'/'View Category'.
 *     @type string $update_item                Default 'Update Tag'/'Update Category'.
 *     @type string $add_new_item               Default 'Add New Tag'/'Add New Category'.
 *     @type string $new_item_name              Default 'New Tag Name'/'New Category Name'.
 *     @type string $separate_items_with_commas This label is only used for non-hierarchical taxonomies. Default
 *                                              'Separate tags with commas', used in the meta box.
 *     @type string $add_or_remove_items        This label is only used for non-hierarchical taxonomies. Default
 *                                              'Add or remove tags', used in the meta box when JavaScript
 *                                              is disabled.
 *     @type string $choose_from_most_used      This label is only used on non-hierarchical taxonomies. Default
 *                                              'Choose from the most used tags', used in the meta box.
 *     @type string $not_found                  Default 'No tags found'/'No categories found', used in
 *                                              the meta box and taxonomy list table.
 *     @type string $no_terms                   Default 'No tags'/'No categories', used in the posts and media
 *                                              list tables.
 *     @type string $items_list_navigation      Label for the table pagination hidden heading.
 *     @type string $items_list                 Label for the table hidden heading.
 *     @type string $most_used                  Title for the Most Used tab. Default 'Most Used'.
 *     @type string $back_to_items              Label displayed after a term has been updated.
 * }
 */
function get_taxonomy_labels( $tax ) {
	$tax->labels = (array) $tax->labels;

	if ( isset( $tax->helps ) && empty( $tax->labels['separate_items_with_commas'] ) )
		$tax->labels['separate_items_with_commas'] = $tax->helps;

	if ( isset( $tax->no_tagcloud ) && empty( $tax->labels['not_found'] ) )
		$tax->labels['not_found'] = $tax->no_tagcloud;

	$nohier_vs_hier_defaults = array(
		'name' => array( _x( 'Tags', 'taxonomy general name' ), _x( 'Categories', 'taxonomy general name' ) ),
		'singular_name' => array( _x( 'Tag', 'taxonomy singular name' ), _x( 'Category', 'taxonomy singular name' ) ),
		'search_items' => array( __( 'Search Tags' ), __( 'Search Categories' ) ),
		'popular_items' => array( __( 'Popular Tags' ), null ),
		'all_items' => array( __( 'All Tags' ), __( 'All Categories' ) ),
		'parent_item' => array( null, __( 'Parent Category' ) ),
		'parent_item_colon' => array( null, __( 'Parent Category:' ) ),
		'edit_item' => array( __( 'Edit Tag' ), __( 'Edit Category' ) ),
		'view_item' => array( __( 'View Tag' ), __( 'View Category' ) ),
		'update_item' => array( __( 'Update Tag' ), __( 'Update Category' ) ),
		'add_new_item' => array( __( 'Add New Tag' ), __( 'Add New Category' ) ),
		'new_item_name' => array( __( 'New Tag Name' ), __( 'New Category Name' ) ),
		'separate_items_with_commas' => array( __( 'Separate tags with commas' ), null ),
		'add_or_remove_items' => array( __( 'Add or remove tags' ), null ),
		'choose_from_most_used' => array( __( 'Choose from the most used tags' ), null ),
		'not_found' => array( __( 'No tags found.' ), __( 'No categories found.' ) ),
		'no_terms' => array( __( 'No tags' ), __( 'No categories' ) ),
		'items_list_navigation' => array( __( 'Tags list navigation' ), __( 'Categories list navigation' ) ),
		'items_list' => array( __( 'Tags list' ), __( 'Categories list' ) ),
		/* translators: Tab heading when selecting from the most used terms */
		'most_used' => array( _x( 'Most Used', 'tags' ), _x( 'Most Used', 'categories' ) ),
		'back_to_items' => array( __( '&larr; Back to Tags' ), __( '&larr; Back to Categories' ) ),
	);
	$nohier_vs_hier_defaults['menu_name'] = $nohier_vs_hier_defaults['name'];

	$labels = _get_custom_object_labels( $tax, $nohier_vs_hier_defaults );

	$taxonomy = $tax->name;

	$default_labels = clone $labels;

	/**
	 * Filters the labels of a specific taxonomy.
	 *
	 * The dynamic portion of the hook name, `$taxonomy`, refers to the taxonomy slug.
	 *
	 * @since 4.4.0
	 *
	 * @see get_taxonomy_labels() for the full list of taxonomy labels.
	 *
	 * @param object $labels Object with labels for the taxonomy as member variables.
	 */
	$labels = apply_filters( "taxonomy_labels_{$taxonomy}", $labels );

	// Ensure that the filtered labels contain all required default values.
	$labels = (object) array_merge( (array) $default_labels, (array) $labels );

	return $labels;
}

/**
 * Add an already registered taxonomy to an object type.
 *
 * @since 3.0.0
 *
 * @global array $wp_taxonomies The registered taxonomies.
 *
 * @param string $taxonomy    Name of taxonomy object.
 * @param string $object_type Name of the object type.
 * @return bool True if successful, false if not.
 */
function register_taxonomy_for_object_type( $taxonomy, $object_type) {
	global $wp_taxonomies;

	if ( !isset($wp_taxonomies[$taxonomy]) )
		return false;

	if ( ! get_post_type_object($object_type) )
		return false;

	if ( ! in_array( $object_type, $wp_taxonomies[$taxonomy]->object_type ) )
		$wp_taxonomies[$taxonomy]->object_type[] = $object_type;

	// Filter out empties.
	$wp_taxonomies[ $taxonomy ]->object_type = array_filter( $wp_taxonomies[ $taxonomy ]->object_type );

	return true;
}

/**
 * Remove an already registered taxonomy from an object type.
 *
 * @since 3.7.0
 *
 * @global array $wp_taxonomies The registered taxonomies.
 *
 * @param string $taxonomy    Name of taxonomy object.
 * @param string $object_type Name of the object type.
 * @return bool True if successful, false if not.
 */
function unregister_taxonomy_for_object_type( $taxonomy, $object_type ) {
	global $wp_taxonomies;

	if ( ! isset( $wp_taxonomies[ $taxonomy ] ) )
		return false;

	if ( ! get_post_type_object( $object_type ) )
		return false;

	$key = array_search( $object_type, $wp_taxonomies[ $taxonomy ]->object_type, true );
	if ( false === $key )
		return false;

	unset( $wp_taxonomies[ $taxonomy ]->object_type[ $key ] );
	return true;
}

//
// Term API
//

/**
 * Retrieve object_ids of valid taxonomy and term.
 *
 * The strings of $taxonomies must exist before this function will continue. On
 * failure of finding a valid taxonomy, it will return an WP_Error class, kind
 * of like Exceptions in PHP 5, except you can't catch them. Even so, you can
 * still test for the WP_Error class and get the error message.
 *
 * The $terms aren't checked the same as $taxonomies, but still need to exist
 * for $object_ids to be returned.
 *
 * It is possible to change the order that object_ids is returned by either
 * using PHP sort family functions or using the database by using $args with
 * either ASC or DESC array. The value should be in the key named 'order'.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int|array    $term_ids   Term id or array of term ids of terms that will be used.
 * @param string|array $taxonomies String of taxonomy name or Array of string values of taxonomy names.
 * @param array|string $args       Change the order of the object_ids, either ASC or DESC.
 * @return WP_Error|array If the taxonomy does not exist, then WP_Error will be returned. On success.
 *	the array can be empty meaning that there are no $object_ids found or it will return the $object_ids found.
 */
function get_objects_in_term( $term_ids, $taxonomies, $args = array() ) {
	global $wpdb;

	if ( ! is_array( $term_ids ) ) {
		$term_ids = array( $term_ids );
	}
	if ( ! is_array( $taxonomies ) ) {
		$taxonomies = array( $taxonomies );
	}
	foreach ( (array) $taxonomies as $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
		}
	}

	$defaults = array( 'order' => 'ASC' );
	$args = wp_parse_args( $args, $defaults );

	$order = ( 'desc' == strtolower( $args['order'] ) ) ? 'DESC' : 'ASC';

	$term_ids = array_map('intval', $term_ids );

	$taxonomies = "'" . implode( "', '", array_map( 'esc_sql', $taxonomies ) ) . "'";
	$term_ids = "'" . implode( "', '", $term_ids ) . "'";

	$sql = "SELECT tr.object_id FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN ($taxonomies) AND tt.term_id IN ($term_ids) ORDER BY tr.object_id $order";

	$last_changed = wp_cache_get_last_changed( 'terms' );
	$cache_key = 'get_objects_in_term:' . md5( $sql ) . ":$last_changed";
	$cache = wp_cache_get( $cache_key, 'terms' );
	if ( false === $cache ) {
		$object_ids = $wpdb->get_col( $sql );
		wp_cache_set( $cache_key, $object_ids, 'terms' );
	} else {
		$object_ids = (array) $cache;
	}

	if ( ! $object_ids ){
		return array();
	}
	return $object_ids;
}

/**
 * Given a taxonomy query, generates SQL to be appended to a main query.
 *
 * @since 3.1.0
 *
 * @see WP_Tax_Query
 *
 * @param array  $tax_query         A compact tax query
 * @param string $primary_table
 * @param string $primary_id_column
 * @return array
 */
function get_tax_sql( $tax_query, $primary_table, $primary_id_column ) {
	$tax_query_obj = new WP_Tax_Query( $tax_query );
	return $tax_query_obj->get_sql( $primary_table, $primary_id_column );
}

// refactored. function get_term( $term, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {}
// :
// refactored. function get_term_children( $term_id, $taxonomy ) {}

/**
 * Get sanitized Term field.
 *
 * The function is for contextual reasons and for simplicity of usage.
 *
 * @since 2.3.0
 * @since 4.4.0 The `$taxonomy` parameter was made optional. `$term` can also now accept a WP_Term object.
 *
 * @see sanitize_term_field()
 *
 * @param string      $field    Term field to fetch.
 * @param int|WP_Term $term     Term ID or object.
 * @param string      $taxonomy Optional. Taxonomy Name. Default empty.
 * @param string      $context  Optional, default is display. Look at sanitize_term_field() for available options.
 * @return string|int|null|WP_Error Will return an empty string if $term is not an object or if $field is not set in $term.
 */
function get_term_field( $field, $term, $taxonomy = '', $context = 'display' ) {
	$term = get_term( $term, $taxonomy );
	if ( is_wp_error($term) )
		return $term;

	if ( !is_object($term) )
		return '';

	if ( !isset($term->$field) )
		return '';

	return sanitize_term_field( $field, $term->$field, $term->term_id, $term->taxonomy, $context );
}

/**
 * Sanitizes Term for editing.
 *
 * Return value is sanitize_term() and usage is for sanitizing the term for
 * editing. Function is for contextual and simplicity.
 *
 * @since 2.3.0
 *
 * @param int|object $id       Term ID or object.
 * @param string     $taxonomy Taxonomy name.
 * @return string|int|null|WP_Error Will return empty string if $term is not an object.
 */
function get_term_to_edit( $id, $taxonomy ) {
	$term = get_term( $id, $taxonomy );

	if ( is_wp_error($term) )
		return $term;

	if ( !is_object($term) )
		return '';

	return sanitize_term($term, $taxonomy, 'edit');
}

// refactored. function get_terms( $args = array(), $deprecated = '' ) {}

/**
 * Adds metadata to a term.
 *
 * @since 4.4.0
 *
 * @param int    $term_id    Term ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Metadata value.
 * @param bool   $unique     Optional. Whether to bail if an entry with the same key is found for the term.
 *                           Default false.
 * @return int|WP_Error|bool Meta ID on success. WP_Error when term_id is ambiguous between taxonomies.
 *                           False on failure.
 */
function add_term_meta( $term_id, $meta_key, $meta_value, $unique = false ) {
	// Bail if term meta table is not installed.
	if ( get_option( 'db_version' ) < 34370 ) {
		return false;
	}

	if ( wp_term_is_shared( $term_id ) ) {
		return new WP_Error( 'ambiguous_term_id', __( 'Term meta cannot be added to terms that are shared between taxonomies.'), $term_id );
	}

	$added = add_metadata( 'term', $term_id, $meta_key, $meta_value, $unique );

	// Bust term query cache.
	if ( $added ) {
		wp_cache_set( 'last_changed', microtime(), 'terms' );
	}

	return $added;
}

/**
 * Removes metadata matching criteria from a term.
 *
 * @since 4.4.0
 *
 * @param int    $term_id    Term ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Optional. Metadata value. If provided, rows will only be removed that match the value.
 * @return bool True on success, false on failure.
 */
function delete_term_meta( $term_id, $meta_key, $meta_value = '' ) {
	// Bail if term meta table is not installed.
	if ( get_option( 'db_version' ) < 34370 ) {
		return false;
	}

	$deleted = delete_metadata( 'term', $term_id, $meta_key, $meta_value );

	// Bust term query cache.
	if ( $deleted ) {
		wp_cache_set( 'last_changed', microtime(), 'terms' );
	}

	return $deleted;
}

/**
 * Retrieves metadata for a term.
 *
 * @since 4.4.0
 *
 * @param int    $term_id Term ID.
 * @param string $key     Optional. The meta key to retrieve. If no key is provided, fetches all metadata for the term.
 * @param bool   $single  Whether to return a single value. If false, an array of all values matching the
 *                        `$term_id`/`$key` pair will be returned. Default: false.
 * @return mixed If `$single` is false, an array of metadata values. If `$single` is true, a single metadata value.
 */
function get_term_meta( $term_id, $key = '', $single = false ) {
	// Bail if term meta table is not installed.
	if ( get_option( 'db_version' ) < 34370 ) {
		return false;
	}

	return get_metadata( 'term', $term_id, $key, $single );
}

/**
 * Updates term metadata.
 *
 * Use the `$prev_value` parameter to differentiate between meta fields with the same key and term ID.
 *
 * If the meta field for the term does not exist, it will be added.
 *
 * @since 4.4.0
 *
 * @param int    $term_id    Term ID.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value.
 * @param mixed  $prev_value Optional. Previous value to check before removing.
 * @return int|WP_Error|bool Meta ID if the key didn't previously exist. True on successful update.
 *                           WP_Error when term_id is ambiguous between taxonomies. False on failure.
 */
function update_term_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ) {
	// Bail if term meta table is not installed.
	if ( get_option( 'db_version' ) < 34370 ) {
		return false;
	}

	if ( wp_term_is_shared( $term_id ) ) {
		return new WP_Error( 'ambiguous_term_id', __( 'Term meta cannot be added to terms that are shared between taxonomies.'), $term_id );
	}

	$updated = update_metadata( 'term', $term_id, $meta_key, $meta_value, $prev_value );

	// Bust term query cache.
	if ( $updated ) {
		wp_cache_set( 'last_changed', microtime(), 'terms' );
	}

	return $updated;
}

// refactored. function update_termmeta_cache( $term_ids ) {}

/**
 * Get all meta data, including meta IDs, for the given term ID.
 *
 * @since 4.9.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $term_id Term ID.
 * @return array|false Array with meta data, or false when the meta table is not installed.
 */
function has_term_meta( $term_id ) {
	// Bail if term meta table is not installed.
	if ( get_option( 'db_version' ) < 34370 ) {
		return false;
	}

	global $wpdb;

	return $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value, meta_id, term_id FROM $wpdb->termmeta WHERE term_id = %d ORDER BY meta_key,meta_id", $term_id ), ARRAY_A );
}

/**
 * Registers a meta key for terms.
 *
 * @since 4.9.8
 *
 * @param string $taxonomy Taxonomy to register a meta key for. Pass an empty string
 *                         to register the meta key across all existing taxonomies.
 * @param string $meta_key The meta key to register.
 * @param array  $args     Data used to describe the meta key when registered. See
 *                         {@see register_meta()} for a list of supported arguments.
 * @return bool True if the meta key was successfully registered, false if not.
 */
function register_term_meta( $taxonomy, $meta_key, array $args ) {
	$args['object_subtype'] = $taxonomy;

	return register_meta( 'term', $meta_key, $args );
}

/**
 * Unregisters a meta key for terms.
 *
 * @since 4.9.8
 *
 * @param string $taxonomy Taxonomy the meta key is currently registered for. Pass
 *                         an empty string if the meta key is registered across all
 *                         existing taxonomies.
 * @param string $meta_key The meta key to unregister.
 * @return bool True on success, false if the meta key was not previously registered.
 */
function unregister_term_meta( $taxonomy, $meta_key ) {
	return unregister_meta_key( 'term', $meta_key, $taxonomy );
}

// refactored. function term_exists( $term, $taxonomy = '', $parent = null ) {}

/**
 * Check if a term is an ancestor of another term.
 *
 * You can use either an id or the term object for both parameters.
 *
 * @since 3.4.0
 *
 * @param int|object $term1    ID or object to check if this is the parent term.
 * @param int|object $term2    The child term.
 * @param string     $taxonomy Taxonomy name that $term1 and `$term2` belong to.
 * @return bool Whether `$term2` is a child of `$term1`.
 */
function term_is_ancestor_of( $term1, $term2, $taxonomy ) {
	if ( ! isset( $term1->term_id ) )
		$term1 = get_term( $term1, $taxonomy );
	if ( ! isset( $term2->parent ) )
		$term2 = get_term( $term2, $taxonomy );

	if ( empty( $term1->term_id ) || empty( $term2->parent ) )
		return false;
	if ( $term2->parent == $term1->term_id )
		return true;

	return term_is_ancestor_of( $term1, get_term( $term2->parent, $taxonomy ), $taxonomy );
}

// refactored. function sanitize_term($term, $taxonomy, $context = 'display') {}
// refactored. function sanitize_term_field($field, $value, $term_id, $taxonomy, $context) {}

/**
 * Count how many terms are in Taxonomy.
 *
 * Default $args is 'hide_empty' which can be 'hide_empty=true' or array('hide_empty' => true).
 *
 * @since 2.3.0
 *
 * @param string       $taxonomy Taxonomy name.
 * @param array|string $args     Optional. Array of arguments that get passed to get_terms().
 *                               Default empty array.
 * @return array|int|WP_Error Number of terms in that taxonomy or WP_Error if the taxonomy does not exist.
 */
function wp_count_terms( $taxonomy, $args = array() ) {
	$defaults = array('hide_empty' => false);
	$args = wp_parse_args($args, $defaults);

	// backward compatibility
	if ( isset($args['ignore_empty']) ) {
		$args['hide_empty'] = $args['ignore_empty'];
		unset($args['ignore_empty']);
	}

	$args['fields'] = 'count';

	return get_terms($taxonomy, $args);
}

/**
 * Will unlink the object from the taxonomy or taxonomies.
 *
 * Will remove all relationships between the object and any terms in
 * a particular taxonomy or taxonomies. Does not remove the term or
 * taxonomy itself.
 *
 * @since 2.3.0
 *
 * @param int          $object_id  The term Object Id that refers to the term.
 * @param string|array $taxonomies List of Taxonomy Names or single Taxonomy name.
 */
function wp_delete_object_term_relationships( $object_id, $taxonomies ) {
	$object_id = (int) $object_id;

	if ( !is_array($taxonomies) )
		$taxonomies = array($taxonomies);

	foreach ( (array) $taxonomies as $taxonomy ) {
		$term_ids = wp_get_object_terms( $object_id, $taxonomy, array( 'fields' => 'ids' ) );
		$term_ids = array_map( 'intval', $term_ids );
		wp_remove_object_terms( $object_id, $term_ids, $taxonomy );
	}
}

/**
 * Removes a term from the database.
 *
 * If the term is a parent of other terms, then the children will be updated to
 * that term's parent.
 *
 * Metadata associated with the term will be deleted.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int          $term     Term ID.
 * @param string       $taxonomy Taxonomy Name.
 * @param array|string $args {
 *     Optional. Array of arguments to override the default term ID. Default empty array.
 *
 *     @type int  $default       The term ID to make the default term. This will only override
 *                               the terms found if there is only one term found. Any other and
 *                               the found terms are used.
 *     @type bool $force_default Optional. Whether to force the supplied term as default to be
 *                               assigned even if the object was not going to be term-less.
 *                               Default false.
 * }
 * @return bool|int|WP_Error True on success, false if term does not exist. Zero on attempted
 *                           deletion of default Category. WP_Error if the taxonomy does not exist.
 */
function wp_delete_term( $term, $taxonomy, $args = array() ) {
	global $wpdb;

	$term = (int) $term;

	if ( ! $ids = term_exists($term, $taxonomy) )
		return false;
	if ( is_wp_error( $ids ) )
		return $ids;

	$tt_id = $ids['term_taxonomy_id'];

	$defaults = array();

	if ( 'category' == $taxonomy ) {
		$defaults['default'] = get_option( 'default_category' );
		if ( $defaults['default'] == $term )
			return 0; // Don't delete the default category
	}

	$args = wp_parse_args($args, $defaults);

	if ( isset( $args['default'] ) ) {
		$default = (int) $args['default'];
		if ( ! term_exists( $default, $taxonomy ) ) {
			unset( $default );
		}
	}

	if ( isset( $args['force_default'] ) ) {
		$force_default = $args['force_default'];
	}

	/**
	 * Fires when deleting a term, before any modifications are made to posts or terms.
	 *
	 * @since 4.1.0
	 *
	 * @param int    $term     Term ID.
	 * @param string $taxonomy Taxonomy Name.
	 */
	do_action( 'pre_delete_term', $term, $taxonomy );

	// Update children to point to new parent
	if ( is_taxonomy_hierarchical($taxonomy) ) {
		$term_obj = get_term($term, $taxonomy);
		if ( is_wp_error( $term_obj ) )
			return $term_obj;
		$parent = $term_obj->parent;

		$edit_ids = $wpdb->get_results( "SELECT term_id, term_taxonomy_id FROM $wpdb->term_taxonomy WHERE `parent` = " . (int)$term_obj->term_id );
		$edit_tt_ids = wp_list_pluck( $edit_ids, 'term_taxonomy_id' );

		/**
		 * Fires immediately before a term to delete's children are reassigned a parent.
		 *
		 * @since 2.9.0
		 *
		 * @param array $edit_tt_ids An array of term taxonomy IDs for the given term.
		 */
		do_action( 'edit_term_taxonomies', $edit_tt_ids );

		$wpdb->update( $wpdb->term_taxonomy, compact( 'parent' ), array( 'parent' => $term_obj->term_id) + compact( 'taxonomy' ) );

		// Clean the cache for all child terms.
		$edit_term_ids = wp_list_pluck( $edit_ids, 'term_id' );
		clean_term_cache( $edit_term_ids, $taxonomy );

		/**
		 * Fires immediately after a term to delete's children are reassigned a parent.
		 *
		 * @since 2.9.0
		 *
		 * @param array $edit_tt_ids An array of term taxonomy IDs for the given term.
		 */
		do_action( 'edited_term_taxonomies', $edit_tt_ids );
	}

	// Get the term before deleting it or its term relationships so we can pass to actions below.
	$deleted_term = get_term( $term, $taxonomy );

	$object_ids = (array) $wpdb->get_col( $wpdb->prepare( "SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $tt_id ) );

	foreach ( $object_ids as $object_id ) {
		$terms = wp_get_object_terms( $object_id, $taxonomy, array( 'fields' => 'ids', 'orderby' => 'none' ) );
		if ( 1 == count($terms) && isset($default) ) {
			$terms = array($default);
		} else {
			$terms = array_diff($terms, array($term));
			if (isset($default) && isset($force_default) && $force_default)
				$terms = array_merge($terms, array($default));
		}
		$terms = array_map('intval', $terms);
		wp_set_object_terms( $object_id, $terms, $taxonomy );
	}

	// Clean the relationship caches for all object types using this term.
	$tax_object = get_taxonomy( $taxonomy );
	foreach ( $tax_object->object_type as $object_type )
		clean_object_term_cache( $object_ids, $object_type );

	$term_meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM $wpdb->termmeta WHERE term_id = %d ", $term ) );
	foreach ( $term_meta_ids as $mid ) {
		delete_metadata_by_mid( 'term', $mid );
	}

	/**
	 * Fires immediately before a term taxonomy ID is deleted.
	 *
	 * @since 2.9.0
	 *
	 * @param int $tt_id Term taxonomy ID.
	 */
	do_action( 'delete_term_taxonomy', $tt_id );
	$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $tt_id ) );

	/**
	 * Fires immediately after a term taxonomy ID is deleted.
	 *
	 * @since 2.9.0
	 *
	 * @param int $tt_id Term taxonomy ID.
	 */
	do_action( 'deleted_term_taxonomy', $tt_id );

	// Delete the term if no taxonomies use it.
	if ( !$wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE term_id = %d", $term) ) )
		$wpdb->delete( $wpdb->terms, array( 'term_id' => $term ) );

	clean_term_cache($term, $taxonomy);

	/**
	 * Fires after a term is deleted from the database and the cache is cleaned.
	 *
	 * @since 2.5.0
	 * @since 4.5.0 Introduced the `$object_ids` argument.
	 *
	 * @param int     $term         Term ID.
	 * @param int     $tt_id        Term taxonomy ID.
	 * @param string  $taxonomy     Taxonomy slug.
	 * @param mixed   $deleted_term Copy of the already-deleted term, in the form specified
	 *                              by the parent function. WP_Error otherwise.
	 * @param array   $object_ids   List of term object IDs.
	 */
	do_action( 'delete_term', $term, $tt_id, $taxonomy, $deleted_term, $object_ids );

	/**
	 * Fires after a term in a specific taxonomy is deleted.
	 *
	 * The dynamic portion of the hook name, `$taxonomy`, refers to the specific
	 * taxonomy the term belonged to.
	 *
	 * @since 2.3.0
	 * @since 4.5.0 Introduced the `$object_ids` argument.
	 *
	 * @param int     $term         Term ID.
	 * @param int     $tt_id        Term taxonomy ID.
	 * @param mixed   $deleted_term Copy of the already-deleted term, in the form specified
	 *                              by the parent function. WP_Error otherwise.
	 * @param array   $object_ids   List of term object IDs.
	 */
	do_action( "delete_{$taxonomy}", $term, $tt_id, $deleted_term, $object_ids );

	return true;
}

/**
 * Deletes one existing category.
 *
 * @since 2.0.0
 *
 * @param int $cat_ID Category term ID.
 * @return bool|int|WP_Error Returns true if completes delete action; false if term doesn't exist;
 * 	Zero on attempted deletion of default Category; WP_Error object is also a possibility.
 */
function wp_delete_category( $cat_ID ) {
	return wp_delete_term( $cat_ID, 'category' );
}

// refactored. function wp_get_object_terms($object_ids, $taxonomies, $args = array()) {}

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
 * If the 'slug' argument is not empty, then it is checked to see if the term
 * is invalid. If it is not a valid, existing term, it is added and the term_id
 * is given.
 *
 * If the taxonomy is hierarchical, and the 'parent' argument is not empty,
 * the term is inserted and the term_id will be given.
 *
 * Error handling:
 * If $taxonomy does not exist or $term is empty,
 * a WP_Error object will be returned.
 *
 * If the term already exists on the same hierarchical level,
 * or the term slug and name are not unique, a WP_Error object will be returned.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.3.0
 *
 * @param string       $term     The term to add or update.
 * @param string       $taxonomy The taxonomy to which to add the term.
 * @param array|string $args {
 *     Optional. Array or string of arguments for inserting a term.
 *
 *     @type string $alias_of    Slug of the term to make this term an alias of.
 *                               Default empty string. Accepts a term slug.
 *     @type string $description The term description. Default empty string.
 *     @type int    $parent      The id of the parent term. Default 0.
 *     @type string $slug        The term slug to use. Default empty string.
 * }
 * @return array|WP_Error An array containing the `term_id` and `term_taxonomy_id`,
 *                        WP_Error otherwise.
 */
function wp_insert_term( $term, $taxonomy, $args = array() ) {
	global $wpdb;

	if ( ! taxonomy_exists($taxonomy) ) {
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
	$defaults = array( 'alias_of' => '', 'description' => '', 'parent' => 0, 'slug' => '');
	$args = wp_parse_args( $args, $defaults );

	if ( $args['parent'] > 0 && ! term_exists( (int) $args['parent'] ) ) {
		return new WP_Error( 'missing_parent', __( 'Parent term does not exist.' ) );
	}

	$args['name'] = $term;
	$args['taxonomy'] = $taxonomy;

	// Coerce null description to strings, to avoid database errors.
	$args['description'] = (string) $args['description'];

	$args = sanitize_term($args, $taxonomy, 'db');

	// expected_slashed ($name)
	$name = wp_unslash( $args['name'] );
	$description = wp_unslash( $args['description'] );
	$parent = (int) $args['parent'];

	$slug_provided = ! empty( $args['slug'] );
	if ( ! $slug_provided ) {
		$slug = sanitize_title( $name );
	} else {
		$slug = $args['slug'];
	}

	$term_group = 0;
	if ( $args['alias_of'] ) {
		$alias = get_term_by( 'slug', $args['alias_of'], $taxonomy );
		if ( ! empty( $alias->term_group ) ) {
			// The alias we want is already in a group, so let's use that one.
			$term_group = $alias->term_group;
		} elseif ( ! empty( $alias->term_id ) ) {
			/*
			 * The alias is not in a group, so we create a new one
			 * and add the alias to it.
			 */
			$term_group = $wpdb->get_var("SELECT MAX(term_group) FROM $wpdb->terms") + 1;

			wp_update_term( $alias->term_id, $taxonomy, array(
				'term_group' => $term_group,
			) );
		}
	}

	/*
	 * Prevent the creation of terms with duplicate names at the same level of a taxonomy hierarchy,
	 * unless a unique slug has been explicitly provided.
	 */
	$name_matches = get_terms( $taxonomy, array(
		'name' => $name,
		'hide_empty' => false,
		'parent' => $args['parent'],
	) );

	/*
	 * The `name` match in `get_terms()` doesn't differentiate accented characters,
	 * so we do a stricter comparison here.
	 */
	$name_match = null;
	if ( $name_matches ) {
		foreach ( $name_matches as $_match ) {
			if ( strtolower( $name ) === strtolower( $_match->name ) ) {
				$name_match = $_match;
				break;
			}
		}
	}

	if ( $name_match ) {
		$slug_match = get_term_by( 'slug', $slug, $taxonomy );
		if ( ! $slug_provided || $name_match->slug === $slug || $slug_match ) {
			if ( is_taxonomy_hierarchical( $taxonomy ) ) {
				$siblings = get_terms( $taxonomy, array( 'get' => 'all', 'parent' => $parent ) );

				$existing_term = null;
				if ( ( ! $slug_provided || $name_match->slug === $slug ) && in_array( $name, wp_list_pluck( $siblings, 'name' ) ) ) {
					$existing_term = $name_match;
				} elseif ( $slug_match && in_array( $slug, wp_list_pluck( $siblings, 'slug' ) ) ) {
					$existing_term = $slug_match;
				}

				if ( $existing_term ) {
					return new WP_Error( 'term_exists', __( 'A term with the name provided already exists with this parent.' ), $existing_term->term_id );
				}
			} else {
				return new WP_Error( 'term_exists', __( 'A term with the name provided already exists in this taxonomy.' ), $name_match->term_id );
			}
		}
	}

	$slug = wp_unique_term_slug( $slug, (object) $args );

	$data = compact( 'name', 'slug', 'term_group' );

	/**
	 * Filters term data before it is inserted into the database.
	 *
	 * @since 4.7.0
	 *
	 * @param array  $data     Term data to be inserted.
	 * @param string $taxonomy Taxonomy slug.
	 * @param array  $args     Arguments passed to wp_insert_term().
	 */
	$data = apply_filters( 'wp_insert_term_data', $data, $taxonomy, $args );

	if ( false === $wpdb->insert( $wpdb->terms, $data ) ) {
		return new WP_Error( 'db_insert_error', __( 'Could not insert term into the database.' ), $wpdb->last_error );
	}

	$term_id = (int) $wpdb->insert_id;

	// Seems unreachable, However, Is used in the case that a term name is provided, which sanitizes to an empty string.
	if ( empty($slug) ) {
		$slug = sanitize_title($slug, $term_id);

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edit_terms', $term_id, $taxonomy );
		$wpdb->update( $wpdb->terms, compact( 'slug' ), compact( 'term_id' ) );

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edited_terms', $term_id, $taxonomy );
	}

	$tt_id = $wpdb->get_var( $wpdb->prepare( "SELECT tt.term_taxonomy_id FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = %s AND t.term_id = %d", $taxonomy, $term_id ) );

	if ( !empty($tt_id) ) {
		return array('term_id' => $term_id, 'term_taxonomy_id' => $tt_id);
	}
	$wpdb->insert( $wpdb->term_taxonomy, compact( 'term_id', 'taxonomy', 'description', 'parent') + array( 'count' => 0 ) );
	$tt_id = (int) $wpdb->insert_id;

	/*
	 * Sanity check: if we just created a term with the same parent + taxonomy + slug but a higher term_id than
	 * an existing term, then we have unwittingly created a duplicate term. Delete the dupe, and use the term_id
	 * and term_taxonomy_id of the older term instead. Then return out of the function so that the "create" hooks
	 * are not fired.
	 */
	$duplicate_term = $wpdb->get_row( $wpdb->prepare( "SELECT t.term_id, tt.term_taxonomy_id FROM $wpdb->terms t INNER JOIN $wpdb->term_taxonomy tt ON ( tt.term_id = t.term_id ) WHERE t.slug = %s AND tt.parent = %d AND tt.taxonomy = %s AND t.term_id < %d AND tt.term_taxonomy_id != %d", $slug, $parent, $taxonomy, $term_id, $tt_id ) );
	if ( $duplicate_term ) {
		$wpdb->delete( $wpdb->terms, array( 'term_id' => $term_id ) );
		$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $tt_id ) );

		$term_id = (int) $duplicate_term->term_id;
		$tt_id   = (int) $duplicate_term->term_taxonomy_id;

		clean_term_cache( $term_id, $taxonomy );
		return array( 'term_id' => $term_id, 'term_taxonomy_id' => $tt_id );
	}

	/**
	 * Fires immediately after a new term is created, before the term cache is cleaned.
	 *
	 * @since 2.3.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	do_action( "create_term", $term_id, $tt_id, $taxonomy );

	/**
	 * Fires after a new term is created for a specific taxonomy.
	 *
	 * The dynamic portion of the hook name, `$taxonomy`, refers
	 * to the slug of the taxonomy the term was created for.
	 *
	 * @since 2.3.0
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id   Term taxonomy ID.
	 */
	do_action( "create_{$taxonomy}", $term_id, $tt_id );

	/**
	 * Filters the term ID after a new term is created.
	 *
	 * @since 2.3.0
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id   Taxonomy term ID.
	 */
	$term_id = apply_filters( 'term_id_filter', $term_id, $tt_id );

	clean_term_cache($term_id, $taxonomy);

	/**
	 * Fires after a new term is created, and after the term cache has been cleaned.
	 *
	 * @since 2.3.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	do_action( 'created_term', $term_id, $tt_id, $taxonomy );

	/**
	 * Fires after a new term in a specific taxonomy is created, and after the term
	 * cache has been cleaned.
	 *
	 * The dynamic portion of the hook name, `$taxonomy`, refers to the taxonomy slug.
	 *
	 * @since 2.3.0
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id   Term taxonomy ID.
	 */
	do_action( "created_{$taxonomy}", $term_id, $tt_id );

	return array('term_id' => $term_id, 'term_taxonomy_id' => $tt_id);
}

/**
 * Create Term and Taxonomy Relationships.
 *
 * Relates an object (post, link etc) to a term and taxonomy type. Creates the
 * term and taxonomy relationship if it doesn't already exist. Creates a term if
 * it doesn't exist (using the slug).
 *
 * A relationship means that the term is grouped in or belongs to the taxonomy.
 * A term has no meaning until it is given context by defining which taxonomy it
 * exists under.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb The WordPress database abstraction object.
 *
 * @param int              $object_id The object to relate to.
 * @param string|int|array $terms     A single term slug, single term id, or array of either term slugs or ids.
 *                                    Will replace all existing related terms in this taxonomy. Passing an
 *                                    empty value will remove all related terms.
 * @param string           $taxonomy  The context in which to relate the term to the object.
 * @param bool             $append    Optional. If false will delete difference of terms. Default false.
 * @return array|WP_Error Term taxonomy IDs of the affected terms.
 */
function wp_set_object_terms( $object_id, $terms, $taxonomy, $append = false ) {
	global $wpdb;

	$object_id = (int) $object_id;

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
	}

	if ( !is_array($terms) )
		$terms = array($terms);

	if ( ! $append )
		$old_tt_ids =  wp_get_object_terms($object_id, $taxonomy, array('fields' => 'tt_ids', 'orderby' => 'none'));
	else
		$old_tt_ids = array();

	$tt_ids = array();
	$term_ids = array();
	$new_tt_ids = array();

	foreach ( (array) $terms as $term) {
		if ( !strlen(trim($term)) )
			continue;

		if ( !$term_info = term_exists($term, $taxonomy) ) {
			// Skip if a non-existent term ID is passed.
			if ( is_int($term) )
				continue;
			$term_info = wp_insert_term($term, $taxonomy);
		}
		if ( is_wp_error($term_info) )
			return $term_info;
		$term_ids[] = $term_info['term_id'];
		$tt_id = $term_info['term_taxonomy_id'];
		$tt_ids[] = $tt_id;

		if ( $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM $wpdb->term_relationships WHERE object_id = %d AND term_taxonomy_id = %d", $object_id, $tt_id ) ) )
			continue;

		/**
		 * Fires immediately before an object-term relationship is added.
		 *
		 * @since 2.9.0
		 * @since 4.7.0 Added the `$taxonomy` parameter.
		 *
		 * @param int    $object_id Object ID.
		 * @param int    $tt_id     Term taxonomy ID.
		 * @param string $taxonomy  Taxonomy slug.
		 */
		do_action( 'add_term_relationship', $object_id, $tt_id, $taxonomy );
		$wpdb->insert( $wpdb->term_relationships, array( 'object_id' => $object_id, 'term_taxonomy_id' => $tt_id ) );

		/**
		 * Fires immediately after an object-term relationship is added.
		 *
		 * @since 2.9.0
		 * @since 4.7.0 Added the `$taxonomy` parameter.
		 *
		 * @param int    $object_id Object ID.
		 * @param int    $tt_id     Term taxonomy ID.
		 * @param string $taxonomy  Taxonomy slug.
		 */
		do_action( 'added_term_relationship', $object_id, $tt_id, $taxonomy );
		$new_tt_ids[] = $tt_id;
	}

	if ( $new_tt_ids )
		wp_update_term_count( $new_tt_ids, $taxonomy );

	if ( ! $append ) {
		$delete_tt_ids = array_diff( $old_tt_ids, $tt_ids );

		if ( $delete_tt_ids ) {
			$in_delete_tt_ids = "'" . implode( "', '", $delete_tt_ids ) . "'";
			$delete_term_ids = $wpdb->get_col( $wpdb->prepare( "SELECT tt.term_id FROM $wpdb->term_taxonomy AS tt WHERE tt.taxonomy = %s AND tt.term_taxonomy_id IN ($in_delete_tt_ids)", $taxonomy ) );
			$delete_term_ids = array_map( 'intval', $delete_term_ids );

			$remove = wp_remove_object_terms( $object_id, $delete_term_ids, $taxonomy );
			if ( is_wp_error( $remove ) ) {
				return $remove;
			}
		}
	}

	$t = get_taxonomy($taxonomy);
	if ( ! $append && isset($t->sort) && $t->sort ) {
		$values = array();
		$term_order = 0;
		$final_tt_ids = wp_get_object_terms($object_id, $taxonomy, array('fields' => 'tt_ids'));
		foreach ( $tt_ids as $tt_id )
			if ( in_array($tt_id, $final_tt_ids) )
				$values[] = $wpdb->prepare( "(%d, %d, %d)", $object_id, $tt_id, ++$term_order);
		if ( $values )
			if ( false === $wpdb->query( "INSERT INTO $wpdb->term_relationships (object_id, term_taxonomy_id, term_order) VALUES " . join( ',', $values ) . " ON DUPLICATE KEY UPDATE term_order = VALUES(term_order)" ) )
				return new WP_Error( 'db_insert_error', __( 'Could not insert term relationship into the database.' ), $wpdb->last_error );
	}

	wp_cache_delete( $object_id, $taxonomy . '_relationships' );
	wp_cache_delete( 'last_changed', 'terms' );

	/**
	 * Fires after an object's terms have been set.
	 *
	 * @since 2.8.0
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      An array of object terms.
	 * @param array  $tt_ids     An array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether to append new terms to the old terms.
	 * @param array  $old_tt_ids Old array of term taxonomy IDs.
	 */
	do_action( 'set_object_terms', $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids );
	return $tt_ids;
}

/**
 * Add term(s) associated with a given object.
 *
 * @since 3.6.0
 *
 * @param int              $object_id The ID of the object to which the terms will be added.
 * @param string|int|array $terms     The slug(s) or ID(s) of the term(s) to add.
 * @param array|string     $taxonomy  Taxonomy name.
 * @return array|WP_Error Term taxonomy IDs of the affected terms.
 */
function wp_add_object_terms( $object_id, $terms, $taxonomy ) {
	return wp_set_object_terms( $object_id, $terms, $taxonomy, true );
}

/**
 * Remove term(s) associated with a given object.
 *
 * @since 3.6.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int              $object_id The ID of the object from which the terms will be removed.
 * @param string|int|array $terms     The slug(s) or ID(s) of the term(s) to remove.
 * @param array|string     $taxonomy  Taxonomy name.
 * @return bool|WP_Error True on success, false or WP_Error on failure.
 */
function wp_remove_object_terms( $object_id, $terms, $taxonomy ) {
	global $wpdb;

	$object_id = (int) $object_id;

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.' ) );
	}

	if ( ! is_array( $terms ) ) {
		$terms = array( $terms );
	}

	$tt_ids = array();

	foreach ( (array) $terms as $term ) {
		if ( ! strlen( trim( $term ) ) ) {
			continue;
		}

		if ( ! $term_info = term_exists( $term, $taxonomy ) ) {
			// Skip if a non-existent term ID is passed.
			if ( is_int( $term ) ) {
				continue;
			}
		}

		if ( is_wp_error( $term_info ) ) {
			return $term_info;
		}

		$tt_ids[] = $term_info['term_taxonomy_id'];
	}

	if ( $tt_ids ) {
		$in_tt_ids = "'" . implode( "', '", $tt_ids ) . "'";

		/**
		 * Fires immediately before an object-term relationship is deleted.
		 *
		 * @since 2.9.0
		 * @since 4.7.0 Added the `$taxonomy` parameter.
		 *
		 * @param int   $object_id Object ID.
		 * @param array $tt_ids    An array of term taxonomy IDs.
		 * @param string $taxonomy  Taxonomy slug.
		 */
		do_action( 'delete_term_relationships', $object_id, $tt_ids, $taxonomy );
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->term_relationships WHERE object_id = %d AND term_taxonomy_id IN ($in_tt_ids)", $object_id ) );

		wp_cache_delete( $object_id, $taxonomy . '_relationships' );
		wp_cache_delete( 'last_changed', 'terms' );

		/**
		 * Fires immediately after an object-term relationship is deleted.
		 *
		 * @since 2.9.0
		 * @since 4.7.0 Added the `$taxonomy` parameter.
		 *
		 * @param int    $object_id Object ID.
		 * @param array  $tt_ids    An array of term taxonomy IDs.
		 * @param string $taxonomy  Taxonomy slug.
		 */
		do_action( 'deleted_term_relationships', $object_id, $tt_ids, $taxonomy );

		wp_update_term_count( $tt_ids, $taxonomy );

		return (bool) $deleted;
	}

	return false;
}

// refactored. function wp_unique_term_slug( $slug, $term ) {}
// refactored. function wp_update_term( $term_id, $taxonomy, $args = array() ) {}

/**
 * Enable or disable term counting.
 *
 * @since 2.5.0
 *
 * @staticvar bool $_defer
 *
 * @param bool $defer Optional. Enable if true, disable if false.
 * @return bool Whether term counting is enabled or disabled.
 */
function wp_defer_term_counting($defer=null) {
	static $_defer = false;

	if ( is_bool($defer) ) {
		$_defer = $defer;
		// flush any deferred counts
		if ( !$defer )
			wp_update_term_count( null, null, true );
	}

	return $_defer;
}

/**
 * Updates the amount of terms in taxonomy.
 *
 * If there is a taxonomy callback applied, then it will be called for updating
 * the count.
 *
 * The default action is to count what the amount of terms have the relationship
 * of term ID. Once that is done, then update the database.
 *
 * @since 2.3.0
 *
 * @staticvar array $_deferred
 *
 * @param int|array $terms       The term_taxonomy_id of the terms.
 * @param string    $taxonomy    The context of the term.
 * @param bool      $do_deferred Whether to flush the deferred term counts too. Default false.
 * @return bool If no terms will return false, and if successful will return true.
 */
function wp_update_term_count( $terms, $taxonomy, $do_deferred = false ) {
	static $_deferred = array();

	if ( $do_deferred ) {
		foreach ( (array) array_keys($_deferred) as $tax ) {
			wp_update_term_count_now( $_deferred[$tax], $tax );
			unset( $_deferred[$tax] );
		}
	}

	if ( empty($terms) )
		return false;

	if ( !is_array($terms) )
		$terms = array($terms);

	if ( wp_defer_term_counting() ) {
		if ( !isset($_deferred[$taxonomy]) )
			$_deferred[$taxonomy] = array();
		$_deferred[$taxonomy] = array_unique( array_merge($_deferred[$taxonomy], $terms) );
		return true;
	}

	return wp_update_term_count_now( $terms, $taxonomy );
}

/**
 * Perform term count update immediately.
 *
 * @since 2.5.0
 *
 * @param array  $terms    The term_taxonomy_id of terms to update.
 * @param string $taxonomy The context of the term.
 * @return true Always true when complete.
 */
function wp_update_term_count_now( $terms, $taxonomy ) {
	$terms = array_map('intval', $terms);

	$taxonomy = get_taxonomy($taxonomy);
	if ( !empty($taxonomy->update_count_callback) ) {
		call_user_func($taxonomy->update_count_callback, $terms, $taxonomy);
	} else {
		$object_types = (array) $taxonomy->object_type;
		foreach ( $object_types as &$object_type ) {
			if ( 0 === strpos( $object_type, 'attachment:' ) )
				list( $object_type ) = explode( ':', $object_type );
		}

		if ( $object_types == array_filter( $object_types, 'post_type_exists' ) ) {
			// Only post types are attached to this taxonomy
			_update_post_term_count( $terms, $taxonomy );
		} else {
			// Default count updater
			_update_generic_term_count( $terms, $taxonomy );
		}
	}

	clean_term_cache($terms, '', false);

	return true;
}

//
// Cache
//

// refactored. function clean_object_term_cache($object_ids, $object_type) {}
// :
// refactored. function _prime_term_caches( $term_ids, $update_meta_cache = true ) {}

//
// Default callbacks
//

/**
 * Will update term count based on object types of the current taxonomy.
 *
 * Private function for the default callback for post_tag and category
 * taxonomies.
 *
 * @access private
 * @since 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array  $terms    List of Term taxonomy IDs.
 * @param object $taxonomy Current taxonomy object of terms.
 */
function _update_post_term_count( $terms, $taxonomy ) {
	global $wpdb;

	$object_types = (array) $taxonomy->object_type;

	foreach ( $object_types as &$object_type )
		list( $object_type ) = explode( ':', $object_type );

	$object_types = array_unique( $object_types );

	if ( false !== ( $check_attachments = array_search( 'attachment', $object_types ) ) ) {
		unset( $object_types[ $check_attachments ] );
		$check_attachments = true;
	}

	if ( $object_types )
		$object_types = esc_sql( array_filter( $object_types, 'post_type_exists' ) );

	foreach ( (array) $terms as $term ) {
		$count = 0;

		// Attachments can be 'inherit' status, we need to base count off the parent's status if so.
		if ( $check_attachments )
			$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts p1 WHERE p1.ID = $wpdb->term_relationships.object_id AND ( post_status = 'publish' OR ( post_status = 'inherit' AND post_parent > 0 AND ( SELECT post_status FROM $wpdb->posts WHERE ID = p1.post_parent ) = 'publish' ) ) AND post_type = 'attachment' AND term_taxonomy_id = %d", $term ) );

		if ( $object_types )
			$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status = 'publish' AND post_type IN ('" . implode("', '", $object_types ) . "') AND term_taxonomy_id = %d", $term ) );

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edit_term_taxonomy', $term, $taxonomy->name );
		$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edited_term_taxonomy', $term, $taxonomy->name );
	}
}

/**
 * Will update term count based on number of objects.
 *
 * Default callback for the 'link_category' taxonomy.
 *
 * @since 3.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array  $terms    List of term taxonomy IDs.
 * @param object $taxonomy Current taxonomy object of terms.
 */
function _update_generic_term_count( $terms, $taxonomy ) {
	global $wpdb;

	foreach ( (array) $terms as $term ) {
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term ) );

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edit_term_taxonomy', $term, $taxonomy->name );
		$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edited_term_taxonomy', $term, $taxonomy->name );
	}
}

function _split_shared_term( $term_id, $term_taxonomy_id, $record = true ) {}

/**
 * Splits a batch of shared taxonomy terms.
 *
 * @since 4.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function _wp_batch_split_terms() {
	global $wpdb;

	$lock_name = 'term_split.lock';

	// Try to lock.
	$lock_result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES (%s, %s, 'no') /* LOCK */", $lock_name, time() ) );

	if ( ! $lock_result ) {
		$lock_result = get_option( $lock_name );

		// Bail if we were unable to create a lock, or if the existing lock is still valid.
		if ( ! $lock_result || ( $lock_result > ( time() - HOUR_IN_SECONDS ) ) ) {
			wp_schedule_single_event( time() + ( 5 * MINUTE_IN_SECONDS ), 'wp_split_shared_term_batch' );
			return;
		}
	}

	// Update the lock, as by this point we've definitely got a lock, just need to fire the actions.
	update_option( $lock_name, time() );

	// Get a list of shared terms (those with more than one associated row in term_taxonomy).
	$shared_terms = $wpdb->get_results(
		"SELECT tt.term_id, t.*, count(*) as term_tt_count FROM {$wpdb->term_taxonomy} tt
		 LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
		 GROUP BY t.term_id
		 HAVING term_tt_count > 1
		 LIMIT 10"
	);

	// No more terms, we're done here.
	if ( ! $shared_terms ) {
		update_option( 'finished_splitting_shared_terms', true );
		delete_option( $lock_name );
		return;
	}

	// Shared terms found? We'll need to run this script again.
	wp_schedule_single_event( time() + ( 2 * MINUTE_IN_SECONDS ), 'wp_split_shared_term_batch' );

	// Rekey shared term array for faster lookups.
	$_shared_terms = array();
	foreach ( $shared_terms as $shared_term ) {
		$term_id = intval( $shared_term->term_id );
		$_shared_terms[ $term_id ] = $shared_term;
	}
	$shared_terms = $_shared_terms;

	// Get term taxonomy data for all shared terms.
	$shared_term_ids = implode( ',', array_keys( $shared_terms ) );
	$shared_tts = $wpdb->get_results( "SELECT * FROM {$wpdb->term_taxonomy} WHERE `term_id` IN ({$shared_term_ids})" );

	// Split term data recording is slow, so we do it just once, outside the loop.
	$split_term_data = get_option( '_split_terms', array() );
	$skipped_first_term = $taxonomies = array();
	foreach ( $shared_tts as $shared_tt ) {
		$term_id = intval( $shared_tt->term_id );

		// Don't split the first tt belonging to a given term_id.
		if ( ! isset( $skipped_first_term[ $term_id ] ) ) {
			$skipped_first_term[ $term_id ] = 1;
			continue;
		}

		if ( ! isset( $split_term_data[ $term_id ] ) ) {
			$split_term_data[ $term_id ] = array();
		}

		// Keep track of taxonomies whose hierarchies need flushing.
		if ( ! isset( $taxonomies[ $shared_tt->taxonomy ] ) ) {
			$taxonomies[ $shared_tt->taxonomy ] = 1;
		}

		// Split the term.
		$split_term_data[ $term_id ][ $shared_tt->taxonomy ] = _split_shared_term( $shared_terms[ $term_id ], $shared_tt, false );
	}

	// Rebuild the cached hierarchy for each affected taxonomy.
	foreach ( array_keys( $taxonomies ) as $tax ) {
		delete_option( "{$tax}_children" );
		_get_term_hierarchy( $tax );
	}

	update_option( '_split_terms', $split_term_data );

	delete_option( $lock_name );
}

/**
 * In order to avoid the _wp_batch_split_terms() job being accidentally removed,
 * check that it's still scheduled while we haven't finished splitting terms.
 *
 * @ignore
 * @since 4.3.0
 */
function _wp_check_for_scheduled_split_terms() {
	if ( ! get_option( 'finished_splitting_shared_terms' ) && ! wp_next_scheduled( 'wp_split_shared_term_batch' ) ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'wp_split_shared_term_batch' );
	}
}

/**
 * Check default categories when a term gets split to see if any of them need to be updated.
 *
 * @ignore
 * @since 4.2.0
 *
 * @param int    $term_id          ID of the formerly shared term.
 * @param int    $new_term_id      ID of the new term created for the $term_taxonomy_id.
 * @param int    $term_taxonomy_id ID for the term_taxonomy row affected by the split.
 * @param string $taxonomy         Taxonomy for the split term.
 */
function _wp_check_split_default_terms( $term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {
	if ( 'category' != $taxonomy ) {
		return;
	}

	foreach ( array( 'default_category', 'default_link_category', 'default_email_category' ) as $option ) {
		if ( $term_id == get_option( $option, -1 ) ) {
			update_option( $option, $new_term_id );
		}
	}
}

/**
 * Check menu items when a term gets split to see if any of them need to be updated.
 *
 * @ignore
 * @since 4.2.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $term_id          ID of the formerly shared term.
 * @param int    $new_term_id      ID of the new term created for the $term_taxonomy_id.
 * @param int    $term_taxonomy_id ID for the term_taxonomy row affected by the split.
 * @param string $taxonomy         Taxonomy for the split term.
 */
function _wp_check_split_terms_in_menus( $term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {
	global $wpdb;
	$post_ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT m1.post_id
		FROM {$wpdb->postmeta} AS m1
			INNER JOIN {$wpdb->postmeta} AS m2 ON ( m2.post_id = m1.post_id )
			INNER JOIN {$wpdb->postmeta} AS m3 ON ( m3.post_id = m1.post_id )
		WHERE ( m1.meta_key = '_menu_item_type' AND m1.meta_value = 'taxonomy' )
			AND ( m2.meta_key = '_menu_item_object' AND m2.meta_value = %s )
			AND ( m3.meta_key = '_menu_item_object_id' AND m3.meta_value = %d )",
		$taxonomy,
		$term_id
	) );

	if ( $post_ids ) {
		foreach ( $post_ids as $post_id ) {
			update_post_meta( $post_id, '_menu_item_object_id', $new_term_id, $term_id );
		}
	}
}

/**
 * If the term being split is a nav_menu, change associations.
 *
 * @ignore
 * @since 4.3.0
 *
 * @param int    $term_id          ID of the formerly shared term.
 * @param int    $new_term_id      ID of the new term created for the $term_taxonomy_id.
 * @param int    $term_taxonomy_id ID for the term_taxonomy row affected by the split.
 * @param string $taxonomy         Taxonomy for the split term.
 */
function _wp_check_split_nav_menu_terms( $term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {
	if ( 'nav_menu' !== $taxonomy ) {
		return;
	}

	// Update menu locations.
	$locations = get_nav_menu_locations();
	foreach ( $locations as $location => $menu_id ) {
		if ( $term_id == $menu_id ) {
			$locations[ $location ] = $new_term_id;
		}
	}
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Get data about terms that previously shared a single term_id, but have since been split.
 *
 * @since 4.2.0
 *
 * @param int $old_term_id Term ID. This is the old, pre-split term ID.
 * @return array Array of new term IDs, keyed by taxonomy.
 */
function wp_get_split_terms( $old_term_id ) {
	$split_terms = get_option( '_split_terms', array() );

	$terms = array();
	if ( isset( $split_terms[ $old_term_id ] ) ) {
		$terms = $split_terms[ $old_term_id ];
	}

	return $terms;
}

/**
 * Get the new term ID corresponding to a previously split term.
 *
 * @since 4.2.0
 *
 * @param int    $old_term_id Term ID. This is the old, pre-split term ID.
 * @param string $taxonomy    Taxonomy that the term belongs to.
 * @return int|false If a previously split term is found corresponding to the old term_id and taxonomy,
 *                   the new term_id will be returned. If no previously split term is found matching
 *                   the parameters, returns false.
 */
function wp_get_split_term( $old_term_id, $taxonomy ) {
	$split_terms = wp_get_split_terms( $old_term_id );

	$term_id = false;
	if ( isset( $split_terms[ $taxonomy ] ) ) {
		$term_id = (int) $split_terms[ $taxonomy ];
	}

	return $term_id;
}

/**
 * Determine whether a term is shared between multiple taxonomies.
 *
 * Shared taxonomy terms began to be split in 4.3, but failed cron tasks or
 * other delays in upgrade routines may cause shared terms to remain.
 *
 * @since 4.4.0
 *
 * @param int $term_id Term ID.
 * @return bool Returns false if a term is not shared between multiple taxonomies or
 *              if splittng shared taxonomy terms is finished.
 */
function wp_term_is_shared( $term_id ) {
	global $wpdb;

	if ( get_option( 'finished_splitting_shared_terms' ) ) {
		return false;
	}

	$tt_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE term_id = %d", $term_id ) );

	return $tt_count > 1;
}

/**
 * Generate a permalink for a taxonomy term archive.
 *
 * @since 2.5.0
 *
 * @global WP_Rewrite $wp_rewrite
 *
 * @param object|int|string $term     The term object, ID, or slug whose link will be retrieved.
 * @param string            $taxonomy Optional. Taxonomy. Default empty.
 * @return string|WP_Error HTML link to taxonomy term archive on success, WP_Error if term does not exist.
 */
function get_term_link( $term, $taxonomy = '' ) {
	global $wp_rewrite;

	if ( !is_object($term) ) {
		if ( is_int( $term ) ) {
			$term = get_term( $term, $taxonomy );
		} else {
			$term = get_term_by( 'slug', $term, $taxonomy );
		}
	}

	if ( !is_object($term) )
		$term = new WP_Error( 'invalid_term', __( 'Empty Term.' ) );

	if ( is_wp_error( $term ) )
		return $term;

	$taxonomy = $term->taxonomy;

	$termlink = $wp_rewrite->get_extra_permastruct($taxonomy);

	/**
	 * Filters the permalink structure for a terms before token replacement occurs.
	 *
	 * @since 4.9.0
	 *
	 * @param string  $termlink The permalink structure for the term's taxonomy.
	 * @param WP_Term $term     The term object.
	 */
	$termlink = apply_filters( 'pre_term_link', $termlink, $term );

	$slug = $term->slug;
	$t = get_taxonomy($taxonomy);

	if ( empty($termlink) ) {
		if ( 'category' == $taxonomy )
			$termlink = '?cat=' . $term->term_id;
		elseif ( $t->query_var )
			$termlink = "?$t->query_var=$slug";
		else
			$termlink = "?taxonomy=$taxonomy&term=$slug";
		$termlink = home_url($termlink);
	} else {
		if ( $t->rewrite['hierarchical'] ) {
			$hierarchical_slugs = array();
			$ancestors = get_ancestors( $term->term_id, $taxonomy, 'taxonomy' );
			foreach ( (array)$ancestors as $ancestor ) {
				$ancestor_term = get_term($ancestor, $taxonomy);
				$hierarchical_slugs[] = $ancestor_term->slug;
			}
			$hierarchical_slugs = array_reverse($hierarchical_slugs);
			$hierarchical_slugs[] = $slug;
			$termlink = str_replace("%$taxonomy%", implode('/', $hierarchical_slugs), $termlink);
		} else {
			$termlink = str_replace("%$taxonomy%", $slug, $termlink);
		}
		$termlink = home_url( user_trailingslashit($termlink, 'category') );
	}
	// Back Compat filters.
	if ( 'post_tag' == $taxonomy ) {

		/**
		 * Filters the tag link.
		 *
		 * @since 2.3.0
		 * @deprecated 2.5.0 Use 'term_link' instead.
		 *
		 * @param string $termlink Tag link URL.
		 * @param int    $term_id  Term ID.
		 */
		$termlink = apply_filters( 'tag_link', $termlink, $term->term_id );
	} elseif ( 'category' == $taxonomy ) {

		/**
		 * Filters the category link.
		 *
		 * @since 1.5.0
		 * @deprecated 2.5.0 Use 'term_link' instead.
		 *
		 * @param string $termlink Category link URL.
		 * @param int    $term_id  Term ID.
		 */
		$termlink = apply_filters( 'category_link', $termlink, $term->term_id );
	}

	/**
	 * Filters the term link.
	 *
	 * @since 2.5.0
	 *
	 * @param string $termlink Term link URL.
	 * @param object $term     Term object.
	 * @param string $taxonomy Taxonomy slug.
	 */
	return apply_filters( 'term_link', $termlink, $term, $taxonomy );
}

/**
 * Display the taxonomies of a post with available options.
 *
 * This function can be used within the loop to display the taxonomies for a
 * post without specifying the Post ID. You can also use it outside the Loop to
 * display the taxonomies for a specific post.
 *
 * @since 2.5.0
 *
 * @param array $args {
 *     Arguments about which post to use and how to format the output. Shares all of the arguments
 *     supported by get_the_taxonomies(), in addition to the following.
 *
 *     @type  int|WP_Post $post   Post ID or object to get taxonomies of. Default current post.
 *     @type  string      $before Displays before the taxonomies. Default empty string.
 *     @type  string      $sep    Separates each taxonomy. Default is a space.
 *     @type  string      $after  Displays after the taxonomies. Default empty string.
 * }
 */
function the_taxonomies( $args = array() ) {
	$defaults = array(
		'post' => 0,
		'before' => '',
		'sep' => ' ',
		'after' => '',
	);

	$r = wp_parse_args( $args, $defaults );

	echo $r['before'] . join( $r['sep'], get_the_taxonomies( $r['post'], $r ) ) . $r['after'];
}

/**
 * Retrieve all taxonomies associated with a post.
 *
 * This function can be used within the loop. It will also return an array of
 * the taxonomies with links to the taxonomy and name.
 *
 * @since 2.5.0
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @param array $args {
 *     Optional. Arguments about how to format the list of taxonomies. Default empty array.
 *
 *     @type string $template      Template for displaying a taxonomy label and list of terms.
 *                                 Default is "Label: Terms."
 *     @type string $term_template Template for displaying a single term in the list. Default is the term name
 *                                 linked to its archive.
 * }
 * @return array List of taxonomies.
 */
function get_the_taxonomies( $post = 0, $args = array() ) {
	$post = get_post( $post );

	$args = wp_parse_args( $args, array(
		/* translators: %s: taxonomy label, %l: list of terms formatted as per $term_template */
		'template' => __( '%s: %l.' ),
		'term_template' => '<a href="%1$s">%2$s</a>',
	) );

	$taxonomies = array();

	if ( ! $post ) {
		return $taxonomies;
	}

	foreach ( get_object_taxonomies( $post ) as $taxonomy ) {
		$t = (array) get_taxonomy( $taxonomy );
		if ( empty( $t['label'] ) ) {
			$t['label'] = $taxonomy;
		}
		if ( empty( $t['args'] ) ) {
			$t['args'] = array();
		}
		if ( empty( $t['template'] ) ) {
			$t['template'] = $args['template'];
		}
		if ( empty( $t['term_template'] ) ) {
			$t['term_template'] = $args['term_template'];
		}

		$terms = get_object_term_cache( $post->ID, $taxonomy );
		if ( false === $terms ) {
			$terms = wp_get_object_terms( $post->ID, $taxonomy, $t['args'] );
		}
		$links = array();

		foreach ( $terms as $term ) {
			$links[] = wp_sprintf( $t['term_template'], esc_attr( get_term_link( $term ) ), $term->name );
		}
		if ( $links ) {
			$taxonomies[$taxonomy] = wp_sprintf( $t['template'], $t['label'], $links, $terms );
		}
	}
	return $taxonomies;
}

/**
 * Retrieve all taxonomies of a post with just the names.
 *
 * @since 2.5.0
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @return array An array of all taxonomy names for the given post.
 */
function get_post_taxonomies( $post = 0 ) {
	$post = get_post( $post );

	return get_object_taxonomies($post);
}

/**
 * Determine if the given object is associated with any of the given terms.
 *
 * The given terms are checked against the object's terms' term_ids, names and slugs.
 * Terms given as integers will only be checked against the object's terms' term_ids.
 * If no terms are given, determines if object is associated with any terms in the given taxonomy.
 *
 * @since 2.7.0
 *
 * @param int              $object_id ID of the object (post ID, link ID, ...).
 * @param string           $taxonomy  Single taxonomy name.
 * @param int|string|array $terms     Optional. Term term_id, name, slug or array of said. Default null.
 * @return bool|WP_Error WP_Error on input error.
 */
function is_object_in_term( $object_id, $taxonomy, $terms = null ) {
	if ( !$object_id = (int) $object_id )
		return new WP_Error( 'invalid_object', __( 'Invalid object ID.' ) );

	$object_terms = get_object_term_cache( $object_id, $taxonomy );
	if ( false === $object_terms ) {
		$object_terms = wp_get_object_terms( $object_id, $taxonomy, array( 'update_term_meta_cache' => false ) );
		if ( is_wp_error( $object_terms ) ) {
			return $object_terms;
		}

		wp_cache_set( $object_id, wp_list_pluck( $object_terms, 'term_id' ), "{$taxonomy}_relationships" );
	}

	if ( is_wp_error( $object_terms ) )
		return $object_terms;
	if ( empty( $object_terms ) )
		return false;
	if ( empty( $terms ) )
		return ( !empty( $object_terms ) );

	$terms = (array) $terms;

	if ( $ints = array_filter( $terms, 'is_int' ) )
		$strs = array_diff( $terms, $ints );
	else
		$strs =& $terms;

	foreach ( $object_terms as $object_term ) {
		// If term is an int, check against term_ids only.
		if ( $ints && in_array( $object_term->term_id, $ints ) ) {
			return true;
		}

		if ( $strs ) {
			// Only check numeric strings against term_id, to avoid false matches due to type juggling.
			$numeric_strs = array_map( 'intval', array_filter( $strs, 'is_numeric' ) );
			if ( in_array( $object_term->term_id, $numeric_strs, true ) ) {
				return true;
			}

			if ( in_array( $object_term->name, $strs ) ) return true;
			if ( in_array( $object_term->slug, $strs ) ) return true;
		}
	}

	return false;
}

// refactored. function is_object_in_taxonomy( $object_type, $taxonomy ) {}

/**
 * Get an array of ancestor IDs for a given object.
 *
 * @since 3.1.0
 * @since 4.1.0 Introduced the `$resource_type` argument.
 *
 * @param int    $object_id     Optional. The ID of the object. Default 0.
 * @param string $object_type   Optional. The type of object for which we'll be retrieving
 *                              ancestors. Accepts a post type or a taxonomy name. Default empty.
 * @param string $resource_type Optional. Type of resource $object_type is. Accepts 'post_type'
 *                              or 'taxonomy'. Default empty.
 * @return array An array of ancestors from lowest to highest in the hierarchy.
 */
function get_ancestors( $object_id = 0, $object_type = '', $resource_type = '' ) {
	$object_id = (int) $object_id;

	$ancestors = array();

	if ( empty( $object_id ) ) {

		/** This filter is documented in wp-includes/taxonomy.php */
		return apply_filters( 'get_ancestors', $ancestors, $object_id, $object_type, $resource_type );
	}

	if ( ! $resource_type ) {
		if ( is_taxonomy_hierarchical( $object_type ) ) {
			$resource_type = 'taxonomy';
		} elseif ( post_type_exists( $object_type ) ) {
			$resource_type = 'post_type';
		}
	}

	if ( 'taxonomy' === $resource_type ) {
		$term = get_term($object_id, $object_type);
		while ( ! is_wp_error($term) && ! empty( $term->parent ) && ! in_array( $term->parent, $ancestors ) ) {
			$ancestors[] = (int) $term->parent;
			$term = get_term($term->parent, $object_type);
		}
	} elseif ( 'post_type' === $resource_type ) {
		$ancestors = get_post_ancestors($object_id);
	}

	/**
	 * Filters a given object's ancestors.
	 *
	 * @since 3.1.0
	 * @since 4.1.1 Introduced the `$resource_type` parameter.
	 *
	 * @param array  $ancestors     An array of object ancestors.
	 * @param int    $object_id     Object ID.
	 * @param string $object_type   Type of object.
	 * @param string $resource_type Type of resource $object_type is.
	 */
	return apply_filters( 'get_ancestors', $ancestors, $object_id, $object_type, $resource_type );
}

/**
 * Returns the term's parent's term_ID.
 *
 * @since 3.1.0
 *
 * @param int    $term_id  Term ID.
 * @param string $taxonomy Taxonomy name.
 * @return int|false False on error.
 */
function wp_get_term_taxonomy_parent_id( $term_id, $taxonomy ) {
	$term = get_term( $term_id, $taxonomy );
	if ( ! $term || is_wp_error( $term ) ) {
		return false;
	}
	return (int) $term->parent;
}

/**
 * Checks the given subset of the term hierarchy for hierarchy loops.
 * Prevents loops from forming and breaks those that it finds.
 *
 * Attached to the {@see 'wp_update_term_parent'} filter.
 *
 * @since 3.1.0
 *
 * @param int    $parent   `term_id` of the parent for the term we're checking.
 * @param int    $term_id  The term we're checking.
 * @param string $taxonomy The taxonomy of the term we're checking.
 *
 * @return int The new parent for the term.
 */
function wp_check_term_hierarchy_for_loops( $parent, $term_id, $taxonomy ) {
	// Nothing fancy here - bail
	if ( !$parent )
		return 0;

	// Can't be its own parent.
	if ( $parent == $term_id )
		return 0;

	// Now look for larger loops.
	if ( !$loop = wp_find_hierarchy_loop( 'wp_get_term_taxonomy_parent_id', $term_id, $parent, array( $taxonomy ) ) )
		return $parent; // No loop

	// Setting $parent to the given value causes a loop.
	if ( isset( $loop[$term_id] ) )
		return 0;

	// There's a loop, but it doesn't contain $term_id. Break the loop.
	foreach ( array_keys( $loop ) as $loop_member )
		wp_update_term( $loop_member, $taxonomy, array( 'parent' => 0 ) );

	return $parent;
}
