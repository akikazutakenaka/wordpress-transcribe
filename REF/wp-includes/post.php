<?php
/**
 * Core Post API
 *
 * @package    WordPress
 * @subpackage Post
 */

/**
 * Retrieve attached file path based on attachment ID.
 *
 * By default the path will go through the 'get_attached_file' filter, but passing a true to the $unfiltered argument of get_attached_file() will return the file path unfiltered.
 *
 * The function works by getting the single post meta name, named '_wp_attached_file' and returning it.
 * This is a convenience function to prevent looking up the meta name and provide a mechanism for sending the attached filename through a filter.
 *
 * @since 2.0.0
 *
 * @param  int          $attachment_id Attachment ID.
 * @param  bool         $unfiltered    Optional.
 *                                     Whether to apply filters.
 *                                     Default false.
 * @return string|false The file path to where the attached file should be, false otherwise.
 */
function get_attached_file( $attachment_id, $unfiltered = FALSE )
{
	$file = get_post_meta( $attachment_id, '_wp_attached_file', TRUE );

	// If the file is relative, prepend upload dir.
	if ( $file && 0 !== strpos( $file, '/' ) && ! preg_match( '|^.:\\\|', $file ) && $uploads = wp_get_upload_dir() && FALSE === $uploads['error'] ) {
		$file = $uploads['basedir'] . "/$file";
	}

	if ( $unfiltered ) {
		return $file;
	}

	return $unfiltered
		? $file
		: /**
		   * Filters the attached file based on the given ID.
		   *
		   * @since 2.1.0
		   *
		   * @param string $file          Path to attached file.
		   * @param int    $attachment_id Attachment ID.
		   */
			apply_filters( 'get_attached_file', $file, $attachment_id );
}

/**
 * Retrieves post data given a post ID or post object.
 *
 * See sanitize_post() for optional $filter values.
 * Also, the parameter `$post`, must be given as a variable, since it is passed by reference.
 *
 * @since  1.5.1
 * @global WP_Post $post
 *
 * @param  int|WP_Post|null   $post   Optional.
 *                                    Post ID or post object.
 *                                    Defaults to global $post.
 * @param  string             $output Optional.
 *                                    The required return type.
 *                                    One of OBJECT, ARRAY_A, or ARRAY_N, which correspond to a WP_Post object, an associative array, or a numeric array, respectively.
 *                                    Default OBJECT.
 * @param  string             $filter Optional.
 *                                    Type of filter to apply.
 *                                    Accepts 'raw', 'edit', 'db', or 'display'.
 *                                    Default 'raw'.
 * @return WP_Post|array|null Type corresponding to $output on success or null on failure.
 *                            When $output is OBJECT, a `WP_Post` instance is returned.
 */
function get_post( $post = NULL, $output = OBJECT, $filter = 'raw' )
{
	if ( empty( $post ) && isset( $GLOBALS['post'] ) ) {
		$post = $GLOBALS['post'];
	}

	$_post = $post instanceof WP_Post
		? $post
		: ( is_object( $post )
			? ( empty( $post->filter )
				? new WP_Post( sanitize_post( $post, 'raw' ) )
				: ( 'raw' == $post->filter
					? new WP_Post( $post )
					: WP_Post::get_instance( $post->ID ) ) )
			: WP_Post::get_instance( $post ) );

	if ( ! $_post ) {
		return NULL;
	}

	$_post = $_post->filter( $filter );

	return $output == ARRAY_A
		? $_post->to_array()
		: ( $output == ARRAY_N
			? array_values( $_post->to_array() )
			: $_post );
}

/**
 * Retrieve ancestors of a post.
 *
 * @since 2.5.0
 *
 * @param  int|WP_Post $post Post ID or post object.
 * @return array       Ancestor IDs or empty array if none are found.
 */
function get_post_ancestors( $post )
{
	$post = get_post( $post );

	if ( ! $post || empty( $post->post_parent ) || $post->post_parent == $post->ID ) {
		return array();
	}

	$ancestors = array();
	$id = $ancestors[] = $post->post_parent;

	while ( $ancestor = get_post( $id ) ) {
		// Loop detection: If the ancestor has been seen before, break.
		if ( empty( $ancestor->post_parent ) || $ancestor->post_parent == $post->ID || in_array( $ancestor->post_parent, $ancestors ) ) {
			break;
		}

		$id = $ancestors[] = $ancestor->post_parent;
	}

	return $ancestors;
}

/**
 * Retrieve data from a post field based on Post ID.
 *
 * Examples of the post field will be, 'post_type', 'post_status', 'post_content', etc and based off of the post object property or key names.
 *
 * The context values are based off of the taxonomy filter functions and supported values are found within those functions.
 *
 * @since 2.3.0
 * @since 4.5.0 The `$post` parameter was made optional.
 * @see   sanitize_post_field()
 *
 * @param  string      $field   Post field name.
 * @param  int|WP_Post $post    Optional.
 *                              Post ID or post object.
 *                              Defaults to current post.
 * @param  string      $context Optional.
 *                              How to filter the field.
 *                              Accepts 'raw', 'edit', 'db', or 'display'.
 *                              Default 'display'.
 * @return string      The value of the post field on success, empty string on failure.
 */
function get_post_field( $field, $post = NULL, $context = 'display' )
{
	$post = get_post( $post );

	if ( ! $post ) {
		return '';
	}

	if ( ! isset( $post->$field ) ) {
		return '';
	}

	return sanitize_post_field( $field, $post->$field, $post->ID, $context );
}

/**
 * Retrieve the post status based on the Post ID.
 *
 * If the post ID is of an attachment, then the parent post status will be given instead.
 *
 * @since 2.0.0
 *
 * @param  int|WP_Post  $ID Optional.
 *                          Post ID or post object.
 *                          Default empty.
 * @return string|false Post status on success, false on failure.
 */
function get_post_status( $ID = '' )
{
	$post = get_post( $ID );

	if ( ! is_object( $post ) ) {
		return FALSE;
	}

	if ( 'attachment' == $post->post_type ) {
		if ( 'private' == $post->post_status ) {
			return 'private';
		}

		// Unattached attachments are assumed to be published.
		if ( 'inherit' == $post->post_status && 0 == $post->post_parent ) {
			return 'publish';
		}

		// Inherit status from the parent.
		if ( $post->post_parent && $post->ID != $post->post_parent ) {
			$parent_post_status = get_post_status( $post->post_parent );

			return 'trash' == $parent_post_status
				? get_post_meta( $post->post_parent, '_wp_trash_meta_status', TRUE )
				: $parent_post_status;
		}
	}

	/**
	 * Filters the post status.
	 *
	 * @since 4.4.0
	 *
	 * @param string  $post_status The post status.
	 * @param WP_Post $post        The post object.
	 */
	return apply_filters( 'get_post_status', $post->post_status, $post );
}

/**
 * Retrieve a post status object by name.
 *
 * @since  3.0.0
 * @global array $wp_post_statuses List of post statuses.
 * @see    register_post_status()
 *
 * @param  string      $post_status The name of a registered post status.
 * @return object|null A post status object.
 */
function get_post_status_object( $post_status )
{
	global $wp_post_statuses;

	return empty( $wp_post_statuses[ $post_status ] )
		? NULL
		: $wp_post_statuses[ $post_status ];
}

/**
 * Get a list of post statuses.
 *
 * @since  3.0.0
 * @global array $wp_post_statuses List of post statuses.
 * @see    register_post_status()
 *
 * @param  array|string $args     Optional.
 *                                Array or string of post status arguments to compare against properties of the global `$wp_post_statuses objects`.
 *                                Default empty array.
 * @param  string       $output   Optional.
 *                                The type of output to return, either 'names' or 'objects'.
 *                                Default 'names'.
 * @param  string       $operator Optional.
 *                                The logical operation to perform.
 *                                'or' means only one element from the array needs to match; 'and' means all elements must match.
 *                                Default 'and'.
 * @return array        A list of post status names or objects.
 */
