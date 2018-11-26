<?php
/**
 * WordPress Theme Administration API
 *
 * @package    WordPress
 * @subpackage Administration
 */

/**
 * Retrieve list of WordPress theme features (aka theme tags).
 *
 * @since 3.1.0
 *
 * @param  bool  $api Optional.
 *                    Whether try to fetch tags from the WordPress.org API.
 *                    Defaults to true.
 * @return array Array of features keyed by category with translations keyed by slug.
 */
function get_theme_feature_list( $api = TRUE )
{
	// Hard-coded list is used if api not accessible.
	$features = array(
		__( 'Subject' )  => array(
				'blog'           => __( 'Blog' ),
				'e-commerce'     => __( 'E-Commerce' ),
				'education'      => __( 'Education' ),
				'entertainment'  => __( 'Entertainment' ),
				'food-and-drink' => __( 'Food & Drink' ),
				'holiday'        => __( 'Holiday' ),
				'news'           => __( 'News' ),
				'photography'    => __( 'Photography' ),
				'portfolio'      => __( 'Portfolio' )
			),
		__( 'Features' ) => array(
				'accessibility-ready'   => __( 'Accessibility Ready' ),
				'custom-background'     => __( 'Custom Background' ),
				'custom-colors'         => __( 'Custom Colors' ),
				'custom-header'         => __( 'Custom Header' ),
				'custom-logo'           => __( 'Custom Logo' ),
				'editor-style'          => __( 'Editor Style' ),
				'featured-image-header' => __( 'Featured Image Header' ),
				'featured-images'       => __( 'Featured Images' ),
				'footer-widgets'        => __( 'Footer Widgets' ),
				'full-width-template'   => __( 'Full Width Template' ),
				'post-formats'          => __( 'Post Formats' ),
				'sticky-post'           => __( 'Sticky Post' ),
				'theme-options'         => __( 'Theme Options' )
			),
		__( 'Layout' )   => array(
				'grid-layout'   => __( 'Grid Layout' ),
				'one-column'    => __( 'One Column' ),
				'two-columns'   => __( 'Two Columns' ),
				'three-columns' => __( 'Three Columns' ),
				'four-columns'  => __( 'Four Columns' ),
				'left-sidebar'  => __( 'Left Sidebar' ),
				'right-sidebar' => __( 'Right Sidebar' )
			)
	);

	if ( ! $api || ! current_user_can( 'install_themes' ) ) {
		return $features;
	}

	if ( ! $feature_list = get_site_transient( 'wporg_theme_feature_list' ) ) {
		set_site_transient( 'wporg_theme_feature_list', array(), 3 * HOUR_IN_SECONDS );
	}

	if ( ! $feature_list ) {
		$feature_list = themes_api( 'feature_list', array() );
/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/post.php: wp_check_post_hierarchy_for_loops( int $post_parent, int $post_ID )
 * <-......: wp-includes/post.php: wp_insert_post( array $postarr [, bool $wp_error = FALSE] )
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::get_page_templates( [WP_Post|null $post = NULL [, string $post_type = 'page']] )
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::get_post_templates()
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::translate_header( string $header, string $value )
 * @NOW 010: wp-admin/includes/theme.php: get_theme_feature_list( [bool $api = TRUE] )
 * ......->: wp-admin/includes/theme.php: themes_api( string $action [, array|object $args = array()] )
 */
	}
}

