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
 * Container for checking for WordPress errors and error messages. Return
 * WP_Error and use is_wp_error() to check if this class is returned. Many
 * core WordPress functions pass this class in the event of an error and
 * if not handled properly will result in code errors.
 *
 * @since 2.1.0
 */
class WP_Error {
	// refactored. public $errors = array();
	// :
	// refactored. public function get_error_message($code = '') {}

	/**
	 * Retrieve error data for error code.
	 *
	 * @since 2.1.0
	 *
	 * @param string|int $code Optional. Error code.
	 * @return mixed Error data, if it exists.
	 */
	public function get_error_data($code = '') {
		if ( empty($code) )
			$code = $this->get_error_code();

		if ( isset($this->error_data[$code]) )
			return $this->error_data[$code];
	}

	// refactored. public function add($code, $message, $data = '') {}

	/**
	 * Add data for error code.
	 *
	 * The error code can only contain one error data.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed $data Error data.
	 * @param string|int $code Error code.
	 */
	public function add_data($data, $code = '') {
		if ( empty($code) )
			$code = $this->get_error_code();

		$this->error_data[$code] = $data;
	}

	/**
	 * Removes the specified error.
	 *
	 * This function removes all error messages associated with the specified
	 * error code, along with any error data for that code.
	 *
	 * @since 4.1.0
	 *
	 * @param string|int $code Error code.
	 */
	public function remove( $code ) {
		unset( $this->errors[ $code ] );
		unset( $this->error_data[ $code ] );
	}
}
