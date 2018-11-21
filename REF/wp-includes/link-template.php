<?php
/**
 * WordPress Link Template Functions
 *
 * @package    WordPress
 * @subpackage Template
 */

/**
 * Retrieves a trailing-slashed string if the site is set for adding trailing slashes.
 *
 * Conditionally adds a trailing slash if the permalink structure has a trailing slash, strips the trailing slash if not.
 * The string is passed through the {@see 'user_trailingslashit'} filter.
 * Will remove trailing slash from string, if site is not set to have them.
 *
 * @since  2.2.0
 * @global WP_Rewrite $wp_rewrite
 *
 * @param  string $string      URL with or without a trailing slash.
 * @param  string $type_of_url Optional.
 *                             The type of URL being considered (e.g. single, category, etc) for use in the filter.
 *                             Default empty string.
 * @return string The URL with the trailing slash appended or stripped.
 */
function user_trailingslashit( $string, $type_of_url = '' )
{
	global $wp_rewrite;

	$string = $wp_rewrite->use_trailing_slashes
		? trailingslashit( $string )
		: untrailingslashit( $string );

	/**
	 * Filters the trailing-slashed string, depending on whether the site is set to use trailing slashes.
	 *
	 * @since 2.2.0
	 *
	 * @param string $string      URL with or without a trailing slash.
	 * @param string $type_of_url The type of URL being considered.
	 *                            Accepts 'single', 'single_trackback', 'single_feed', 'single_paged', 'commentpaged', 'paged', 'home', 'feed', 'category', 'page', 'year', 'month', 'day', 'post_type_archive'.
	 */
	return apply_filters( 'user_trailingslashit', $string, $type_of_url );
}

/**
 * Retrieves the full permalink for the current post or post ID.
 *
 * @since 1.0.0
 *
 * @param  int|WP_Post  $post      Optional.
 *                                 Post ID or post object.
 *                                 Default is the global `$post`.
 * @param  bool         $leavename Optional.
 *                                 Whether to keep post name or page name.
 *                                 Default false.
 * @return string|false The permalink URL or false if post does not exist.
 */
function get_permalink( $post = 0, $leavename = FALSE )
{
	$rewritecode = array( '%year%', '%monthnum%', '%day%', '%hour%', '%minute%', '%second%', $leavename ? '' : '%postname%', '%post_id%', '%category%', '%author%', $leavename ? '' : '%pagename%' );

	if ( is_object( $post ) && isset( $post->filter ) && 'sample' == $post->filter ) {
		$sample = TRUE;
	} else {
		$post = get_post( $post );
		$sample = FALSE;
	}

	if ( empty( $post->ID ) ) {
		return FALSE;
	}

	if ( $post->post_type == 'page' ) {
		return get_page_link( $post, $leavename, $sample );
	} elseif ( $post->post_type == 'attachment' ) {
		return get_attachment_link( $post, $leavename );
	} elseif ( in_array( $post->post_type, get_post_types( array( '_builtin' => FALSE ) ) ) ) {
		return get_post_permalink( $post, $leavename, $sample );
	}

	$permalink = get_option( 'permalink_structure' );

	/**
	 * Filters the permalink structure for a post before token replacement occurs.
	 *
	 * Only applies to posts with post_type of 'post'.
	 *
	 * @since 3.0.0
	 *
	 * @param string  $permalink The site's permalink structure.
	 * @param WP_Post $post      The post in question.
	 * @param bool    $leavename Whether to keep the post name.
	 */
	$permalink = apply_filters( 'pre_post_link', $permalink, $post, $leavename );

	if ( '' != $permalink && ! in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft', 'future' ) ) ) {
		$unixtime = strtotime( $post->post_date );
		$category = '';

		if ( strpos( $permalink, '%category%' ) !== FALSE ) {
			$cats = get_the_category( $post->ID );

			if ( $cats ) {
				$cats = wp_list_sort( $cats, array( 'term_id' => 'ASC' ) );
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * @NOW 007: wp-includes/link-template.php
 * -> wp-includes/class-wp-list-util.php
 */
			}
		}
	}
}

