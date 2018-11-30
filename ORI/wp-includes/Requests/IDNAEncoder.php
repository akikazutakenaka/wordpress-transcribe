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

	/**
	 * Convert a UTF-8 string to an ASCII string using Punycode
	 *
	 * @throws Requests_Exception Provided string longer than 64 ASCII characters (`idna.provided_too_long`)
	 * @throws Requests_Exception Prepared string longer than 64 ASCII characters (`idna.prepared_too_long`)
	 * @throws Requests_Exception Provided string already begins with xn-- (`idna.provided_is_prefixed`)
	 * @throws Requests_Exception Encoded string longer than 64 ASCII characters (`idna.encoded_too_long`)
	 *
	 * @param string $string ASCII or UTF-8 string (max length 64 characters)
	 * @return string ASCII string
	 */
	public static function to_ascii($string) {
		// Step 1: Check if the string is already ASCII
		if (self::is_ascii($string)) {
			// Skip to step 7
			if (strlen($string) < 64) {
				return $string;
			}

			throw new Requests_Exception('Provided string is too long', 'idna.provided_too_long', $string);
		}

		// Step 2: nameprep
		$string = self::nameprep($string);

		// Step 3: UseSTD3ASCIIRules is false, continue
		// Step 4: Check if it's ASCII now
		if (self::is_ascii($string)) {
			// Skip to step 7
			if (strlen($string) < 64) {
				return $string;
			}

			throw new Requests_Exception('Prepared string is too long', 'idna.prepared_too_long', $string);
		}

		// Step 5: Check ACE prefix
		if (strpos($string, self::ACE_PREFIX) === 0) {
			throw new Requests_Exception('Provided string begins with ACE prefix', 'idna.provided_is_prefixed', $string);
		}

		// Step 6: Encode with Punycode
		$string = self::punycode_encode($string);

		// Step 7: Prepend ACE prefix
		$string = self::ACE_PREFIX . $string;

		// Step 8: Check size
		if (strlen($string) < 64) {
			return $string;
		}

		throw new Requests_Exception('Encoded string is too long', 'idna.encoded_too_long', $string);
	}

	// refactored. protected static function is_ascii($string) {}
	// :
	// refactored. protected static function adapt($delta, $numpoints, $firsttime) {}
}