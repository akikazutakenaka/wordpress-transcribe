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
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * @NOW 007: wp-includes/post.php
 */
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
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * @NOW 006: wp-includes/post.php
 * -> wp-includes/post.php
 */
		}
	}
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
