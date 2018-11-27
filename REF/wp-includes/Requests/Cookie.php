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
 * @NOW 014: wp-includes/Requests/Cookie.php: Requests_Cookie::normalize()
 * ......->: wp-includes/Requests/Cookie.php: Requests_Cookie::normalize_attribute( string $name, string|bool $avlue )
 */
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
 * <-......: wp-includes/Requests/Cookie.php: Requests_Cookie::normalize()
 * @NOW 015: wp-includes/Requests/Cookie.php: Requests_Cookie::normalize_attribute( string $name, string|bool $avlue )
 */
}
