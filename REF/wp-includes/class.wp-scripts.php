<?php
/**
 * Dependencies API: WP_Scripts class
 *
 * @since      2.6.0
 * @package    WordPress
 * @subpackage Dependencies
 */

/**
 * Core class used to register scripts.
 *
 * @since 2.1.0
 * @see   WP_Dependencies
 */
class WP_Scripts extends WP_Dependencies
{
	/**
	 * Base URL for scripts.
	 *
	 * Full URL with trailing slash.
	 *
	 * @since 2.6.0
	 * @var   string
	 */
	public $base_url;

	/**
	 * URL of the content directory.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public $content_url;

	/**
	 * Default version string for stylesheets.
	 *
	 * @since 2.6.0
	 *
	 * @var string
	 */
	public $default_version;

	/**
	 * Holds handles of scripts which are enqueued in footer.
	 *
	 * @since 2.8.0
	 *
	 * @var array
	 */
	public $in_footer = array();

	/**
	 * Holds a list of script handles which will be concatenated.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public $concat = '';

	/**
	 * Holds a string which contains script handles and their version.
	 *
	 * @since      2.8.0
	 * @deprecated 3.4.0
	 *
	 * @var string
	 */
	public $concat_version = '';

	/**
	 * Whether to perform concatenation.
	 *
	 * @since 2.8.0
	 *
	 * @var bool
	 */
	public $do_concat = FALSE;

	/**
	 * Holds HTML markup of scripts and additional data if concatenation is enabled.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public $print_html = '';

	/**
	 * Holds inline code if concatenation is enabled.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public $print_code = '';

	/**
	 * Holds a list of script handles which are not in the default directory if concatenation is enabled.
	 *
	 * Unused in core.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public $ext_handles = '';

	/**
	 * Holds a string which contains handles and versions of scripts which are not in the default directory if concatenation is enabled.
	 *
	 * Unused in core.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public $ext_version = '';

	/**
	 * List of default directories.
	 *
	 * @since 2.8.0
	 *
	 * @var array
	 */
	public $default_dirs;

/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/post-template.php: prepend_attachment( string $content )
 * <-......: wp-includes/media.php: wp_video_shortcode( array $attr [, string $content = ''] )
 * <-......: wp-includes/functions.wp-scripts.php: wp_enqueue_script( string $handle [, string $src = '' [, array $deps = array() [, string|bool|null $ver = FALSE [, bool $in_footer = FALSE]]]] )
 * <-......: wp-includes/functions.wp-scripts.php: wp_scripts()
 * @NOW 009: wp-includes/class.wp-scripts.php: WP_Scripts::__construct()
 */
}