/**
 * Retrieves the permalink for a post of a custom post type.
 *
 * @since  3.0.0
 * @global WP_Rewrite $wp_rewrite
 *
 * @param  int|WP_Post     $id        Optional.
 *                                    Post ID or post object.
 *                                    Default is the global `$post`.
 * @param  bool            $leavename Optional, defaults to false.
 *                                    Whether to keep post name.
 * @param  bool            $sample    Optional, defaults to false.
 *                                    Is it a sample permalink.
 * @return string|WP_Error The post permalink.
 */
function get_post_permalink( $id = 0, $leavename = FALSE, $sample = FALSE )
{
	global $wp_rewrite;
	$post = get_post( $id );

	if ( is_wp_error( $post ) ) {
		return $post;
	}

	$post_link = $wp_rewrite->get_extra_permastruct( $post->post_type );
	$slug = $post->post_name;
	$draft_or_pending = get_post_status( $post ) && in_array( get_post_status( $post ), array( 'draft', 'pending', 'auto-draft', 'future' ) );
	$post_type = get_post_type_object( $post->post_type );

	if ( $post_type->hierarchical ) {
		$slug = get_page_uri( $post );
	}

	if ( ! empty( $post_link )
	  && ( ! $draft_or_pending || $sample ) ) {
		if ( ! $leavename ) {
			$post_link = str_replace( "%$post->post_type%", $slug, $post_link );
		}

		$post_link = home_url( user_trailingslashit( $post_link ) );
	} else {
		$post_link = $post_type->query_var && isset( $post->post_status ) && ! $draft_or_pending
			? add_query_arg( $post_type->query_var, $slug, '' )
			: add_query_arg( array(
					'post_type' => $post->post_type,
					'p'         => $post->ID
				), '' );

		$post_link = home_url( $post_link );
	}

	/**
	 * Filters the permalink for a post of a custom post type.
	 *
	 * @since 3.0.0
	 *
	 * @param string  $post_link The post's permalink.
	 * @param WP_Post $post      The post in question.
	 * @param bool    $leavename Whether to keep the post name.
	 * @param bool    $sample    Is it a sample permalink.
	 */
	return apply_filters( 'post_type_link', $post_link, $post, $leavename, $sample );
}

/**
 * Retrieves the permalink for the current page or page ID.
 *
 * Respects page_on_front.
 * Use this one.
 *
 * @since 1.5.0
 *
 * @param  int|WP_Post $post      Optional.
 *                                Post ID or object.
 *                                Default uses the global `$post`.
 * @param  bool        $leavename Optional.
 *                                Whether to keep the page name.
 *                                Default false.
 * @param  bool        $sample    Optional.
 *                                Whether it should be treated as a sample permalink.
 *                                Default false.
 * @return string      The page permalink.
 */
function get_page_link( $post = FALSE, $leavename = FALSE, $sample = FALSE )
{
	$post = get_post( $post );

	$link = 'page' == get_option( 'show_on_front' ) && $post->ID == get_option( 'page_on_front' )
		? home_url( '/' )
		: _get_page_link( $post, $leavename, $sample );

	/**
	 * Filters the permalink for a page.
	 *
	 * @since 1.5.0
	 *
	 * @param string $link    The page's permalink.
	 * @param int    $post_id The ID of the page.
	 * @param bool   $sample  Is it a sample permalink.
	 */
	return apply_filters( 'page_link', $link, $post->ID, $sample );
}

/**
 * Retrieves the page permalink.
 *
 * Ignores page_on_front.
 * Internal use only.
 *
 * @since  2.1.0
 * @access private
 * @global WP_Rewrite $wp_rewrite
 *
 * @param  int|WP_Post $post      Optional.
 *                                Post ID or object.
 *                                Default uses the global `$post`.
 * @param  bool        $leavename Optional.
 *                                Whether to keep the page name.
 *                                Default false.
 * @param  bool        $sample    Optional.
 *                                Whether it should be treated as a sample permalink.
 *                                Default false.
 * @return string      The page permalink.
 */
