<?php
/**
 * Exception for HTTP requests.
 *
 * @package Requests
 */

/**
 * Exception for HTTP requests.
 *
 * @package Requests
 */
class Requests_Exception extends Exception
{
	/**
	 * Type of exception.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Data associated with the exception.
	 *
	 * @var mixed
	 */
	protected $data;

	/**
	 * Create a new exception.
	 *
	 * @param string $message Exception message.
	 * @param string $type    Exception type.
	 * @param mixed  $data    Associated data.
	 * @param int    $code    Exception numerical code, if applicable.
	 */
	public function __construct( $message, $type, $data = NULL, $code = 0 )
	{
		parent::__construct( $message, $code );
		$this->type = $type;
		$this->data = $data;
	}
}
