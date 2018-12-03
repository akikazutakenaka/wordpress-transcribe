<?php
/**
 * HTTP API: WP_HTTP_Requests_Response class
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      4.6.0
 */

/**
 * Core wrapper object for a Requests_Response for standardisation.
 *
 * @since 4.6.0
 * @see   WP_HTTP_Response
 */
class WP_HTTP_Requests_Response extends WP_HTTP_Response
{
	/**
	 * Requests Response object.
	 *
	 * @since 4.6.0
	 *
	 * @var Requests_Response
	 */
	protected $response;

	/**
	 * Filename the response was saved to.
	 *
	 * @since 4.6.0
	 *
	 * @var string|null
	 */
	protected $filename;

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param Requests_Response $response HTTP response.
	 * @param string            $filename Optional.
	 *                                    File name.
	 *                                    Default empty.
	 */
	public function __construct( Requests_Response $response, $filename = '' )
	{
		$this->response = $response;
		$this->filename = $filename;
	}
}