function _get_page_link( $post = FALSE, $leavename = FALSE, $sample = FALSE )
{
	global $wp_rewrite;
	$post = get_post( $post );
	$draft_or_pending = in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) );
	$link = $wp_rewrite->get_page_permastruct();

	if ( ! empty( $link )
	  && ( isset( $post->post_status ) && ! $draft_or_pending
	    || $sample ) ) {
		if ( ! $leavename ) {
			$link = str_replace( '%pagename%', get_page_uri( $post ), $link );
		}

		$link = home_url( $link );
		$link = user_trailingslashit( $link, 'page' );
	} else {
		$link = home_url( '?page_id=' . $post->ID );
	}

	/**
	 * Filters the permalink for a non-page_on_front page.
	 *
	 * @since 2.1.0
	 *
	 * @param string $link    The page's permalink.
	 * @param int    $post_id The ID of the page.
	 */
	return apply_filters( '_get_page_link', $link, $post->ID );
}

/**
 * Retrieves the permalink for an attachment.
 *
 * This can be used in the WordPress Loop or outside of it.
 *
 * @since  2.0.0
 * @global WP_Rewrite $wp_rewrite
 *
 * @param  int|object $post      Optional.
 *                               Post ID or object.
 *                               Default uses the global `$post`.
 * @param  bool       $leavename Optional.
 *                               Whether to keep the page name.
 *                               Default false.
 * @return string     The attachment permalink.
 */
function get_attachment_link( $post = NULL, $leavename = FALSE )
{
	global $wp_rewrite;
	$link = FALSE;
	$post = get_post( $post );

	$parent = $post->post_parent > 0 && $post->post_parent != $post->ID
		? get_post( $post->post_parent )
		: 0;

	if ( $parent && ! in_array( $parent->post_type, get_post_types() ) ) {
		$parent = FALSE;
	}

	if ( $wp_rewrite->using_permalinks() && $parent ) {
		$parentlink = 'page' == $parent->post_type
			? _get_page_link( $post->post_parent ) // Ignores page_on_front
			: get_permalink( $post->parent );

		$name = is_numeric( $post->post_name ) || FALSE !== strpos( get_option( 'permalink_structure' ), '%category%' )
			? 'attachment/' . $post->post_name // <permalink>/<int>/ is paged so we use the explicit attachment marker.
			: $post->post_name;

		if ( strpos( $parentlink, '?' ) === FALSE ) {
			$link = user_trailingslashit( trailingslashit( $parentlink ) . '%postname%' );
		}

		if ( ! $leavename ) {
			$link = str_replace( '%postname%', $name, $link );
		}
	} elseif ( $wp_rewrite->using_permalinks() && ! $leavename ) {
		$link = home_url( user_trailingslashit( $post->post_name ) );
	}

	if ( ! $link ) {
		$link = home_url( '/?attachment_id=' . $post->ID );
	}

	/**
	 * Filters the permalink for an attachment.
	 *
	 * @since 2.0.0
	 *
	 * @param string $link    The attachment's permalink.
	 * @param int    $post_id Attachment ID.
	 */
	return apply_filters( 'attachment_link', $link, $post->ID );
}

/**
 * Retrieves the permalink for the feed type.
 *
 * @since  1.5.0
 * @global WP_Rewrite $wp_rewrite
 *
 * @param  string $feed Optional.
 *                      Feed type.
 *                      Default empty.
 * @return string The feed permalink.
 */
