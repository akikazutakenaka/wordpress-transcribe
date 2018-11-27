<?php
/**
 * Handles adding and dispatching events.
 *
 * @package    Requests
 * @subpackage Utilities
 */

/**
 * Handles adding and dispatching events.
 *
 * @package    Requests
 * @subpackage Utilities
 */
class Requests_Hooks implements Requests_Hooker
{
	/**
	 * Registered callbacks for each hook.
	 *
	 * @var array
	 */
	protected $hooks = array();

	/**
	 * Constructor.
	 */
	public function __construct()
	{}

	/**
	 * Register a callback for a hook.
	 *
	 * @param string   $hook     Hook name.
	 * @param callback $callback Function/method to call on event.
	 * @param int      $priority Priority number.
	 *                           <0 is executed earlier, >0 is executed later.
	 */
	public function register( $hook, $callback, $priority = 0 )
	{
		if ( ! isset( $this->hooks[ $hook ] ) ) {
			$this->hooks[ $hook ] = array();
		}

		if ( ! isset( $this->hooks[ $hook ][ $priority ] ) ) {
			$this->hooks[ $hook ][ $priority ] = array();
		}

		$this->hooks[ $hook ][ $priority ][] = $callback;
	}
}