function get_post_stati( $args = array(), $output = 'names', $operator = 'and' )
{
	global $wp_post_statuses;

	$field = 'names' == $output
		? 'name'
		: FALSE;

	return wp_filter_object_list( $wp_post_statuses, $args, $operator, $field );
}

/**
 * Whether the post type is hierarchical.
 *
 * A false return value might also mean that the post type does not exist.
 *
 * @since 3.0.0
 * @see   get_post_type_object()
 *
 * @param  string $post_type Post type name.
 * @return bool   Whether post type is hierarchical.
 */
function is_post_type_hierarchical( $post_type )
{
	if ( ! post_type_exists( $post_type ) ) {
		return FALSE;
	}

	$post_type = get_post_type_object( $post_type );
	return $post_type->hierarchical;
}

/**
 * Check if a post type is registered.
 *
 * @since 3.0.0
 * @see   get_post_type_object()
 *
 * @param  string $post_type Post type name.
 * @return bool   Whether post type is registered.
 */
function post_type_exists( $post_type )
{
	return ( bool ) get_post_type_object( $post_type );
}

/**
 * Retrieves the post type of the current post or of a given post.
 *
 * @since 2.1.0
 *
 * @param  int|WP_Post|null $post Optional.
 *                                Post ID or post object.
 *                                Default is global $post.
 * @return string|false     Post type on success, false on failure.
 */
function get_post_type( $post = NULL )
{
	return ( $post = get_post( $post ) )
		? $post->post_type
		: FALSE;
}

/**
 * Retrieves a post type object by name.
 *
 * @since  3.0.0
 * @since  4.6.0 Object returned is now an instance of WP_Post_Type.
 * @global array $wp_post_types List of post types.
 * @see    register_post_type()
 *
 * @param  string            $post_type The name of a registered post type.
 * @return WP_Post_Type|null WP_Post_Type object if it exists, null otherwise.
 */
function get_post_type_object( $post_type )
{
	global $wp_post_types;

	return ! is_scalar( $post_type ) || empty( $wp_post_types[ $post_type ] )
		? NULL
		: $wp_post_types[ $post_type ];
}

/**
 * Get a list of all registered post type objects.
 *
 * @since  2.9.0
 * @global array $wp_post_types List of post types.
 * @see    register_post_type() for accepted arguments.
 *
 * @param  array|string $args     Optional.
 *                                An array of key => value arguments to match against the post type objects.
 *                                Default empty array.
 * @param  string       $output   Optional.
 *                                The type of output to return.
 *                                Accepts post type 'names' or 'objects'.
 *                                Default 'names'.
 * @param  string       $operator Optional.
 *                                The logical operation to perform.
 *                                'or' means only one element from the array needs to match; 'and' means all elements must match; 'not' means no elements may match.
 *                                Default 'and'.
 * @return array        A list of post type names or objects.
 */
function get_post_types( $args = array(), $output = 'names', $operator = 'and' )
{
	global $wp_post_types;

	$field = 'name' == $output
		? 'name'
		: FALSE;

	return wp_filter_object_list( $wp_post_types, $args, $operator, $field );
}

/**
 * Check a post type's support for a given feature.
 *
 * @since  3.0.0
 * @global array $_wp_post_type_features
 *
 * @param  string $post_type The post type being checked.
 * @param  string $feature   The feature being checked.
 * @return bool   Whether the post type supports the given feature.
 */
function post_type_supports( $post_type, $feature )
{
	global $_wp_post_type_features;
	return isset( $_wp_post_type_features[ $post_type ][ $feature ] );
}

/**
 * Retrieve list of latest posts or posts matching criteria.
 *
 * The defaults are as follows:
 *
 * @since 1.2.0
 * @see   WP_Query::parse_query()
 *
 * @param  array $args {
 *     Optional.
 *     Arguments to retrieve posts.
 *     See WP_Query::parse_query() for all available arguments.
 *
 *     @type int        $numberposts      Total number of posts to retrieve.
 *                                        Is an alias of $posts_per_page in WP_Query.
 *                                        Accepts -1 for all.
 *                                        Default 5.
 *     @type int|string $category         Category ID or comma-separated list of IDs (this or any children).
 *                                        Is an alias of $cat in WP_Query.
 *                                        Default 0.
 *     @type array      $include          An array of post IDs to retrieve, sticky posts will be included.
 *                                        Is an alias of $post__in in WP_Query.
 *                                        Default empty array.
 *     @type array      $exclude          An array of post IDs not to retrieve.
 *                                        Default empty array.
 *     @type bool       $suppress_filters Whether to suppress filters.
 *                                        Default true.
 * }
 * @return array List of posts.
 */
function get_posts( $args = NULL )
{
	$defaults = array(
		'numberposts'      => 5,
		'category'         => 0,
		'orderby'          => 'date',
		'order'            => 'DESC',
		'include'          => array(),
		'exclude'          => array(),
		'meta_key'         => '',
		'meta_value'       => '',
		'post_type'        => 'post',
		'suppress_filters' => TRUE
	);
	$r = wp_parse_args( $args, $defaults );

	if ( empty( $r['post_status'] ) ) {
		$r['post_status'] = 'attachment' == $r['post_type']
			? 'inherit'
			: 'publish';
	}

	if ( ! empty( $r['numberposts'] ) && empty( $r['posts_per_page'] ) ) {
		$r['posts_per_page'] = $r['numberposts'];
	}

	if ( ! empty( $r['category'] ) ) {
		$r['cat'] = $r['category'];
	}

	if ( ! empty( $r['include'] ) ) {
		$incposts = wp_parse_id_list( $r['include'] );
		$r['posts_per_page'] = count( $incposts ); // Only the number of posts included
		$r['post__in'] = $incposts;
	} elseif ( ! empty( $r['exclude'] ) ) {
		$r['post__not_in'] = wp_parse_id_list( $r['exclude'] );
	}

	$r['ignore_sticky_posts'] = TRUE;
	$r['no_found_rows'] = TRUE;
	$get_posts = new WP_Query;
	return $get_posts->query( $r );
}

//
// Post meta functions
//

/**
 * Add meta data field to a post.
 *
 * Post meta data is called "Custom Fields" on the Administration Screen.
 *
 * @since 1.5.0
 *
 * @param  int       $post_id    Post ID.
 * @param  string    $meta_key   Metadata name.
 * @param  mixed     $meta_value Metadata value.
 *                               Must be serializable if non-scalar.
 * @param  bool      $unique     Optional.
 *                               Whether the same key should not be added.
 *                               Default false.
 * @return int|false Meta ID on success, false on failure.
 */
function add_post_meta( $post_id, $meta_key, $meta_value, $unique = FALSE )
{
	// Make sure meta is added to the post, not a revision.
	if ( $the_post = wp_is_post_revision( $post_id ) ) {
		$post_id = $the_post;
	}

	$added = add_metadata( 'post', $post_id, $meta_key, $meta_value, $unique );

	if ( $added ) {
		wp_cache_set( 'last_changed', microtime(), 'posts' );
	}

	return $added;
}

/**
 * Remove metadata matching criteria from a post.
 *
 * You can match based on the key, or key and value.
 * Removing based on key and value, will keep from removing duplicate metadata with the same key.
 * It also allows removing all metadata matching key, if needed.
 *
 * @since 1.5.0
 *
 * @param  int    $post_id    Post ID.
 * @param  string $meta_key   Metadata name.
 * @param  mixed  $meta_value Optional.
 *                            Metadata value.
 *                            Must be serializable if non-scalar.
 *                            Default empty.
 * @return bool   True on success, false on failure.
 */
