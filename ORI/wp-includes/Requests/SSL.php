<?php
/**
 * SSL utilities for Requests
 *
 * @package Requests
 * @subpackage Utilities
 */

/**
 * SSL utilities for Requests
 *
 * Collection of utilities for working with and verifying SSL certificates.
 *
 * @package Requests
 * @subpackage Utilities
 */
class Requests_SSL {
	/**
	 * Verify the certificate against common name and subject alternative names
	 *
	 * Unfortunately, PHP doesn't check the certificate against the alternative
	 * names, leading things like 'https://www.github.com/' to be invalid.
	 * Instead
	 *
	 * @see https://tools.ietf.org/html/rfc2818#section-3.1 RFC2818, Section 3.1
	 *
	 * @throws Requests_Exception On not obtaining a match for the host (`fsockopen.ssl.no_match`)
	 * @param string $host Host name to verify against
	 * @param array $cert Certificate data from openssl_x509_parse()
	 * @return bool
	 */
	public static function verify_certificate($host, $cert) {
		// Calculate the valid wildcard match if the host is not an IP address
		$parts = explode('.', $host);
		if (ip2long($host) === false) {
			$parts[0] = '*';
		}
		$wildcard = implode('.', $parts);

		$has_dns_alt = false;

		// Check the subjectAltName
		if (!empty($cert['extensions']) && !empty($cert['extensions']['subjectAltName'])) {
			$altnames = explode(',', $cert['extensions']['subjectAltName']);
			foreach ($altnames as $altname) {
				$altname = trim($altname);
				if (strpos($altname, 'DNS:') !== 0) {
					continue;
				}

				$has_dns_alt = true;

				// Strip the 'DNS:' prefix and trim whitespace
				$altname = trim(substr($altname, 4));

				// Check for a match
				if (self::match_domain($host, $altname) === true) {
					return true;
				}
			}
		}

		// Fall back to checking the common name if we didn't get any dNSName
		// alt names, as per RFC2818
		if (!$has_dns_alt && !empty($cert['subject']['CN'])) {
			// Check for a match
			if (self::match_domain($host, $cert['subject']['CN']) === true) {
				return true;
			}
		}

		return false;
	}

	// refactored. public static function verify_reference_name($reference) {}
	// refactored. public static function match_domain($host, $reference) {}
}