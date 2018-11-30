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

		// Step 3: UseSTD3ASCIIRules is false, continue.
		// Step 4: Check if it's ASCII now.
		if ( self::is_ascii( $string ) ) {
			// Skip to step 7.
			if ( strlen( $string ) < 64 ) {
				return $string;
			}

			throw new Requests_Exception( 'Prepared string is too long', 'idna.prepared_too_long' );
		}

		// Step 5: Check ACE prefix.
		if ( strpos( $string, self::ACE_PREFIX ) === 0 ) {
			throw new Requests_Exception( 'Provided string begins with ACE prefix', 'idna.provided_is_prefixed', $string );
		}

		// Step 6: Encode with Punycode.
		$string = self::punycode_encode( $string );
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

	/**
	 * Convert a UTF-8 string to a UCS-4 codepoint array.
	 *
	 * Based on Requests_IRI::replace_invalid_with_pct_encoding().
	 *
	 * @throws Requests_Exception Invalid UTF-8 codepoint (`idna.invalidcodepoint`)
	 *
	 * @param  string $input
	 * @return array  Unicode code points.
	 */
	protected static function utf8_to_codepoints( $input )
	{
		$codepoints = array();

		// Get number of bytes.
		$strlen = strlen( $input );

		for ( $position = 0; $position < $strlen; $position++ ) {
			$value = ord( $input[ $position ] );

			if ( ( ~ $value & 0x80 ) === 0x80 ) {
				// One byte sequence:
				$character = $value;
				$length = 1;
				$remaining = 0;
			} elseif ( ( $value & 0xE0 ) === 0xC0 ) {
				// Two byte sequence:
				$character = ( $value & 0x1F ) << 6;
				$length = 2;
				$remaining = 1;
			} elseif ( ( $value & 0xF0 ) === 0xE0 ) {
				// Three byte sequence:
				$character = ( $value & 0x0F ) << 12;
				$length = 3;
				$remaining = 2;
			} elseif ( ( $value & 0xF8 ) === 0xF0 ) {
				// Four byte sequence:
				$character = ( $value & 0x07 ) << 18;
				$length = 4;
				$remaining = 3;
			} else {
				// Invalid byte:
				throw new Requests_Exception( 'Invalid Unicode codepoint', 'idna.invalidcodepoint', $value );
			}

			if ( $remaining > 0 ) {
				if ( $position + $length > $strlen ) {
					throw new Requests_Exception( 'Invalid Unicode codepoint', 'idna.invalidcodepoint', $character );
				}

				for ( $position++; $remaining > 0; $position++ ) {
					$value = ord( $input[ $position ] );

					// If it is invalid, count the sequence as invalid and reprocess the current byte:
					if ( ( $value & 0xC0 ) !== 0x80 ) {
						throw new Requests_Exception( 'Invalid Unicode codepoint', 'idna.invalidcodepoint', $character );
					}

					$character |= ( $value & 0x3F ) << ( --$remaining * 6 );
				}

				$position--;
			}

			if ( $length > 1 && $character <= 0x7F
			  || $length > 2 && $character <= 0x7FF
			  || $length > 3 && $character <= 0xFFFF
			  || ( $character & 0xFFFE ) === 0xFFFE
			  || $character >= 0xFDD0 && $character <= 0xFDEF
			  || ( $character > 0xD7FF && $character < 0xF900
			    || $character < 0x20
			    || $character > 0x7E && $character < 0xA0
			    || $character > 0xEFFFD ) ) {
				throw new Requests_Exception( 'Invalid Unicode codepoint', 'idna.invalidcodepoint', $character );
			}

			$codepoints[] = $character;
		}

		return $codepoints;
	}

	/**
	 * RFC3492-compliant encoder.
	 *
	 * @throws Requests_Exception On character outside of the domain (never happens with Punycode) (`idna.character_outside_domain`)
	 *
	 * @param  string $input UTF-8 encoded string to encode.
	 * @return string Punycode-encoded string.
	 */
	public static function punycode_encode( $input )
	{
		$output = '';
		$n = self::BOOTSTRAP_INITIAL_N;
		$delta = 0;
		$bias = self::BOOTSTRAP_INITIAL_BIAS;
		$h = $b = 0;
		$codepoints = self::utf8_to_codepoints( $input );
		$extended = array();

		foreach ( $codepoints as $char ) {
			if ( $char < 128 ) {
				/**
				 * Character is valid ASCII.
				 * @todo This should also check if it's valid for a URL.
				 */
				$output .= chr( $char );
				$h++;
			} elseif ( $char < $n ) {
				/**
				 * Check if the character is non-ASCII, but below initial n.
				 * This never occurs for Punycode, so ignore in coverage.
				 */
				throw new Requests_Exception( 'Invalid character', 'idna.character_outside_domain', $char );
			} else {
				$extended[ $char ] = TRUE;
			}
		}

		$extended = array_keys( $extended );
		sort( $extended );
		$b = $h;

		if ( strlen( $output ) > 0 ) {
			$output .= '-';
		}

		while ( $h < count( $codepoints ) ) {
			$m = array_shift( $extended );
			$delta += ( $m - $n ) * ( $h + 1 );
			$n = $m;

			for ( $num = 0; $num < count( $codepoints ); $num++ ) {
				$c = $codepoints[ $num ];

				if ( $c < $n ) {
					$delta++;
				} elseif ( $c === $n ) {
					$q = $delta;

					for ( $k = self::BOOSTRAP_BASE; ; $k += self::BOOTSTRAP_BASE ) {
						$t = $k <= ( $bias + self::BOOTSTRAP_TMIN )
							? self::BOOTSTRAP_TMIN
							: ( $k >= ( $bias + self::BOOTSTRAP_TMAX )
								? self::BOOTSTRAP_TMAX
								: $k - $bias );

						if ( $q < $t ) {
							break;
						}

						$digit = $t + ( ( $q - $t ) % ( self::BOOTSTRAP_BASE - $t ) );
						$output .= self::digit_to_char( $digit );
						$q = floor( ( $q - $t ) / ( self::BOOTSTRAP_BASE - $t ) );
					}

					$output .= self::digit_to_char( $q );
					$bias = self::adapt( $delta, $h + 1, $h === $b );
					$delta = 0;
					$h++;
				}
			}

			$delta++;
			$n++;
		}

		return $output;
	}

	/**
	 * Convert a digit to its respective character.
	 *
	 * @see    https://tools.ietf.org/html/rfc3492#section-5
	 * @throws Requests_Exception On invalid digit (`idna.invalid_digit`)
	 *
	 * @param  int    $digit Digit in the range 0-35.
	 * @return string Single character corresponding to digit.
	 */
	protected static function digit_to_char( $digit )
	{
		// As far as I know, this never happens, but still good to be sure.
		if ( $digit < 0 || $digit > 35 ) {
			throw new Requests_Exception( sprintf( 'Invalid digit %d', $digit ), 'idna.invalid_digit', $digit );
		}

		$digits = 'abcdefghijklmnopqrstuvwxyz0123456789';
		return substr( $digits, $digit, 1 );
	}

	/**
	 * Adapt the bias.
	 *
	 * @see https://tools.ietf.org/html/rfc3492#section-6.1
	 *
	 * @param  int  $delta
	 * @param  int  $numpoints
	 * @param  bool $firsttime
	 * @return int  New bias.
	 */
	protected static function adapt( $delta, $numpoints, $firsttime )
	{
		$delta = $firsttime
			? floor( $delta / self::BOOTSTRAP_DAMP )
			: floor( $delta / 2 );

		$delta += floor( $delta / $numpoints );
		$k = 0;
		$max = floor( ( self::BOOTSTRAP_BASE - self::BOOTSTRAP_TMIN ) * self::BOOTSTRAP_TMAX / 2 );

		while ( $delta > $max ) {
			$delta = floor( $delta / ( self::BOOTSTRAP_BASE - self::BOOTSTRAP_TMIN ) );
			$k += self::BOOTSTRAP_BASE;
		}

		return $k + floor( ( self::BOOTSTRAP_BASE - self::BOOTSTRAP_TMIN + 1 ) * $delta / ( $delta + self::BOOTSTRAP_SKEW ) );
	}
}
