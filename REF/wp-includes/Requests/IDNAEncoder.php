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
	 * Convert a UTF-8 string to an ASCII string using Punycode.
	 *
	 * @throws Requests_Exception Provided string longer than 64 ASCII characters (`idna.provided_too_long`).
	 * @throws Requests_Exception Prepared string longer than 64 ASCII characters (`idna.prepared_too_long`).
	 * @throws Requests_Exception Provided string already begins with xn-- (`idna.provided_is_prefixed`).
	 * @throws Requests_Exception Encoded string longer than 64 ASCII characters (`idna.encoded_too_long`).
	 *
	 * @param  string $string ASCII or UTF-8 string (max length 64 characters).
	 * @return string ASCII string.
	 */
	public static function to_ascii( $string )
	{
		// Step 1: Check if the string is already ASCII.
		if ( self::is_ascii( $string ) ) {
			// Skip to step 7.
			if ( strlen( $string ) < 64 ) {
				return $string;
			}

			throw new Requests_Exception( 'Provided string is too long', 'idna.provided_too_long', $string );
		}

		// Step 2: nameprep.
		$string = self::nameprep( $string );
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

	/**
	 * Check whether a given string contains only ASCII characters.
	 *
	 * @internal (Testing found regex was the fastest implementation)
	 *
	 * @param  string $string
	 * @return bool   Is the string ASCII-only?
	 */
	protected static function is_ascii( $string )
	{
		return preg_match( '/(?:[^\x00-\x7F])/', $string ) !== 1;
	}

	/**
	 * Prepare a string for use as an IDNA name.
	 *
	 * @todo Implement this based on RFC 3491 and the newer 5891.
	 *
	 * @param  string $string
	 * @return string Prepared string.
	 */
	protected static function nameprep( $string )
	{
		return $string;
	}
}