function get_feed_link( $feed = '' )
{
	global $wp_rewrite;
	$permalink = $wp_rewrite->get_feed_permastruct();

	if ( '' != $permalink ) {
		if ( FALSE !== strpos( $feed, 'comments_' ) ) {
			$feed = str_replace( 'comments_', '', $feed );
			$permalink = $wp_rewrite->get_comment_feed_permastruct();
		}

		if ( get_default_feed() == $feed ) {
			$feed = '';
		}

		$permalink = str_replace( '%feed%', $feed, $permalink );
		$permalink = preg_replace( '#/+#', '/', "/$permalink" );
		$output = home_url( user_trailingslashit( $permalink, 'feed' ) );
	} else {
		if ( empty( $feed ) ) {
			$feed = get_default_feed();
		}

		if ( FALSE !== strpos( $feed, 'comments_' ) ) {
			$feed = str_replace( 'comments_', 'comments-', $feed );
		}

		$output = home_url( "?feed{$feed}" );
	}

	/**
	 * Filters the feed type permalink.
	 *
	 * @since 1.5.0
	 *
	 * @param string $output The feed permalink.
	 * @param string $feed   Feed type.
	 */
	return apply_filters( 'feed_link', $output, $feed );
}

/**
 * Retrieves the URL for the current site where the front end is accessible.
 *
 * Returns the 'home' option with the appropriate protocol.
 * The protocol will be 'https' if is_ssl() evaluates to true; otherwise, it will be the same as the 'home' option.
 * If `$scheme` is 'http' or 'https', is_ssl() is overridden.
 *
 * @since 3.0.0
 *
 * @param  string      $path   Optional.
 *                             Path relative to the home URL.
 *                             Default empty.
 * @param  string|null $scheme Optional.
 *                             Scheme to give the home URL context.
 *                             Accepts 'http', 'https', 'relative', 'rest', or null.
 *                             Default null.
 * @return string      Home URL link with optional path appended.
 */
function home_url( $path = '', $scheme = NULL )
{
	return get_home_url( NULL, $path, $scheme );
}

/**
 * Retrieves the URL for a given site where the front end is accessible.
 *
 * Returns the 'home' option with the appropriate protocol.
 * The protocol will be 'https' if is_ssl() evaluates to true; otherwise, it will be the same as the 'home' option.
 * If `$scheme` is 'http' or 'https', is_ssl() is overridden.
 *
 * @since  3.0.0
 * @global $pagenow
 *
 * @param  int         $blog_id Optional.
 *                              Site ID.
 *                              Default null (current site).
 * @param  string      $path    Optional.
 *                              Path relative to the home URL.
 *                              Default empty.
 * @param  string|null $scheme  Optional.
 *                              Scheme to give the home URL context.
 *                              Accepts 'http', 'https', 'relative', 'rest', or null.
 *                              Default null.
 * @return string      Home URL link with optional path appended.
 */
function get_home_url( $blog_id = NULL, $path = '', $scheme = NULL )
{
	global $pagenow;
	$orig_scheme = $scheme;

	if ( empty( $blog_id ) || ! is_multisite() ) {
		$url = get_option( 'home' );
	} else {
		switch_to_blog( $blog_id );
		$url = get_option( 'home' );
		restore_current_blog();
	}

	if ( ! in_array( $scheme, array( 'http', 'https', 'relative' ) ) ) {
		$scheme = is_ssl() && ! is_admin() && 'wp-login.php' !== $pagenow
			? 'https'
			: parse_url( $url, PHP_URL_SCHEME );
	}

	$url = set_url_scheme( $url, $scheme );

	if ( $path && is_string( $path ) ) {
		$url .= '/' . ltrim( $path, '/' );
	}

	/**
	 * Filters the home URL.
	 *
	 * @since 3.0.0
	 *
	 * @param string      $url         The complete home URL including scheme and path.
	 * @param string      $path        Path relative to the home URL.
	 *                                 Blank string if no path is specified.
	 * @param string|null $orig_scheme Scheme to give the home URL context.
	 *                                 Accepts 'http', 'https', 'relative', 'rest', or null.
	 * @param int|null    $blog_id     Site ID, or null for the current site.
	 */
	return apply_filters( 'home_url', $url, $path, $orig_scheme, $blog_id );
}

