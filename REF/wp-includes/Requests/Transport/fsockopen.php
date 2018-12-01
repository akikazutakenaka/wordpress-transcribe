<?php
/**
 * fsockopen HTTP transport
 *
 * @package    Requests
 * @subpackage Transport
 */

/**
 * fsockopen HTTP transport.
 *
 * @package    Requests
 * @subpackage Transport
 */
class Requests_Transport_fsockopen implements Requests_Transport
{
	/**
	 * Second to microsecond conversion.
	 *
	 * @var int
	 */
	const SECOND_IN_MICROSECONDS = 1000000;

	/**
	 * Raw HTTP data.
	 *
	 * @var string
	 */
	public $headers = '';

	/**
	 * Stream metadata.
	 *
	 * @var array Associative array of properties, see {@see https://secure.php.net/stream_get_meta_data}
	 */
	public $info;

	/**
	 * What's the maximum number of bytes we should keep?
	 *
	 * @var int|bool Byte count, or false if no limit.
	 */
	protected $max_bytes = FALSE;

	protected $connect_error = '';

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
 * @NOW 014: wp-includes/Requests/Transport/fsockopen.php: Requests_Transport_fsockopen::request( string $url [, array $headers = array() [, string|array $data = array() [, array $options = array()]]] )
 */

	/**
	 * Whether this transport is valid.
	 *
	 * @return bool True if the transport is valid, false otherwise.
	 */
	public static function test( $capabilities = array() )
	{
		if ( ! function_exists( 'fsockopen' ) ) {
			return FALSE;
		}

		// If needed, check that streams support SSL.
		if ( isset( $capabilities['ssl'] ) && $capabilities['ssl'] ) {
			if ( ! extension_loaded( 'openssl' ) || ! function_exists( 'openssl_x509_parse' ) ) {
				return FALSE;
			}

			// Currently broken, thanks to https://github.com/facebook/hhvm/issues/2156
			if ( defined( 'HHVM_VERSION' ) ) {
				return FALSE;
			}
		}

		return TRUE;
	}
}
