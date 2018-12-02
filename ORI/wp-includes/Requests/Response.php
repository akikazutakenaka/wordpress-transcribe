<?php
/**
 * HTTP response class
 *
 * Contains a response from Requests::request()
 * @package Requests
 */

/**
 * HTTP response class
 *
 * Contains a response from Requests::request()
 * @package Requests
 */
class Requests_Response {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->headers = new Requests_Response_Headers();
		$this->cookies = new Requests_Cookie_Jar();
	}

	// refactored. public $body = '';
	// :
	// refactored. public $cookies = array();

	/**
	 * Is the response a redirect?
	 *
	 * @return boolean True if redirect (3xx status), false if not.
	 */
	public function is_redirect() {
		$code = $this->status_code;
		return in_array($code, array(300, 301, 302, 303, 307)) || $code > 307 && $code < 400;
	}

	/**
	 * Throws an exception if the request was not successful
	 *
	 * @throws Requests_Exception If `$allow_redirects` is false, and code is 3xx (`response.no_redirects`)
	 * @throws Requests_Exception_HTTP On non-successful status code. Exception class corresponds to code (e.g. {@see Requests_Exception_HTTP_404})
	 * @param boolean $allow_redirects Set to false to throw on a 3xx as well
	 */
	public function throw_for_status($allow_redirects = true) {
		if ($this->is_redirect()) {
			if (!$allow_redirects) {
				throw new Requests_Exception('Redirection not allowed', 'response.no_redirects', $this);
			}
		}
		elseif (!$this->success) {
			$exception = Requests_Exception_HTTP::get_class($this->status_code);
			throw new $exception(null, $this);
		}
	}
}