/**
 * Retrieves the URL for the current site where WordPress application files (e.g. wp-blog-header.php or the wp-admin/ folder) are accessible.
 *
 * Returns the 'site_url' option with the appropriate protocol, 'https' if is_ssl() and 'http' otherwise.
 * If $scheme is 'http' or 'https', is_ssl() is overridden.
 *
 * @since 3.0.0
 *
 * @param  string $path   Optional.
 *                        Path relative to the site URL.
 *                        Default empty.
 * @param  string $scheme Optional.
 *                        Scheme to give the site URL context.
 *                        See set_url_scheme().
 * @return string Site URL link with optional path appended.
 */
function site_url( $path = '', $scheme = NULL )
{
	return get_site_url( NULL, $path, $scheme );
}

/**
 * Retrieves the URL for a given site WHERE WordPress application files (e.g. wp-blog-header.php or the wp-admin/ folder) are accessible.
 *
 * Returns the 'site_url' option with the appropriate protocol, 'https' if is_ssl() and 'http' otherwise.
 * If `$scheme` is 'http' or 'https', `is_ssl()` is overridden.
 *
 * @since 3.0.0
 *
 * @param  int    $blog_id Optional.
 *                         Site ID.
 *                         Default null (current site).
 * @param  string $path    Optional.
 *                         Path relative to the site URL.
 *                         Default empty.
 * @param  string $scheme  Optional.
 *                         Scheme to give the site URL context.
 *                         Accepts 'http', 'https', 'login', 'login_post', 'admin' or 'relative'.
 *                         Default null.
 * @return string Site URL link with optional path appended.
 */
function get_site_url( $blog_id = NULL, $path = '', $scheme = NULL )
{
	if ( empty( $blog_id ) || ! is_multisite() ) {
		$url = get_option( 'siteurl' );
	} else {
		switch_to_blog( $blog_id );
		$url = get_option( 'siteurl' );
		restore_current_blog();
	}

	$url = set_url_scheme( $url, $scheme );

	if ( $path && is_string( $path ) ) {
		$url .= '/' . ltrim( $path, '/' );
	}

	/**
	 * Filters the site URL.
	 *
	 * @since 2.7.0
	 *
	 * @param string      $url     The complete site URL including scheme and path.
	 * @param string      $path    Path relative to the site URL.
	 *                             Blank string if no path is specified.
	 * @param string|null $scheme  Scheme to give the site URL context.
	 *                             Accepts 'http', 'https', 'login', 'login_post', 'admin', 'relative' or null.
	 * @param int|null    $blog_id Site ID, or null for the current site.
	 */
	return apply_filters( 'site_url', $url, $path, $scheme, $blog_id );
}

/**
 * Retrieves the URL to the content directory.
 *
 * @since 2.6.0
 *
 * @param  string $path Optional.
 *                      Path relative to the content URL.
 *                      Default empty.
 * @return string Content URL link with optional path appended.
 */
function content_url( $path = '' )
{
	$url = set_url_scheme( WP_CONTENT_URL );

	if ( $path && is_string( $path ) ) {
		$url .= '/' . ltrim( $path, '/' );
	}

	/**
	 * Filters the URL to the content directory.
	 *
	 * @since 2.8.0
	 *
	 * @param string $url  The complete URL to the content directory including scheme and path.
	 * @param string $path Path relative to the URL to the content directory.
	 *                     Blank string if no path is specified.
	 */
	return apply_filters( 'content_url', $url, $path );
}

