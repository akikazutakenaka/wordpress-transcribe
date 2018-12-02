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
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/post.php: wp_check_post_hierarchy_for_loops( int $post_parent, int $post_ID )
 * <-......: wp-includes/post.php: wp_insert_post( array $postarr [, bool $wp_error = FALSE] )
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::get_page_templates( [WP_Post|null $post = NULL [, string $post_type = 'page']] )
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::get_post_templates()
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::translate_header( string $header, string $value )
 * <-......: wp-admin/includes/theme.php: get_theme_feature_list( [bool $api = TRUE] )
 * <-......: wp-admin/includes/theme.php: themes_api( string $action [, array|object $args = array()] )
 * <-......: wp-includes/class-http.php: WP_Http::request( string $url [, string|array $args = array()] )
 * <-......: wp-includes/class-requests.php: Requests::parse_response( string $headers, string $url, array $req_headers, array $req_data, array $options )
 * @NOW 014: wp-includes/Requests/Response.php: Requests_Response::__construct()
 */
}
