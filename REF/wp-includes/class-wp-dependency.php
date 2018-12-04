<?php
/**
 * Dependencies API: _WP_Dependency class
 *
 * @since      4.7.0
 * @package    WordPress
 * @subpackage Dependencies
 */

/**
 * Class _WP_Dependency
 *
 * Helper class to register a handle and associated data.
 *
 * @access private
 * @since  2.6.0
 */
class _WP_Dependency
{
	/**
	 * The handle name.
	 *
	 * @since 2.6.0
	 *
	 * @var null
	 */
	public $handle;

	/**
	 * The handle source.
	 *
	 * @since 2.6.0
	 *
	 * @var null
	 */
	public $src;

	/**
	 * An array of handle dependencies.
	 *
	 * @since 2.6.0
	 *
	 * @var array
	 */
	public $deps = array();

	/**
	 * The handle version.
	 *
	 * Used for cache-busting.
	 *
	 * @since 2.6.0
	 *
	 * @var bool|string
	 */
	public $ver = FALSE;

	/**
	 * Additional arguments for the handle.
	 *
	 * @since 2.6.0
	 *
	 * @var null
	 */
	public $args = NULL; // Custom property, such as $in_footer or $media.

	/**
	 * Extra data to supply to the handle.
	 *
	 * @since 2.6.0
	 *
	 * @var array
	 */
	public $extra = array();

/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/post-template.php: prepend_attachment( string $content )
 * <-......: wp-includes/media.php: wp_video_shortcode( array $attr [, string $content = ''] )
 * <-......: wp-includes/functions.wp-scripts.php: wp_enqueue_script( string $handle [, string $src = '' [, array $deps = array() [, string|bool|null $ver = FALSE [, bool $in_footer = FALSE]]]] )
 * <-......: wp-includes/class.wp-dependencies.php: WP_Dependencies::add( string $handle, string $src [, array $deps = array() [, string|bool|null $ver = FALSE [, mixed $args = NULL]]] )
 * @NOW 009: wp-includes/class-wp-dependency.php: _WP_Dependency::__construct()
 */
}
