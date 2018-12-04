<?php
/**
 * Dependencies API: WP_Dependencies base class
 *
 * @since 2.6.0
 *
 * @package WordPress
 * @subpackage Dependencies
 */

/**
 * Core base class extended to register items.
 *
 * @since 2.6.0
 *
 * @see _WP_Dependency
 */
class WP_Dependencies {
	// refactored. public $registered = array();
	// :
	// refactored. public $group = 0;

	/**
	 * Processes the items and dependencies.
	 *
	 * Processes the items passed to it or the queue, and their dependencies.
	 *
	 * @since 2.6.0
	 * @since 2.8.0 Added the `$group` parameter.
	 *
	 * @param mixed $handles Optional. Items to be processed: Process queue (false), process item (string), process items (array of strings).
	 * @param mixed $group   Group level: level (int), no groups (false).
	 * @return array Handles of items that have been processed.
	 */
	public function do_items( $handles = false, $group = false ) {
		/*
		 * If nothing is passed, print the queue. If a string is passed,
		 * print that item. If an array is passed, print those items.
		 */
		$handles = false === $handles ? $this->queue : (array) $handles;
		$this->all_deps( $handles );

		foreach ( $this->to_do as $key => $handle ) {
			if ( !in_array($handle, $this->done, true) && isset($this->registered[$handle]) ) {
				/*
				 * Attempt to process the item. If successful,
				 * add the handle to the done array.
				 *
				 * Unset the item from the to_do array.
				 */
				if ( $this->do_item( $handle, $group ) )
					$this->done[] = $handle;

				unset( $this->to_do[$key] );
			}
		}

		return $this->done;
	}

	/**
	 * Processes a dependency.
	 *
	 * @since 2.6.0
	 *
	 * @param string $handle Name of the item. Should be unique.
	 * @return bool True on success, false if not set.
	 */
	public function do_item( $handle ) {
		return isset($this->registered[$handle]);
	}

	/**
	 * Determines dependencies.
	 *
	 * Recursively builds an array of items to process taking
	 * dependencies into account. Does NOT catch infinite loops.
	 *
	 * @since 2.1.0
	 * @since 2.6.0 Moved from `WP_Scripts`.
	 * @since 2.8.0 Added the `$group` parameter.
	 *
	 * @param mixed     $handles   Item handle and argument (string) or item handles and arguments (array of strings).
	 * @param bool      $recursion Internal flag that function is calling itself.
	 * @param int|false $group     Group level: (int) level, (false) no groups.
	 * @return bool True on success, false on failure.
	 */
	public function all_deps( $handles, $recursion = false, $group = false ) {
		if ( !$handles = (array) $handles )
			return false;

		foreach ( $handles as $handle ) {
			$handle_parts = explode('?', $handle);
			$handle = $handle_parts[0];
			$queued = in_array($handle, $this->to_do, true);

			if ( in_array($handle, $this->done, true) ) // Already done
				continue;

			$moved     = $this->set_group( $handle, $recursion, $group );
			$new_group = $this->groups[ $handle ];

			if ( $queued && !$moved ) // already queued and in the right group
				continue;

			$keep_going = true;
			if ( !isset($this->registered[$handle]) )
				$keep_going = false; // Item doesn't exist.
			elseif ( $this->registered[$handle]->deps && array_diff($this->registered[$handle]->deps, array_keys($this->registered)) )
				$keep_going = false; // Item requires dependencies that don't exist.
			elseif ( $this->registered[$handle]->deps && !$this->all_deps( $this->registered[$handle]->deps, true, $new_group ) )
				$keep_going = false; // Item requires dependencies that don't exist.

			if ( ! $keep_going ) { // Either item or its dependencies don't exist.
				if ( $recursion )
					return false; // Abort this branch.
				else
					continue; // We're at the top level. Move on to the next one.
			}

			if ( $queued ) // Already grabbed it and its dependencies.
				continue;

			if ( isset($handle_parts[1]) )
				$this->args[$handle] = $handle_parts[1];

			$this->to_do[] = $handle;
		}

		return true;
	}

