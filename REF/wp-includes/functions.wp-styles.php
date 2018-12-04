<?php
/**
 * Dependencies API: Styles functions
 *
 * @since      2.6.0
 * @package    WordPress
 * @subpackage Dependencies
 */

/**
 * Initialize $wp_styles if it has not been set.
 *
 * @global WP_Styles $wp_styles
 * @since  4.2.0
 *
 * @return WP_Styles WP_Styles instance.
 */
function wp_styles()
{
	global $wp_styles;

	if ( ! ( $wp_styles instanceof WP_Styles ) ) {
		$wp_styles = new WP_Styles();
	}

	return $wp_styles;
}

/**
 * Enqueue a CSS stylesheet.
 *
 * Registers the style if source provided (does NOT overwrite) and enqueues.
 *
 * @see   WP_Dependencies::add()
 * @see   WP_Dependencies::enqueue()
 * @link  https://www.w3.org/TR/CSS2/media.html#media-types List of CSS media types.
 * @since 2.6.0
 *
 * @param string           $handle Name of the stylesheet.
 *                                 Should be unique.
 * @param string           $src    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
 *                                 Default empty.
 * @param array            $deps   Optional.
 *                                 An array of registered stylesheet handles this stylesheet depends on.
 *                                 Default empty array.
 * @param string|bool|null $ver    Optional.
 *                                 String specifying stylesheet version number, if it has one, which is added to the URL as a query string for cache busting purposes.
 *                                 If version is set to false, a version number is automatically added equal to current installed WordPress version.
 *                                 If set to null, no version is added.
 * @param string           $media  Optional.
 *                                 The media for which this stylesheet has been defined.
 *                                 Default 'all'.
 *                                 Accepts media types like 'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
 */
function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = FALSE, $media = 'all' )
{
	_wp_scripts_maybe_doing_it_wrong( __FUNCTION__ );
	$wp_styles = wp_styles();
/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/post-template.php: prepend_attachment( string $content )
 * <-......: wp-includes/media.php: wp_video_shortcode( array $attr [, string $content = ''] )
 * @NOW 007: wp-includes/functions.wp-styles.php: wp_enqueue_style( string $handle [, string $src = '' [, array $deps = array() [, string|bool|null $ver = FALSE [, string $media = 'all']]]] )
 */
}