function delete_post_meta( $post_id, $meta_key, $meta_value = '' )
{
	// Make sure meta is added to the post, not a revision.
	if ( $the_post = wp_is_post_revision( $post_id ) ) {
		$post_id = $the_post;
	}

	$deleted = delete_metadata( 'post', $post_id, $meta_key, $meta_value );

	if ( $deleted ) {
		wp_cache_set( 'last_changed', microtime(), 'posts' );
	}

	return $deleted;
}

/**
 * Retrieve post meta field for a post.
 *
 * @since 1.5.0
 *
 * @param  int    $post_id Post ID.
 * @param  string $key     Optional.
 *                         The meta key to retrieve.
 *                         By default, returns data for all keys.
 *                         Default empty.
 * @param  bool   $single  Optional.
 *                         Whether to return a single value.
 *                         Default false.
 * @return mixed  Will be an array if $single is false.
 *                Will be value of meta data field if $single is true.
 */
function get_post_meta( $post_id, $key = '', $single = FALSE )
{
	return get_metadata( 'post', $post_id, $key, $single );
}

/**
 * Sanitize every post field.
 *
 * If the context is 'raw', then the post object or array will get minimal sanitization of the integer fields.
 *
 * @since 2.3.0
 * @see   sanitize_post_field()
 *
 * @param  object|WP_Post|array $post    The Post Object or Array.
 * @param  string               $context Optional.
 *                                       How to sanitize post fields.
 *                                       Accepts 'raw', 'edit', 'db', or 'display'.
 *                                       Default 'display'.
 * @param  object|WP_Post|array The now sanitized Post Object or Array (will be the same type as $post).
 */
function sanitize_post( $post, $context = 'display' )
{
	if ( is_object( $post ) ) {
		// Check if post already filtered for this context.
		if ( isset( $post->filter ) && $context == $post->filter ) {
			return $post;
		}

		if ( ! isset( $post->ID ) ) {
			$post->ID = 0;
		}

		foreach ( array_keys( get_object_vars( $post ) ) as $field ) {
			$post->$field = sanitize_post_field( $field, $post->$field, $post->ID, $context );
		}

		$post->filter = $context;
	} elseif ( is_array( $post ) ) {
		// Check if post already filtered for this context.
		if ( isset( $post['filter'] ) && $context == $post['filter'] ) {
			return $post;
		}

		if ( ! isset( $post['ID'] ) ) {
			$post['ID'] = 0;
		}

		foreach ( array_keys( $post ) as $field ) {
			$post[ $field ] = sanitize_post_field( $field, $post[ $field ], $post['ID'], $context );
		}

		$post['filter'] = $context;
	}

	return $post;
}

/**
 * Sanitize post field based on context.
 *
 * Possible context values are: 'raw', 'edit', 'db', 'display', 'attribute' and 'js'.
 * The 'display' context is used by default.
 * 'attribute' and 'js' contexts are treated like 'display' when calling filters.
 *
 * @since 2.3.0
 * @since 4.4.0 Like `sanitize_post()`, `$context` defaults to 'display'.
 *
 * @param  string $field   The Post Object field name.
 * @param  mixed  $value   The Post Object value.
 * @param  int    $post_id Post ID.
 * @param  string $context Optional.
 *                         How to sanitize post fields.
 *                         Looks for 'raw', 'edit', 'db', 'display', 'attribute' and 'js'.
 *                         Default 'display'.
 * @return mixed  Sanitized value.
 */
function sanitize_post_field( $field, $value, $post_id, $context = 'display' )
{
	$int_fields = array( 'ID', 'post_parent', 'menu_order' );

	if ( in_array( $field, $int_fields ) ) {
		$value = ( int ) $value;
	}

	// Fields which contain arrays of integers.
	$array_int_fields = array( 'ancestors' );

	if ( in_array( $field, $array_int_fields ) ) {
		$value = array_map( 'absint', $value );
		return $value;
	}

	if ( 'raw' == $context ) {
		return $value;
	}

	$prefixed = FALSE;

	if ( FALSE !== strpos( $field, 'post_' ) ) {
		$prefixed = TRUE;
		$field_no_prefix = str_replace( 'post_', '', $field );
	}

	if ( 'edit' == $context ) {
		$format_to_edit = array( 'post_content', 'post_excerpt', 'post_title', 'post_password' );

		if ( $prefixed ) {
			/**
			 * Filters the value of a specific post field to edit.
			 *
			 * The dynamic portion of the hook name, `$field`, refers to the post field name.
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value   Value of the post field.
			 * @param int   $post_id Post ID.
			 */
			$value = apply_filters( "edit_{$field}", $value, $post_id );

			/**
			 * Filters the value of a specific post field to edit.
			 *
			 * The dynamic portion of the hook name, `$field_no_prefix`, refers to the post field name.
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value   Value of the post field.
			 * @param int   $post_id Post ID.
			 */
			$value = apply_filters( "{$field_no_prefix}_edit_pre", $value, $post_id );
		} else {
			$value = apply_filters( "edit_post_{$field}", $value, $post_id );
		}

		$value = in_array( $field, $format_to_edit )
			? ( 'post_content' == $field
				? format_to_edit( $value, user_can_richedit() )
				: format_to_edit( $value ) )
			: esc_attr( $value );
	} elseif ( 'db' == $context ) {
		if ( $prefixed ) {
			/**
			 * Filters the value of a specific post field before saving.
			 *
			 * The dynamic portion of the hook name, `$field`, refers to the post field name.
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value Value of the post field.
			 */
			$value = apply_filters( "pre_{$field}", $value );

			/**
			 * Filters the value of a specific field before saving.
			 *
			 * The dynamic portion of the hook name, `$field_no_prefix`, refers to the post field name.
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value Value of the post field.
			 */
			$value = apply_filters( "{$field_no_prefix}_save_pre", $value );
		} else {
			$value = apply_filters( "pre_post_{$field}", $value );

			/**
			 * Filters the value of a specific post field before saving.
			 *
			 * The dynamic portion of the hook name, `$field`, refers to the post field name.
			 *
			 * @since 2.3.0
			 *
			 * @param mixed $value Value of the post field.
			 */
			$value = apply_filters( "{$field}_pre", $value );
		}
	} else {
		// Use display filters by default.
		if ( $prefixed ) {
			/**
			 * Filters the value of a specific post field for display.
			 *
			 * The dynamic portion of the hook name, `$field`, refers to the post field name.
			 *
			 * @since 2.3.0
			 *
			 * @param mixed  $value   Value of the prefixed post field.
			 * @param int    $post_id Post ID.
			 * @param string $context Context for how to sanitize the field.
			 *                        Possible values include 'raw', 'edit', 'db', 'display', 'attribute', and 'js'.
			 */
			$value = apply_filters( "{$field}", $value, $post_id, $context );
		} else {
			$value = apply_filters( "post_{$field}", $value, $post_id, $context );
		}

		if ( 'attribute' == $context ) {
			$value = esc_attr( $value );
		} elseif ( 'js' == $context ) {
			$value = esc_js( $value );
		}
	}

	return $value;
}

/**
 * Convert MIME types into SQL.
 *
 * @since 2.5.0
 *
 * @param  string|array $post_mime_types List of mime types or comma separated string of mime types.
 * @param  string       $table_alias     Optional.
 *                                       Specify a table alias, if needed.
 *                                       Default empty.
 * @return string       The SQL AND clause for mime searching.
 */
