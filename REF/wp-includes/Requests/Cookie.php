<?php
/**
 * Cookie storage object
 *
 * @package    Requests
 * @subpackage Cookies
 */

/**
 * Cookie storage object.
 *
 * @package    Requests
 * @subpackage Cookies
 */
class Requests_Cookie
{
	/**
	 * Cookie name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Cookie value.
	 *
	 * @var string
	 */
	public $value;

	/**
	 * Cookie attributes.
	 *
	 * Valid keys are (currently) path, domain, expires, max-age, secure and httponly.
	 *
	 * @var Requests_Utility_CaseInsensitiveDictionary|array Array-like object.
	 */
	public $attributes = array();

	/**
	 * Cookie flags.
	 *
	 * Vaoid keys are (currently) creation, last-access, persistent and host-only.
	 *
	 * @var array
	 */
	public $flags = array();

	/**
	 * Reference time for relative calculations.
	 *
	 * This is used in place of `time()` when calculating Max-Age expiration and checking time validity.
	 *
	 * @var int
	 */
	public $reference_time = 0;

	/**
	 * Create a new cookie object.
	 *
	 * @param string                                           $name
	 * @param string                                           $value
	 * @param array|Requests_Utility_CaseInsensitiveDictionary $attributes     Associative array of attribute data.
	 * @param array                                            $flags
	 * @param int                                              $reference_time
	 */
	public function __construct( $name, $value, $attributes = array(), $flags = array(), $reference_time = NULL )
	{
		$this->name = $name;
		$this->value = $value;
		$this->attributes = $attributes;
		$default_flags = array(
			'creation'    => time(),
			'last-access' => time(),
			'persistent'  => FALSE,
			'host-only'   => TRUE
		);
		$this->flags = array_merge( $default_flags, $flags );
		$this->reference_time = time();

		if ( $reference_time !== NULL ) {
			$this->reference_time = $reference_time;
		}

		$this->normalize();
	}

	/**
	 * Check if a cookie is expired.
	 *
	 * Checks the age against $this->reference_time to determine if the cookie is expired.
	 *
	 * @return bool True if expired, false if time is valid.
	 */
	public function is_expired()
	{
		/**
		 * RFC6265, s. 4.1.2.2:
		 * If a cookie has both the Max-Age and the Expires attribute, the Max-Age attribute has precedence and controls the expiration date of the cookie.
		 */
		if ( isset( $this->attributes['max-age'] ) ) {
			$max_age = $this->attributes['max-age'];
			return $max_age < $this->reference_time;
		}

		if ( isset( $this->attributes['expires'] ) ) {
			$expires = $this->attributes['expires'];
			return $expires < $this->reference_time;
		}

		return FALSE;
	}