/**
 * Retrieves theme installer pages from the WordPress.org Themes API.
 *
 * It is possible for a theme to override the Themes API result with three filters.
 * Assume this is for themes, which can extend on the Theme Info to offer more choices.
 * This is very powerful and must be used with care, when overriding the filters.
 *
 * The first filter, {@see 'themes_api_args'}, is for the args and gives the action as the second parameter.
 * The hook for {@see 'themes_api_args'} must ensure that an object is returned.
 *
 * The second filter, {@see 'themes_api'}, allows a plugin to override the WordPress.org THeme API entirely.
 * If `$action` is 'query_themes', 'theme_information', or 'feature_list', an object MUST be passed.
 * If `$action` is 'hot_tags', an array should be passed.
 *
 * Finally, the third filter, {@see 'themes_api_result'}, makes it possible to filter the response object or array, depending on the `$action` type.
 *
 * Supported arguments per action:
 *
 * | Argument Name | 'query_themes' | 'theme_information' | 'hot_tags' | 'feature_list' |
 * | ------------- | -------------- | ------------------- | ---------- | -------------- |
 * | `$slug`       | No             | Yes                 | No         | No             |
 * | `$per_page`   | Yes            | No                  | No         | No             |
 * | `$page`       | Yes            | No                  | No         | No             |
 * | `$number`     | No             | No                  | Yes        | No             |
 * | `$search`     | Yes            | No                  | No         | No             |
 * | `$tag`        | Yes            | No                  | No         | No             |
 * | `$author`     | Yes            | No                  | No         | No             |
 * | `$user`       | Yes            | No                  | No         | No             |
 * | `$browse`     | Yes            | No                  | No         | No             |
 * | `$locale`     | Yes            | Yes                 | No         | No             |
 * | `$fields`     | Yes            | Yes                 | No         | No             |
 *
 * @since 2.8.0
 *
 * @param  string                $action API action to perform: 'query_themes', 'theme_information', 'hot_tags' or 'feature_list'.
 * @param  array|object          $args {
 *     Optional.
 *     Array or object of arguments to serialize for the Themes API.
 *
 *     @type string $slug     The theme slug.
 *                            Default empty.
 *     @type int    $per_page Number of themes per page.
 *                            Default 24.
 *     @type int    $page     Number of current page.
 *                            Default 1.
 *     @type int    $number   Number of tags to be queried.
 *     @type string $search   A search term.
 *                            Default empty.
 *     @type string $tag      Tag to filter themes.
 *                            Default empty.
 *     @type string $author   Username of an author to filter themes.
 *                            Default empty.
 *     @type string $user     Username to query for their favorites.
 *                            Default empty.
 *     @type string $browse   Browse view: 'featured', 'popular', 'updated', 'favorites'.
 *     @type string $locale   Locale to provide context-sensitive results.
 *                            Default is the value of get_locale().
 *     @type array  $fields {
 *         Array of fields which should or should not be returned.
 *
 *         @type bool $description        Whether to return the theme full description.
 *                                        Default false.
 *         @type bool $sections           Whether to return the theme readmen sections: description, installation, FAQ, screenshots, other notes, and changelog.
 *                                        Default false.
 *         @type bool $rating             Whether to return the rating in percent and total number of ratings.
 *                                        Default false.
 *         @type bool $ratings            Whether to return the number of rating for each star (1-5).
 *                                        Default false.
 *         @type bool $downloaded         Whether to return the download count.
 *                                        Default false.
 *         @type bool $downloadlink       Whether to return the download link for the package.
 *                                        Default false.
 *         @type bool $last_updated       Whether to return the date of the last update.
 *                                        Default false.
 *         @type bool $tags               Whether to return the assigned tags.
 *                                        Default false.
 *         @type bool $homepage           Whether to return the theme homepage link.
 *                                        Default false.
 *         @type bool $screenshots        Whether to return the screenshots.
 *                                        Default false.
 *         @type int  $screenshot_count   Number of screenshots to return.
 *                                        Default 1.
 *         @type bool $screenshot_url     Whether to return the URL of the first screenshot.
 *                                        Default false.
 *         @type bool $photon_screenshots Whether to return the screenshots via Photon.
 *                                        Default false.
 *         @type bool $template           Whether to return the slug of the parent theme.
 *                                        Default false.
 *         @type bool $parent             Whether to return the slug, name and homepage of the parent theme.
 *                                        Default false.
 *         @type bool $versions           Whether to return the list of all available versions.
 *                                        Default false.
 *         @type bool $theme_url          Whether to return theme's URL.
 *                                        Default false.
 *         @type bool $extended_author    Whether to return nicename or nicename and display name.
 *                                        Default false.
 *     }
 * }
 * @return object|array|WP_Error Response object or array on success, WP_Error on failure.
 *                               See the {@link https://developer.wordpress.org/reference/functions/themes_api/ function reference article} for more information on the make-up of possible return objects depending on the value of `$action`.
 */
function themes_api( $action, $args = array() )
{
	if ( is_array( $args ) ) {
		$args = ( object ) $args;
	}

	if ( ! isset( $args->per_page ) ) {
		$args->per_page = 24;
	}

	if ( ! isset( $args->locale ) ) {
		$args->locale = get_user_locale();
	}

	/**
	 * Filters arguments used to query for installer pages from the WordPress.org Themes API.
	 *
	 * Important: An object MUST be returned to this filter.
	 *
	 * @since 2.8.0
	 *
	 * @param object $args   Arguments used to query for installer pages from the WordPress.org Themes API.
	 * @param object $action Requested action.
	 *                       Likely values are 'theme_information', 'feature_list', or 'query_themes'.
	 */
	$args = apply_filters( 'themes_api_args', $args, $action );

	/**
	 * Filters whether to override the WordPress.org Themes API.
	 *
	 * Passing a non-false value will effectively short-circuit the WordPress.org API request.
	 *
	 * If `$action` is 'query_themes', 'theme_information', or 'feature_list', an object MUST be passed.
	 * If `$action` is 'hot_tags', an array should be passed.
	 *
	 * @since 2.8.0
	 *
	 * @param false|object|array $override Whether to override the WordPress.org Themes API.
	 *                                     Default false.
	 * @param string             $action   Requested action.
	 *                                     Likely values are 'theme_information', 'feature_list', or 'query_themes'.
	 * @param object             $args     Arguments used to query for installer pages from the Themes API.
	 */
	$res = apply_filters( 'themes_api', FALSE, $action, $args );

	if ( ! $res ) {
		// Include an unmodified $wp_version
		include( ABSPATH . WPINC . '/version.php' );

		$url = $http_url = 'http://api.wordpress.org/themes/info/1.0/';

		if ( $ssl = wp_http_supports( array( 'ssl' ) ) ) {
			$url = set_url_scheme( $url, 'https' );
		}

		$http_args = array(
			'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url( '/' ),
			'body'       => array(
				'action'  => $action,
				'request' => serialize( $args )
			)
		);
		$request = wp_remote_post( $url, $http_args );
/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/post.php: wp_check_post_hierarchy_for_loops( int $post_parent, int $post_ID )
 * <-......: wp-includes/post.php: wp_insert_post( array $postarr [, bool $wp_error = FALSE] )
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::get_page_templates( [WP_Post|null $post = NULL [, string $post_type = 'page']] )
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::get_post_templates()
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::translate_header( string $header, string $value )
 * <-......: wp-admin/includes/theme.php: get_theme_feature_list( [bool $api = TRUE] )
 * @NOW 011: wp-admin/includes/theme.php: themes_api( string $action [, array|object $args = array()] )
 * ......->: wp-includes/class-http.php: WP_Http::post( string $url [, string|array $args = array()] )
 */
	}
}
