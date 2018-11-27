<?php
/**
 * Handles adding and dispatching events
 *
 * @package Requests
 * @subpackage Utilities
 */

/**
 * Handles adding and dispatching events
 *
 * @package Requests
 * @subpackage Utilities
 */
class Requests_Hooks implements Requests_Hooker {
	// refactored. protected $hooks = array();
	// :
	// refactored. public function register($hook, $callback, $priority = 0) {}

	/**
	 * Dispatch a message
	 *
	 * @param string $hook Hook name
	 * @param array $parameters Parameters to pass to callbacks
	 * @return boolean Successfulness
	 */
	public function dispatch($hook, $parameters = array()) {
		if (empty($this->hooks[$hook])) {
			return false;
		}

		foreach ($this->hooks[$hook] as $priority => $hooked) {
			foreach ($hooked as $callback) {
				call_user_func_array($callback, $parameters);
			}
		}

		return true;
	}
}