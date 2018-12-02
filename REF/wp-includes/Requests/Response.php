<?php
/**
 * HTTP response class
 *
 * Contains a response from Requests::request()
 *
 * @package Requests
 */

/**
 * HTTP response class.
 *
 * Contains a response from Requests::request().
 *
 * @package Requests
 */
class Requests_Response
{
	/**
	 * Response body.
	 *
	 * @var string
	 */
	public $body = '';

	/**
	 * Raw HTTP data from the transport.
	 *
	 * @var string
	 */
	public $raw = '';

	/**
	 * Headers, as an associative array.
	 *
	 * @var Requests_Response_Headers Array-like object representing headers.
	 */
	public $headers = array();

	/**
	 * Status code, false if non-blocking.
	 *
	 * @var int|bool
	 */
	public $status_code = FALSE;

	/**
	 * Protocol version, false if non-blocking.
	 *
	 * @var float|bool
	 */
	public $protocol_version = FALSE;

	/**
	 * Whether the request succeeded or not.
	 *
	 * @var bool
	 */
	public $success = FALSE;

	/**
	 * Number of redirects the request used.
	 *
	 * @var int
	 */
	public $redirects = 0;

	/**
	 * URL requested.
	 *
	 * @var string
	 */
	public $url = '';

	/**
	 * Previous requests (from redirects).
	 *
	 * @var array Array of Requests_Response objects.
	 */
	public $history = array();

	/**
	 * Cookies from the request.
	 *
	 * @var Requests_Cookie_Jar Array-like object representing a cookie jar.
	 */
	public $cookies = array();

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->headers = new Requests_Response_Headers();
		$this->cookies = new Requests_Cookie_Jar();
	}
}
