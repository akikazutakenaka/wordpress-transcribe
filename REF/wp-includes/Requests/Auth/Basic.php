<?php
/**
 * Basic Authentication provider
 *
 * @package    Requests
 * @subpackage Authentication
 */

/**
 * Basic Authentication provider.
 *
 * Provides a handler for Basic HTTP authentication via the Authorization header.
 *
 * @package    Requests
 * @subpackage Authentication
 */
class Requests_Auth_Basic implements Requests_Auth
{
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
	 * Constructor.
	 *
	 * @throws Requests_Exception On incorrect number of arguments (`authbasicbadargs`).
	 *
	 * @param array|null $args Array of user and password.
	 *                         Must have exactly two elements.
	 */
	public function __construct( $args = NULL )
	{
		if ( is_array( $args ) ) {
			if ( count( $args ) !== 2 ) {
				throw new Requests_Exception( 'Invalid number of arguments.', 'authbasicbadargs' );
			}

			list( $this->user, $this->pass ) = $args;
		}
	}

	/**
	 * Register the necessary callbacks.
	 *
	 * @see curl_before_send
	 * @see fsockopen_header
	 *
	 * @param Requests_Hooks $hooks Hook system.
	 */
	public function register( Requests_Hooks &$hooks )
	{
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
 * @NOW 015: wp-includes/Requests/Auth/Basic.php: Requests_Auth_Basic::register( &Requests_Hooks $hooks )
 * ......->: wp-includes/Requests/Auth.php: Requests_Auth::register( &Requests_Hooks $hooks )
 */
	}
}
