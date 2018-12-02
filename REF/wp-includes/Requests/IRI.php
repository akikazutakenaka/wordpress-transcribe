<?php
/**
 * IRI parser/serialiser/normaliser
 *
 * @package    Requests
 * @subpackage Utilities
 */

/**
 * IRI parser/serialiser/normaliser.
 *
 * Copyright (C) 2007-2010, Geoffrey Sneddon and Steve Minutillo.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * * Neither the name of the SimplePie Team nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Requests
 * @subpackage Utilities
 * @author     Geoffrey Sneddon
 * @author     Steve Minutillo
 * @copyright  2007-2009 Geoffrey Sneddon and Steve Minutillo
 * @license    http://www.opensource.org/licenses/bsd-license.php
 * @link       http://hg.gsnedders.com/iri/
 */
class Requests_IRI
{
	/**
	 * Scheme part of the IRI.
	 *
	 * @var string
	 */
	protected $scheme = NULL;

	/**
	 * Userinfo part of the IRI (after '://' and before '@').
	 *
	 * @var string
	 */
	protected $iuserinfo = NULL;

	/**
	 * Host part of the IRI.
	 *
	 * @var string
	 */
	protected $ihost = NULL;

	/**
	 * Port part of the IRI (after ':').
	 *
	 * @var string
	 */
	protected $port = NULL;

	/**
	 * Path part of the IRI (after first '/').
	 *
	 * @var string
	 */
	protected $ipath = '';

	/**
	 * Query part of the IRI (after '?').
	 *
	 * @var string
	 */
	protected $iquery = NULL;

	/**
	 * Fragment part of the IRI (after '#').
	 *
	 * @var string
	 */
	protected $ifragment = NULL;

	/**
	 * Normalization database.
	 *
	 * Each key is the scheme, each value is an array with each key as the IRI part and value as the default value for that part.
	 *
	 * @var array
	 */
	protected $normalization = array(
		'acap'  => array( 'port' => 674 ),
		'dict'  => array( 'port' => 2628 ),
		'file'  => array( 'ihost' => 'localhost' ),
		'http'  => array( 'port' => 80 ),
		'https' => array( 'port' => 443 )
	);

	/**
	 * Create a new IRI object, from a specified string.
	 *
	 * @param string|null $iri
	 */
	public function __construct( $iri = NULL )
	{
		$this->set_iri( $iri );
	}

	/**
	 * Create a new IRI object by resolving a relative IRI.
	 *
	 * Returns false if $base is not absolute, otherwise an IRI.
	 *
	 * @param  IRI|string $base     (Absolute) Base IRI.
	 * @param  IRI|string $relative Relative IRI.
	 * @return IRI|false
	 */
	public static function absolutize( $base, $relative )
	{
		if ( ! ( $relative instanceof Requests_IRI ) ) {
			$relative = new Requests_IRI( $relative );
		}

		if ( ! $relative->is_valid() ) {
			return FALSE;
		} elseif ( $relative->scheme !== NULL ) {
			return clone $relative;
		}

		if ( ! ( $base instanceof Requests_IRI ) ) {
			$base = new Requests_IRI( $base );
		}

		if ( $base->scheme === NULL || ! $base->is_valid() ) {
			return FALSE;
		}

		if ( $relative->get_iri() !== '' ) {
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
 * <-......: wp-includes/class-requests.php: Requests::parse_response( string $headers, string $url, array $req_headers, array $req_data, array $options )
 * @NOW 014: wp-includes/Requests/IRI.php: Requests_IRI::absolutize( IRI|string $base, IRI|string $relative )
 */
		}
	}

