<?php
/**
 * SSL utilities for Requests
 *
 * @package    Requests
 * @subpackage Utilities
 */

/**
 * SSL utilities for Requests.
 *
 * Collection of utilities for working with and verifying SSL certificates.
 *
 * @package    Requests
 * @subpackage Utilities
 */
class Requests_SSL
{
	/**
	 * Verify the certificate against common name and subject alternative names.
	 *
	 * Unfortunately, PHP doesn't check the certificate against the alternative names, leading things like 'https://www.github.com/' to be invalid.
	 *
	 * @see    https://tools.ietf.org/html/rfc2818#section-3.1 RFC2818, Section 3.1
	 * @throws Requests_Exception On not obtaining a match for the host (`fsockopen.ssl.no_match`)
	 *
	 * @param  string $host Host name to verify against.
	 * @param  array  $cert Certificate data from openssl_x509_parse().
	 * @return bool
	 */
	public static function verify_certificate( $host, $cert )
	{
		// Calculate the valid wildcard match if the host is not an IP address.
		$parts = explode( '.', $host );

		if ( ip2long( $host ) === FALSE ) {
			$parts[0] = '*';
		}

		$wildcard = implode( '.', $parts );
		$has_dns_alt = FALSE;

		// Check the subjectAltName.
		if ( ! empty( $cert['extensions'] ) && ! empty( $cert['extensions']['subjectAltName'] ) ) {
			$altnames = explode( ',', $cert['extensions']['subjectAltName'] );

			foreach ( $altnames as $altname ) {
				$altname = trim( $altname );

				if ( strpos( $altname, 'DNS:' ) !== 0 ) {
					continue;
				}

				$has_dns_alt = TRUE;

				// Strip the 'DNS:' prefix and trim whitespace.
				$altname = trim( substr( $altname, 4 ) );

				// Check for a match.
				if ( self::match_domain( $host, $altname ) === TRUE ) {
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
 * <-......: wp-includes/Requests/Transport/fsockopen.php: Requests_Transport_fsockopen::request( string $url [, array $headers = array() [, string|array $data = array() [, array $options = array()]]] )
 * <-......: wp-includes/Requests/Transport/fsockopen.php: verify_certificate_from_context( string $host, resource $context )
 * @NOW 016: wp-includes/Requests/SSL.php: Requests_SSL::verity_certificate( string $host, array $cert )
 * ......->: wp-includes/Requests/SSL.php: Requests_SSL::match_domain( string $host, string $reference )
 */
				}
			}
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
 * <-......: wp-includes/Requests/Transport/fsockopen.php: Requests_Transport_fsockopen::request( string $url [, array $headers = array() [, string|array $data = array() [, array $options = array()]]] )
 * <-......: wp-includes/Requests/Transport/fsockopen.php: verify_certificate_from_context( string $host, resource $context )
 * <-......: wp-includes/Requests/SSL.php: Requests_SSL::verity_certificate( string $host, array $cert )
 * @NOW 017: wp-includes/Requests/SSL.php: Requests_SSL::match_domain( string $host, string $reference )
 */
}
