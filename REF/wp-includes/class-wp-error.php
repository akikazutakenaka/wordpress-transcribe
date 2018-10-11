<?php
/**
 * WordPress Error API.
 *
 * Contains the WP_Error class and the is_wp_error() function.
 *
 * @package WordPress
 */

/**
 * WordPress Error class.
 *
 * Container for checking for WordPress errors and error messages.
 * Return WP_Error and use is_wp_error() to check if this class is returned.
 * Many core WordPress functions pass this class in the event of an error and if not handled properly will result in code errors.
 *
 * @since 2.1.0
 */
class WP_Error
{
	/**
	 * Stores the list of errors.
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	public $errors = [];

	/**
	 * Stores the list of data for error codes.
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	public $error_data = [];

	/**
	 * Initialize the error.
	 *
	 * If `$code` is empty, the other parameters will be ignored.
	 * When `$code` is not empty, `$message` will be used even if it is empty.
	 * The `$data` parameter will be used only if it is not empty.
	 *
	 * Though the class is constructed with a single error code and message, multiple codes can be added using the `add()` method.
	 *
	 * @since 2.1.0
	 *
	 * @param string|int $code    Error code.
	 * @param string     $message Error message.
	 * @param mixed      $data    Optional.
	 *                            Error data.
	 */
	public function __construct( $code = '', $message = '', $data = '' )
	{
		if ( empty( $code ) ) {
			return;
		}

		$this->errors[$code][] = $message;

		if ( ! empty( $data ) ) {
			$this->error_data[ $code ] = $data;
		}
	}
}
