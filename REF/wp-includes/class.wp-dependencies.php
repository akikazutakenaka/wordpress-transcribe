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
}