function wp_post_mime_type_where( $post_mime_types, $table_alias = '' )
{
	$where = '';
	$wildcards = array( '', '%', '%/%' );

	if ( is_string( $post_mime_types ) ) {
		$post_mime_types = array_map( 'trim', explode( ',', $post_mime_types ) );
	}

	$wheres = array();

	foreach ( ( array ) $post_mime_types as $mime_type ) {
		$mime_type = preg_replace( '/\s/', '', $mime_type );
		$slashpos = strpos( $mime_type, '/' );

		if ( FALSE !== $slashpos ) {
			$mime_group = preg_replace( '/[^-*.a-zA-Z0-9]/', '', substr( $mime_type, 0, $slashpos ) );
			$mime_subgroup = preg_replace( '/[^-*.+a-zA-Z0-9]/', '', substr( $mime_type, $slashpos + 1 ) );

			$mime_subgroup = empty( $mime_subgroup )
				? '*'
				: str_replace( '/', '', $mime_subgroup );

			$mime_pattern = "$mime_group/$mime_subgroup";
		} else {
			$mime_pattern = preg_replace( '/[^-*.a-zA-Z0-9]/', '', $mime_type );

			if ( FALSE === strpos( $mime_pattern, '*' ) ) {
				$mime_pattern .= '/*';
			}
		}

		$mime_pattern = preg_replace( '/\*+/', '%', $mime_pattern );

		if ( in_array( $mime_type, $wildcards ) ) {
			return '';
		}

		$wheres[] = FALSE !== strpos( $mime_pattern, '%' )
			? ( empty( $table_alias )
				? "post_mime_type LIKE '$mime_pattern'"
				: "$table_alias.post_mime_type LIKE '$mime_pattern'" )
			: ( empty( $table_alias )
				? "post_mime_type = '$mime_pattern'"
				: "$table_alias.post_mime_type = '$mime_pattern'" );
	}

	if ( ! empty( $wheres ) ) {
		$where = ' AND (' . join( ' OR ', $wheres ) . ') ';
	}

	return $where;
}

/**
 * Insert or update a post.
 *
 * If the $postarr parameter has 'ID' set to a value, then post will be updated.
 *
 * You can set the post date manually, by setting the values for 'post_date' and 'post_date_gmt' keys.
 * You can close the comments or open the comments by setting the value for 'comment_status' key.
 *
 * @since  1.0.0
 * @since  4.2.0 Support was added for encoding emoji in the post title, content, and excerpt.
 * @since  4.4.0 A 'meta_input' array can now be passed to `$postarr` to add post meta data.
 * @see    sanitize_post()
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param  array        $postarr {
 *     An array of elements that make up a post to update or insert.
 *
 *     @type int    $ID                    The post ID.
 *                                         If equal to something other than 0, the post with that ID will be updated.
 *                                         Default 0.
 *     @type int    $post_author           The ID of the user who added the post.
 *                                         Default is the current user ID.
 *     @type string $post_date             The date of the post.
 *                                         Default is the current time.
 *     @type string $post_date_gmt         The date of the post in the GMT timezone.
 *                                         Default is the value of `$post_date`.
 *     @type mixed  $post_content          The post content.
 *                                         Default empty.
 *     @type string $post_content_filtered The filtered post content.
 *                                         Default empty.
 *     @type string $post_title            The post title.
 *                                         Default empty.
 *     @type string $post_excerpt          The post excerpt.
 *                                         Default empty.
 *     @type string $post_status           The post status.
 *                                         Default 'draft'.
 *     @type string $post_type             The post type.
 *                                         Default 'post'.
 *     @type string $comment_status        Whether the post can accept comments.
 *                                         Accepts 'open' or 'closed'.
 *                                         Default is the value of 'default_comment_status' option.
 *     @type string $ping_status           Whether the post can accept pings.
 *                                         Accepts 'open' or 'closed'.
 *                                         Default is the value of 'default_ping_status' option.
 *     @type string $post_password         The password to access the post.
 *                                         Default empty.
 *     @type string $post_name             The post name.
 *                                         Default is the sanitized post title when creating a new post.
 *     @type string $to_ping               Space or carriage return-separated list of URLs to ping.
 *                                         Default empty.
 *     @type string $pinged                Space or carriage return-separated list of URLs that have been pinged.
 *                                         Default empty.
 *     @type string $post_modified         The date when the post was last modified.
 *                                         Default is the current time.
 *     @type string $post_modified_gmt     The date when the post was last modified in the GMT timezone.
 *                                         Default is the current time.
 *     @type int    $post_parent           Set this for the post it belongs to, if any.
 *                                         Default 0.
 *     @type int    $menu_order            The order the post should be displayed in.
 *                                         Default 0.
 *     @type string $post_mime_type        The mime type of the post.
 *                                         Default empty.
 *     @type string $guid                  Global Unique ID for referencing the post.
 *                                         Default empty.
 *     @type array  $post_category         Array of category names, slugs, or IDs.
 *                                         Defaults to value of the 'default_category' option.
 *     @type array  $tags_input            Array of tag names, slugs, or IDs.
 *                                         Default empty.
 *     @type array  $tax_input             Array of taxonomy terms keyed by their taxonomy name.
 *                                         Default empty.
 *     @type array  $meta_input            Array of post meta values keyed by their post meta key.
 *                                         Default empty.
 * }
 * @param  bool         $wp_error Optional.
 *                                Whether to return a WP_Error on failure.
 *                                Default false.
 * @return int|WP_Error The post ID on success.
 *                      The value 0 or WP_Error on failure.
 */
