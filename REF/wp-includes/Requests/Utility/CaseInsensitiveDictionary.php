<?php
/**
 * Case-insensitive dictionary, suitable for HTTP headers
 *
 * @package    Requests
 * @subpackage Utilities
 */

/**
 * Case-insensitive dictionary, suitable for HTTP headers.
 *
 * @package    Requests
 * @subpackage Utilities
 */
class Requests_Utility_CaseInsensitiveDictionary implements ArrayAccess, IteratorAggregate
{
	/**
	 * Actual item data.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Creates a case insensitive dictionary.
	 *
	 * @param array $data Dictionary/map to convert to case-insensitive.
	 */
	public function __construct( array $data = array() )
	{
		foreach ( $data as $key => $value ) {
			$this->offsetSet( $key, $value );
		}
	}

	/**
	 * Set the given item.
	 *
	 * @throws Requests_Exception On attempting to use dictionary as list (`invalidset`)
	 *
	 * @param string $key   Item name.
	 * @param string $value Item value.
	 */
	public function offsetSet( $key, $value )
	{
		if ( $key === NULL ) {
			throw new Requests_Exception( 'Object is a dictionary, not a list', 'invalidset' );
		}

		$key = strtolower( $key );
		$this->data[ $key ] = $value;
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
 * <-......: wp-includes/class-wp-http-requests-response.php: WP_HTTP_Requests_Response::get_headers()
 * @NOW 014: wp-includes/Requests/Utility/CaseInsensitiveDictionary.php: Requests_Utility_CaseInsensitiveDictionary::getAll()
 */
}
