<?php
/**
 * Cookie storage object
 *
 * @package    Requests
 * @subpackage Cookies
 */

/**
 * Cookie storage object.
 *
 * @package    Requests
 * @subpackage Cookies
 */
class Requests_Cookie
{
	/**
	 * Cookie name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Cookie value.
	 *
	 * @var string
	 */
	public $value;

	/**
	 * Cookie attributes.
	 *
	 * Valid keys are (currently) path, domain, expires, max-age, secure and httponly.
	 *
	 * @var Requests_Utility_CaseInsensitiveDictionary|array Array-like object.
	 */
	public $attributes = array();

	/**
	 * Cookie flags.
	 *
	 * Vaoid keys are (currently) creation, last-access, persistent and host-only.
	 *
	 * @var array
	 */
	public $flags = array();

	/**
	 * Reference time for relative calculations.
	 *
	 * This is used in place of `time()` when calculating Max-Age expiration and checking time validity.
	 *
	 * @var int
	 */
	public $reference_time = 0;

	/**
	 * Create a new cookie object.
	 *
	 * @param string                                           $name
	 * @param string                                           $value
	 * @param array|Requests_Utility_CaseInsensitiveDictionary $attributes     Associative array of attribute data.
	 * @param array                                            $flags
	 * @param int                                              $reference_time
	 */
	public function __construct( $name, $value, $attributes = array(), $flags = array(), $reference_time = NULL )
	{
		$this->name = $name;
		$this->value = $value;
		$this->attributes = $attributes;
		$default_flags = array(
			'creation'    => time(),
			'last-access' => time(),
			'persistent'  => FALSE,
			'host-only'   => TRUE
		);
		$this->flags = array_merge( $default_flags, $flags );
		$this->reference_time = time();

		if ( $reference_time !== NULL ) {
			$this->reference_time = $reference_time;
		}

		$this->normalize();
	}

	/**
	 * Normalize cookie and attributes.
	 *
	 * @return bool Whether the cookie was successfully normalized.
	 */
	public function normalize()
	{
		foreach ( $this->attributes as $key => $value ) {
			$orig_value = $value;
			$value = $this->normalize_attribute( $key, $value );

			if ( $value === NULL ) {
				unset( $this->attributes[ $key ] );
				continue;
			}

			if ( $value !== $orig_value ) {
				$this->attributes[ $key ] = $value;
			}
		}

		return TRUE;
	}

	/**
	 * Parse an individual cookie attribute.
	 *
	 * Handles parsing individual attributes from the cookie values.
	 *
	 * @param  string      $name  Attribute name.
	 * @param  string|bool $value Attribute value (string value, or true if empty/flag).
	 * @return mixed       Value if available, or null if the attribute value is invalid (and should be skipped).
	 */
	protected function normalize_attribute( $name, $value )
	{
		switch ( strtolower( $name ) ) {
			case 'expires':
				// Expiration parsing, as per RFC 6265 section 5.2.1
				if ( is_int( $value ) ) {
					return $value;
				}

				$expiry_time = strtotime( $value );

				if ( $expiry_time === FALSE ) {
					return NULL;
				}

				return $expiry_time;

			case 'max-age':
				// Expiration parsing, as per RFC 6265 section 5.2.2
				if ( is_int( $value ) ) {
					return $value;
				}

				// Check that we have a valid age.
				if ( ! preg_match( '/^-?\d+$/', $value ) ) {
					return NULL;
				}

				$delta_seconds = ( int ) $value;

				$expiry_time = $delta_seconds <= 0
					? 0
					: $this->reference_time + $delta_seconds;

				return $expiry_time;

			case 'domain':
				// Domain normalization, as per RFC 6265 section 5.2.3
				if ( $value[0] === '.' ) {
					$value = substr( $value, 1 );
				}

				return $value;

			default:
				return $value;
		}
	}

	/**
	 * Parse a cookie string into a cookie object.
	 *
	 * Based on Mozilla's parsing code in Firefox and related projects, which is an intentional deviation from RFC 2109 and RFC 2616.
	 * RFC 6265 specifies some of this handling, but not in a thorough manner.
	 *
	 * @param  string          $string Cookie header value (from a Set-Cookie header)
	 * @param  string          $name
	 * @param  int             $reference_time
	 * @return Requests_Cookie Parsed cookie object.
	 */
	public static function parse( $string, $name = '', $reference_time = NULL )
	{
		$parts = explode( ';', $string );
		$kvparts = array_shift( $parts );

		if ( ! empty( $name ) ) {
			$value = $string;
		} elseif ( strpos( $kvparts, '=' ) === FALSE ) {
			/**
			 * Some sites might only have a value without the equals separator.
			 * Deviate from RFC 6265 and pretend it was actually a blank name (`=foo`).
			 *
			 * https://bugzilla.mozilla.org/show_bug.cgi?id=169091
			 */
			$name = '';
			$value = $kvparts;
		} else {
			list( $name, $value ) = explode( '=', $kvparts, 2 );
		}

		$name = trim( $name );
		$value = trim( $value );

		// Attribute key are handled case-insensitively.
		$attributes = new Requests_Utility_CaseInsensitiveDictionary();
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
 * <-......: wp-includes/Requests/Cookie/Jar.php: Requests_Cookie_Jar::before_request( string $url, &array $headers, &array $data, &string $type, &array $options )
 * @NOW 017: wp-includes/Requests/Cookie.php: Requests_Cookie::parse( string $string [, string $name = '' [, int $reference_time = NULL]] )
 * ......->: wp-includes/Requests/Utility/CaseInsensitiveDictionary.php: Requests_Utility_CaseInsensitiveDictionary::offsetSet( string $key, string $value )
 */
	}
}
