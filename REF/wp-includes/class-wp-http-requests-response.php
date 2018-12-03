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

	/**
	 * Retrieves headers associated with the response.
	 *
	 * @since 4.6.0
	 *
	 * @see \Requests_Utility_CaseInsensitiveDictionary
	 *
	 * @return \Requests_Utility_CaseInsensitiveDictionary Map of header name to header value.
	 */
	public function get_headers()
	{
		// Ensure headers remain case-insensitive.
		$converted = new Requests_Utility_CaseInsensitiveDictionary();

		foreach ( $this->response->headers->getAll() as $key => $value ) {
			$converted[ $key ] = count( $value ) === 1
				? $value[0]
				: $value;
		}

		return $converted;
	}

	/**
	 * Retrieves the HTTP return code for the response.
	 *
	 * @since 4.6.0
	 *
	 * @return int The 3-digit HTTP status code.
	 */
	public function get_status()
	{
		return $this->response->status_code;
	}

	/**
	 * Retrieves the response data.
	 *
	 * @since 4.6.0
	 *
	 * @return mixed Response data.
	 */
	public function get_data()
	{
		return $this->response->body;
	}

	/**
	 * Retrieves cookies from the response.
	 *
	 * @since 4.6.0
	 *
	 * @return WP_HTTP_Cookie[] List of cookie objects.
	 */
	public function get_cookies()
	{
		$cookies = array();

		foreach ( $this->response->cookies as $cookie ) {
			$cookies[] = new WP_Http_Cookie( array(
					'name'    => $cookie->name,
					'value'   => urldecode( $cookie->value ),
					'expires' => isset( $cookie->attributes['expires'] )
						? $cookie->attributes['expires']
						: NULL,
					'path'    => isset( $cookie->attributes['path'] )
						? $cookie->attributes['path']
						: NULL,
					'domain'  => isset( $cookie->attributes['domain'] )
						? $cookie->attributes['domain']
						: NULL
				) );
		}

		return $cookies;
	}

	/**
	 * Converts the object to a WP_Http response array.
	 *
	 * @since 4.6.0
	 *
	 * @return array WP_Http response array, per WP_Http::request().
	 */
	public function to_array()
	{
		return array(
			'headers'  => $this->get_headers(),
			'body'     => $this->get_data(),
			'response' => array(
				'code'    => $this->get_status(),
				'message' => get_status_header_desc( $this->get_status() )
			),
			'cookies'  => $this->get_cookies(),
			'filename' => $this->filename
		);
	}
}