/**
 * Retrieves a URL within the plugins or mu-plugins directory.
 *
 * Defaults to the plugins directory URL if no arguments are supplied.
 *
 * @since 2.6.0
 *
 * @param  string $path   Optional.
 *                        Extra path appended to the end of the URL, including the relative directory if $plugin is supplied.
 *                        Default empty.
 * @param  string $plugin Optional.
 *                        A full path to a file inside a plugin or mu-plugin.
 *                        The URL will be relative to its directory.
 *                        Default empty.
 *                        Typically this is done by passing `__FILE__` as the argument.
 * @return string Plugins URL link with optional paths appended.
 */
function plugins_url( $path = '', $plugin = '' )
{
	$path = wp_normalize_path( $path );
	$plugin = wp_normalize_path( $plugin );
	$mu_plugin_dir = wp_normalize_path( WPMU_PLUGIN_DIR );

	$url = ! empty( $plugin ) && 0 === strpos( $plugin, $mu_plugin_dir )
		? WPMU_PLUGIN_DIR
		: WP_PLUGIN_DIR;

	$url = set_url_scheme( $url );

	if ( ! empty( $plugin ) && is_string( $plugin ) ) {
		$folder = dirname( plugin_basename( $plugin ) );

		if ( '.' != $folder ) {
			$url .= '/' . ltrim( $folder, '/' );
		}
	}

	if ( $path && is_string( $path ) ) {
		$url .= '/' . ltrim( $path, '/' );
	}

	/**
	 * Filters the URL to the plugins directory.
	 *
	 * @since 2.8.0
	 *
	 * @param string $url    The complete URL to the plugins directory including scheme and path.
	 * @param string $path   Path relative to the URL to the plugins directory.
	 *                       Blank string if no path is specified.
	 * @param string $plugin The plugin file path to be relative to.
	 *                       Blank string if no plugin is specified.
	 */
	return apply_filters( 'plugins_url', $url, $path, $plugin );
}

/**
 * Sets the scheme for a URL.
 *
 * @since 3.4.0
 * @since 4.4.0 The 'rest' scheme was added.
 *
 * @param  string      $url    Absolute URL that includes a scheme.
 * @param  string|null $scheme Optional.
 *                             Scheme to give $url.
 *                             Currently 'http', 'https', 'login', 'login_post', 'admin', 'relative', 'rest', 'rpc', or null.
 *                             Default null.
 * @return string      $url    URL with chosen scheme.
 */
function set_url_scheme( $url, $scheme = NULL )
{
	$orig_scheme = $scheme;

	if ( ! $scheme ) {
		$scheme = is_ssl()
			? 'https'
			: 'http';
	} elseif ( $scheme === 'admin' || $scheme === 'login' || $scheme === 'login_post' || $scheme === 'rpc' ) {
		$scheme = is_ssl() || force_ssl_admin()
			? 'https'
			: 'http';
	} elseif ( $scheme !== 'http' && $scheme !== 'https' && $scheme !== 'relative' ) {
		$scheme = is_ssl()
			? 'https'
			: 'http';
	}

	$url = trim( $url );

	if ( substr( $url, 0, 2 ) === '//' ) {
		$url = 'http:' . $url;
	}

	if ( 'relative' == $scheme ) {
		$url = ltrim( preg_replace( '#^\w+://[^/]*#', '', $url ) );

		if ( $url !== '' && $url[0] === '/' ) {
			$url = '/' . ltrim( $url, "/ \t\n\r\0\x0B" );
		}
	} else {
		$url = preg_replace( '#^\w+://#', $scheme . '://', $url );
	}

	/**
	 * Filters the resulting URL after setting the scheme.
	 *
	 * @since 3.4.0
	 *
	 * @param string      $url         The complete URL including scheme and path.
	 * @param string      $scheme      Scheme applied to the URL.
	 *                                 One of 'http', 'https', or 'relative'.
	 * @param string|null $orig_scheme Scheme requested for the URL.
	 *                                 One of 'http', 'https', 'login', 'login_post', 'admin', 'relative', 'rest', 'rpc', or null.
	 */
	return apply_filters( 'set_url_scheme', $url, $scheme, $orig_scheme );
}
