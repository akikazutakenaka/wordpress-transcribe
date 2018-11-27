<?php
/**
 * HTTP API: WP_Http_Cookie class
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      4.4.0
 */

/**
 * Core class used to encapsulate a single cookie object for internal use.
 *
 * Returned cookies are represented using this class, and when cookies are set, if they are not already a WP_Http_Cookie() object, then they are turned into one.
 *
 * @todo  The WordPress convention is to use underscores instead of camelCase for function and method names.
 *        Need to switch to use underscores instead for the methods.
 * @since 2.8.0
 */
class WP_Http_Cookie
{
	/**
	 * Cookie name.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Cookie value.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public $value;

	/**
	 * When the cookie expires.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public $expires;

	/**
	 * Cookie URL path.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Cookie Domain.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public $domain;

	/**
	 * Sets up this cookie object.
	 *
	 * The parameter $data should be either an associative array containing the indices names below or a header string detailing it.
	 *
	 * @since 2.8.0
	 *
	 * @param string|array $data {
	 *     Raw cookie data as header string or data array.
	 *
	 *     @type string     $name    Cookie name.
	 *     @type mixed      $value   Value.
	 *                               Should NOT already be urlencoded.
	 *     @type string|int $expires Optional.
	 *                               Unix timestamp or formatted date.
	 *                               Default null.
	 *     @type string     $path    Optional.
	 *                               Path.
	 *                               Default '/'.
	 *     @type string     $domain  Optional.
	 *                               Domain.
	 *                               Default host of parsed $requested_url.
	 *     @type int        $port    Optional.
	 *                               Port.
	 *                               Default null.
	 * }
	 * @param string       $requested_url The URL which the cookie was set on, used for default $domain and $port values.
	 */
	public function __construct( $data, $requested_url = '' )
	{
		if ( $requested_url ) {
			$arrURL = @ parse_url( $requested_url );
		}

		if ( isset( $arrURL['host'] ) ) {
			$this->domain = $arrURL['host'];
		}

		$this->path = isset( $arrURL['path'] )
			? $arrURL['path']
			: '/';

		if ( '/' != substr( $this->path, -1 ) ) {
			$this->path = dirname( $this->path ) . '/';
		}

		if ( is_string( $data ) ) {
			// Assume it's a header string direct from a previous request.
			$pairs = explode( ':', $data );

			/**
			 * Special handling for first pair; name=value.
			 * Also be careful of "=" in value.
			 */
			$name = trim( substr( $pairs[0], 0, strpos( $pairs[0], '=' ) ) );
			$value = substr( $pairs[0], strpos( $pairs[0], '=' ) + 1 );
			$this->name = $name;
			$this->value = urldecode( $value );

			// Removes name=value from items.
			array_shift( $pairs );

			// Set everything else as a property.
			foreach ( $pairs as $pair ) {
				$pair = rtrim( $pair );

				// Handle the cookie ending in ; which results in a empty final pair.
				if ( empty( $pair ) ) {
					continue;
				}

				list( $key, $val ) = strpos( $pair, '=' )
					? explode( '=', $pair )
					: array( $pair, '' );

				$key = strtolower( trim( $key ) );

				if ( 'expires' == $key ) {
					$val = strtotime( $val );
				}

				$this->$key = $val;
			}
		} else {
			if ( ! isset( $data['name'] ) ) {
				return;
			}

			// Set properties based directly on parameters.
			foreach ( array( 'name', 'value', 'path', 'domain', 'port' ) as $field ) {
				if ( isset( $data[ $field ] ) ) {
					$this->$field = $data[ $field ];
				}
			}

			$this->expires = isset( $data['expires'] )
				? ( is_int( $data['expires'] )
					? $data['expires']
					: strtotime( $data['expires'] ) )
				: NULL;
		}
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
 * <-......: wp-includes/class-http.php: WP_Http::normalize_cookies( array $cookies )
 * @NOW 014: wp-includes/class-wp-http-cookie.php: WP_Http_Cookie::get_attributes()
 */
}