	/**
	 * Parse an IRI into scheme/authority/path/query/fragment segments.
	 *
	 * @param  string $iri
	 * @return array
	 */
	protected function parse_iri( $iri )
	{
		$iri = trim( $iri, "\x20\x09\x0A\x0C\x0D" );
		$has_match = preg_match( '/^((?P<scheme>[^:\/?#]+):)?(\/\/(?P<authority>[^\/?#]*))?(?P<path>[^?#]*)(\?(?P<query>[^#]*))?(#(?P<fragment>.*))?$/', $iri, $match );

		if ( ! $has_match ) {
			throw new Requests_Exception( 'Cannot parse supplied IRI', 'iri.cannot_parse', $iri );
		}

		if ( $match[1] === '' ) {
			$match['scheme'] = NULL;
		}

		if ( ! isset( $match[3] ) || $match[3] === '' ) {
			$match['authority'] = NULL;
		}

		if ( ! isset( $match[5] ) ) {
			$match['path'] = '';
		}

		if ( ! isset( $match[6] ) || $match[6] === '' ) {
			$match['query'] = NULL;
		}

		if ( ! isset( $match[8] ) || $match[8] === '' ) {
			$match['fragment'] = NULL;
		}

		return $match;
	}

	/**
	 * Remove dot segments from a path.
	 *
	 * @param  string $input
	 * @return string
	 */
	protected function remove_dot_segments( $input )
	{
		$output = '';

		while ( strpos( $input, './' ) !== FALSE || strpos( $input, '/.' ) !== FALSE || $input === '.' || $input === '..' ) {
			if ( strpos( $input, '../' ) === 0 ) {
				// A: If the input buffer begins with a prefix of "../" or "./", then remove that prefix from the input buffer.
				$input = substr( $input, 3 );
			} elseif ( strpos( $input, './' ) === 0 ) {
				$input = substr( $input, 2 );
			} elseif ( strpos( $input, '/./' ) === 0 ) {
				// B: If the input buffer begins with a prefix of "/./" or "/.", where "." is a complete path segment, then replace that prefix with "/" in the input buffer.
				$input = substr( $input, 2 );
			} elseif ( $input === '/.' ) {
				$input = '/';
			} elseif ( strpos( $input, '/../' ) === 0 ) {
				// C: If the input buffer begins with a prefix of "/../" or "/..", where ".." is a complete path segment, then replace that prefix with "/" in the input buffer and remove the last segment and its preceding "/" (if any) from the output buffer.
				$input = substr( $input, 3 );
				$output = substr_replace( $output, '', strrpos( $output, '/' ) );
			} elseif ( $input === '/..' ) {
				$input = '/';
				$output = substr_replace( $output, '', strrpos( $output, '/' ) );
			} elseif ( $input === '.' || $input === '..' ) {
				// D: If the input buffer consists only of "." or "..", then remove that from the input buffer.
				$input = '';
			} elseif ( ( $pos = strpos( $input, '/', 1 ) ) !== FALSE ) {
				// E: Move the first path segment in the input buffer to the end of the output buffer, including the initial "/" character (if any) and any subsequent characters up to, but not including, the next "/" character or the end of the input buffer.
				$output .= substr( $input, 0, $pos );
				$input = substr_replace( $input, '', 0, $pos );
			} else {
				$output .= $input;
				$input = '';
			}
		}

		return $output . $input;
	}