function wp_insert_post( $postarr, $wp_error = FALSE )
{
	global $wpdb;
	$user_id = get_current_user_id();
	$defaults = array(
		'post_author'           => $user_id,
		'post_content'          => '',
		'post_content_filtered' => '',
		'post_title'            => '',
		'post_excerpt'          => '',
		'post_status'           => 'draft',
		'post_type'             => 'post',
		'comment_status'        => '',
		'ping_status'           => '',
		'post_password'         => '',
		'to_ping'               => '',
		'pinged'                => '',
		'post_parent'           => 0,
		'menu_order'            => 0,
		'guid'                  => '',
		'import_id'             => 0,
		'context'               => ''
	);
	$postarr = wp_parse_args( $postarr, $defaults );
	unset( $postarr['filter'] );
	$postarr = sanitize_post( $postarr, 'db' );

	// Are we updating or creating?
	$post_ID = 0;
	$update = FALSE;
	$guid = $postarr['guid'];

	if ( ! empty( $postarr['ID'] ) ) {
		$update = TRUE;

		// Get the post ID and GUID.
		$post_ID = $postarr['ID'];
		$post_before = get_post( $post_ID );

		if ( is_null( $post_before ) ) {
			return $wp_error
				? new WP_Error( 'invalid_post', __( 'Invalid post ID.' ) )
				: 0;
		}

		$guid = get_post_field( 'guid', $post_ID );
		$previous_status = get_post_field( 'post_status', $post_ID );
	} else {
		$previous_status = 'new';
	}

	$post_type = empty( $postarr['post_type'] )
		? 'post'
		: $postarr['post_type'];

	$post_title   = $postarr['post_title'];
	$post_content = $postarr['post_content'];
	$post_excerpt = $postarr['post_excerpt'];

	$post_name = isset( $postarr['post_name'] )
		? $postarr['post_name']
		: $post_before->post_name; // For an update, don't modify the post_name if it wasn't supplied as an argument.

	$maybe_empty = 'attachment' !== $post_type && ! $post_content && ! $post_title && ! $post_excerpt && post_type_supports( $post_type, 'editor' ) && post_type_supports( $post_type, 'title' ) && post_type_supports( $post_type, 'excerpt' );

	/**
	 * Filters whether the post should be considered "empty".
	 *
	 * The post is considered "empty" if both:
	 * 1. The post type supports the title, editor, and excerpt fields
	 * 2. The title, editor, and excerpt fields are all empty
	 *
	 * Returning a truthy value to the filter will effectively short-circuit the new post being inserted, returning 0.
	 * If $wp_error is true, a WP_Error will be returned instead.
	 *
	 * @since 3.3.0
	 *
	 * @param bool  $maybe_empty Whether the post should be considered "empty".
	 * @param array $postarr     Array of post data.
	 */
	if ( apply_filters( 'wp_insert_post_empty_content', $maybe_empty, $postarr ) ) {
		return $wp_error
			? new WP_Error( 'empty_content', __( 'Content, title, and excerpt are empty.' ) )
			: 0;
	}

	$post_status = empty( $postarr['post_status'] )
		? 'draft'
		: $postarr['post_status'];

	if ( 'attachment' === $post_type && ! in_array( $post_status, array( 'inherit', 'private', 'trash', 'auto-draft' ), TRUE ) ) {
		$post_status = 'inherit';
	}

	if ( ! empty( $postarr['post_category'] ) ) {
		// Filter out empty terms.
		$post_category = array_filter( $postarr['post_category'] );
	}

	// Make sure we set a valid category.
	if ( empty( $post_category ) || 0 == count( $post_category ) || ! is_array( $post_category ) ) {
		// 'post' requires at least one category.
		$post_category = 'post' == $post_type && 'auto-draft' != $post_status
			? array( get_option( 'default_category' ) )
			: array();
	}

	// Don't allow contributors to set the post slug for pending review posts.
	if ( 'pending' == $post_status && ! current_user_can( 'publish_posts' ) ) {
		$post_name = '';
	}

	/**
	 * Create a valid post name.
	 * Drafts and pending posts are allowed to have an empty post name.
	 */
	if ( empty( $post_name ) ) {
		$post_name = ! in_array( $post_status, array( 'draft', 'pending', 'auto-draft' ) )
			? sanitize_title( $post_title )
			: '';
	} else {
		// On updates, we need to check to see if it's using the old, fixed sanitization context.
		$check_name = sanitize_title( $post_name, '', 'old-save' );

		$post_name = $update && strtolower( urlencode( $post_name ) ) == $check_name && get_post_field( 'post_name', $post_ID ) == $check_name
			? $check_name
			: sanitize_title( $post_name ); // New post, or slug has changed.
	}

	// If the post date is empty (due to having been new or a draft) and status is not 'draft' or 'pending', set date to now.
	$post_date = empty( $postarr['post_date'] ) || '0000-00-00 00:00:00' == $postarr['post_date']
		? ( empty( $postarr['post_date_gmt'] ) || '0000-00-00 00:00:00' == $postarr['post_date_gmt']
			? current_time( 'mysql' )
			: get_date_from_gmt( $postarr['post_date_gmt'] ) )
		: $postarr['post_date'];

	// Validate the date.
	$mm = substr( $post_date, 5, 2 );
	$jj = substr( $post_date, 8, 2 );
	$aa = substr( $post_date, 0, 4 );
	$valid_date = wp_checkdate( $mm, $jj, $aa, $post_date );

	if ( ! $valid_date ) {
		return $wp_error
			? new WP_Error( 'invalid_date', __( 'Invalid date.' ) )
			: 0;
	}

	$post_date_gmt = empty( $postarr['post_date_gmt'] ) || '0000-00-00 00:00:00' == $postarr['post_date_gmt']
		? ( ! in_array( $post_status, array( 'draft', 'pending', 'auto-draft' ) )
			? get_gmt_from_date( $post_date )
			: '0000-00-00 00:00:00' )
		: $postarr['post_date_gmt'];

	if ( $update || '0000-00-00 00:00:00' == $post_date ) {
		$post_modified     = current_time( 'mysql' );
		$post_modified_gmt = current_time( 'mysql', 1 );
	} else {
		$post_modified     = $post_date;
		$post_modified_gmt = $post_date_gmt;
	}

	if ( 'attachment' !== $post_type ) {
		if ( 'publish' == $post_status ) {
			$now = gmdate( 'Y-m-d H:i:59' );

			if ( mysql2date( 'U', $post_date_gmt, FALSE ) > mysql2date( 'U', $now, FALSE ) ) {
				$post_status = 'future';
			}
		} elseif ( 'future' == $post_status ) {
			$now = gmdate( 'Y-m-d H:i:59' );

			if ( mysql2date( 'U', $post_date_gmt, FALSE ) <= mysql2date( 'U', $now, FALSE ) ) {
				$post_status = 'publish';
			}
		}
	}

	// Comment status.
	$comment_status = empty( $postarr['comment_status'] )
		? ( $update
			? 'closed'
			: get_default_comment_status( $post_type ) )
		: $postarr['comment_status'];

	// These variables are needed by compact() later.
	$post_content_filtered = $postarr['post_content_filtered'];

	$post_author = isset( $postarr['post_author'] )
		? $postarr['post_author']
		: $user_id;

	$ping_status = empty( $postarr['ping_status'] )
		? get_default_comment_status( $post_type, 'pingback' )
		: $postarr['ping_status'];

	$to_ping = isset( $postarr['to_ping'] )
		? sanitize_trackback_urls( $postarr['to_ping'] )
		: '';

	$pinged = isset( $postarr['pinged'] )
		? $postarr['pinged']
		: '';

	$import_id = isset( $postarr['import_id'] )
		? $postarr['import_id']
		: 0;

	/**
	 * The 'wp_insert_post_parent' filter expects all variables to be present.
	 * Previously, these variables would have already been extracted.
	 */
	$menu_order = isset( $postarr['menu_order'] )
		? ( int ) $postarr['menu_order']
		: 0;

	$post_password = isset( $postarr['post_password'] )
		? $postarr['post_password']
		: '';

	if ( 'private' == $post_status ) {
		$post_password = '';
	}

	$post_parent = isset( $postarr['post_parent'] )
		? ( int ) $postarr['post_parent']
		: 0;

	/**
	 * Filters the post parent -- used to check for and prevent hierarchy loops.
	 *
	 * @since 3.1.0
	 *
	 * @param int   $post_parent Post parent ID.
	 * @param int   $post_ID     Post ID.
	 * @param array $new_postarr Array of parsed post data.
	 * @param array $postarr     Array of saintized, but otherwise unmodified post data.
	 */
	$post_parent = apply_filters( 'wp_insert_post_parent', $post_parent, $post_ID, compact( array_keys( $postarr ) ), $postarr );

	// If the postis being untrashed and it has a desired slug stored in post meta, reassign it.
	if ( 'trash' === $previous_status && 'trash' !== $post_status ) {
		$desired_post_slug = get_post_meta( $post_ID, '_wp_desired_post_slug', TRUE );

		if ( $desired_post_slug ) {
			delete_post_meta( $post_ID, '_wp_desired_post_slug' );
			$post_name = $desired_post_slug;
		}
	}

	// If a trashed post has the desired slug, change it and let this post have it.
	if ( 'trash' !== $post_status && $post_name ) {
		wp_add_trashed_suffix_to_post_name_for_trashed_posts( $post_name, $post_ID );
	}

	$post_name = wp_unique_post_slug( $post_name, $post_ID, $post_status, $post_type, $post_parent );
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * @NOW 006: wp-includes/post.php
 */
}

/**
 * Update a post with new post data.
 *
 * The date does not have to be set for drafts.
 * You can set the date and it will not be overridden.
 *
 * @since 1.0.0
 *
 * @param  array|object $postarr  Optional.
 *                                Post data.
 *                                Arrays are expected to be escaped, objects are not.
 *                                Default array.
 * @param  bool         $wp_error Optional.
 *                                Allow return of WP_Error on failure.
 *                                Default false.
 * @return int|WP_Error The value 0 or WP_Error on failure.
 *                      The post ID on success.
 */
