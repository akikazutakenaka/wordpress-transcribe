<?php
/**
 * Handles adding and dispatching events.
 *
 * @package    Requests
 * @subpackage Utilities
 */

/**
 * Handles adding and dispatching events.
 *
 * @package    Requests
 * @subpackage Utilities
 */
class Requests_Hooks implements Requests_Hooker
{
	/**
	 * Registered callbacks for each hook.
	 *
	 * @var array
	 */
	protected $hooks = array();

	/**
	 * Constructor.
	 */
	public function __construct()
	{}

	/**
	 * Register a callback for a hook.
	 *
	 * @param string   $hook     Hook name.
	 * @param callback $callback Function/method to call on event.
	 * @param int      $priority Priority number.
	 *                           <0 is executed earlier, >0 is executed later.
	 */
	public function register( $hook, $callback, $priority = 0 )
	{
		if ( ! isset( $this->hooks[ $hook ] ) ) {
			$this->hooks[ $hook ] = array();
		}

		if ( ! isset( $this->hooks[ $hook ][ $priority ] ) ) {
			$this->hooks[ $hook ][ $priority ] = array();
		}

		$this->hooks[ $hook ][ $priority ][] = $callback;
	}

	/**
	 * Dispatch a message.
	 *
	 * @param  string $hook       Hook name.
	 * @param  array  $parameters Parameters to pass to callbacks.
	 * @return bool   Successfulness.
	 */
	public function dispatch( $hook, $parameters = array() )
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
 * <-......: wp-includes/class-wp-http-requests-hooks.php: WP_HTTP_Requests::dispatch( string $hook [, array $parameters = array()] )
 * @NOW 015: wp-includes/Requests/Hooks.php: Requests_Hooks::dispatch( string $hook [, array $parameters = array()] )
 * ......->: wp-includes/Requests/Hooker.php: Requests_Hooker::dispatch( string $hook [, array $parameters = array()] )
 */
	}
}
