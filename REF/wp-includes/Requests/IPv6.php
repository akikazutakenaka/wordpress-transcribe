<?php
/**
 * Class to validate and to work with IPv6 addresses
 *
 * @package    Requests
 * @subpackage Utilities
 */

/**
 * Class to validate and to work with IPv6 addresses.
 *
 * This was originally based on the PEAR class of the same name, but has been entirely rewritten.
 *
 * @package    Requests
 * @subpackage Utilities
 */
class Requests_IPv6
{
	/**
	 * Uncompresses an IPv6 address.
	 *
	 * RFC 4291 allows you to compress consecutive zero pieces in an address to '::'.
	 * This method expects a valid IPv6 address and expands the '::' to the required number of zero pieces.
	 *
	 * Example: FF01::101 -> FF01:0:0:0:0:0:0:101
	 *          ::1       -> 0:0:0:0:0:0:0:1
	 *
	 * @author    Alexander Merz <alexander.merz@web.de>
	 * @author    elfrink@introweb.nl
	 * @author    Josh Peck <jmp@joshpeck.org>
	 * @copyright 2003-2005 The PHP Group
	 * @license   http://www.opensource.org/licenses/bsd-license.php
	 *
	 * @param  string $ip An IPv6 address.
	 * @return string The uncompressed IPv6 address.
	 */
	public static function uncompress( $ip )
	{
		if ( substr_count( $ip, '::' ) !== 1 ) {
			return $ip;
		}

		list( $ip1, $ip2 ) = explode( '::', $ip );

		$c1 = $ip1 === ''
			? -1
			: substr_count( $ip1, ':' );

		$c2 = $ip2 === ''
			? -1
			: substr_count( $ip2, ':' );

		if ( strpos( $ip2, '.' ) !== FALSE ) {
			$c2++;
		}

		if ( $c1 === -1 && $c2 === -1 ) {
			// ::
			$ip = '0:0:0:0:0:0:0:0';
		} elseif ( $c1 === -1 ) {
			// ::xxx
			$fill = str_repeat( '0:', 7 - $c2 );
			$ip = str_replace( '::', $fill, $ip );
		} elseif ( $c2 === -1 ) {
			// xxx::
			$fill = str_repeat( ':0', 7 - $c1 );
			$ip = str_replace( '::', $fill, $ip );
		} else {
			// xxx::xxx
			$fill = ':' . str_repeat( '0:', 6 - $c2 - $c1 );
			$ip = str_replace( '::', $fill, $ip );
		}

		return $ip;
	}

	/**
	 * Splits an IPv6 address into the IPv6 and IPv4 representation parts.
	 *
	 * RFC 4291 allows you to represent the last two parts of an IPv6 address using the standard IPv4 representation.
	 *
	 * Example: 0:0:0:0:0:0:13.1.68.3
	 *          0:0:0:0:0:FFFF:129.144.52.38
	 *
	 * @param  string   $ip An IPv6 address.
	 * @return string[] [0] contains the IPv6 represented part, and [1] the IPv4 represented part.
	 */
	protected static function split_v6_v4( $ip )
	{
		if ( strpos( $ip, '.' ) !== FALSE ) {
			$pos = strrpos( $ip, ':' );
			$ipv6_part = substr( $ip, 0, $pos );
			$ipv4_part = substr( $ip, $pos + 1 );
			return array( $ipv6_part, $ipv4_part );
		} else {
			return array( $ip, '' );
		}
	}

	/**
	 * Checks an IPv6 address.
	 *
	 * Checks if the given IP is a valid IPv6 address.
	 *
	 * @param  string $ip An IPv6 address.
	 * @return bool   True if $ip is a valid IPv6 address.
	 */
	public static function check_ipv6( $ip )
	{
		$ip = self::uncompress( $ip );
		list( $ipv6, $ipv4 ) = self::split_v6_v4( $ip );
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
 * <-......: wp-includes/Requests/IRI.php: Requests_IRI::set_iri( string $iri )
 * <-......: wp-includes/Requests/IRI.php: Requests_IRI::set_authority( string $authority )
 * <-......: wp-includes/Requests/IRI.php: Requests_IRI::set_host( string $host )
 * @NOW 020: wp-includes/Requests/IPv6.php: Requests_IPv6::check_ipv6( string $ip )
 */
	}
}
