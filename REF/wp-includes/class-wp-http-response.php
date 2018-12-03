<?php
/**
 * HTTP API: WP_HTTP_Response class
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      4.4.0
 */

/**
 * Core class used to prepare HTTP responses.
 *
 * @since 4.4.0
 */
class WP_HTTP_Response
{
	/**
	 * Response data.
	 *
	 * @since 4.4.0
	 *
	 * @var mixed
	 */
	public $data;

	/**
	 * Response headers.
	 *
	 * @since 4.4.0
	 *
	 * @var array
	 */
	public $headers;

	/**
	 * Response status.
	 *
	 * @since 4.4.0
	 *
	 * @var int
	 */
	public $status;

	/**
	 * Constructor.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $data    Response data.
	 *                       Default null.
	 * @param int   $status  Optional.
	 *                       HTTP status code.
	 *                       Default 200.
	 * @param array $headers Optional.
	 *                       HTTP header map.
	 *                       Default empty array.
	 */
	public function __construct( $data = NULL, $status = 200, $headers = array() )
	{
		$this->set_data( $data );
		$this->set_status( $status );
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
 * <-......: wp-includes/class-wp-http-requests-response.php: WP_HTTP_Requests_Response
 * @NOW 014: wp-includes/class-wp-http-response.php: WP_HTTP_Response::__construct( [mixed $data = NULL [, int $status = 200 [, array $headers = array()]]] )
 */
	}

	/**
	 * Sets the 3-digit HTTP status code.
	 *
	 * @since 4.4.0
	 *
	 * @param int $code HTTP status.
	 */
	public function set_status( $code )
	{
		$this->status = absint( $code );
	}

	/**
	 * Sets the response data.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $data Response data.
	 */
	public function set_data( $data )
	{
		$this->data = $data;
	}
}
