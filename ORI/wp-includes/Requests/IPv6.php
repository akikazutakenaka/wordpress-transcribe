<?php
/**
 * Class to validate and to work with IPv6 addresses
 *
 * @package Requests
 * @subpackage Utilities
 */

/**
 * Class to validate and to work with IPv6 addresses
 *
 * This was originally based on the PEAR class of the same name, but has been
 * entirely rewritten.
 *
 * @package Requests
 * @subpackage Utilities
 */
class Requests_IPv6 {
	// refactored. public static function uncompress($ip) {}

	/**
	 * Compresses an IPv6 address
	 *
	 * RFC 4291 allows you to compress consecutive zero pieces in an address to
	 * '::'. This method expects a valid IPv6 address and compresses consecutive
	 * zero pieces to '::'.
	 *
	 * Example:  FF01:0:0:0:0:0:0:101   ->  FF01::101
	 *           0:0:0:0:0:0:0:1        ->  ::1
	 *
	 * @see uncompress()
	 * @param string $ip An IPv6 address
	 * @return string The compressed IPv6 address
	 */
	public static function compress($ip) {
		// Prepare the IP to be compressed
		$ip = self::uncompress($ip);
		$ip_parts = self::split_v6_v4($ip);

		// Replace all leading zeros
		$ip_parts[0] = preg_replace('/(^|:)0+([0-9])/', '\1\2', $ip_parts[0]);

		// Find bunches of zeros
		if (preg_match_all('/(?:^|:)(?:0(?::|$))+/', $ip_parts[0], $matches, PREG_OFFSET_CAPTURE)) {
			$max = 0;
			$pos = null;
			foreach ($matches[0] as $match) {
				if (strlen($match[0]) > $max) {
					$max = strlen($match[0]);
					$pos = $match[1];
				}
			}

			$ip_parts[0] = substr_replace($ip_parts[0], '::', $pos, $max);
		}

		if ($ip_parts[1] !== '') {
			return implode(':', $ip_parts);
		}
		else {
			return $ip_parts[0];
		}
	}

	// refactored. protected static function split_v6_v4($ip) {}
	// refactored. public static function check_ipv6($ip) {}
}
