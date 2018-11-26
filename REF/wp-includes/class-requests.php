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
 * <-......: wp-includes/http.php: wp_http_supports( [array $capabilities = array() [, string $url = NULL]] )
 * <-......: wp-includes/http.php: _wp_http_get_object()
 * <-......: wp-includes/class-http.php
 * @NOW 015: wp-includes/class-requests.php: Requests::register_autoloader()
 */
}
