<?php
/**
 * Site/blog functions that work with the blogs table and related data.
 *
 * @package    WordPress
 * @subpackage Multisite
 * @since      MU (3.0.0)
 */

/**
 * Retrieve option value for a given blog id based on name of option.
 *
 * If the option does not exist or does not have a value, then the return value will be false.
 * This is useful to check whether you need to install an option and is commonly used during installation of plugin options and to test whether upgrading is required.
 *
 * If the option was serialized then it will be unserialized when it is returned.
 *
 * @since MU (3.0.0)
 *
 * @param  int    $id      A blog ID.
 *                         Can be null to refer to the current blog.
 * @param  string $option  Name of option to retrieve.
 *                         Expected to not be SQL-escaped.
 * @param  mixed  $default Optional.
 *                         Default value to return if the option does not exist.
 * @return mixed  Value set for the option.
 */
function get_blog_option( $id, $option, $default = FALSE )
{
	$id = ( int ) $id;

	if ( empty( $id ) ) {
		$id = get_current_blog_id();
	}

	if ( get_current_blog_id() == $id ) {
		return get_option( $option, $default );
	}

	switch_to_blog( $id );
	$value = get_option( $option, $default );
	restore_current_blog();

	/**
	 * Filters a blog option value.
	 *
	 * The dynamic portion of the hook name, `$option`, refers to the blog option name.
	 *
	 * @since 3.5.0
	 *
	 * @param string $value The option value.
	 * @param int    $id    Blog ID.
	 */
	return apply_filters( "blog_option_{$option}", $value, $id );
}

/**
 * Switch the current blog.
 *
 * This function is useful if you need to pull posts, or other information, from other blogs.
 * You can switch back afterwards using restore_current_blog().
 *
 * Things that aren't switched:
 *     - plugins. See #14941
 *
 * @see    restore_current_blog()
 * @since  MU (3.0.0)
 * @global wpdb            $wpdb
 * @global int             $blog_id
 * @global array           $_wp_switched_stack
 * @global bool            $switched
 * @global string          $table_prefix
 * @global WP_Object_Cache $wp_object_cache
 *
 * @param  int  $new_blog   The id of the blog you want to switch to.
 *                          Default: current blog
 * @param  bool $deprecated Deprecated argument.
 * @return true Always returns true.
 */
function switch_to_blog( $new_blog, $deprecated = NULL )
{
	global $wpdb;
	$blog_id = get_current_blog_id();

	if ( empty( $new_blog ) ) {
		$new_blog = $blog_id;
	}

	$GLOBALS['_wp_switched_stack'][] = $blog_id;

	// If we're switching to the same blog id that we're on, set the right vars, do the associated actions, but skip the extra unnecessary work.
	if ( $new_blog == $blog_id ) {
		/**
		 * Fires when the blog is switched.
		 *
		 * @since MU (3.0.0)
		 *
		 * @param int $new_blog New blog ID.
		 * @param int $new_blog Blog ID.
		 */
		do_action( 'switch_blog', $new_blog, $new_blog );

		$GLOBALS['switched'] = TRUE;
		return TRUE;
	}

	$wpdb->set_blog_id( $new_blog );
	$GLOBALS['table_prefix'] = $wpdb->get_blog_prefix();
	$prev_blog_id = $blog_id;
	$GLOBALS['blog_id'] = $new_blog;

	if ( function_exists( 'wp_cache_switch_to_blog' ) ) {
		wp_cache_switch_to_blog( $new_blog );
	} else {
		global $wp_object_cache;
		$global_groups = ( is_object( $wp_object_cache ) && isset( $wp_object_cache->global_groups ) )
			? $wp_object_cache->global_groups
			: FALSE;
		wp_cache_init();

		if ( function_exists( 'wp_cache_add_global_groups' ) ) {
			if ( is_array( $global_groups ) ) {
				wp_cache_add_global_groups( $global_groups );
			} else {
				wp_cache_add_global_groups( ['users', 'userlogins', 'usermeta', 'user_meta', 'useremail', 'userslugs', 'site-transient', 'site-options', 'blog-lookup', 'blog-details', 'rss', 'global-posts', 'blog-id-cache', 'networks', 'sites', 'site-details'] );
			}

			wp_cache_add_non_persistent_groups( ['counts', 'plugins'] );
		}
	}

	// This filter is documented in wp-includes/ms-blogs.php
	do_action( 'switch_blog', $new_blog, $prev_blog_id );

	$GLOBALS['switched'] = TRUE;
	return TRUE;
}

