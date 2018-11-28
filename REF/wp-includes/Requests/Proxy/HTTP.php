<?php
/**
 * HTTP Proxy connection interface
 *
 * @package    Requests
 * @subpackage Proxy
 * @since      1.6
 */

/**
 * HTTP Proxy connection interface.
 *
 * Provides a handler for connection via an HTTP proxy.
 *
 * @package    Requests
 * @subpackage Proxy
 * @since      1.6
 */
class Requests_Proxy_HTTP implements Requests_Proxy
{
	/**
	 * Proxy host and port.
	 *
	 * Notation: "host:port" (e.g. 127.0.0.1:8080 or someproxy.com:3128)
	 *
	 * @var string
	 */
	public $proxy;

	/**
	 * Username.
	 *
	 * @var string
	 */
	public $user;

	/**
	 * Password.
	 *
	 * @var string
	 */
	public $pass;

	/**
	 * Do we need to authenticate?
	 * (i.e. username & password have been provided)
	 *
	 * @var bool
	 */
	public $use_authentication;

	/**
	 * Constructor.
	 *
	 * @since  1.6
	 * @throws Requests_Exception On incorrect number of arguments (`authbasicbadargs`)
	 *
	 * @param array|null $args Array of user and password.
	 *                         Must have exactly two elements.
	 */
	public function __construct( $args = NULL )
	{
		if ( is_string( $args ) ) {
			$this->proxy = $args;
		} elseif ( is_array( $args ) ) {
			if ( count( $args ) == 1 ) {
				list( $this->proxy ) = $args;
			} elseif ( count( $args ) == 3 ) {
				list( $this->proxy, $this->user, $this->pass ) = $args;
				$this->use_authentication = TRUE;
			} else {
				throw new Requests_Exception( 'Invalid number of arguments', 'proxyhttpbadargs' );
			}
		}
	}

	/**
	 * Register the necessary callbacks.
	 *
	 * @since 1.6
	 * @see   curl_before_send
	 * @see   fsockopen_remote_socket
	 * @see   fsockopen_remote_host_path
	 * @see   fsockopen_header
	 *
	 * @param Requests_Hooks $hooks Hook system.
	 */
	public function register( Requests_Hooks &$hooks )
	{
		$hooks->register( 'curl.before_send', array( &$this, 'curl_before_send' ) );
		$hooks->register( 'fsockopen.remote_socket', array( &$this, 'fsockopen_remote_socket' ) );
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
 * <-......: wp-includes/class-requests.php: Requests::set_defaults( &string $url, &array $headers, &array|null $data, &string $type, &array $options )
 * @NOW 015: wp-includes/Requests/Proxy/HTTP.php: Requests_Proxy_HTTP::register( &Requests_Hooks $hooks )
 */
	}

	/**
	 * Set cURL parameters before the data is sent.
	 *
	 * @since 1.6
	 *
	 * @param resource $handle cURL resource.
	 */
	public function curl_before_send( &$handle )
	{
		curl_setopt( $handle, CURLOPT_PROXYTYPE, CURLPROXY_HTTP );
		curl_setopt( $handle, CURLOPT_PROXY, $this->proxy );

		if ( $this->use_authentication ) {
			curl_setopt( $handle, CURLOPT_PROXYAUTH, CURLAUTH_ANY );
			curl_setopt( $handle, CURLOPT_PROXYUSERPWD, $this->get_auth_string() );
		}
	}

	/**
	 * Alter remote socket information before opening socket connection.
	 *
	 * @since 1.6
	 *
	 * @param string $remote_socket Socket connection string.
	 */
	public function fsockopen_remote_socket( &$remote_socket )
	{
		$remote_socket = $this->proxy;
	}

	/**
	 * Get the authentication string (user:pass).
	 *
	 * @since 1.6
	 *
	 * @return string
	 */
	public function get_auth_string()
	{
		return $this->user . ':' . $this->pass;
	}
}
