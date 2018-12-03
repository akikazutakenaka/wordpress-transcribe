<?php
/**
 * HTTP API: WP_HTTP_Requests_Response class
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      4.6.0
 */

/**
 * Core wrapper object for a Requests_Response for standardisation.
 *
 * @since 4.6.0
 * @see   WP_HTTP_Response
 */
class WP_HTTP_Requests_Response extends WP_HTTP_Response
{
	/**
	 * Requests Response object.
	 *
	 * @since 4.6.0
	 *
	 * @var Requests_Response
	 */
	protected $response;

	/**
	 * Filename the response was saved to.
	 *
	 * @since 4.6.0
	 *
	 * @var string|null
	 */
	protected $filename;

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param Requests_Response $response HTTP response.
	 * @param string            $filename Optional.
	 *                                    File name.
	 *                                    Default empty.
	 */
	public function __construct( Requests_Response $response, $filename = '' )
	{
		$this->response = $response;
		$this->filename = $filename;
	}

	/**
	 * Retrieves headers associated with the response.
	 *
	 * @since 4.6.0
	 *
	 * @see \Requests_Utility_CaseInsensitiveDictionary
	 *
	 * @return \Requests_Utility_CaseInsensitiveDictionary Map of header name to header value.
	 */
	public function get_headers()
	{
		// Ensure headers remain case-insensitive.
		$converted = new Requests_Utility_CaseInsensitiveDictionary();

		foreach ( $this->response->headers->getAll() as $key => $value ) {
			$converted[ $key ] = count( $value ) === 1
				? $value[0]
				: $value;
		}

		return $converted;
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
 * @NOW 013: wp-includes/class-wp-http-requests-response.php: WP_HTTP_Requests_Response::get_data()
 */

	/**
	 * Converts the object to a WP_Http response array.
	 *
	 * @since 4.6.0
	 *
	 * @return array WP_Http response array, per WP_Http::request().
	 */
	public function to_array()
	{
		return array(
			'headers'  => $this->get_headers(),
			'body'     => $this->get_data(),
			'response' => array(
				'code'    => $this->get_status(),
				'message' => get_status_header_desc( $this->get_status() )
			),
			'cookies'  => $this->get_cookies(),
			'filename' => $this->filename
		);
	}
}