function wp_update_post( $postarr = array(), $wp_error = FALSE )
{
	if ( is_object( $postarr ) ) {
		// Non-escaped post was passed.
		$postarr = get_object_vars( $postarr );
		$postarr = wp_slash( $postarr );
	}

	// First, get all of the original fields.
	$post = get_post( $postarr['ID'], ARRAY_A );

	if ( is_null( $post ) ) {
		return $wp_error
			? new WP_Error( 'invalid_post', __( 'Invalid post ID.' ) )
			: 0;
	}

	// Escape data pulled from DB.
	$post = wp_slash( $post );

	// Passed post category list overwrites existing category list if not empty.
	$post_cats = isset( $postarr['post_category'] ) && is_array( $postarr['post_category'] ) && 0 != count( $postarr['post_category'] )
		? $postarr['post_category']
		: $post['post_category'];

	// Drafts shouldn't be assigned a date unless explicitly done so by the user.
	$clear_date = isset( $post['post_status'] ) && in_array( $post['post_status'], array( 'draft', 'pending', 'auto-draft' ) ) && empty( $postarr['edit_date'] ) && '0000-00-00 00:00:00' == $post['post_date_gmt'];

	// Merge old and new fields with new fields overwriting old ones.
	$postarr = array_merge( $post, $postarr );
	$postarr['post_category'] = $post_cats;

	if ( $clear_date ) {
		$postarr['post_date'] = current_time( 'mysql' );
		$postarr['post_date_gmt'] = '';
	}

	return $postarr['post_type'] == 'attachment'
		? wp_insert_attachment( $postarr )
		: wp_insert_post( $postarr, $wp_error );
}

/**
 * Computes a unique slug for the post, when given the desired slug and some post details.
 *
 * @since  2.8.0
 * @global wpdb       $wpdb       WordPress database abstraction object.
 * @global WP_Rewrite $wp_rewrite
 *
 * @param  string $slug        The desired slug (post_name).
 * @param  int    $post_ID     Post ID.
 * @param  string $post_status No uniqueness checks are made if the post is still draft or pending.
 * @param  string $post_type   Post type.
 * @param  int    $post_parent Post parent ID.
 * @return string Unique slug for the post, based on $post_name (with a -1, -2, etc. suffix).
 */
function wp_unique_post_slug( $slug, $post_ID, $post_status, $post_type, $post_parent )
{
	if ( in_array( $post_status, array( 'draft', 'pending', 'auto-draft' ) )
	  || 'inherit' == $post_status && 'revision' == $post_type
	  || 'user_request' === $post_type ) {
		return $slug;
	}

	global $wpdb, $wp_rewrite;
	$original_slug = $slug;
	$feeds = $wp_rewrite->feeds;

	if ( ! is_array( $feeds ) ) {
		$feeds = array();
	}

	if ( 'attachment' == $post_type ) {
		// Attachment slugs must be unique across all types.
		$check_sql = <<<EOQ
SELECT post_name
FROM $wpdb->posts
WHERE post_name = %s
  AND ID != %d
LIMIT 1
EOQ;
		$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $post_ID ) );

		/**
		 * Filters whether the post slug would make a bad attachment slug.
		 *
		 * @since 3.1.0
		 *
		 * @param bool   $bad_slug Whether the slug would be bad as an attachment slug.
		 * @param string $slug     The post slug.
		 */
		if ( $post_name_check || in_array( $slug, $feeds ) || 'embed' === $slug || apply_filters( 'wp_unique_post_slug_is_bad_attachment_slug', FALSE, $slug ) ) {
			$suffix = 2;

			do {
				$alt_post_name = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
				$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_post_name, $post_ID ) );
				$suffix++;
			} while ( $post_name_check );

			$slug = $alt_post_name;
		}
	} elseif ( is_post_type_hierarchical( $post_type ) ) {
		if ( 'nav_menu_item' == $post_type ) {
			return $slug;
		}

		/**
		 * Page slugs must be unique within their own trees.
		 * Pages are in a separate namespace than posts so page slugs are allowed to overlap post slugs.
		 */
		$check_sql = <<<EOQ
SELECT post_name
FROM $wpdb->posts
WHERE post_name = %s
  AND post_type IN ( %s, 'attachment' )
  AND ID != %d
  AND post_parent = %d
LIMIT 1
EOQ;
		$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $post_type, $post_ID, $post_parent ) );

		/**
		 * Filters whether the post slug would make a bad hierarchical post slug.
		 *
		 * @since 3.1.0
		 *
		 * @param bool   $bad_slug    Whether the post slug would be bad in a hierarchical post context.
		 * @param string $slug        The post slug.
		 * @param string $post_type   Post type.
		 * @param int    $post_parent Post parent ID.
		 */
		if ( $post_name_check || in_array( $slug, $feeds ) || 'embed' === $slug || preg_match( "@^($wp_rewrite->pagination_base)?\d+$@", $slug ) || apply_filters( 'wp_unique_post_slug_is_bad_hierarchical_slug', FALSE, $slug, $post_type, $post_parent ) ) {
			$suffix = 2;

			do {
				$alt_post_name = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
				$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_post_name, $post_type, $post_ID, $post_parent ) );
				$suffix++;
			} while ( $post_name_check );

			$slug = $alt_post_name;
		}
	} else {
		// Post slugs must be unique across all posts.
		$check_sql = <<<EOQ
SELECT post_name
FROM $wpdb->posts
WHERE post_name = %s
  AND post_type = %s
  AND ID != %d
LIMIT 1
EOQ;
		$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $post_type, $post_ID ) );

		// Prevent new post slugs that could result in URLs that conflict with date archives.
		$post = get_post( $post_ID );
		$conflicts_with_date_archive = FALSE;

		if ( 'post' === $post_type
		  && ( ! $post || $post->post_name !== $slug )
		  && preg_match( '/^[0-9]+$/', $slug )
		  && $slug_num = intval( $slug ) ) {
			$permastructs   = array_values( array_filter( explode( '/', get_option( 'permalink_structure' ) ) ) );
			$postname_index = array_search( '%postname%', $permastructs );

			/**
			 * Potential date clashes are as follows:
			 *
			 * - Any integer in the first permastruct position could be a year.
			 * - An integer between 1 and 12 that follows 'year' conflicts with 'monthnum'.
			 * - An integer between 1 and 31 that follows 'monthnum' conflicts with 'day'.
			 */
			if ( 0 === $postname_index
			  || $postname_index && '%year%' === $permastructs[ $postname_index - 1 ] && 13 > $slug_num
			  || $postname_index && '%monthnum%' === $permastructs[ $postname_index - 1 ] && 32 > $slug_num ) {
				$conflicts_with_date_archive = TRUE;
			}
		}

		/**
		 * Filters whether the post slug would be bad as a flat slug.
		 *
		 * @since 3.1.0
		 *
		 * @param bool   $bad_slug  Whether the post slug would be bad as a flat slug.
		 * @param string $slug      The post slug.
		 * @param string $post_type Post type.
		 */
		if ( $post_name_check || in_array( $slug, $feeds ) || 'embed' === $slug || $conflicts_with_date_archive || apply_filters( 'wp_unique_post_slug_is_bad_flat_slug', FALSE, $slug, $post_type ) ) {
			$suffix = 2;

			do {
				$alt_post_name = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
				$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_post_name, $psot_type, $post_ID ) );
				$suffix++;
			} while ( $post_name_check );

			$slug = $alt_post_name;
		}
	}

	/**
	 * Filters the unique post slug.
	 *
	 * @since 3.3.0
	 *
	 * @param string $slug          The post slug.
	 * @param int    $post_ID       Post ID.
	 * @param string $post_status   The post status.
	 * @param string $post_type     Post type.
	 * @param int    $post_parent   Post parent ID.
	 * @param string $original_slug The original post slug.
	 */
	return apply_filters( 'wp_unique_post_slug', $slug, $post_ID, $post_status, $post_type, $post_parent, $original_slug );
}

