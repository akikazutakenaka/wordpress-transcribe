<?php
/**
 * Cookie holder object
 *
 * @package    Requests
 * @subpackage Cookies
 */

/**
 * Cookie holder object.
 *
 * @package    Requests
 * @subpackage Cookies
 */
class Requests_Cookie_Jar implements ArrayAccess, IteratorAggregate
{
	/**
	 * Actual item data.
	 *
	 * @var array
	 */
	protected $cookies = array();

	/**
	 * Create a new jar.
	 *
	 * @param array $cookies Existing cookie values.
	 */
	public function __construct( $cookies = array() )
	{
		$this->cookies = $cookies;
	}

	/**
	 * Normalize cookie data into a Requests_Cookie.
	 *
	 * @param  string|Requests_Cookie $cookie
	 * @param  string                 $key
	 * @return Requests_Cookie
	 */
	public function normalize_cookie( $cookie, $key = NULL )
	{
		if ( $cookie instanceof Requests_Cookie ) {
			return $cookie;
		}

		return Requests_Cookie::parse( $cookie, $key );
	}

	/**
	 * Register the cookie handler with the request's hooking system.
	 *
	 * @param Requests_Hooker $hooks Hooking system.
	 */
	public function register( Requests_Hooker $hooks )
	{
		$hooks->register( 'requests.before_request', array( $this, 'before_request' ) );
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
 * @NOW 015: wp-includes/Requests/Cookie/Jar.php: Requests_Cookie_Jar::register( Requests_Hooker $hooks )
 * ......->: wp-includes/Requests/Cookie/Jar.php: Requests_Cookie_Jar::before_request( string $url, &array $headers, &array $data, &string $type, &array $options )
 */
	}

	/**
	 * Add Cookie header to a request if we have any.
	 *
	 * As per RFC 6265, cookies are separated by '; '.
	 *
	 * @param string $url
	 * @param array  $headers
	 * @param array  $data
	 * @param string $type
	 * @param array  $options
	 */
	public function before_request( $url, &$headers, &$data, &$type, &$options )
	{
		if ( ! $url instanceof Requests_IRI ) {
			$url = new Requests_IRI( $url );
		}

		if ( ! empty( $this->cookies ) ) {
			$cookies = array();

			foreach ( $this->cookies as $key => $cookie ) {
				$cookie = $this->normalize_cookie( $cookie, $key );

				// Skip expired cookies.
				if ( $cookie->is_expired() ) {
					continue;
				}

				if ( $cookie->domain_matches( $url->host ) ) {
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
 * <-......: wp-includes/Requests/Cookie/Jar.php: Requests_Cookie_Jar::register( Requests_Hooker $hooks )
 * @NOW 016: wp-includes/Requests/Cookie/Jar.php: Requests_Cookie_Jar::before_request( string $url, &array $headers, &array $data, &string $type, &array $options )
 */
				}
			}
		}
	}
}
