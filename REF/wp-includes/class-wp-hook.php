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

	/**
	 * Hooks a function or method to a specific filter action.
	 *
	 * @since 4.7.0
	 *
	 * @param string   $tag             The name of the filter to hook the $function_to_add callback to.
	 * @param callable $function_to_add The callback to be run when the filter is applied.
	 * @param int      $priority        The order in which the functions associated with a particular action are executed.
	 *                                  Lower numbers correspond with earlier execution, and functions with the same priority are executed in the order in which they were added to the action.
	 * @param int      $accepted_args   The number of arguments the function accepts.
	 */
	public function add_filter( $tag, $function_to_add, $priority, $accepted_args )
	{
		$idx = _wp_filter_build_unique_id( $tag, $function_to_add, $priority );
		// @NOW 006
	}

	/**
	 * Normalizes filters set up before WordPress has initialized to WP_Hook objects.
	 *
	 * @since  4.7.0
	 * @static
	 *
	 * @param  array     $filters Filters to normalize.
	 * @return WP_Hook[] Array of normalized filters.
	 */
	public static function build_preinitialized_hooks( $filters )
	{
		/**
		 * @var WP_Hook[] $normalized
		 */
		$normalized = [];

		foreach ( $filters as $tag => $callback_groups ) {
			if ( is_object( $callback_groups ) && $callback_groups instanceof WP_Hook ) {
				$normalized[$tag] = $callback_groups;
				continue;
			}

			$hook = new WP_Hook();

			// Loop through callback groups.
			foreach ( $callback_groups as $priority => $callbacks )
				// Loop through callbacks.
				foreach ( $callbacks as $cb )
					$hook->add_filter( $tag, $cb['function'], $priority, $cb['accepted_args'] );
					// @NOW 005 -> wp-includes/class-wp-hook.php
		}
	}
}
