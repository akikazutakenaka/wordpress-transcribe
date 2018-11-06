<?php
/**
 * Core User Role & Capabilities API
 *
 * @package    WordPress
 * @subpackage Users
 */

/**
 * Map meta capabilities to primitive capabilities.
 *
 * This does not actually compare whether the user ID has the actual capability, just what the capability or capabilities are.
 * Meta capability list value can be 'delete_user', 'edit_user', 'remove_user', 'promote_user', 'delete_post', 'delete_page', 'edit_post', 'edit_page', 'read_post', or 'read_page'.
 *
 * @since  2.0.0
 * @global array $post_type_meta_caps Used to get post type meta capabilities.
 *
 * @param  string $cap       Capability name.
 * @param  int    $user_id   User ID.
 * @param  int    $object_id Optional.
 *                           ID of the specific object to check against if `$cap` is a "meta" cap.
 *                           "Meta" capabilities, e.g. 'edit_post', 'edit_user', etc., are capabilities used by map_meta_cap() to map to other "primitive" capabilities, e.g. 'edit_posts', 'edit_others_posts', etc.
 *                           The parameter is accessed via func_get_args().
 * @return array  Actual capabilities for meta capability.
 */
function map_meta_cap( $cap, $user_id )
{
	$args = array_slice( func_get_args(), 2 );
	$caps = array();

	switch ( $cap ) {
		case 'remove_user':
			// In multisite the user must be a super admin to remove themselves.
			$caps[] = isset( $args[0] ) && $user_id == $args[0] && ! is_super_admin( $user_id )
				? 'do_not_allow'
				: 'remove_users';

			break;

		case 'promote_user':
		case 'add_users':
			$caps[] = 'promote_users';
			break;

		case 'edit_user':
		case 'edit_users':
			// Allow user to edit itself
			if ( 'edit_user' == $cap && isset( $args[0] ) && $user_id == $args[0] ) {
				break;
			}

			/**
			 * In multisite the user must have manage_network_users caps.
			 * If editing a super admin, the user must be a super admin.
			 */
			$caps[] = is_multisite()
			       && ( ! is_super_admin( $user_id ) && 'edit_user' === $cap && is_super_admin( $args[0] )
			         || ! user_can( $user_id, 'manage_network_users' ) )
				? 'do_not_allow'
				: 'edit_users'; // edit_user maps to edit_users.

			break;

		case 'delete_post':
		case 'delete_page':
			$post = get_post( $args[0] );

			if ( ! $post ) {
				$caps[] = 'do_not_allow';
				break;
			}

			if ( 'revision' == $post->post_type ) {
				$post = get_post( $post->post_parent );

				if ( ! $post ) {
					$caps[] = 'do_not_allow';
					break;
				}
			}

			if ( get_option( 'page_for_posts' ) == $post->ID || get_option( 'page_on_front' ) == $post->ID ) {
				$caps[] = 'manage_options';
				break;
			}

			$post_type = get_post_type_object( $post->post_type );

			if ( ! $post_type ) {
				_doing_it_wrong( __FUNCTION__, sprintf( __( 'The post type %1$s is not registered, so it may not be reliable to check the capability "%2$s" against a post of that type.' ), $post->post_type, $cap ), '4.4.0' );
				$caps[] = 'edit_others_posts';
				break;
			}

			if ( ! $post_type->map_meta_cap ) {
				$caps[] = $post_type->cap->$cap;

				// Prior to 3.1 we would re-call map_meta_cap here.
				if ( 'delete_post' == $cap ) {
					$cap = $post_type->cap->$cap;
				}

				break;
			}

			// If the post author is set and the user is the author...
			if ( $post->post_author && $user_id == $post->post_author ) {
				// If the post is published or scheduled...
				if ( in_array( $post->post_status, array( 'publish', 'future' ), TRUE ) ) {
					$caps[] = $post_type->cap->delete_published_posts;
				} elseif ( 'trash' == $post->post_status ) {
					$status = get_post_meta( $post->ID, '_wp_trash_meta_status', TRUE );

					$caps[] = in_array( $status, array( 'publish', 'future' ), TRUE )
						? $post_type->cap->delete_published_posts
						: $post_type->cap->delete_posts;
				} else {
					// If the post is draft...
					$caps[] = $post_type->cap->delete_posts;
				}
			} else {
				// The user is trying to edit someone else's post.
				$caps[] = $post_type->cap->delete_others_posts;

				// The post is published or scheduled, extra cap required.
				if ( in_array( $post->post_status, array( 'publish', 'future' ), TRUE ) ) {
					$caps[] = $post_type->cap->delete_published_posts;
				} elseif ( 'private' == $post->post_status ) {
					$caps[] = $post_type->cap->delete_private_posts;
				}
			}

			//Setting the privacy policy page requires `manage_privacy_options`, so deleting it should require that too.
			if ( ( int ) get_option( 'wp_page_for_privacy_policy' ) === $post->ID ) {
				$caps = array_merge( $caps, map_meta_cap( 'manage_privacy_options', $user_id ) );
			}

			break;

		case 'edit_post':
		case 'edit_page':
			// edit_post breaks down to edit_posts, edit_published_posts, or edit_others_posts
			$post = get_post( $args[0] );

			if ( ! $post ) {
				$caps[] = 'do_not_allow';
				break;
			}

			if ( 'revision' == $post->post_type ) {
				$post = get_post( $post->post_parent );

				if ( ! $post ) {
					$caps[] = 'do_not_allow';
					break;
				}
			}

			$post_type = get_post_type_object( $post->post_type );

			if ( ! $post_type ) {
				_doing_it_wrong( __FUNCTION__, sprintf( __( 'The post type %1$s is not registered, so it may not be reliable to check the capability "%2$s" against a post of that type.' ), $post->post_type, $cap ), '4.4.0' );
				$caps[] = 'edit_others_posts';
				break;
			}

			if ( ! $post_type->map_meta_cap ) {
				$caps[] = $post_type->cap->$cap;

				// Prior to 3.1 we would re-call map_meta_cap here.
				if ( 'edit_post' == $cap ) {
					$cap = $post_type->cap->$cap;
				}

				break;
			}

			// If the post author is set and the user is the author...
			if ( $post->post_author && $user_id == $post->post_author ) {
				// If the post is published or scheduled...
				if ( in_array( $post->post_status, array( 'publish', 'future' ), TRUE ) ) {
					$caps[] = $post_type->cap->edit_published_posts;
				} elseif ( 'trash' == $post->post_status ) {
					$status = get_post_meta( $post->ID, '_wp_trash_meta_status', TRUE );

					$caps[] = in_array( $status, array( 'publish', 'future' ), TRUE )
						? $post_type->cap->edit_published_posts
						: $post_type->cap->edit_posts;
				} else {
					// If the post is draft...
					$caps[] = $post_type->cap->edit_posts;
				}
			} else {
				// The user is trying to edit someone else's post.
				$caps[] = $post_type->cap->edit_others_posts;

				// The post is published or scheduled, extra cap required.
				if ( in_array( $post->post_status, array( 'publish', 'future' ), TRUE ) ) {
					$caps[] = $post_type->cap->edit_published_posts;
				} elseif ( 'private' == $post->post_status ) {
					$caps[] = $post_type->cap->edit_private_posts;
				}
			}

			// Setting the privacy policy page requires `manage_privacy_options`, so editing it should require that too.
			if ( ( int ) get_option( 'wp_page_for_privacy_policy' ) === $post->ID ) {
				$caps = array_merge( $caps, map_meta_cap( 'manage_privacy_options', $user_id ) );
			}

			break;

		case 'read_post':
		case 'read_page':
			$post = get_post( $args[0] );

			if ( ! $post ) {
				$caps[] = 'do_not_allow';
				break;
			}

			if ( 'revision' == $post->post_type ) {
				$post = get_post( $post->post_parent );

				if ( ! $post ) {
					$caps[] = 'do_not_allow';
					break;
				}
			}

			$post_type = get_post_type_object( $post->post_type );

			if ( ! $post_type ) {
				_doing_it_wrong( __FUNCTION__, sprintf( __( 'The post type %1$s is not registered, so it may not be reliable to check the capability "%2$s" against a post of that type.' ), $post->post_type, $cap ), '4.4.0' );
				$caps[] = 'edit_others_posts';
				break;
			}

			if ( ! $post_type->map_meta_cap ) {
				$caps[] = $post_type->cap->$cap;

				// Prior to 3.1 we would re-call map_meta_cap here.
				if ( 'read_post' == $cap ) {
					$cap = $post_type->cap->$cap;
				}

				break;
			}

			$status_obj = get_post_status_object( $post->post_status );
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/class-wp-user.php
 * @NOW 008: wp-includes/capabilities.php
 */
	}
}

