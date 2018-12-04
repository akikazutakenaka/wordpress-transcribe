<?php
/**
 * Dependencies API: WP_Dependencies base class
 *
 * @since      2.6.0
 * @package    WordPress
 * @subpackage Dependencies
 */

/**
 * Core base class extended to register items.
 *
 * @since 2.6.0
 * @see   _WP_Dependency
 */
class WP_Dependencies
{
	/**
	 * An array of registered handle objects.
	 *
	 * @since 2.6.8
	 *
	 * @var array
	 */
	public $registered = array();

	/**
	 * An array of queued _WP_Dependency handle objects.
	 *
	 * @since 2.6.8
	 *
	 * @var array
	 */
	public $queue = array();

	/**
	 * An array of _WP_Dependency handle objects to queue.
	 *
	 * @since 2.6.0
	 *
	 * @var array
	 */
	public $to_do = array();

	/**
	 * An array of _WP_Dependency handle objects already queued.
	 *
	 * @since 2.6.0
	 *
	 * @var array
	 */
	public $done = array();

	/**
	 * An array of additional arguments passed when a handle is registered.
	 *
	 * Arguments are appended to the item query string.
	 *
	 * @since 2.6.0
	 *
	 * @var array
	 */
	public $args = array();

	/**
	 * An array of handle groups to enqueue.
	 *
	 * @since 2.8.0
	 *
	 * @var array
	 */
	public $groups = array();

	/**
	 * A handle group to enqueue.
	 *
	 * @since      2.8.0
	 * @deprecated 4.5.0
	 *
	 * @var int
	 */
	public $group = 0;

	/**
	 * Register an item.
	 *
	 * Registers the item if no item of that name already exists.
	 *
	 * @since 2.1.0
	 * @since 2.6.0 Moved from `WP_Scripts`.
	 *
	 * @param  string           $handle Name of the item.
	 *                                  Should be unique.
	 * @param  string           $src    Full URL of the item, or path of the item relative to the WordPress root directory.
	 * @param  array            $deps   Optional.
	 *                                  An array of registered item handles this item depends on.
	 *                                  Default empty array.
	 * @param  string|bool|null $ver    Optional.
	 *                                  String specifying item version number, if it has one, which is added to the URL as a query string for cache busting purposes.
	 *                                  If version is set to false, a version number is automatically added equal to current installed WordPress version.
	 *                                  If set to null, no version is added.
	 * @param  mixed            $args   Optional.
	 *                                  Custom property of the item.
	 *                                  NOT the class property $args.
	 *                                  Examples: $media, $in_footer.
	 * @return bool             Whether the item has been registered.
	 *                          True on success, false on failure.
	 */
	public function add( $handle, $src, $deps = array(), $ver = FALSE, $args = NULL )
	{
		if ( isset( $this->registered[ $handle ] ) ) {
			return FALSE;
		}

		$this->registered[ $handle ] = new _WP_Dependency( $handle, $src, $deps, $ver, $args );
		return TRUE;
	}

	/**
	 * Add extra item data.
	 *
	 * Adds data to a registered item.
	 *
	 * @since 2.6.0
	 *
	 * @param  string $handle Name of the item.
	 *                        Should be unique.
	 * @param  string $key    The data key.
	 * @param  mixed  $value  The data value.
	 * @return bool   True on success, false on failure.
	 */
	public function add_data( $handle, $key, $value )
	{
		return ! isset( $this->registered[ $handle ] )
			? FALSE
			: $this->registered[ $handle ]->add_data( $key, $data );
	}
}
