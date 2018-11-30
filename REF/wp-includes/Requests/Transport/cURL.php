<?php
/**
 * cURL HTTP transport
 *
 * @package    Requests
 * @subpackage Transport
 */

/**
 * cURL HTTP transport.
 *
 * @package    Requests
 * @subpackage Transport
 */
class Requests_Transport_cURL implements Requests_Transport
{
	const CURL_7_10_5 = 0x070A05;
	const CURL_7_16_2 = 0x071002;

	/**
	 * Raw HTTP data.
	 *
	 * @var string
	 */
	public $headers = '';

	/**
	 * Raw body data.
	 *
	 * @var string
	 */
	public $response_data = '';

	/**
	 * Information on the current request.
	 *
	 * @var array cURL information array, see {@see https://secure.php.net/curl_getinfo}
	 */
	public $info;

	/**
	 * Version string.
	 *
	 * @var long
	 */
	public $version;

	/**
	 * cURL handle.
	 *
	 * @var resource
	 */
	protected $handle;

	/**
	 * Hook dispatcher instance.
	 *
	 * @var Requests_Hooks
	 */
	protected $hooks;

	/**
	 * Have we finished the headers yet?
	 *
	 * @var bool
	 */
	protected $done_headers = FALSE;

	/**
	 * If streaming to a file, keep the file pointer.
	 *
	 * @var resource
	 */
	protected $stream_handle;

	/**
	 * How many bytes are in the response body?
	 *
	 * @var int
	 */
	protected $response_bytes;

	/**
	 * What's the maximum number of bytes we should keep?
	 *
	 * @var int|bool Byte count, or false if no limit.
	 */
	protected $response_byte_limit;

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
 * <-......: wp-includes/class-requests.php: Requests::request( string $url [, array $headers = array() [, array|null $data = array() [, string $type = self::GET [, array $options = array()]]]] )
 * <-......: wp-includes/class-requests.php: Requests::get_transport( [array $capabilities = array()] )
 * @NOW 015: wp-includes/Requests/Transport/cURL.php: Requests_Transport_cURL
 * ......->: wp-includes/Requests/Transport.php: Requests_Transport
 */
}
