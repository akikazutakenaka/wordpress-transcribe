<?php
/**
 * Plugin API: WP_Hook class
 *
 * @package WordPress
 * @subpackage Plugin
 * @since 4.7.0
 */

/**
 * Core class used to implement action and filter hook functionality.
 *
 * @since 4.7.0
 *
 * @see Iterator
 * @see ArrayAccess
 */
final class WP_Hook implements Iterator, ArrayAccess {
	// refactored. public $callbacks = array();
	// :
	// refactored. public function has_filters() {}

	/**
	 * Removes all callbacks from the current filter.
	 *
	 * @since 4.7.0
	 *
	 * @param int|bool $priority Optional. The priority number to remove. Default false.
	 */
	public function remove_all_filters( $priority = false ) {
		if ( ! $this->callbacks ) {
			return;
		}

		if ( false === $priority ) {
			$this->callbacks = array();
		} else if ( isset( $this->callbacks[ $priority ] ) ) {
			unset( $this->callbacks[ $priority ] );
		}

		if ( $this->nesting_level > 0 ) {
			$this->resort_active_iterations();
		}
	}

	// refactored. public function apply_filters( $value, $args ) {}
	// :
	// refactored. public function do_all_hook( &$args ) {}

	/**
	 * Return the current priority level of the currently running iteration of the hook.
	 *
	 * @since 4.7.0
	 *
	 * @return int|false If the hook is running, return the current priority level. If it isn't running, return false.
	 */
	public function current_priority() {
		if ( false === current( $this->iterations ) ) {
			return false;
		}

		return current( current( $this->iterations ) );
	}

	// refactored. public static function build_preinitialized_hooks( $filters ) {}

	/**
	 * Determines whether an offset value exists.
	 *
	 * @since 4.7.0
	 *
	 * @link https://secure.php.net/manual/en/arrayaccess.offsetexists.php
	 *
	 * @param mixed $offset An offset to check for.
	 * @return bool True if the offset exists, false otherwise.
	 */
	public function offsetExists( $offset ) {
		return isset( $this->callbacks[ $offset ] );
	}

	/**
	 * Retrieves a value at a specified offset.
	 *
	 * @since 4.7.0
	 *
	 * @link https://secure.php.net/manual/en/arrayaccess.offsetget.php
	 *
	 * @param mixed $offset The offset to retrieve.
	 * @return mixed If set, the value at the specified offset, null otherwise.
	 */
	public function offsetGet( $offset ) {
		return isset( $this->callbacks[ $offset ] ) ? $this->callbacks[ $offset ] : null;
	}

	/**
	 * Sets a value at a specified offset.
	 *
	 * @since 4.7.0
	 *
	 * @link https://secure.php.net/manual/en/arrayaccess.offsetset.php
	 *
	 * @param mixed $offset The offset to assign the value to.
	 * @param mixed $value The value to set.
	 */
	public function offsetSet( $offset, $value ) {
		if ( is_null( $offset ) ) {
			$this->callbacks[] = $value;
		} else {
			$this->callbacks[ $offset ] = $value;
		}
	}

	/**
	 * Unsets a specified offset.
	 *
	 * @since 4.7.0
	 *
	 * @link https://secure.php.net/manual/en/arrayaccess.offsetunset.php
	 *
	 * @param mixed $offset The offset to unset.
	 */
	public function offsetUnset( $offset ) {
		unset( $this->callbacks[ $offset ] );
	}

	/**
	 * Returns the current element.
	 *
	 * @since 4.7.0
	 *
	 * @link https://secure.php.net/manual/en/iterator.current.php
	 *
	 * @return array Of callbacks at current priority.
	 */
	public function current() {
		return current( $this->callbacks );
	}

	/**
	 * Moves forward to the next element.
	 *
	 * @since 4.7.0
	 *
	 * @link https://secure.php.net/manual/en/iterator.next.php
	 *
	 * @return array Of callbacks at next priority.
	 */
	public function next() {
		return next( $this->callbacks );
	}

	/**
	 * Returns the key of the current element.
	 *
	 * @since 4.7.0
	 *
	 * @link https://secure.php.net/manual/en/iterator.key.php
	 *
	 * @return mixed Returns current priority on success, or NULL on failure
	 */
	public function key() {
		return key( $this->callbacks );
	}

	/**
	 * Checks if current position is valid.
	 *
	 * @since 4.7.0
	 *
	 * @link https://secure.php.net/manual/en/iterator.valid.php
	 *
	 * @return boolean
	 */
	public function valid() {
		return key( $this->callbacks ) !== null;
	}

	/**
	 * Rewinds the Iterator to the first element.
	 *
	 * @since 4.7.0
	 *
	 * @link https://secure.php.net/manual/en/iterator.rewind.php
	 */
	public function rewind() {
		reset( $this->callbacks );
	}

}
