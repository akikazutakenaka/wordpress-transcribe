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
}