	// refactored. public function add( $handle, $src, $deps = array(), $ver = false, $args = null ) {}
	// refactored. public function add_data( $handle, $key, $value ) {}

	/**
	 * Get extra item data.
	 *
	 * Gets data associated with a registered item.
	 *
	 * @since 3.3.0
	 *
	 * @param string $handle Name of the item. Should be unique.
	 * @param string $key    The data key.
	 * @return mixed Extra item data (string), false otherwise.
	 */
	public function get_data( $handle, $key ) {
		if ( !isset( $this->registered[$handle] ) )
			return false;

		if ( !isset( $this->registered[$handle]->extra[$key] ) )
			return false;

		return $this->registered[$handle]->extra[$key];
	}

	/**
	 * Un-register an item or items.
	 *
	 * @since 2.1.0
	 * @since 2.6.0 Moved from `WP_Scripts`.
	 *
	 * @param mixed $handles Item handle and argument (string) or item handles and arguments (array of strings).
	 * @return void
	 */
	public function remove( $handles ) {
		foreach ( (array) $handles as $handle )
			unset($this->registered[$handle]);
	}

	// refactored. public function enqueue( $handles ) {}

	/**
	 * Dequeue an item or items.
	 *
	 * Decodes handles and arguments, then dequeues handles
	 * and removes arguments from the class property $args.
	 *
	 * @since 2.1.0
	 * @since 2.6.0 Moved from `WP_Scripts`.
	 *
	 * @param mixed $handles Item handle and argument (string) or item handles and arguments (array of strings).
	 */
	public function dequeue( $handles ) {
		foreach ( (array) $handles as $handle ) {
			$handle = explode('?', $handle);
			$key = array_search($handle[0], $this->queue);
			if ( false !== $key ) {
				unset($this->queue[$key]);
				unset($this->args[$handle[0]]);
			}
		}
	}

	/**
	 * Recursively search the passed dependency tree for $handle
	 *
	 * @since 4.0.0
	 *
	 * @param array  $queue  An array of queued _WP_Dependency handle objects.
	 * @param string $handle Name of the item. Should be unique.
	 * @return bool Whether the handle is found after recursively searching the dependency tree.
	 */
	protected function recurse_deps( $queue, $handle ) {
		foreach ( $queue as $queued ) {
			if ( ! isset( $this->registered[ $queued ] ) ) {
				continue;
			}

			if ( in_array( $handle, $this->registered[ $queued ]->deps ) ) {
				return true;
			} elseif ( $this->recurse_deps( $this->registered[ $queued ]->deps, $handle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Query list for an item.
	 *
	 * @since 2.1.0
	 * @since 2.6.0 Moved from `WP_Scripts`.
	 *
	 * @param string $handle Name of the item. Should be unique.
	 * @param string $list   Property name of list array.
	 * @return bool|_WP_Dependency Found, or object Item data.
	 */
	public function query( $handle, $list = 'registered' ) {
		switch ( $list ) {
			case 'registered' :
			case 'scripts': // back compat
				if ( isset( $this->registered[ $handle ] ) )
					return $this->registered[ $handle ];
				return false;

			case 'enqueued' :
			case 'queue' :
				if ( in_array( $handle, $this->queue ) ) {
					return true;
				}
				return $this->recurse_deps( $this->queue, $handle );

			case 'to_do' :
			case 'to_print': // back compat
				return in_array( $handle, $this->to_do );

			case 'done' :
			case 'printed': // back compat
				return in_array( $handle, $this->done );
		}
		return false;
	}

	/**
	 * Set item group, unless already in a lower group.
	 *
	 * @since 2.8.0
	 *
	 * @param string $handle    Name of the item. Should be unique.
	 * @param bool   $recursion Internal flag that calling function was called recursively.
	 * @param mixed  $group     Group level.
	 * @return bool Not already in the group or a lower group
	 */
	public function set_group( $handle, $recursion, $group ) {
		$group = (int) $group;

		if ( isset( $this->groups[ $handle ] ) && $this->groups[ $handle ] <= $group ) {
			return false;
		}

		$this->groups[ $handle ] = $group;

		return true;
	}

}
