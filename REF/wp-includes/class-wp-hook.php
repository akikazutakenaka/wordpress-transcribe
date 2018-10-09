<?php
/**
 * Plugin API: WP_Hook class
 *
 * @package    WordPress
 * @subpackage Plugin
 * @since      4.7.0
 */

/**
 * Core class used to implement action and filter hook functionality.
 *
 * @since 4.7.0
 * @see   Iterator
 * @see   ArrayAccess
 */
final class WP_Hook implements Iterator, ArrayAccess
{
	/**
	 * Hook callbacks.
	 *
	 * @since 4.7.0
	 * @var   array
	 */
	public $callbacks = [];

	/**
	 * The priority keys of actively running iterations of a hook.
	 *
	 * @since 4.7.0
	 * @var   array
	 */
	private $iterations = [];

	/**
	 * The current priority of actively running iterations of a hook.
	 *
	 * @since 4.7.0
	 * @var   array
	 */
	private $current_priority = [];

	/**
	 * Number of levels this hook can be recursively called.
	 *
	 * @since 4.7.0
	 * @var   int
	 */
	private $nesting_level = 0;

	/**
	 * Flag for if we're current doing an action, rather than a filter.
	 *
	 * @since 4.7.0
	 * @var   bool
	 */
	private $doing_action = FALSE;

	// @NOW 005
}
