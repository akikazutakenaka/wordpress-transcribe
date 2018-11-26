<?php
/**
 * Requests for PHP
 *
 * Inspired by Requests for Python.
 *
 * Based on concepts from SimplePie_File, RequestCore and WP_Http.
 *
 * @package Requests
 */

/**
 * Requests for PHP
 *
 * Inspired by Requests for Python.
 *
 * Based on concepts from SimplePie_File, RequestCore and WP_Http.
 *
 * @package Requests
 */
class Requests
{
	/**
	 * POST method.
	 *
	 * @var string
	 */
	const POST = 'POST';

	/**
	 * PUT method.
	 *
	 * @var string
	 */
	const PUT = 'PUT';

	/**
	 * GET method.
	 *
	 * @var string
	 */
	const GET = 'GET';

	/**
	 * HEAD method.
	 *
	 * @var string
	 */
	const HEAD = 'HEAD';

	/**
	 * DELETE method.
	 *
	 * @var string
	 */
	const DELETE = 'DELETE';

	/**
	 * OPTIONS method.
	 *
	 * @var string
	 */
	const OPTIONS = 'OPTIONS';

	/**
	 * TRACE method.
	 *
	 * @var string
	 */
	const TRACE = 'TRACE';

	/**
	 * PATCH method.
	 *
	 * @link https://tools.ietf.org/html/rfc5789
	 *
	 * @var string
	 */
	const PATCH = 'PATCH';

	/**
	 * Default size of buffer size to read streams.
	 *
	 * @var integer
	 */
	const BUFFER_SIZE = 1160;

	/**
	 * Current version of Requests.
	 *
	 * @var string
	 */
	const VERSION = '1.7';

	/**
	 * Registered transport classes.
	 *
	 * @var array
	 */
	protected static $transports = array();

	/**
	 * Selected transport name.
	 *
	 * Use {@see get_transport()} instead.
	 *
	 * @var array
	 */
	public static $transport = array();

	/**
	 * Default certificate path.
	 *
	 * @see Requests::get_certificate_path()
	 * @see Requests::set_certificate_path()
	 *
	 * @var string
	 */
	protected static $certificate_path;

	/**
	 * This is a static class, do not instantiate it.
	 *
	 * @codeCoverageIgnore
	 */
	private function __construct()
	{}

	/**
	 * Register the built-in autoloader.
	 *
	 * @codeCoverageIgnore
	 */
	public static function register_autoloader()
	{
		spl_autoload_register( array( 'Requests', 'autoloader' ) );
	}

	/**
	 * Set default certificate path.
	 *
	 * @param string $path Certificate path, pointing to a PEM file.
	 */
	public static function set_certificate_path( $path )
	{
		Requests::$certificate_path = $path;
	}
}