	/**
	 * Replace invalid character with percent encoding.
	 *
	 * @param  string $string      Input string.
	 * @param  string $extra_chars Valid characters not in iunreserved or iprivate (this is ASCII-only).
	 * @param  bool   $iprivate    Allow iprivate.
	 * @return string
	 */
	protected function replace_invalid_with_pct_encoding( $string, $extra_chars, $private = FALSE )
	{
		// Normalize as many pct-encoded sections as possible.
		$string = preg_replace_callback( '/(?:%[A-Fa-f0-9]{2})+/', array( &$this, 'remove_iunreserved_percent_encoded' ), $string );

		// Replace invalid percent characters.
		$string = preg_replace( '/%(?![A-Fa-f0-9]{2})/', '%25', $string );

		// Add unreserved and % to $extra_chars (the latter is safe because all pct-encoded sections are now valid).
		$extra_chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~%';

		// Now replace any bytes that aren't allowed with their pct-encoded versions.
		$position = 0;
		$strlen = strlen( $string );

		while ( ( $position += strspn( $string, $extra_chars, $position ) ) < $strlen ) {
			$value = ord( $string[ $position ] );

			// Start position.
			$start = $position;

			// By default we are valid.
			$valid = TRUE;

			// No one byte sequences are valid due to the while.
			if ( ( $value & 0xE0 ) === 0xC0 ) {
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
				$remaining = 2;
			} else {
				// Invalid byte:
				$valid = FALSE;
				$length = 1;
				$remaining = 0;
			}

			if ( $remaining ) {
				if ( $position + $length <= $strlen ) {
					for ( $position++; $remaining; $position++ ) {
						$value = ord( $string[ $position ] );

						if ( ( $value & 0xC0 ) === 0x80 ) {
							// Check that the byte is valid, then add it to the character:
							$character |= ( $value & 0x3F ) << ( --$remaining * 6 );
						} else {
							// If it is invalid, count the sequence as invalid and reprocess the current byte:
							$valid = FALSE;
							$position--;
							break;
						}
					}
				} else {
					$position = $strlen - 1;
					$valid = FALSE;
				}
			}

			// Percent encode anything invalid or not in ucschar.
			if ( ! $valid
			  || $length > 1 && $character <= 0x7F
			  || $length > 2 && $character <= 0x7FF
			  || $length > 3 && $character <= 0xFFFF
			  || ( $character & 0xFFFE ) === 0xFFFE
			  || $character >= 0xFDD0 && $character < 0xFDEF
			  || ( $character > 0xD7FF && $character < 0xF900
			    || $character < 0xA0
			    || $character > 0xEFFFD )
			  && ( ! $iprivate || $character < 0xE000 || $character > 0x10FFFD ) ) {
				// If we were a character, pretend we weren't, but rather an error.
				if ( $valid ) {
					$position--;
				}

				for ( $j = $start; $j <= $position; $j++ ) {
					$string = substr_replace( $string, sprintf( '%%%02X', ord( $string[ $j ] ) ), $j, 1 );
					$j += 2;
					$position += 2;
					$strlen += 2;
				}
			}
		}

		return $string;
	}

	/**
	 * Callback function for preg_replace_callback.
	 *
	 * Removes sequences of percent encoded bytes that represent UTF-8 encoded characters in iunreserved.
	 *
	 * @param  array  $match PCRE match.
	 * @return string Replacement.
	 */
	protected function remove_iunreserved_percent_encoded( $match )
	{
		// As we just have valid percent encoded sequences we can just explode and ignore the first member of the returned array (an empty string).
		$bytes = explode( '%', $match[0] );

		// Initialize the new string (this is what will be returned) and that there are no bytes remaining in the current sequence (unsurprising at the first byte!).
		$string = '';
		$remaining = 0;

		// Loop over each and every byte, and set $value to its value.
		for ( $i = 1, $len = count( $bytes ); $i < $len; $i++ ) {
			$value = hexdec( $bytes[ $i ] );

			if ( ! $remaining ) {
				// If we're the first byte of sequence:

				// Start position.
				$start = $i;

				// By default we are valid.
				$valid = TRUE;

				if ( $value <= 0x7F ) {
					// One byte sequence:
					$character = $value;
					$length = 1;
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
					$valid = FALSE;
					$remaining = 0;
				}
			} else {
				// Continuation byte:
				if ( ( $value & 0xC0 ) === 0x80 ) {
					// Check that the byte is valid, then add it to the character:
					$remaining--;
					$character |= ( $value & 0x3F ) << ( $remaining * 6 );
				} else {
					// If it is invalid, count the sequence as invalid and reprocess the current byte as the start of a sequence:
					$valid = FALSE;
					$remaining = 0;
					$i--;
				}
			}

			// If we've reached the end of the current byte sequence, append it to Unicode::$data.
			if ( ! $remaining ) {
				// Percent encode anything invalid or not in iunreserved.
				if ( ! $valid
				  || $length > 1 && $character <= 0x7F
				  || $length > 2 && $character <= 0x7FF
				  || $length > 3 && $character <= 0xFFFF
				  || $character < 0x2D
				  || $character > 0xEFFFD
				  || ( $character & 0xFFFE ) === 0xFFFE
				  || $character >= 0xFDD0 && $character <= 0xFDEF
				  || $character === 0x2F
				  || $character > 0x39 && $character < 0x41
				  || $character > 0x5A && $character < 0x61
				  || $character > 0x7A && $character < 0x7E
				  || $character > 0x7E && $character < 0xA0
				  || $character > 0xD7FF && $character < 0xF900 ) {
					for ( $j = $start; $j <= $i; $j++ ) {
						$string .= '%' . strtoupper( $bytes[$j] );
					}
				} else {
					for ( $j = $start; $j <= $i; $j++ ) {
						$string .= chr( hexdec( $bytes[ $j ] ) );
					}
				}
			}
		}

		// If we have any bytes left over they are invalid (i.e., we are mid-way through a multi-byte sequence):
		if ( $remaining ) {
			for ( $j = $start; $j < $len; $j++ ) {
				$string .= '%' . strtoupper( $bytes[ $j ] );
			}
		}

		return $string;
	}

