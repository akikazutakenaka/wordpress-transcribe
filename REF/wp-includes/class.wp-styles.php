<?php
/**
 * Dependencies API: WP_Styles class
 *
 * @since      2.6.0
 * @package    WordPress
 * @subpackage Dependencies
 */

/**
 * Core class used to register styles.
 *
 * @since 2.6.0
 * @see   WP_Dependencies
 */
class WP_Styles extends WP_Dependencies
{
	/**
	 * Base URL for styles.
	 *
	 * Full URL with trailing slash.
	 *
	 * @since 2.6.0
	 *
	 * @var string
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
	 * The current text direction.
	 *
	 * @since 2.6.0
	 *
	 * @var string
	 */
	public $text_direction = 'ltr';

	/**
	 * Holds a list of style handles which will be concatenated.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public $concat = '';

	/**
	 * Holds a string which contains style handles and their version.
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
	 * Holds HTML markup of styles and additional data if concatenation is enabled.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public $print_html = '';

	/**
	 * Holds inline styles if concatenation is enabled.
	 *
	 * @since 3.3.0
	 *
	 * @var string
	 */
	public $print_code = '';

	/**
	 * List of default directories.
	 *
	 * @since 2.8.0
	 *
	 * @var array
	 */
	public $default_dirs;

	/**
	 * Constructor.
	 *
	 * @since 2.6.0
	 */
	public function __construct()
	{
		/**
		 * Fires when the WP_Styles instance is initialized.
		 *
		 * @since 2.6.0
		 *
		 * @param WP_Styles $this WP_Styles instance (passed by reference).
		 */
		do_action_ref_array( 'wp_default_styles', array( &$this ) );
	}
}
