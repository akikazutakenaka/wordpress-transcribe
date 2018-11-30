<?php

/**
 * IDNA URL encoder
 *
 * Note: Not fully compliant, as nameprep does nothing yet.
 *
 * @package Requests
 * @subpackage Utilities
 * @see https://tools.ietf.org/html/rfc3490 IDNA specification
 * @see https://tools.ietf.org/html/rfc3492 Punycode/Bootstrap specification
 */
class Requests_IDNAEncoder {
	// refactored. const ACE_PREFIX = 'xn--';
	// :
	// refactored. const BOOTSTRAP_INITIAL_N    = 128;

	/**
	 * Encode a hostname using Punycode
	 *
	 * @param string $string Hostname
	 * @return string Punycode-encoded hostname
	 */
	public static function encode($string) {
		$parts = explode('.', $string);
		foreach ($parts as &$part) {
			$part = self::to_ascii($part);
		}
		return implode('.', $parts);
	}

	// refactored. public static function to_ascii($string) {}
	// :
	// refactored. protected static function adapt($delta, $numpoints, $firsttime) {}
}