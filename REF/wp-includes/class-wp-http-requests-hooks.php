<?php
/**
 * HTTP API: Requests hook bridge class
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      4.7.0
 */

/**
 * Bridge to connect Requests internal hooks to WordPress actions.
 *
 * @since 4.7.0
 * @see   Requests_Hooks
 */
class WP_HTTP_Requests_Hooks extends Requests_Hooks
{
	/**
	 * Requested URL.
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * WordPress WP_HTTP request data.
	 *
	 * @var array Request data in WP_Http format.
	 */
	protected $request = array();

	/**
	 * Constructor.
	 *
	 * @param string $url     URL to request.
	 * @param array  $request Request data in WP_Http format.
	 */
	public function __construct( $url, $request )
	{
		$this->url = $url;
		$this->request = $request;
	}
}
