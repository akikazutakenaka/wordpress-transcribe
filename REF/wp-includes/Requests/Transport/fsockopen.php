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
	 * Perform a request.
	 *
	 * @throws Requests_Exception On failure to connect to socket (`fsockopenerror`)
	 * @throws Requests_Exception On socket timeout (`timeout`)
	 *
	 * @param  string       $url     URL to request.
	 * @param  array        $headers Associative array of request headers.
	 * @param  string|array $data    Data to send either as the POST body, or as parameters in the URL for a GET/HEAD.
	 * @param  array        $options Request options, see {@see Requests::response()} for documentation.
	 * @return string       Raw HTTP result.
	 */
	public function request( $url, $headers = array(), $data = array(), $options = array() )
	{
		$options['hooks']->dispatch( 'fsockopen.before_request' );
		$url_parts = parse_url( $url );

		if ( empty( $url_parts ) ) {
			throw new Requests_Exception( 'Invalid URL.', 'invalidurl', $url );
		}

		$host = $url_parts['host'];
		$context = stream_context_create();
		$verifyname = FALSE;
		$case_insensitive_headers = new Requests_Utility_CaseInsensitiveDictionary( $headers );

		// HTTPS support.
		if ( isset( $url_parts['scheme'] ) && strtolower( $url_parts['scheme'] ) === 'https' ) {
			$remote_socket = 'ssl://' . $host;

			if ( ! isset( $url_parts['port'] ) ) {
				$url_parts['port'] = 443;
			}

			$context_options = array(
				'verify_peer'       => TRUE,
				'capture_peer_cert' => TRUE
			);
			$verifyname = TRUE;

			// SNI, if enabled (OpenSSL >= 0.9.8j).
			if ( defined( 'OPENSSL_TLSEXT_SERVER_NAME' ) && OPENSSL_TLSEXT_SERVER_NAME ) {
				$context_options['SNI_enabled'] = TRUE;

				if ( isset( $options['verifyname'] ) && $options['verifyname'] === FALSE ) {
					$context_options['SNI_enabled'] = FALSE;
				}
			}

			if ( isset( $options['verify'] ) ) {
				if ( $options['verify'] === FALSE ) {
					$context_options['verify_peer'] = FALSE;
				} elseif ( is_string( $options['verify'] ) ) {
					$context_options['cafile'] = $options['verify'];
				}
			}

			if ( isset( $options['verifyname'] ) && $options['verifyname'] === FALSE ) {
				$context_options['verify_peer_name'] = FALSE;
				$verifyname = FALSE;
			}

			stream_context_set_option( $context, array( 'ssl' => $context_options ) );
		} else {
			$remote_socket = 'tcp://' . $host;
		}

		$this->max_bytes = $options['max_bytes'];

		if ( ! isset( $url_parts['port'] ) ) {
			$url_parts['port'] = 80;
		}

		$remote_socket .= ':' . $url_parts['port'];
		set_error_handler( array( $this, 'connect_error_handler' ), E_WARNING | E_NOTICE );
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
 * ......->: wp-includes/Requests/Transport/fsockopen.php: Requests_Transport_fsockopen::connect_error_handler( int $errno, string $errstr )
 */
	}

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
 * <-......: wp-includes/Requests/Transport/fsockopen.php: Requests_Transport_fsockopen::request( string $url [, array $headers = array() [, string|array $data = array() [, array $options = array()]]] )
 * @NOW 015: wp-includes/Requests/Transport/fsockopen.php: Requests_Transport_fsockopen::connect_error_handler( int $errno, string $errstr )
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