/**
 * Restore the current blog, after calling switch_to_blog().
 *
 * @see    switch_to_blog()
 * @since  MU (3.0.0)
 * @global wpdb            $wpdb
 * @global array           $_wp_switched_stack
 * @global int             $blog_id
 * @global bool            $switched
 * @global string          $table_prefix
 * @global WP_Object_Cache $wp_object_cache
 *
 * @return bool True on success, false if we're already on the current blog.
 */
function restore_current_blog()
{
	global $wpdb;

	if ( empty( $GLOBALS['_wp_switched_stack'] ) ) {
		return FALSE;
	}

	$blog = array_pop( $GLOBALS['_wp_switched_stack'] );
	$blog_id = get_current_blog_id();

	if ( $blog_id == $blog ) {
		// This filter is documented in wp-includes/ms-blogs.php
		do_action( 'switch_blog', $blog, $blog );

		// If we still have items in the switched stack, consider ourselves still 'switched'.
		$GLOBALS['switched'] = ! empty( $GLOBALS['_wp_switched_stack'] );
		return TRUE;
	}

	$wpdb->set_blog_id( $blog );
	$prev_blog_id = $blog_id;
	$GLOBALS['blog_id'] = $blog;
	$GLOBALS['table_prefix'] = $wpdb->get_blog_prefix();

	if ( function_exists( 'wp_cache_switch_to_blog' ) ) {
		wp_cache_switch_to_blog( $blog );
	} else {
		global $wp_object_cache;
		$global_groups = ( is_object( $wp_object_cache ) && isset( $wp_object_cache->global_groups ) ) ? $wp_object_cache->global_groups : FALSE;
		wp_cache_init();

		if ( function_exists( 'wp_cache_add_global_groups' ) ) {
			if ( is_array( $global_groups ) ) {
				wp_cache_add_global_groups( $global_groups );
			} else {
				wp_cache_add_global_groups( ['users', 'userlogins', 'usermeta', 'mser_meta', 'useremail', 'userslugs', 'site-transient', 'site-options', 'blog-lookup', 'blog-details', 'rss', 'global-posts', 'blog-id-cache', 'networks', 'sites', 'site-details'] );
			}

			wp_cache_add_non_persistent_groups( ['counts', 'plugins'] );
		}
	}

	// This filter is documented in wp-includes/ms-blogs.php
	do_action( 'switch_blog', $blog, $prev_blog_id );

	// If we still have items in teh switched stack, consider ourselves still 'switched'.
	$GLOBALS['switched'] = ! empty( $GLOBALS['_wp_switched_stack'] );

	return TRUE;
}

/**
 * Switches the initialized roles and current user capabilities to another site.
 *
 * @since 4.9.0
 *
 * @param int $new_site_id New site ID.
 * @param int $old_site_id Old site ID.
 */
function wp_switch_roles_and_user( $new_site_id, $old_site_id )
{
	if ( $new_site_id == $old_site_id ) {
		return;
	}

	if ( ! did_action( 'init' ) ) {
		return;
	}

	wp_roles()->for_site( $new_site_id );
	wp_get_current_user()->for_site( $new_site_id );
}

// @NOW 023

/**
 * Retrieves network data given a network ID or network object.
 *
 * Network data will be cached and returned after being passed through a filter.
 * If the provided network is empty, the current network global will be used.
 *
 * @since  4.6.0
 * @global WP_Network $current_site
 *
 * @param  WP_Network|int|null $network Optional.
 *                                      Network to retrieve.
 *                                      Default is the current network.
 * @return WP_Network|null     The network object or null if not found.
 */
function get_network( $network = NULL )
{
	global $current_site;

	if ( empty( $network ) && isset( $current_site ) ) {
		$network = $current_site;
	}

	$_network = ( $network instanceof WP_Network )
		? $network
		: ( is_object( $network )
			? new WP_Network( $network )
			: WP_Network::get_instance( $network ) );

	if ( ! $_network ) {
		return NULL;
	}

	/**
	 * Fires after a network is retrieved.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_Network $_network Network data.
	 */
	$_network = apply_filters( 'get_network', $_network );

	return $_network;
}
