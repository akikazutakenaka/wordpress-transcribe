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
 * @NOW 014: wp-includes/Requests/Cookie.php: Requests_Cookie::__construct( string $name, string $value [, array|Requests_Utility_CaseInsensitiveDictionary $attributes = array() [, array $flags = array() [, int $reference_time = NULL]]] )
 */
}