/**
 * Truncate a post slug.
 *
 * @since  3.6.0
 * @access private
 * @see    utf8_uri_encode()
 *
 * @param  string $slug   The slug to truncate.
 * @param  int    $length Optional.
 *                        Max length of the slug.
 *                        Default 200 (characters).
 * @return The truncated slug.
 */
function _truncate_post_slug( $slug, $length = 200 )
{
	if ( strlen( $slug ) > $length ) {
		$decoded_slug = urldecode( $slug );

		$slug = $decoded_slug === $slug
			? substr( $slug, 0, $length )
			: utf8_uri_encode( $decoded_slug, $length );
	}

	return rtrim( $slug, '-' );
}

/**
 * Retrieves a page given its path.
 *
 * @since  2.1.0
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param  string             $page_path Page path.
 * @param  string             $output    Optional.
 *                                       The required return type.
 *                                       One of OBJECT, ARRAY_A, or ARRAY_N, which correspond to a WP_Post object, an associative array, or a numeric array, respectively.
 *                                       Default OBJECT.
 * @param  string|array       $post_type Optional.
 *                                       Post type or array of post types.
 *                                       Default 'page'.
 * @return WP_Post|array|null WP_Post (or array) on success, or null on failure.
 */
function get_page_by_path( $page_path, $output = OBJECT, $post_type = 'page' )
{
	global $wpdb;
	$last_changed = wp_cache_get_last_changed( 'posts' );
	$hash = md5( $path_path . serialize( $post_type ) );
	$cache_key = "get_page_by_path:$hash:$last_changed";
	$cached = wp_cache_get( $cache_key, 'posts' );

	if ( FALSE !== $cached ) {
		// Special case: '0' is a bad `$page_path`.
		if ( '0' === $cached || 0 === $cached ) {
			return;
		} else {
			return get_post( $cached, $output );
		}
	}

	$page_path = rawurlencode( urldecode( $page_path ) );
	$page_path = str_replace( '%2F', '/', $page_path );
	$page_path = str_replace( '%20', ' ', $page_path );
	$parts = explode( '/', trim( $page_path, '/' ) );
	$parts = array_map( 'sanitize_title_for_query', $parts );
	$escaped_parts = esc_sql( $parts );
	$in_string = "'" . implode( "','", $escaped_parts ) . "'";

	$post_types = is_array( $post_type )
		? $post_type
		: array( $post_type, 'attachment' );

	$post_types = esc_sql( $post_types );
	$post_type_in_string = "'" . implode( "','", $post_types ) . "'";
	$sql = <<<EOQ
SELECT ID, post_name, post_parent, post_type
FROM $wpdb->posts
WHERE post_name IN( $in_string )
  AND post_type IN( $post_type_in_string )
EOQ;
	$pages = $wpdb->get_results( $sql, OBJECT_K );
	$revparts = array_reverse( $parts );
	$foundid = 0;

	foreach ( ( array ) $pages as $page ) {
		if ( $page->post_name == $revparts[0] ) {
			$count = 0;
			$p = $page;

			// Loop through the given path parts from right to left, ensuring each matches the post ancestry.
			while ( $p->post_parent != 0 && isset( $pages[ $p->post_parent ] ) ) {
				$count++;
				$parent = $pages[ $p->post_parent ];

				if ( ! isset( $revparts[ $count ] ) || $parent->post_name != $revparts[ $count ] ) {
					break;
				}

				$p = $parent;
			}

			if ( $p->post_parent == 0 && $count + 1 == count( $revparts ) && $p->post_name == $revparts[ $count ] ) {
				$foundid = $page->ID;

				if ( $page->post_type == $post_type ) {
					break;
				}
			}
		}
	}

	// We cache misses as well as hits.
	wp_cache_set( $cache_key, $foundid, 'posts' );

	if ( $foundid ) {
		return get_post( $foundid, $output );
	}
}

/**
 * Insert an attachment.
 *
 * If you set the 'ID' in the $args parameter, it will mean that you are updating and attempt to update the attachment.
 * You can also set the attachment name or title by setting the key 'post_name' or 'post_title'.
 *
 * You can set the dates for the attachment manually by setting the 'post_date' and 'post_date_gmt' keys' values.
 *
 * By default, the comments will use the default settings for whether the comments are allowed.
 * You can close them manually or keep them open by setting the value for the 'comment_status' key.
 *
 * @since 2.0.0
 * @since 4.7.0 Added the `$wp_error` parameter to allow a WP_Error to be returned on failure.
 * @see   wp_insert_post()
 *
 * @param  string|array $args     Arguments for inserting an attachment.
 * @param  string       $file     Optional.
 *                                Filename.
 * @param  int          $parent   Optional.
 *                                Parent post ID.
 * @param  bool         $wp_error Optional.
 *                                Whether to return a WP_Error on failure.
 *                                Default false.
 * @return int|WP_Error The attachment ID on success.
 *                      The value 0 or WP_Error on failure.
 */
function wp_insert_attachment( $args, $file = FALSE, $parent = 0, $wp_error = FALSE )
{
	$defaults = array(
		'file'        => $file,
		'post_parent' => 0
	);
	$data = wp_parse_args( $args, $defaults );

	if ( ! empty( $parent ) ) {
		$data['post_parent'] = $parent;
	}

	$data['post_type'] = 'attachment';
	return wp_insert_post( $data, $wp_error );
}

/**
 * Updates posts in cache.
 *
 * @since 1.5.1
 *
 * @param array $posts Array of post objects (passed by reference).
 */
function update_post_cache( &$posts )
{
	if ( ! $posts ) {
		return;
	}

	foreach ( $posts as $post ) {
		wp_cache_add( $post->ID, $post, 'posts' );
	}
}

/**
 * Will clean the post in the cache.
 *
 * Cleaning means delete from the cache of the post.
 * Will call to clean the term object cache associated with the post ID.
 *
 * This function not run if $_wp_suspend_cache_invalidation is not empty.
 * See wp_suspend_cache_invalidation().
 *
 * @since  2.0.0
 * @global bool $_wp_suspend_cache_invalidation
 *
 * @param int|WP_Post $post Post ID or post object to remove from the cache.
 */
function clean_post_cache( $post )
{
	global $_wp_suspend_cache_invalidation;

	if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
		return;
	}

	$post = get_post( $post );

	if ( empty( $post ) ) {
		return;
	}

	wp_cache_delete( $post->ID, 'posts' );
	wp_cache_delete( $post->ID, 'post_meta' );
	clean_object_term_cache( $post->ID, $post->post_type );
	wp_cache_delete( 'wp_get_archives', 'general' );

	/**
	 * Fires immediately after the given post's cache is cleaned.
	 *
	 * @since 2.5.0
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	do_action( 'clean_post_cache', $post->ID, $post );

	if ( 'page' == $post->post_type ) {
		wp_cache_delete( 'all_page_ids', 'posts' );

		/**
		 * Fires immediately after the given page's cache is cleaned.
		 *
		 * @since 2.5.0
		 *
		 * @param int $post_id Post ID.
		 */
		do_action( 'clean_page_cache', $post->ID );
	}

	wp_cache_set( 'last_changed', microtime(), 'posts' );
}