	protected function scheme_normalization()
	{
		if ( isset( $this->normalization[ $this->scheme ]['iuserinfo'] ) && $this->iuserinfo === $this->normalization[ $this->scheme ]['iuserinfo'] ) {
			$this->iuserinfo = NULL;
		}

		if ( isset( $this->normalization[ $this->scheme ]['ihost'] ) && $this->ihost === $this->normalization[ $this->scheme ]['ihost'] ) {
			$this->ihost = NULL;
		}

		if ( isset( $this->normalization[ $this->scheme ]['port'] ) && $this->port === $this->normalization[ $this->scheme ]['port'] ) {
			$this->port = NULL;
		}

		if ( isset( $this->normalization[ $this->scheme ]['ipath'] ) && $this->ipath === $this->normalization[ $this->scheme ]['ipath'] ) {
			$this->ipath = '';
		}

		if ( isset( $this->ihost ) && empty( $this->ipath ) ) {
			$this->ipath = '/';
		}

		if ( isset( $this->normalization[ $this->scheme ]['iquery'] ) && $this->iquery === $this->normalization[ $this->scheme ]['iquery'] ) {
			$this->iquery = NULL;
		}

		if ( isset( $this->normalization[ $this->scheme ]['ifragment'] ) && $this->ifragment === $this->normalization[ $this->scheme ]['ifragment'] ) {
			$this->ifragment = NULL;
		}
	}

	/**
	 * Check if the object represents a valid IRI.
	 * This needs to be done on each call as some things change depending on another part of the IRI.
	 *
	 * @return bool
	 */
	public function is_valid()
	{
		return ( $this->ipath !== ''
		      && ( $isauthority && $this->ipath[0] !== '/'
		        || $this->scheme === NULL
		        && ! $is_authority
		        && strpos( $this->ipath, ':' ) !== FALSE
		        && ( strpos( $this->ipath, '/' ) === FALSE
		        	? TRUE
		        	: strpos( $this->ipath, ':' ) < strpos( $this->ipath, '/' ) ) ) )
			? FALSE
			: TRUE;
	}

	/**
	 * Set the entire IRI.
	 * Returns true on success, false on failure (if there are any invalid characters).
	 *
	 * @param  string $iri
	 * @return bool
	 */
	protected function set_iri( $iri )
	{
		static $cache;

		if ( ! $cache ) {
			$cache = array();
		}

		if ( $iri === NULL ) {
			return TRUE;
		}

		if ( isset( $cache[ $iri ] ) ) {
			list( $this->scheme, $this->iuserinfo, $this->ihost, $this->port, $this->ipath, $this->iquery, $this->ifragment, $return ) = $cache[ $iri ];
			return $return;
		}

		$parsed = $this->parse_iri( ( string ) $iri );
		$return = $this->set_scheme( $parsed['scheme'] ) && $this->set_authority( $parsed['authority'] ) && $this->set_path( $parsed['path'] ) && $this->set_query( $parsed['query'] ) && $this->set_fragment( $parsed['fragment'] );
		$cache[ $iri ] = array( $this->scheme, $this->iuserinfo, $this->ihost, $this->port, $this->ipath, $this->iquery, $this->ifragment, $return );
		return $return;
	}