/**
 * Whether the current user has a specific capability.
 *
 * While checking against particular roles in place of a capability is supported in part, this practice is discouraged as it may produce unreliable results.
 *
 * Note: Will always return true if the current user is a super admin, unless specifically denied.
 *
 * @since 2.0.0
 * @see   WP_User::has_cap()
 * @see   map_meta_cap()
 *
 * @param  string $capability Capability name.
 * @param  int    $object_id  Optional.
 *                            ID of the specific object to check against if `$capability` is a "meta" cap.
 *                            "Meta" capabilities, e.g. 'edit_post', 'edit_user', etc., are capabilities used by map_meta_cap() to map to other "primitive" capabilities, e.g. 'edit_posts', 'edit_other_posts', etc.
 *                            Accessed via func_get_args() and passed to WP_User::has_cap(), then map_meta_cap().
 * @return bool   Whether the current user has the given capability.
 *                If `$capability` is a meta cap and `$object_id` is passed, whether the current user has the given meta capability for the given object.
 */
function current_user_can( $capability )
{
	$current_user = wp_get_current_user();

	if ( empty( $current_user ) ) {
		return FALSE;
	}

	$args = array_slice( func_get_args(), 1 );
	$args = array_merge( array( $capability ), $args );
	return call_user_func_array( array( $current_user, 'has_cap' ), $args );
}

