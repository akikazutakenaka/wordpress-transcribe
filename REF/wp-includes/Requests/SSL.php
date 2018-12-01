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
					return TRUE;
				}
			}
		}

		// Fall back to checking the common name if we didn't get any DNS name alt names, as per RFC 2818.
		if ( ! $has_dns_alt && ! empty( $cert['subject']['CN'] ) ) {
			// Check for a match.
			if ( self::match_domain( $host, $cert['subject']['CN'] ) === TRUE ) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Verify that a reference name is valid.
	 *
	 * Verifies a DNS name for HTTPS usage, (almost) as per Firefox's rules:
	 * - Wildcards can only occur in a name with more than 3 components.
	 * - Wildcards can only occur as the last character in the first component.
	 * - Wildcards may be preceded by additional characters.
	 *
	 * We modify these rules to be a bit stricter and only allow the wildcard character to be the full first component; that is, with the exclusion of the third rule.
	 *
	 * @param  string $reference Reference DNS name.
	 * @return bool   Is the name valid?
	 */
	public static function verify_reference_name( $reference )
	{
		$parts = explode( '.', $reference );

		// Check the first part of the name.
		$first = array_shift( $parts );

		if ( strpos( $first, '*' ) !== FALSE ) {
			// Check that the wildcard is the full part.
			if ( $first !== '*' ) {
				return FALSE;
			}

			// Check that we have at least 3 components (including first).
			if ( count( $parts ) < 2 ) {
				return FALSE;
			}
		}

		// Check the remaining parts.
		foreach ( $parts as $part ) {
			if ( strpos( $part, '*' ) !== FALSE ) {
				return FALSE;
			}
		}

		// Nothing found, verified!
		return TRUE;
	}

	/**
	 * Match a hostname against a DNS name reference.
	 *
	 * @param  string $host      Requested host.
	 * @param  string $reference DNS name to match against.
	 * @return bool   Does the domain match?
	 */
	public static function match_domain( $host, $reference )
	{
		// Check if the reference is blacklisted first.
		if ( self::verify_reference_name( $reference ) !== TRUE ) {
			return FALSE;
		}

		// Check for a direct match.
		if ( $host === $reference ) {
			return TRUE;
		}

		/**
		 * Calculate the valid wildcard match if the host is not an IP address.
		 * Also validates that the host has 3 parts or more, as per Firefox's ruleset.
		 */
		if ( ip2long( $host ) === FALSE ) {
			$parts = explode( '.', $host );
			$parts[0] = '*';
			$wildcard = implode( '.', $parts );

			if ( $wildcard === $reference ) {
				return TRUE;
			}
		}

		return FALSE;
	}
}