	/**
	 * Set the scheme.
	 * Returns true on success, false on failure (if there are any invalid characters).
	 *
	 * @param  string $scheme
	 * @return bool
	 */
	protected function set_scheme( $scheme )
	{
		if ( $scheme === NULL ) {
			$this->scheme = NULL;
		} elseif ( ! preg_match( '/^[A-Za-z][0-9A-Za-z+\-.]*$/', $scheme ) ) {
			$this->scheme = NULL;
			return FALSE;
		} else {
			$this->scheme = strtolower( $scheme );
		}

		return TRUE;
	}

	/**
	 * Set the authority.
	 * Returns true on success, false on failure (if there are any invalid characters).
	 *
	 * @param  string $authority
	 * @return bool
	 */
	protected function set_authority( $authority )
	{
		static $cache;

		if ( ! $cache ) {
			$cache = array();
		}

		if ( $authority === NULL ) {
			$this->iuserinfo = NULL;
			$this->ihost = NULL;
			$this->port = NULL;
			return TRUE;
		}

		if ( isset( $cache[ $authority ] ) ) {
			list( $this->iuserinfo, $this->ihost, $this->port, $return ) = $cache[ $authority ];
			return $return;
		}

		$remaining = $authority;

		if ( ( $iuserinfo_end = strrpos( $remaining, '@' ) ) !== FALSE ) {
			$iuserinfo = substr( $remaining, 0, $iuserinfo_end );
			$remaining = substr( $remaining, $iuserinfo_end + 1 );
		} else {
			$iuserinfo = NULL;
		}

		if ( ( $port_start = strpos( $remaining, ':', strpos( $remaining, ']' ) ) ) !== FALSE ) {
			$port = substr( $remaining, $port_start + 1 );

			if ( $port === FALSE || $port === '' ) {
				$port = NULL;
			}

			$remaining = substr( $remaining, 0, $port_start );
		} else {
			$port = NULL;
		}

		$return = $this->set_userinfo( $iuserinfo ) && $this->set_host( $remaining ) && $this->set_port( $port );
		$cache[ $authority ] = array( $this->iuserinfo, $this->ihost, $this->port, $return );
		return $return;
	}

	/**
	 * Set the iuserinfo.
	 *
	 * @param  string $iuserinfo
	 * @return bool
	 */
	protected function set_userinfo( $iuserinfo )
	{
		if ( $iuserinfo === NULL ) {
			$this->iuserinfo = NULL;
		} else {
			$this->iuserinfo = $this->replace_invalid_with_pct_encoding( $iuserinfo, '!$&\'()*+,;=:' );
			$this->scheme_normalization();
		}

		return TRUE;
	}

