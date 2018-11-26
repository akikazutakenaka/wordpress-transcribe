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
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/class-wp-theme.php
 * <- wp-includes/class-wp-theme.php
 * <- wp-includes/class-wp-theme.php
 * @NOW 010: wp-admin/includes/theme.php
 * -> wp-admin/includes/theme.php
 */
	}
}

/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/class-wp-theme.php
 * <- wp-includes/class-wp-theme.php
 * <- wp-includes/class-wp-theme.php
 * <- wp-admin/includes/theme.php
 * @NOW 011: wp-admin/includes/theme.php
 */