/**
 * Call major cache updating functions for list of Post objects.
 *
 * @since 1.5.0
 *
 * @param array  $posts             Array of Post objects.
 * @param string $post_type         Optional.
 *                                  Post type.
 *                                  Default 'post'.
 * @param bool   $update_term_cache Optional.
 *                                  Whether to update the term cache.
 *                                  Default true.
 * @param bool   $update_meta_cache Optional.
 *                                  Whether to update the meta cache.
 *                                  Default true.
 */
function update_post_caches( &$posts, $post_type = 'post', $update_term_cache = TRUE, $update_meta_cache = TRUE )
{
	// No point in doing all this work if we didn't match any posts.
	if ( ! $posts ) {
		return;
	}

	update_post_cache( $posts );
	$post_ids = array();

	foreach ( $posts as $post ) {
		$post_ids[] = $post->ID;
	}

	if ( ! $post_type ) {
		$post_type = 'any';
	}

	if ( $update_term_cache ) {
		if ( is_array( $post_type ) ) {
			$ptypes = $post_type;
		} elseif ( 'any' == $post_type ) {
			$ptypes = array();

			// Just use the post_types in the supplied posts.
			foreach ( $posts as $post ) {
				$ptypes[] = $post->post_type;
			}

			$ptypes = array_unique( $ptypes );
		} else {
			$ptypes = array( $post_type );
		}

		if ( ! empty( $ptypes ) ) {
			update_object_term_cache( $post_ids, $ptypes );
		}
	}

	if ( $update_meta_cache ) {
		update_postmeta_cache( $post_ids );
	}
}

/**
 * Updates metadata cache for list of post IDs.
 *
 * Performs SQL query to retrieve the metadata for the post IDs and updates the metadata cache for the posts.
 * Therefore, the functions, which call this function, do not need to perform SQL queries on their own.
 *
 * @since 2.1.0
 *
 * @param  array       $post_ids List of post IDs.
 * @return array|false Returns false if there is nothing to update or an array of metadata.
 */
function update_postmeta_cache( $post_ids )
{
	return update_meta_cache( 'post', $post_ids );
}

/**
 * Check the given subset of the post hierarchy for hierarchy loops.
 *
 * Prevents loops from forming and breaks those that it finds.
 * Attached to the {@see 'wp_insert_post_parent'} filter.
 *
 * @since 3.1.0
 * @see   wp_find_hierarchy_loop()
 *
 * @param  int $post_parent ID of the parent for the post we're checking.
 * @param  int $post_ID     ID of the post we're checking.
 * @return int The new post_parent for the post, 0 otherwise.
 */
function wp_check_post_hierarchy_for_loops( $post_parent, $post_ID )
{
	// Nothing fancy here - bail.
	if ( ! $post_parent ) {
		return 0;
	}

	// New post can't cause a loop.
	if ( empty( $post_ID ) ) {
		return $post_parent;
	}

	// Can't be its own parent.
	if ( $post_parent == $post_ID ) {
		return 0;
	}

	// Now look for larger loops.
	if ( ! $loop = wp_find_hierarchy_loop( 'wp_get_post_parent_id', $post_ID, $post_parent ) ) {
		return $post_parent; // No loop
	}

	// Setting $post_parent to the given value causes a loop.
	if ( isset( $loop[ $post_ID ] ) ) {
		return 0;
	}

	/**
	 * There's a loop, but it doesn't contain $post_ID.
	 * Break the loop.
	 */
	foreach ( array_keys( $loop ) as $loop_member ) {
		wp_update_post( array(
				'ID'          => $loop_member,
				'post_parent' => 0
			) );
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * @NOW 005: wp-includes/post.php
 * -> wp-includes/post.php
 */
	}
}

/**
 * Queues posts for lazy-loading of term meta.
 *
 * @since 4.5.0
 *
 * @param array $posts Array of WP_Post objects.
 */
function wp_queue_posts_for_term_meta_lazyload( $posts )
{
	$post_type_taxonomies = $term_ids = array();

	foreach ( $posts as $post ) {
		if ( ! ( $post instanceof WP_Post ) ) {
			continue;
		}

		if ( ! isset( $post_type_taxonomies[ $post->post_type ] ) ) {
			$post_type_taxonomies[ $post->post_type ] = get_object_taxonomies( $post->post_type );
		}

		foreach ( $post_type_taxonomies[ $post->post_type ] as $taxonomy ) {
			// Term cache should already be primed by `update_post_term_cache()`.
			$terms = get_object_term_cache( $post->ID, $taxonomy );

			if ( FALSE !== $terms ) {
				foreach ( $terms as $term ) {
					if ( ! isset( $term_ids[ $term->term_id ] ) ) {
						$term_ids[] = $term->term_id;
					}
				}
			}
		}
	}

	if ( $term_ids ) {
		$lazyloader = wp_metadata_lazyloader();
		$lazyloader->queue_objects( 'term', $term_ids );
	}
}

/**
 * Adds any posts from the given ids to the cache that do not already exist in the cache.
 *
 * @since  3.4.0
 * @access private
 * @see    update_post_caches()
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array $ids               ID list.
 * @param bool  $update_term_cache Optional.
 *                                 Whether to update the term cache.
 *                                 Default true.
 * @param bool  $update_meta_cache Optional.
 *                                 Whether to update the meta cache.
 *                                 Default true.
 */
function _prime_post_cache( $ids, $update_term_cache = TRUE, $update_meta_cache = FALSE )
{
	global $wpdb;
	$non_cached_ids = _get_non_cached_ids( $ids, 'posts' );

	if ( ! empty( $non_cached_ids ) ) {
		$fresh_posts = $wpdb->get_results( sprintf( <<<EOQ
SELECT $wpdb->posts.*
FROM $wpdb->posts
WHERE ID IN (%s)
EOQ
				, join( ",", $non_cached_ids ) ) );
		update_post_caches( $fresh_posts, 'any', $update_term_cache, $update_meta_cache );
	}
}

/**
 * Adds a suffix if any trashed posts have a given slug.
 *
 * Store its desired (i.e. current) slug so it can try to reclaim it if the post is untrashed.
 *
 * For internal use.
 *
 * @since  4.5.0
 * @access private
 *
 * @param string $post_name Slug.
 * @param string $post_ID   Optional.
 *                          Post ID that should be ignored.
 *                          Default 0.
 */
function wp_add_trashed_suffix_to_post_name_for_trashed_posts( $post_name, $post_ID = 0 )
{
	$trashed_posts_with_desired_slug = get_posts( array(
			'name'         => $post_name,
			'post_status'  => 'trash',
			'post_type'    => 'any',
			'nopaging'     => TRUE,
			'post__not_in' => array( $post_ID )
		) );

	if ( ! empty( $trashed_posts_with_desired_slug ) ) {
		foreach ( $trashed_posts_with_desired_slug as $_post ) {
			wp_add_trashed_suffix_to_post_name_for_post( $_post );
		}
	}
}

/**
 * Adds a trashed suffix for a given post.
 *
 * Store its desired (i.e. current) slug so it can try to reclaim it if the post is untrashed.
 *
 * For internal use.
 *
 * @since  4.5.0
 * @access private
 *
 * @param  WP_Post $post The post.
 * @return string  New slug for the post.
 */
function wp_add_trashed_suffix_to_post_name_for_post( $post )
{
	global $wpdb;
	$post = get_post( $post );

	if ( '__trashed' === substr( $post->post_name, -9 ) ) {
		return $post->post_name;
	}

	add_post_meta( $post->ID, '_wp_desired_post_slug', $post->post_name );
	$post_name = _truncate_post_slug( $post->post_name, 191 ) . '__trashed';
	$wpdb->update( $wpdb->posts, array( 'post_name' => $post_name ), array( 'ID' => $post->ID ) );
	clean_post_cache( $post->ID );
	return $post_name;
}
