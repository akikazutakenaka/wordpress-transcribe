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
		$priority_existed = isset( $this->callbacks[$priority] );
		$this->callbacks[$priority][$idx] = [
			'function'      => $function_to_add,
			'accepted_args' => $accepted_args
		];

		// If we're adding a new priority to the list, put them in sorted order
		if ( ! $priority_existed && count( $this->callbacks ) > 1 )
			ksort( $this->callbacks, SORT_NUMERIC );

		if ( $this->nesting_level > 0 )
			$this->resort_active_iterations( $priority, $priority_existed );
	}

	/**
	 * Handles reseting callback priority keys mid-iteration.
	 *
	 * @since  4.7.0
	 *
	 * @param bool|int $new_priority     Optional.
	 *                                   The priority of the new filter being added.
	 *                                   Default false, for no priority being added.
	 * @param bool     $priority_existed Optional.
	 *                                   Flag for whether the priority already existed before the new filter was added.
	 *                                   Default false.
	 */
	private function resort_active_iterations( $new_priority = FALSE, $priority_existed = FALSE )
	{
		$new_priorities = array_keys( $this->callbacks );

		// If there are no remaining hooks, clear out all running iterations.
		if ( ! $new_priorities ) {
			foreach ( $this->iterations as $index => $iteration )
				$this->iterations[$index] = $new_priorities;

			return;
		}

		$min = min( $new_priorities );

		foreach ( $this->iterations as $index => &$iteration ) {
			$current = current( $iteration );

			// If we're already at the end of this iteration, just leave the array pointer where it is.
			if ( FALSE === $current )
				continue;

			$iteration = $new_priorities;

			if ( $current < $min ) {
				array_unshift( $iteration, $current );
				continue;
			}

			while ( current( $iteration ) < $current )
				if ( FALSE === next( $iteration ) )
					break;

			// If we have a new priority that didn't exist, but ::apply_filters() or ::do_action() thinks it's the current priority...
			if ( $new_priority === $this->current_priority[$index] && ! $priority_existed ) {
				// ...and the new priority is the same as what $this->iterations thinks is the previous priority, we need to move back to it.
				$prev = ( FALSE === current( $iteration ) )
					? end( $iteration ) // If we've already moved off the end of the array, go back to the last element.
					: prev( $iteration ); // Otherwise, just go back to the previous element.

				if ( FALSE === $prev )
					// Start of the array.
					// Reset, and go about our day.
					reset( $iteration );
				elseif ( $new_priority !== $prev )
					// Previous wasn't the same.
					// Move forward again.
					next( $iteration );
			}
		}

		unset( $iteration );
	}

	// @NOW 008

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

			$normalized[$tag] = $hook;
		}

		return $normalized;
	}
}