/**
 * Whether a particular user has a specific capability.
 *
 * @since 3.1.0
 *
 * @param  int|WP_User $user       User ID or object.
 * @param  string      $capability Capability name.
 * @return bool        Whether the user has the given capability.
 */
function user_can( $user, $capability )
{
	if ( ! is_object( $user ) ) {
		$user = get_userdata( $user );
	}

	if ( ! $user || ! $user->exists() ) {
		return FALSE;
	}

	$args = array_slice( func_get_args(), 2 );
	$args = array_merge( array( $capability ), $args );
	return call_user_func_array( array( $user, 'has_cap' ), $args );
}

/**
 * Retrieves the global WP_Roles instance and instantiates it if necessary.
 *
 * @since  4.3.0
 * @global WP_Roles $wp_roles WP_Roles global instance.
 *
 * @return WP_Roles WP_Roles global instance if not already instantiated.
 */
function wp_roles()
{
	global $wp_roles;

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}

	return $wp_roles;
}

/**
 * Retrieve role object.
 *
 * @since 2.0.0
 *
 * @param  string       $role Role name.
 * @return WP_Role|null WP_Role object if found, null if the role does not exist.
 */
function get_role( $role )
{
	return wp_roles()->get_role( $role );
}

/**
 * Retrieve a list of super admins.
 *
 * @since  3.0.0
 * @global array $super_admins
 *
 * @return array List of super admin logins.
 */
function get_super_admins()
{
	global $super_admins;

	return isset( $super_admins )
		? $super_admins
		: get_site_option( 'site_admins', array( 'admin' ) );
}

/**
 * Determine if user is a site admin.
 *
 * @since 3.0.0
 *
 * @param  int  $user_id (Optional) The ID of a user.
 *                       Defaults to the current user.
 * @return bool True if the user is a site admin.
 */
function is_super_admin( $user_id = FALSE )
{
	$user = ! $user_id || $user_id == get_current_user_id()
		? wp_get_current_user()
		: get_userdata( $user_id );

	if ( ! $user || ! $user->exists() ) {
		return FALSE;
	}

	if ( is_multisite() ) {
		$super_admins = get_super_admins();

		if ( is_array( $super_admins ) && in_array( $user->user_login, $super_admins ) ) {
			return TRUE;
		}
	} else {
		if ( $user->has_cap( 'delete_users' ) ) {
			return TRUE;
		}
	}

	return FALSE;
}
