<?php

/**
 * IDNA URL encoder.
 *
 * Note: Not fully compliant, as nameprep does nothing yet.
 *
 * @package    Requests
 * @subpackage Utilities
 * @see        https://tools.ietf.org/html/rfc3490 IDNA specification
 * @see        https://tools.ietf.org/html/rfc3492 Punycode/Bootstrap specification
 */
class Requests_IDNAEncoder
{
	/**
	 * ACE prefix used for IDNA.
	 *
	 * @see https://tools.ietf.org/html/rfc3490#section-5
	 *
	 * @var string
	 */
	const ACE_PREFIX = 'xn--';

	/**
	 * Bootstrap constant for Punycode.
	 *
	 * @see https://tools.ietf.org/html/rfc3492#section-5
	 *
	 * @var int
	 */
	const BOOTSTRAP_BASE         = 36;
	const BOOTSTRAP_TMIN         = 1;
	const BOOTSTRAP_TMAX         = 26;
	const BOOTSTRAP_SKEW         = 38;
	const BOOTSTRAP_DAMP         = 700;
	const BOOTSTRAP_INITIAL_BIAS = 72;
	const BOOTSTRAP_INITIAL_N    = 128;

	/**
	 * Encode a hostname using Punycode.
	 *
	 * @param  string $string Hostname.
	 * @return string Punycode-encoded hostname.
	 */
	public static function encode( $string )
	{
		$parts = explode( '.', $string );

		foreach ( $parts as &$part ) {
			$part = self::to_ascii( $part );
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
 * @NOW 015: wp-includes/Requests/IDNAEncoder.php: Requests_IDNAEncoder::encode( string $string )
 * ......->: wp-includes/Requests/IDNAEncoder.php: Requests_IDNAEncoder::to_ascii( string $string )
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
 * <-......: wp-includes/class-requests.php: Requests::request( string $url [, array $headers = array() [, array|null $data = array() [, string $type = self::GET [, array $options = array()]]]] )
 * <-......: wp-includes/class-requests.php: Requests::set_defaults( &string $url, &array $headers, &array|null $data, &string $type, &array $options )
 * <-......: wp-includes/Requests/IDNAEncoder.php: Requests_IDNAEncoder::encode( string $string )
 * @NOW 016: wp-includes/Requests/IDNAEncoder.php: Requests_IDNAEncoder::to_ascii( string $string )
 */
}