	/**
	 * Set the ihost.
	 * Returns true on success, false on failure (if there are any invalid characters).
	 *
	 * @param  string $ihost
	 * @return bool
	 */
	protected function set_host( $ihost )
	{
		if ( $ihost === NULL ) {
			$this->ihost = NULL;
			return TRUE;
		}

		if ( substr( $ihost, 0, 1 ) === '[' && substr( $ihost, -1 ) === ']' ) {
			if ( Requests_IPv6::check_ipv6( substr( $ihost, 1, -1 ) ) ) {
				$this->ihost = '[' . Requests_IPv6::compress( substr( $ihost, 1, -1 ) ) . ']';
			} else {
				$this->ihost = NULL;
				return FALSE;
			}
		} else {
			$ihost = $this->replace_invalid_with_pct_encoding( $ihost, '!$&\'()*+,;=' );

			/**
			 * Lowercase, but ignore pct-encoded sections (as they should remain uppercase).
			 * This must be done after the previous step as that can add unescaped characters.
			 */
			$position = 0;
			$strlen = strlen( $ihost );

			while ( ( $position += strcspn( $ihost, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ%', $position ) ) < $strlen ) {
				if ( $ihost[ $position ] === '%' ) {
					$position += 3;
				} else {
					$ihost[ $position ] = strtolower( $ihost[ $position ] );
					$position++;
				}
			}

			$this->ihost = $ihost;
		}

		$this->scheme_normalization();
		return TRUE;
	}

	/**
	 * Set the port.
	 * Returns true on success, false on failure (if there are any invalid characters).
	 *
	 * @param  string $port
	 * @return bool
	 */
	protected function set_port( $port )
	{
		if ( $port === NULL ) {
			$this->port = NULL;
			return TRUE;
		}

		if ( strspn( $port, '0123456789' ) === strlen( $port ) ) {
			$this->port = ( int ) $port;
			$this->scheme_normalization();
			return TRUE;
		}

		$this->port = NULL;
		return FALSE;
	}

	/**
	 * Set the ipath.
	 *
	 * @param  string $ipath
	 * @return bool
	 */
	protected function set_path( $ipath )
	{
		static $cache;

		if ( ! $cache ) {
			$cache = array();
		}

		$ipath = ( string ) $ipath;

		if ( isset( $cache[ $ipath ] ) ) {
			$this->ipath = $cache[ $ipath ][ ( int ) ( $this->scheme !== NULL ) ];
		} else {
			$valid = $this->replace_invalid_with_pct_encoding( $ipath, '!$&\'()*+,;=@:/' );
			$removed = $this->remove_dot_segments( $valid );
			$cache[ $ipath ] = array( $valid, $removed );

			$this->ipath = $this->scheme !== NULL
				? $removed
				: $valid;
		}

		$this->scheme_normalization();
		return TRUE;
	}

	/**
	 * Set the iquery.
	 *
	 * @param  string $iquery
	 * @return bool
	 */
	protected function set_query( $iquery )
	{
		if ( $iquery === NULL ) {
			$this->iquery = NULL;
		} else {
			$this->iquery = $this->replace_invalid_with_pct_encoding( $iquery, '!$&\'()*+,;=:@/?', TRUE );
			$this->scheme_normalization();
		}

		return TRUE;
	}

	/**
	 * Set the ifragment.
	 *
	 * @param  string $ifragment.
	 * @return bool
	 */
	protected function set_fragment( $ifragment )
	{
		if ( $ifragment === NULL ) {
			$this->ifragment = NULL;
		} else {
			$this->ifragment = $this->replace_invalid_with_pct_encoding( $ifragment, '!$&\'()*+,;=:@/?' );
			$this->scheme_normalization();
		}

		return TRUE;
	}

	/**
	 * Get the complete IRI.
	 *
	 * @return string
	 */
	protected function get_iri()
	{
		if ( ! $this->is_valid() ) {
			return FALSE;
		}

		$iri = '';

		if ( $this->scheme !== NULL ) {
			$iri .= $this->scheme . ':';
		}

		if ( ( $iauthority = $this->get_iauthority() ) !== NULL ) {
			$iri .= '//' . $iauthority;
		}

		$iri .= $this->ipath;

		if ( $this->iquery !== NULL ) {
			$iri .= '?' . $this->iquery;
		}

		if ( $this->ifragment !== NULL ) {
			$iri .= '#' . $this->ifragment;
		}

		return $iri;
	}

	/**
	 * Get the complete iauthority.
	 *
	 * @return string
	 */
	protected function get_iauthority()
	{
		if ( $this->iuserinfo === NULL && $this->ihost === NULL && $this->port === NULL ) {
			return NULL;
		}

		$iauthority = '';

		if ( $this->iuserinfo !== NULL ) {
			$iauthority .= $this->iuserinfo . '@';
		}

		if ( $this->ihost !== NULL ) {
			$iauthority .= $this->ihost;
		}

		if ( $this->port !== NULL ) {
			$iauthority .= ':' . $this->port;
		}

		return $iauthority;
	}
}
