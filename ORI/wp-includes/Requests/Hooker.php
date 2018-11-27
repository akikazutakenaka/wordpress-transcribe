<?php
/**
 * Event dispatcher
 *
 * @package Requests
 * @subpackage Utilities
 */

/**
 * Event dispatcher
 *
 * @package Requests
 * @subpackage Utilities
 */
interface Requests_Hooker {
	// refactored. public function register($hook, $callback, $priority = 0);

	/**
	 * Dispatch a message
	 *
	 * @param string $hook Hook name
	 * @param array $parameters Parameters to pass to callbacks
	 * @return boolean Successfulness
	 */
	public function dispatch($hook, $parameters = array());
}