	/**
	 * Check if a cookie is valid for a given domain.
	 *
	 * @param  string $string Domain to check.
	 * @return bool   Whether the cookie is valid for the given domain.
	 */
	public function domain_matches( $string )
	{
		if ( ! isset( $this->attributes['domain'] ) ) {
			// Cookies created manually; cookies created by Requests will set the domain to the requested domain.
			return TRUE;
		}

		$domain_string = $this->attributes['domain'];

		if ( $domain_string === $string ) {
			// The domain string and the string are identical.
			return TRUE;
		}

		// If the cookie is marked as host-only and we don't have an exact match, reject the cookie.
		if ( $this->flags['host-only'] === TRUE ) {
			return FALSE;
		}

		if ( strlen( $string ) <= strlen( $domain_string ) ) {
			// For obvious reasons, the string cannot be a suffix if the domain is shorter than the domain string.
			return FALSE;
		}

		if ( substr( $string, -1 * strlen( $domain_string ) ) !== $domain_string ) {
			// The domain string should be a suffix of the string.
			return FALSE;
		}

		$prefix = substr( $string, 0, strlen( $string ) - strlen( $domain_string ) );

		if ( substr( $prefix, -1 ) !== '.' ) {
			// The last character of the string that is not included in the domain string should be a %x2E (".") character.
			return FALSE;
		}

		// The string should be a host name (i.e., not an IP address).
		return ! preg_match( '#^(.+\.)\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $string );
	}

	/**
	 * Normalize cookie and attributes.
	 *
	 * @return bool Whether the cookie was successfully normalized.
	 */
	public function normalize()
	{
		foreach ( $this->attributes as $key => $value ) {
			$orig_value = $value;
			$value = $this->normalize_attribute( $key, $value );

			if ( $value === NULL ) {
				unset( $this->attributes[ $key ] );
				continue;
			}

			if ( $value !== $orig_value ) {
				$this->attributes[ $key ] = $value;
			}
		}

		return TRUE;
	}

	/**
	 * Parse an individual cookie attribute.
	 *
	 * Handles parsing individual attributes from the cookie values.
	 *
	 * @param  string      $name  Attribute name.
	 * @param  string|bool $value Attribute value (string value, or true if empty/flag).
	 * @return mixed       Value if available, or null if the attribute value is invalid (and should be skipped).
	 */
	protected function normalize_attribute( $name, $value )
	{
		switch ( strtolower( $name ) ) {
			case 'expires':
				// Expiration parsing, as per RFC 6265 section 5.2.1
				if ( is_int( $value ) ) {
					return $value;
				}

				$expiry_time = strtotime( $value );

				if ( $expiry_time === FALSE ) {
					return NULL;
				}

				return $expiry_time;

			case 'max-age':
				// Expiration parsing, as per RFC 6265 section 5.2.2
				if ( is_int( $value ) ) {
					return $value;
				}

				// Check that we have a valid age.
				if ( ! preg_match( '/^-?\d+$/', $value ) ) {
					return NULL;
				}

				$delta_seconds = ( int ) $value;

				$expiry_time = $delta_seconds <= 0
					? 0
					: $this->reference_time + $delta_seconds;

				return $expiry_time;

			case 'domain':
				// Domain normalization, as per RFC 6265 section 5.2.3
				if ( $value[0] === '.' ) {
					$value = substr( $value, 1 );
				}

				return $value;

			default:
				return $value;
		}
	}

	/**
	 * Parse a cookie string into a cookie object.
	 *
	 * Based on Mozilla's parsing code in Firefox and related projects, which is an intentional deviation from RFC 2109 and RFC 2616.
	 * RFC 6265 specifies some of this handling, but not in a thorough manner.
	 *
	 * @param  string          $string Cookie header value (from a Set-Cookie header)
	 * @param  string          $name
	 * @param  int             $reference_time
	 * @return Requests_Cookie Parsed cookie object.
	 */
	public static function parse( $string, $name = '', $reference_time = NULL )
	{
		$parts = explode( ';', $string );
		$kvparts = array_shift( $parts );

		if ( ! empty( $name ) ) {
			$value = $string;
		} elseif ( strpos( $kvparts, '=' ) === FALSE ) {
			/**
			 * Some sites might only have a value without the equals separator.
			 * Deviate from RFC 6265 and pretend it was actually a blank name (`=foo`).
			 *
			 * https://bugzilla.mozilla.org/show_bug.cgi?id=169091
			 */
			$name = '';
			$value = $kvparts;
		} else {
			list( $name, $value ) = explode( '=', $kvparts, 2 );
		}

		$name = trim( $name );
		$value = trim( $value );

		// Attribute key are handled case-insensitively.
		$attributes = new Requests_Utility_CaseInsensitiveDictionary();

		if ( ! empty( $parts ) ) {
			foreach ( $parts as $part ) {
				if ( strpos( $part, '=' ) === FALSE ) {
					$part_key = $part;
					$part_value = TRUE;
				} else {
					list( $part_key, $part_value ) = explode( '=', $part, 2 );
					$part_value = trim( $part_value );
				}

				$part_key = trim( $part_key );
				$attributes[ $part_key ] = $part_value;
			}
		}

		return new Requests_Cookie( $name, $value, $attributes, array(), $reference_time );
	}
}
