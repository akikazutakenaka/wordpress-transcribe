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
	 * Setup dependencies.
	 *
	 * @since 2.6.0
	 */
	public function __construct()
	{
		@ list( $this->handle, $this->src, $this->deps, $this->ver, $this->args ) = func_get_args();

		if ( ! is_array( $this->deps ) ) {
			$this->deps = array();
		}
	}
}
