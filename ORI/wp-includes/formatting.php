<?php
/**
 * Main WordPress Formatting API.
 *
 * Handles many functions for formatting output.
 *
 * @package WordPress
 */

// refactored. function wptexturize( $text, $reset = false ) {}
// :
// refactored. function _wp_specialchars( $string, $quote_style = ENT_NOQUOTES, $charset = false, $double_encode = false ) {}

/**
 * Converts a number of HTML entities into their special characters.
 *
 * Specifically deals with: &, <, >, ", and '.
 *
 * $quote_style can be set to ENT_COMPAT to decode " entities,
 * or ENT_QUOTES to do both " and '. Default is ENT_NOQUOTES where no quotes are decoded.
 *
 * @since 2.8.0
 *
 * @param string     $string The text which is to be decoded.
 * @param string|int $quote_style Optional. Converts double quotes if set to ENT_COMPAT,
 *                                both single and double if set to ENT_QUOTES or
 *                                none if set to ENT_NOQUOTES.
 *                                Also compatible with old _wp_specialchars() values;
 *                                converting single quotes if set to 'single',
 *                                double if set to 'double' or both if otherwise set.
 *                                Default is ENT_NOQUOTES.
 * @return string The decoded text without HTML entities.
 */
function wp_specialchars_decode( $string, $quote_style = ENT_NOQUOTES ) {
	$string = (string) $string;

	if ( 0 === strlen( $string ) ) {
		return '';
	}

	// Don't bother if there are no entities - saves a lot of processing
	if ( strpos( $string, '&' ) === false ) {
		return $string;
	}

	// Match the previous behaviour of _wp_specialchars() when the $quote_style is not an accepted value
	if ( empty( $quote_style ) ) {
		$quote_style = ENT_NOQUOTES;
	} elseif ( !in_array( $quote_style, array( 0, 2, 3, 'single', 'double' ), true ) ) {
		$quote_style = ENT_QUOTES;
	}

	// More complete than get_html_translation_table( HTML_SPECIALCHARS )
	$single = array( '&#039;'  => '\'', '&#x27;' => '\'' );
	$single_preg = array( '/&#0*39;/'  => '&#039;', '/&#x0*27;/i' => '&#x27;' );
	$double = array( '&quot;' => '"', '&#034;'  => '"', '&#x22;' => '"' );
	$double_preg = array( '/&#0*34;/'  => '&#034;', '/&#x0*22;/i' => '&#x22;' );
	$others = array( '&lt;'   => '<', '&#060;'  => '<', '&gt;'   => '>', '&#062;'  => '>', '&amp;'  => '&', '&#038;'  => '&', '&#x26;' => '&' );
	$others_preg = array( '/&#0*60;/'  => '&#060;', '/&#0*62;/'  => '&#062;', '/&#0*38;/'  => '&#038;', '/&#x0*26;/i' => '&#x26;' );

	if ( $quote_style === ENT_QUOTES ) {
		$translation = array_merge( $single, $double, $others );
		$translation_preg = array_merge( $single_preg, $double_preg, $others_preg );
	} elseif ( $quote_style === ENT_COMPAT || $quote_style === 'double' ) {
		$translation = array_merge( $double, $others );
		$translation_preg = array_merge( $double_preg, $others_preg );
	} elseif ( $quote_style === 'single' ) {
		$translation = array_merge( $single, $others );
		$translation_preg = array_merge( $single_preg, $others_preg );
	} elseif ( $quote_style === ENT_NOQUOTES ) {
		$translation = $others;
		$translation_preg = $others_preg;
	}

	// Remove zero padding on numeric entities
	$string = preg_replace( array_keys( $translation_preg ), array_values( $translation_preg ), $string );

	// Replace characters according to translation table
	return strtr( $string, $translation );
}

// refactored. function wp_check_invalid_utf8( $string, $strip = false ) {}

/**
 * Encode the Unicode values to be used in the URI.
 *
 * @since 1.5.0
 *
 * @param string $utf8_string
 * @param int    $length Max  length of the string
 * @return string String with Unicode encoded for URI.
 */
function utf8_uri_encode( $utf8_string, $length = 0 ) {
	$unicode = '';
	$values = array();
	$num_octets = 1;
	$unicode_length = 0;

	mbstring_binary_safe_encoding();
	$string_length = strlen( $utf8_string );
	reset_mbstring_encoding();

	for ($i = 0; $i < $string_length; $i++ ) {

		$value = ord( $utf8_string[ $i ] );

		if ( $value < 128 ) {
			if ( $length && ( $unicode_length >= $length ) )
				break;
			$unicode .= chr($value);
			$unicode_length++;
		} else {
			if ( count( $values ) == 0 ) {
				if ( $value < 224 ) {
					$num_octets = 2;
				} elseif ( $value < 240 ) {
					$num_octets = 3;
				} else {
					$num_octets = 4;
				}
			}

			$values[] = $value;

			if ( $length && ( $unicode_length + ($num_octets * 3) ) > $length )
				break;
			if ( count( $values ) == $num_octets ) {
				for ( $j = 0; $j < $num_octets; $j++ ) {
					$unicode .= '%' . dechex( $values[ $j ] );
				}

				$unicode_length += $num_octets * 3;

				$values = array();
				$num_octets = 1;
			}
		}
	}

	return $unicode;
}

// refactored. function remove_accents( $string ) {}

/**
 * Sanitizes a filename, replacing whitespace with dashes.
 *
 * Removes special characters that are illegal in filenames on certain
 * operating systems and special characters requiring special escaping
 * to manipulate at the command line. Replaces spaces and consecutive
 * dashes with a single dash. Trims period, dash and underscore from beginning
 * and end of filename. It is not guaranteed that this function will return a
 * filename that is allowed to be uploaded.
 *
 * @since 2.1.0
 *
 * @param string $filename The filename to be sanitized
 * @return string The sanitized filename
 */
function sanitize_file_name( $filename ) {
	$filename_raw = $filename;
	$special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}", "%", "+", chr(0));
	/**
	 * Filters the list of characters to remove from a filename.
	 *
	 * @since 2.8.0
	 *
	 * @param array  $special_chars Characters to remove.
	 * @param string $filename_raw  Filename as it was passed into sanitize_file_name().
	 */
	$special_chars = apply_filters( 'sanitize_file_name_chars', $special_chars, $filename_raw );
	$filename = preg_replace( "#\x{00a0}#siu", ' ', $filename );
	$filename = str_replace( $special_chars, '', $filename );
	$filename = str_replace( array( '%20', '+' ), '-', $filename );
	$filename = preg_replace( '/[\r\n\t -]+/', '-', $filename );
	$filename = trim( $filename, '.-_' );

	if ( false === strpos( $filename, '.' ) ) {
		$mime_types = wp_get_mime_types();
		$filetype = wp_check_filetype( 'test.' . $filename, $mime_types );
		if ( $filetype['ext'] === $filename ) {
			$filename = 'unnamed-file.' . $filetype['ext'];
		}
	}

	// Split the filename into a base and extension[s]
	$parts = explode('.', $filename);

	// Return if only one extension
	if ( count( $parts ) <= 2 ) {
		/**
		 * Filters a sanitized filename string.
		 *
		 * @since 2.8.0
		 *
		 * @param string $filename     Sanitized filename.
		 * @param string $filename_raw The filename prior to sanitization.
		 */
		return apply_filters( 'sanitize_file_name', $filename, $filename_raw );
	}

	// Process multiple extensions
	$filename = array_shift($parts);
	$extension = array_pop($parts);
	$mimes = get_allowed_mime_types();

	/*
	 * Loop over any intermediate extensions. Postfix them with a trailing underscore
	 * if they are a 2 - 5 character long alpha string not in the extension whitelist.
	 */
	foreach ( (array) $parts as $part) {
		$filename .= '.' . $part;

		if ( preg_match("/^[a-zA-Z]{2,5}\d?$/", $part) ) {
			$allowed = false;
			foreach ( $mimes as $ext_preg => $mime_match ) {
				$ext_preg = '!^(' . $ext_preg . ')$!i';
				if ( preg_match( $ext_preg, $part ) ) {
					$allowed = true;
					break;
				}
			}
			if ( !$allowed )
				$filename .= '_';
		}
	}
	$filename .= '.' . $extension;
	/** This filter is documented in wp-includes/formatting.php */
	return apply_filters('sanitize_file_name', $filename, $filename_raw);
}

// refactored. function sanitize_user( $username, $strict = false ) {}
// :
// refactored. function sanitize_title( $title, $fallback_title = '', $context = 'save' ) {}

/**
 * Sanitizes a title with the 'query' context.
 *
 * Used for querying the database for a value from URL.
 *
 * @since 3.1.0
 *
 * @param string $title The string to be sanitized.
 * @return string The sanitized string.
 */
function sanitize_title_for_query( $title ) {
	return sanitize_title( $title, '', 'query' );
}

/**
 * Sanitizes a title, replacing whitespace and a few other characters with dashes.
 *
 * Limits the output to alphanumeric characters, underscore (_) and dash (-).
 * Whitespace becomes a dash.
 *
 * @since 1.2.0
 *
 * @param string $title     The title to be sanitized.
 * @param string $raw_title Optional. Not used.
 * @param string $context   Optional. The operation for which the string is sanitized.
 * @return string The sanitized title.
 */
function sanitize_title_with_dashes( $title, $raw_title = '', $context = 'display' ) {
	$title = strip_tags($title);
	// Preserve escaped octets.
	$title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
	// Remove percent signs that are not part of an octet.
	$title = str_replace('%', '', $title);
	// Restore octets.
	$title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);

	if (seems_utf8($title)) {
		if (function_exists('mb_strtolower')) {
			$title = mb_strtolower($title, 'UTF-8');
		}
		$title = utf8_uri_encode($title, 200);
	}

	$title = strtolower($title);

	if ( 'save' == $context ) {
		// Convert nbsp, ndash and mdash to hyphens
		$title = str_replace( array( '%c2%a0', '%e2%80%93', '%e2%80%94' ), '-', $title );
		// Convert nbsp, ndash and mdash HTML entities to hyphens
		$title = str_replace( array( '&nbsp;', '&#160;', '&ndash;', '&#8211;', '&mdash;', '&#8212;' ), '-', $title );
		// Convert forward slash to hyphen
		$title = str_replace( '/', '-', $title );

		// Strip these characters entirely
		$title = str_replace( array(
			// iexcl and iquest
			'%c2%a1', '%c2%bf',
			// angle quotes
			'%c2%ab', '%c2%bb', '%e2%80%b9', '%e2%80%ba',
			// curly quotes
			'%e2%80%98', '%e2%80%99', '%e2%80%9c', '%e2%80%9d',
			'%e2%80%9a', '%e2%80%9b', '%e2%80%9e', '%e2%80%9f',
			// copy, reg, deg, hellip and trade
			'%c2%a9', '%c2%ae', '%c2%b0', '%e2%80%a6', '%e2%84%a2',
			// acute accents
			'%c2%b4', '%cb%8a', '%cc%81', '%cd%81',
			// grave accent, macron, caron
			'%cc%80', '%cc%84', '%cc%8c',
		), '', $title );

		// Convert times to x
		$title = str_replace( '%c3%97', 'x', $title );
	}

	$title = preg_replace('/&.+?;/', '', $title); // kill entities
	$title = str_replace('.', '-', $title);

	$title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
	$title = preg_replace('/\s+/', '-', $title);
	$title = preg_replace('|-+|', '-', $title);
	$title = trim($title, '-');

	return $title;
}

/**
 * Ensures a string is a valid SQL 'order by' clause.
 *
 * Accepts one or more columns, with or without a sort order (ASC / DESC).
 * e.g. 'column_1', 'column_1, column_2', 'column_1 ASC, column_2 DESC' etc.
 *
 * Also accepts 'RAND()'.
 *
 * @since 2.5.1
 *
 * @param string $orderby Order by clause to be validated.
 * @return string|false Returns $orderby if valid, false otherwise.
 */
function sanitize_sql_orderby( $orderby ) {
	if ( preg_match( '/^\s*(([a-z0-9_]+|`[a-z0-9_]+`)(\s+(ASC|DESC))?\s*(,\s*(?=[a-z0-9_`])|$))+$/i', $orderby ) || preg_match( '/^\s*RAND\(\s*\)\s*$/i', $orderby ) ) {
		return $orderby;
	}
	return false;
}

/**
 * Sanitizes an HTML classname to ensure it only contains valid characters.
 *
 * Strips the string down to A-Z,a-z,0-9,_,-. If this results in an empty
 * string then it will return the alternative value supplied.
 *
 * @todo Expand to support the full range of CDATA that a class attribute can contain.
 *
 * @since 2.8.0
 *
 * @param string $class    The classname to be sanitized
 * @param string $fallback Optional. The value to return if the sanitization ends up as an empty string.
 * 	Defaults to an empty string.
 * @return string The sanitized value
 */
function sanitize_html_class( $class, $fallback = '' ) {
	//Strip out any % encoded octets
	$sanitized = preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', $class );

	//Limit to A-Z,a-z,0-9,_,-
	$sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', $sanitized );

	if ( '' == $sanitized && $fallback ) {
		return sanitize_html_class( $fallback );
	}
	/**
	 * Filters a sanitized HTML class string.
	 *
	 * @since 2.8.0
	 *
	 * @param string $sanitized The sanitized HTML class.
	 * @param string $class     HTML class before sanitization.
	 * @param string $fallback  The fallback string.
	 */
	return apply_filters( 'sanitize_html_class', $sanitized, $class, $fallback );
}

// refactored. function convert_chars( $content, $deprecated = '' ) {}
// :
// refactored. function force_balance_tags( $text ) {}

/**
 * Acts on text which is about to be edited.
 *
 * The $content is run through esc_textarea(), which uses htmlspecialchars()
 * to convert special characters to HTML entities. If `$richedit` is set to true,
 * it is simply a holder for the {@see 'format_to_edit'} filter.
 *
 * @since 0.71
 * @since 4.4.0 The `$richedit` parameter was renamed to `$rich_text` for clarity.
 *
 * @param string $content   The text about to be edited.
 * @param bool   $rich_text Optional. Whether `$content` should be considered rich text,
 *                          in which case it would not be passed through esc_textarea().
 *                          Default false.
 * @return string The text after the filter (and possibly htmlspecialchars()) has been run.
 */
function format_to_edit( $content, $rich_text = false ) {
	/**
	 * Filters the text to be formatted for editing.
	 *
	 * @since 1.2.0
	 *
	 * @param string $content The text, prior to formatting for editing.
	 */
	$content = apply_filters( 'format_to_edit', $content );
	if ( ! $rich_text )
		$content = esc_textarea( $content );
	return $content;
}

/**
 * Add leading zeros when necessary.
 *
 * If you set the threshold to '4' and the number is '10', then you will get
 * back '0010'. If you set the threshold to '4' and the number is '5000', then you
 * will get back '5000'.
 *
 * Uses sprintf to append the amount of zeros based on the $threshold parameter
 * and the size of the number. If the number is large enough, then no zeros will
 * be appended.
 *
 * @since 0.71
 *
 * @param int $number     Number to append zeros to if not greater than threshold.
 * @param int $threshold  Digit places number needs to be to not have zeros added.
 * @return string Adds leading zeros to number if needed.
 */
function zeroise( $number, $threshold ) {
	return sprintf( '%0' . $threshold . 's', $number );
}

/**
 * Adds backslashes before letters and before a number at the start of a string.
 *
 * @since 0.71
 *
 * @param string $string Value to which backslashes will be added.
 * @return string String with backslashes inserted.
 */
function backslashit( $string ) {
	if ( isset( $string[0] ) && $string[0] >= '0' && $string[0] <= '9' )
		$string = '\\\\' . $string;
	return addcslashes( $string, 'A..Za..z' );
}

// refactored. function trailingslashit( $string ) {}
// refactored. function untrailingslashit( $string ) {}

/**
 * Adds slashes to escape strings.
 *
 * Slashes will first be removed if magic_quotes_gpc is set, see {@link
 * https://secure.php.net/magic_quotes} for more details.
 *
 * @since 0.71
 *
 * @param string $gpc The string returned from HTTP request data.
 * @return string Returns a string escaped with slashes.
 */
function addslashes_gpc($gpc) {
	if ( get_magic_quotes_gpc() )
		$gpc = stripslashes($gpc);

	return wp_slash($gpc);
}

// refactored. function stripslashes_deep( $value ) {}
// refactored. function stripslashes_from_strings_only( $value ) {}

/**
 * Navigates through an array, object, or scalar, and encodes the values to be used in a URL.
 *
 * @since 2.2.0
 *
 * @param mixed $value The array or string to be encoded.
 * @return mixed $value The encoded value.
 */
function urlencode_deep( $value ) {
	return map_deep( $value, 'urlencode' );
}

/**
 * Navigates through an array, object, or scalar, and raw-encodes the values to be used in a URL.
 *
 * @since 3.4.0
 *
 * @param mixed $value The array or string to be encoded.
 * @return mixed $value The encoded value.
 */
function rawurlencode_deep( $value ) {
	return map_deep( $value, 'rawurlencode' );
}

/**
 * Navigates through an array, object, or scalar, and decodes URL-encoded values
 *
 * @since 4.4.0
 *
 * @param mixed $value The array or string to be decoded.
 * @return mixed $value The decoded value.
 */
function urldecode_deep( $value ) {
	return map_deep( $value, 'urldecode' );
}

/**
 * Converts email addresses characters to HTML entities to block spam bots.
 *
 * @since 0.71
 *
 * @param string $email_address Email address.
 * @param int    $hex_encoding  Optional. Set to 1 to enable hex encoding.
 * @return string Converted email address.
 */
function antispambot( $email_address, $hex_encoding = 0 ) {
	$email_no_spam_address = '';
	for ( $i = 0, $len = strlen( $email_address ); $i < $len; $i++ ) {
		$j = rand( 0, 1 + $hex_encoding );
		if ( $j == 0 ) {
			$email_no_spam_address .= '&#' . ord( $email_address[$i] ) . ';';
		} elseif ( $j == 1 ) {
			$email_no_spam_address .= $email_address[$i];
		} elseif ( $j == 2 ) {
			$email_no_spam_address .= '%' . zeroise( dechex( ord( $email_address[$i] ) ), 2 );
		}
	}

	return str_replace( '@', '&#64;', $email_no_spam_address );
}

/**
 * Callback to convert URI match to HTML A element.
 *
 * This function was backported from 2.5.0 to 2.3.2. Regex callback for make_clickable().
 *
 * @since 2.3.2
 * @access private
 *
 * @param array $matches Single Regex Match.
 * @return string HTML A element with URI address.
 */
function _make_url_clickable_cb( $matches ) {
	$url = $matches[2];

	if ( ')' == $matches[3] && strpos( $url, '(' ) ) {
		// If the trailing character is a closing parethesis, and the URL has an opening parenthesis in it, add the closing parenthesis to the URL.
		// Then we can let the parenthesis balancer do its thing below.
		$url .= $matches[3];
		$suffix = '';
	} else {
		$suffix = $matches[3];
	}

	// Include parentheses in the URL only if paired
	while ( substr_count( $url, '(' ) < substr_count( $url, ')' ) ) {
		$suffix = strrchr( $url, ')' ) . $suffix;
		$url = substr( $url, 0, strrpos( $url, ')' ) );
	}

	$url = esc_url($url);
	if ( empty($url) )
		return $matches[0];

	return $matches[1] . "<a href=\"$url\" rel=\"nofollow\">$url</a>" . $suffix;
}

/**
 * Callback to convert URL match to HTML A element.
 *
 * This function was backported from 2.5.0 to 2.3.2. Regex callback for make_clickable().
 *
 * @since 2.3.2
 * @access private
 *
 * @param array $matches Single Regex Match.
 * @return string HTML A element with URL address.
 */
function _make_web_ftp_clickable_cb( $matches ) {
	$ret = '';
	$dest = $matches[2];
	$dest = 'http://' . $dest;

	// removed trailing [.,;:)] from URL
	if ( in_array( substr($dest, -1), array('.', ',', ';', ':', ')') ) === true ) {
		$ret = substr($dest, -1);
		$dest = substr($dest, 0, strlen($dest)-1);
	}

	$dest = esc_url($dest);
	if ( empty($dest) )
		return $matches[0];

	return $matches[1] . "<a href=\"$dest\" rel=\"nofollow\">$dest</a>$ret";
}

/**
 * Callback to convert email address match to HTML A element.
 *
 * This function was backported from 2.5.0 to 2.3.2. Regex callback for make_clickable().
 *
 * @since 2.3.2
 * @access private
 *
 * @param array $matches Single Regex Match.
 * @return string HTML A element with email address.
 */
function _make_email_clickable_cb( $matches ) {
	$email = $matches[2] . '@' . $matches[3];
	return $matches[1] . "<a href=\"mailto:$email\">$email</a>";
}

/**
 * Convert plaintext URI to HTML links.
 *
 * Converts URI, www and ftp, and email addresses. Finishes by fixing links
 * within links.
 *
 * @since 0.71
 *
 * @param string $text Content to convert URIs.
 * @return string Content with converted URIs.
 */
function make_clickable( $text ) {
	$r = '';
	$textarr = preg_split( '/(<[^<>]+>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE ); // split out HTML tags
	$nested_code_pre = 0; // Keep track of how many levels link is nested inside <pre> or <code>
	foreach ( $textarr as $piece ) {

		if ( preg_match( '|^<code[\s>]|i', $piece ) || preg_match( '|^<pre[\s>]|i', $piece ) || preg_match( '|^<script[\s>]|i', $piece ) || preg_match( '|^<style[\s>]|i', $piece ) )
			$nested_code_pre++;
		elseif ( $nested_code_pre && ( '</code>' === strtolower( $piece ) || '</pre>' === strtolower( $piece ) || '</script>' === strtolower( $piece ) || '</style>' === strtolower( $piece ) ) )
			$nested_code_pre--;

		if ( $nested_code_pre || empty( $piece ) || ( $piece[0] === '<' && ! preg_match( '|^<\s*[\w]{1,20}+://|', $piece ) ) ) {
			$r .= $piece;
			continue;
		}

		// Long strings might contain expensive edge cases ...
		if ( 10000 < strlen( $piece ) ) {
			// ... break it up
			foreach ( _split_str_by_whitespace( $piece, 2100 ) as $chunk ) { // 2100: Extra room for scheme and leading and trailing paretheses
				if ( 2101 < strlen( $chunk ) ) {
					$r .= $chunk; // Too big, no whitespace: bail.
				} else {
					$r .= make_clickable( $chunk );
				}
			}
		} else {
			$ret = " $piece "; // Pad with whitespace to simplify the regexes

			$url_clickable = '~
				([\\s(<.,;:!?])                                        # 1: Leading whitespace, or punctuation
				(                                                      # 2: URL
					[\\w]{1,20}+://                                # Scheme and hier-part prefix
					(?=\S{1,2000}\s)                               # Limit to URLs less than about 2000 characters long
					[\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]*+         # Non-punctuation URL character
					(?:                                            # Unroll the Loop: Only allow puctuation URL character if followed by a non-punctuation URL character
						[\'.,;:!?)]                            # Punctuation URL character
						[\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]++ # Non-punctuation URL character
					)*
				)
				(\)?)                                                  # 3: Trailing closing parenthesis (for parethesis balancing post processing)
			~xS'; // The regex is a non-anchored pattern and does not have a single fixed starting character.
			      // Tell PCRE to spend more time optimizing since, when used on a page load, it will probably be used several times.

			$ret = preg_replace_callback( $url_clickable, '_make_url_clickable_cb', $ret );

			$ret = preg_replace_callback( '#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]+)#is', '_make_web_ftp_clickable_cb', $ret );
			$ret = preg_replace_callback( '#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', '_make_email_clickable_cb', $ret );

			$ret = substr( $ret, 1, -1 ); // Remove our whitespace padding.
			$r .= $ret;
		}
	}

	// Cleanup of accidental links within links
	return preg_replace( '#(<a([ \r\n\t]+[^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i', "$1$3</a>", $r );
}

/**
 * Breaks a string into chunks by splitting at whitespace characters.
 * The length of each returned chunk is as close to the specified length goal as possible,
 * with the caveat that each chunk includes its trailing delimiter.
 * Chunks longer than the goal are guaranteed to not have any inner whitespace.
 *
 * Joining the returned chunks with empty delimiters reconstructs the input string losslessly.
 *
 * Input string must have no null characters (or eventual transformations on output chunks must not care about null characters)
 *
 *     _split_str_by_whitespace( "1234 67890 1234 67890a cd 1234   890 123456789 1234567890a    45678   1 3 5 7 90 ", 10 ) ==
 *     array (
 *         0 => '1234 67890 ',  // 11 characters: Perfect split
 *         1 => '1234 ',        //  5 characters: '1234 67890a' was too long
 *         2 => '67890a cd ',   // 10 characters: '67890a cd 1234' was too long
 *         3 => '1234   890 ',  // 11 characters: Perfect split
 *         4 => '123456789 ',   // 10 characters: '123456789 1234567890a' was too long
 *         5 => '1234567890a ', // 12 characters: Too long, but no inner whitespace on which to split
 *         6 => '   45678   ',  // 11 characters: Perfect split
 *         7 => '1 3 5 7 90 ',  // 11 characters: End of $string
 *     );
 *
 * @since 3.4.0
 * @access private
 *
 * @param string $string The string to split.
 * @param int    $goal   The desired chunk length.
 * @return array Numeric array of chunks.
 */
function _split_str_by_whitespace( $string, $goal ) {
	$chunks = array();

	$string_nullspace = strtr( $string, "\r\n\t\v\f ", "\000\000\000\000\000\000" );

	while ( $goal < strlen( $string_nullspace ) ) {
		$pos = strrpos( substr( $string_nullspace, 0, $goal + 1 ), "\000" );

		if ( false === $pos ) {
			$pos = strpos( $string_nullspace, "\000", $goal + 1 );
			if ( false === $pos ) {
				break;
			}
		}

		$chunks[] = substr( $string, 0, $pos + 1 );
		$string = substr( $string, $pos + 1 );
		$string_nullspace = substr( $string_nullspace, $pos + 1 );
	}

	if ( $string ) {
		$chunks[] = $string;
	}

	return $chunks;
}

/**
 * Adds rel nofollow string to all HTML A elements in content.
 *
 * @since 1.5.0
 *
 * @param string $text Content that may contain HTML A elements.
 * @return string Converted content.
 */
function wp_rel_nofollow( $text ) {
	// This is a pre save filter, so text is already escaped.
	$text = stripslashes($text);
	$text = preg_replace_callback('|<a (.+?)>|i', 'wp_rel_nofollow_callback', $text);
	return wp_slash( $text );
}

/**
 * Callback to add rel=nofollow string to HTML A element.
 *
 * Will remove already existing rel="nofollow" and rel='nofollow' from the
 * string to prevent from invalidating (X)HTML.
 *
 * @since 2.3.0
 *
 * @param array $matches Single Match
 * @return string HTML A Element with rel nofollow.
 */
function wp_rel_nofollow_callback( $matches ) {
	$text = $matches[1];
	$atts = shortcode_parse_atts( $matches[1] );
	$rel  = 'nofollow';

	if ( preg_match( '%href=["\'](' . preg_quote( set_url_scheme( home_url(), 'http' ) ) . ')%i', $text ) ||
	     preg_match( '%href=["\'](' . preg_quote( set_url_scheme( home_url(), 'https' ) ) . ')%i', $text )
	) {
		return "<a $text>";
	}

	if ( ! empty( $atts['rel'] ) ) {
		$parts = array_map( 'trim', explode( ' ', $atts['rel'] ) );
		if ( false === array_search( 'nofollow', $parts ) ) {
			$parts[] = 'nofollow';
		}
		$rel = implode( ' ', $parts );
		unset( $atts['rel'] );

		$html = '';
		foreach ( $atts as $name => $value ) {
			$html .= "{$name}=\"$value\" ";
		}
		$text = trim( $html );
	}
	return "<a $text rel=\"$rel\">";
}

/**
 * Convert one smiley code to the icon graphic file equivalent.
 *
 * Callback handler for convert_smilies().
 *
 * Looks up one smiley code in the $wpsmiliestrans global array and returns an
 * `<img>` string for that smiley.
 *
 * @since 2.8.0
 *
 * @global array $wpsmiliestrans
 *
 * @param array $matches Single match. Smiley code to convert to image.
 * @return string Image string for smiley.
 */
function translate_smiley( $matches ) {
	global $wpsmiliestrans;

	if ( count( $matches ) == 0 )
		return '';

	$smiley = trim( reset( $matches ) );
	$img = $wpsmiliestrans[ $smiley ];

	$matches = array();
	$ext = preg_match( '/\.([^.]+)$/', $img, $matches ) ? strtolower( $matches[1] ) : false;
	$image_exts = array( 'jpg', 'jpeg', 'jpe', 'gif', 'png' );

	// Don't convert smilies that aren't images - they're probably emoji.
	if ( ! in_array( $ext, $image_exts ) ) {
		return $img;
	}

	/**
	 * Filters the Smiley image URL before it's used in the image element.
	 *
	 * @since 2.9.0
	 *
	 * @param string $smiley_url URL for the smiley image.
	 * @param string $img        Filename for the smiley image.
	 * @param string $site_url   Site URL, as returned by site_url().
	 */
	$src_url = apply_filters( 'smilies_src', includes_url( "images/smilies/$img" ), $img, site_url() );

	return sprintf( '<img src="%s" alt="%s" class="wp-smiley" style="height: 1em; max-height: 1em;" />', esc_url( $src_url ), esc_attr( $smiley ) );
}

/**
 * Convert text equivalent of smilies to images.
 *
 * Will only convert smilies if the option 'use_smilies' is true and the global
 * used in the function isn't empty.
 *
 * @since 0.71
 *
 * @global string|array $wp_smiliessearch
 *
 * @param string $text Content to convert smilies from text.
 * @return string Converted content with text smilies replaced with images.
 */
function convert_smilies( $text ) {
	global $wp_smiliessearch;
	$output = '';
	if ( get_option( 'use_smilies' ) && ! empty( $wp_smiliessearch ) ) {
		// HTML loop taken from texturize function, could possible be consolidated
		$textarr = preg_split( '/(<.*>)/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE ); // capture the tags as well as in between
		$stop = count( $textarr );// loop stuff

		// Ignore proessing of specific tags
		$tags_to_ignore = 'code|pre|style|script|textarea';
		$ignore_block_element = '';

		for ( $i = 0; $i < $stop; $i++ ) {
			$content = $textarr[$i];

			// If we're in an ignore block, wait until we find its closing tag
			if ( '' == $ignore_block_element && preg_match( '/^<(' . $tags_to_ignore . ')>/', $content, $matches ) )  {
				$ignore_block_element = $matches[1];
			}

			// If it's not a tag and not in ignore block
			if ( '' ==  $ignore_block_element && strlen( $content ) > 0 && '<' != $content[0] ) {
				$content = preg_replace_callback( $wp_smiliessearch, 'translate_smiley', $content );
			}

			// did we exit ignore block
			if ( '' != $ignore_block_element && '</' . $ignore_block_element . '>' == $content )  {
				$ignore_block_element = '';
			}

			$output .= $content;
		}
	} else {
		// return default text.
		$output = $text;
	}
	return $output;
}

// refactored. function is_email( $email, $deprecated = false ) {}

/**
 * Convert to ASCII from email subjects.
 *
 * @since 1.2.0
 *
 * @param string $string Subject line
 * @return string Converted string to ASCII
 */
function wp_iso_descrambler( $string ) {
	/* this may only work with iso-8859-1, I'm afraid */
	if (!preg_match('#\=\?(.+)\?Q\?(.+)\?\=#i', $string, $matches)) {
		return $string;
	} else {
		$subject = str_replace('_', ' ', $matches[2]);
		return preg_replace_callback( '#\=([0-9a-f]{2})#i', '_wp_iso_convert', $subject );
	}
}

/**
 * Helper function to convert hex encoded chars to ASCII
 *
 * @since 3.1.0
 * @access private
 *
 * @param array $match The preg_replace_callback matches array
 * @return string Converted chars
 */
function _wp_iso_convert( $match ) {
	return chr( hexdec( strtolower( $match[1] ) ) );
}

/**
 * Returns a date in the GMT equivalent.
 *
 * Requires and returns a date in the Y-m-d H:i:s format. If there is a
 * timezone_string available, the date is assumed to be in that timezone,
 * otherwise it simply subtracts the value of the 'gmt_offset' option. Return
 * format can be overridden using the $format parameter.
 *
 * @since 1.2.0
 *
 * @param string $string The date to be converted.
 * @param string $format The format string for the returned date (default is Y-m-d H:i:s)
 * @return string GMT version of the date provided.
 */
function get_gmt_from_date( $string, $format = 'Y-m-d H:i:s' ) {
	$tz = get_option( 'timezone_string' );
	if ( $tz ) {
		$datetime = date_create( $string, new DateTimeZone( $tz ) );
		if ( ! $datetime ) {
			return gmdate( $format, 0 );
		}
		$datetime->setTimezone( new DateTimeZone( 'UTC' ) );
		$string_gmt = $datetime->format( $format );
	} else {
		if ( ! preg_match( '#([0-9]{1,4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})#', $string, $matches ) ) {
			$datetime = strtotime( $string );
			if ( false === $datetime ) {
				return gmdate( $format, 0 );
			}
			return gmdate( $format, $datetime );
		}
		$string_time = gmmktime( $matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1] );
		$string_gmt = gmdate( $format, $string_time - get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
	}
	return $string_gmt;
}

/**
 * Converts a GMT date into the correct format for the blog.
 *
 * Requires and returns a date in the Y-m-d H:i:s format. If there is a
 * timezone_string available, the returned date is in that timezone, otherwise
 * it simply adds the value of gmt_offset. Return format can be overridden
 * using the $format parameter
 *
 * @since 1.2.0
 *
 * @param string $string The date to be converted.
 * @param string $format The format string for the returned date (default is Y-m-d H:i:s)
 * @return string Formatted date relative to the timezone / GMT offset.
 */
function get_date_from_gmt( $string, $format = 'Y-m-d H:i:s' ) {
	$tz = get_option( 'timezone_string' );
	if ( $tz ) {
		$datetime = date_create( $string, new DateTimeZone( 'UTC' ) );
		if ( ! $datetime )
			return date( $format, 0 );
		$datetime->setTimezone( new DateTimeZone( $tz ) );
		$string_localtime = $datetime->format( $format );
	} else {
		if ( ! preg_match('#([0-9]{1,4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})#', $string, $matches) )
			return date( $format, 0 );
		$string_time = gmmktime( $matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1] );
		$string_localtime = gmdate( $format, $string_time + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
	}
	return $string_localtime;
}

/**
 * Computes an offset in seconds from an iso8601 timezone.
 *
 * @since 1.5.0
 *
 * @param string $timezone Either 'Z' for 0 offset or '±hhmm'.
 * @return int|float The offset in seconds.
 */
function iso8601_timezone_to_offset( $timezone ) {
	// $timezone is either 'Z' or '[+|-]hhmm'
	if ($timezone == 'Z') {
		$offset = 0;
	} else {
		$sign    = (substr($timezone, 0, 1) == '+') ? 1 : -1;
		$hours   = intval(substr($timezone, 1, 2));
		$minutes = intval(substr($timezone, 3, 4)) / 60;
		$offset  = $sign * HOUR_IN_SECONDS * ($hours + $minutes);
	}
	return $offset;
}

/**
 * Converts an iso8601 date to MySQL DateTime format used by post_date[_gmt].
 *
 * @since 1.5.0
 *
 * @param string $date_string Date and time in ISO 8601 format {@link https://en.wikipedia.org/wiki/ISO_8601}.
 * @param string $timezone    Optional. If set to GMT returns the time minus gmt_offset. Default is 'user'.
 * @return string The date and time in MySQL DateTime format - Y-m-d H:i:s.
 */
function iso8601_to_datetime( $date_string, $timezone = 'user' ) {
	$timezone = strtolower($timezone);

	if ($timezone == 'gmt') {

		preg_match('#([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})(Z|[\+|\-][0-9]{2,4}){0,1}#', $date_string, $date_bits);

		if (!empty($date_bits[7])) { // we have a timezone, so let's compute an offset
			$offset = iso8601_timezone_to_offset($date_bits[7]);
		} else { // we don't have a timezone, so we assume user local timezone (not server's!)
			$offset = HOUR_IN_SECONDS * get_option('gmt_offset');
		}

		$timestamp = gmmktime($date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1]);
		$timestamp -= $offset;

		return gmdate('Y-m-d H:i:s', $timestamp);

	} elseif ($timezone == 'user') {
		return preg_replace('#([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})(Z|[\+|\-][0-9]{2,4}){0,1}#', '$1-$2-$3 $4:$5:$6', $date_string);
	}
}

// refactored. function sanitize_email( $email ) {}

/**
 * Determines the difference between two timestamps.
 *
 * The difference is returned in a human readable format such as "1 hour",
 * "5 mins", "2 days".
 *
 * @since 1.5.0
 *
 * @param int $from Unix timestamp from which the difference begins.
 * @param int $to   Optional. Unix timestamp to end the time difference. Default becomes time() if not set.
 * @return string Human readable time difference.
 */
function human_time_diff( $from, $to = '' ) {
	if ( empty( $to ) ) {
		$to = time();
	}

	$diff = (int) abs( $to - $from );

	if ( $diff < HOUR_IN_SECONDS ) {
		$mins = round( $diff / MINUTE_IN_SECONDS );
		if ( $mins <= 1 )
			$mins = 1;
		/* translators: Time difference between two dates, in minutes (min=minute). 1: Number of minutes */
		$since = sprintf( _n( '%s min', '%s mins', $mins ), $mins );
	} elseif ( $diff < DAY_IN_SECONDS && $diff >= HOUR_IN_SECONDS ) {
		$hours = round( $diff / HOUR_IN_SECONDS );
		if ( $hours <= 1 )
			$hours = 1;
		/* translators: Time difference between two dates, in hours. 1: Number of hours */
		$since = sprintf( _n( '%s hour', '%s hours', $hours ), $hours );
	} elseif ( $diff < WEEK_IN_SECONDS && $diff >= DAY_IN_SECONDS ) {
		$days = round( $diff / DAY_IN_SECONDS );
		if ( $days <= 1 )
			$days = 1;
		/* translators: Time difference between two dates, in days. 1: Number of days */
		$since = sprintf( _n( '%s day', '%s days', $days ), $days );
	} elseif ( $diff < MONTH_IN_SECONDS && $diff >= WEEK_IN_SECONDS ) {
		$weeks = round( $diff / WEEK_IN_SECONDS );
		if ( $weeks <= 1 )
			$weeks = 1;
		/* translators: Time difference between two dates, in weeks. 1: Number of weeks */
		$since = sprintf( _n( '%s week', '%s weeks', $weeks ), $weeks );
	} elseif ( $diff < YEAR_IN_SECONDS && $diff >= MONTH_IN_SECONDS ) {
		$months = round( $diff / MONTH_IN_SECONDS );
		if ( $months <= 1 )
			$months = 1;
		/* translators: Time difference between two dates, in months. 1: Number of months */
		$since = sprintf( _n( '%s month', '%s months', $months ), $months );
	} elseif ( $diff >= YEAR_IN_SECONDS ) {
		$years = round( $diff / YEAR_IN_SECONDS );
		if ( $years <= 1 )
			$years = 1;
		/* translators: Time difference between two dates, in years. 1: Number of years */
		$since = sprintf( _n( '%s year', '%s years', $years ), $years );
	}

	/**
	 * Filters the human readable difference between two timestamps.
	 *
	 * @since 4.0.0
	 *
	 * @param string $since The difference in human readable text.
	 * @param int    $diff  The difference in seconds.
	 * @param int    $from  Unix timestamp from which the difference begins.
	 * @param int    $to    Unix timestamp to end the time difference.
	 */
	return apply_filters( 'human_time_diff', $since, $diff, $from, $to );
}

/**
 * Generates an excerpt from the content, if needed.
 *
 * The excerpt word amount will be 55 words and if the amount is greater than
 * that, then the string ' [&hellip;]' will be appended to the excerpt. If the string
 * is less than 55 words, then the content will be returned as is.
 *
 * The 55 word limit can be modified by plugins/themes using the {@see 'excerpt_length'} filter
 * The ' [&hellip;]' string can be modified by plugins/themes using the {@see 'excerpt_more'} filter
 *
 * @since 1.5.0
 *
 * @param string $text Optional. The excerpt. If set to empty, an excerpt is generated.
 * @return string The excerpt.
 */
function wp_trim_excerpt( $text = '' ) {
	$raw_excerpt = $text;
	if ( '' == $text ) {
		$text = get_the_content('');

		$text = strip_shortcodes( $text );

		/** This filter is documented in wp-includes/post-template.php */
		$text = apply_filters( 'the_content', $text );
		$text = str_replace(']]>', ']]&gt;', $text);

		/**
		 * Filters the number of words in an excerpt.
		 *
		 * @since 2.7.0
		 *
		 * @param int $number The number of words. Default 55.
		 */
		$excerpt_length = apply_filters( 'excerpt_length', 55 );
		/**
		 * Filters the string in the "more" link displayed after a trimmed excerpt.
		 *
		 * @since 2.9.0
		 *
		 * @param string $more_string The string shown within the more link.
		 */
		$excerpt_more = apply_filters( 'excerpt_more', ' ' . '[&hellip;]' );
		$text = wp_trim_words( $text, $excerpt_length, $excerpt_more );
	}
	/**
	 * Filters the trimmed excerpt string.
	 *
	 * @since 2.8.0
	 *
	 * @param string $text        The trimmed text.
	 * @param string $raw_excerpt The text prior to trimming.
	 */
	return apply_filters( 'wp_trim_excerpt', $text, $raw_excerpt );
}

/**
 * Trims text to a certain number of words.
 *
 * This function is localized. For languages that count 'words' by the individual
 * character (such as East Asian languages), the $num_words argument will apply
 * to the number of individual characters.
 *
 * @since 3.3.0
 *
 * @param string $text      Text to trim.
 * @param int    $num_words Number of words. Default 55.
 * @param string $more      Optional. What to append if $text needs to be trimmed. Default '&hellip;'.
 * @return string Trimmed text.
 */
function wp_trim_words( $text, $num_words = 55, $more = null ) {
	if ( null === $more ) {
		$more = __( '&hellip;' );
	}

	$original_text = $text;
	$text = wp_strip_all_tags( $text );

	/*
	 * translators: If your word count is based on single characters (e.g. East Asian characters),
	 * enter 'characters_excluding_spaces' or 'characters_including_spaces'. Otherwise, enter 'words'.
	 * Do not translate into your own language.
	 */
	if ( strpos( _x( 'words', 'Word count type. Do not translate!' ), 'characters' ) === 0 && preg_match( '/^utf\-?8$/i', get_option( 'blog_charset' ) ) ) {
		$text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $text ), ' ' );
		preg_match_all( '/./u', $text, $words_array );
		$words_array = array_slice( $words_array[0], 0, $num_words + 1 );
		$sep = '';
	} else {
		$words_array = preg_split( "/[\n\r\t ]+/", $text, $num_words + 1, PREG_SPLIT_NO_EMPTY );
		$sep = ' ';
	}

	if ( count( $words_array ) > $num_words ) {
		array_pop( $words_array );
		$text = implode( $sep, $words_array );
		$text = $text . $more;
	} else {
		$text = implode( $sep, $words_array );
	}

	/**
	 * Filters the text content after words have been trimmed.
	 *
	 * @since 3.3.0
	 *
	 * @param string $text          The trimmed text.
	 * @param int    $num_words     The number of words to trim the text to. Default 55.
	 * @param string $more          An optional string to append to the end of the trimmed text, e.g. &hellip;.
	 * @param string $original_text The text before it was trimmed.
	 */
	return apply_filters( 'wp_trim_words', $text, $num_words, $more, $original_text );
}

/**
 * Converts named entities into numbered entities.
 *
 * @since 1.5.1
 *
 * @param string $text The text within which entities will be converted.
 * @return string Text with converted entities.
 */
function ent2ncr( $text ) {

	/**
	 * Filters text before named entities are converted into numbered entities.
	 *
	 * A non-null string must be returned for the filter to be evaluated.
	 *
	 * @since 3.3.0
	 *
	 * @param null   $converted_text The text to be converted. Default null.
	 * @param string $text           The text prior to entity conversion.
	 */
	$filtered = apply_filters( 'pre_ent2ncr', null, $text );
	if ( null !== $filtered )
		return $filtered;

	$to_ncr = array(
		'&quot;' => '&#34;',
		'&amp;' => '&#38;',
		'&lt;' => '&#60;',
		'&gt;' => '&#62;',
		'|' => '&#124;',
		'&nbsp;' => '&#160;',
		'&iexcl;' => '&#161;',
		'&cent;' => '&#162;',
		'&pound;' => '&#163;',
		'&curren;' => '&#164;',
		'&yen;' => '&#165;',
		'&brvbar;' => '&#166;',
		'&brkbar;' => '&#166;',
		'&sect;' => '&#167;',
		'&uml;' => '&#168;',
		'&die;' => '&#168;',
		'&copy;' => '&#169;',
		'&ordf;' => '&#170;',
		'&laquo;' => '&#171;',
		'&not;' => '&#172;',
		'&shy;' => '&#173;',
		'&reg;' => '&#174;',
		'&macr;' => '&#175;',
		'&hibar;' => '&#175;',
		'&deg;' => '&#176;',
		'&plusmn;' => '&#177;',
		'&sup2;' => '&#178;',
		'&sup3;' => '&#179;',
		'&acute;' => '&#180;',
		'&micro;' => '&#181;',
		'&para;' => '&#182;',
		'&middot;' => '&#183;',
		'&cedil;' => '&#184;',
		'&sup1;' => '&#185;',
		'&ordm;' => '&#186;',
		'&raquo;' => '&#187;',
		'&frac14;' => '&#188;',
		'&frac12;' => '&#189;',
		'&frac34;' => '&#190;',
		'&iquest;' => '&#191;',
		'&Agrave;' => '&#192;',
		'&Aacute;' => '&#193;',
		'&Acirc;' => '&#194;',
		'&Atilde;' => '&#195;',
		'&Auml;' => '&#196;',
		'&Aring;' => '&#197;',
		'&AElig;' => '&#198;',
		'&Ccedil;' => '&#199;',
		'&Egrave;' => '&#200;',
		'&Eacute;' => '&#201;',
		'&Ecirc;' => '&#202;',
		'&Euml;' => '&#203;',
		'&Igrave;' => '&#204;',
		'&Iacute;' => '&#205;',
		'&Icirc;' => '&#206;',
		'&Iuml;' => '&#207;',
		'&ETH;' => '&#208;',
		'&Ntilde;' => '&#209;',
		'&Ograve;' => '&#210;',
		'&Oacute;' => '&#211;',
		'&Ocirc;' => '&#212;',
		'&Otilde;' => '&#213;',
		'&Ouml;' => '&#214;',
		'&times;' => '&#215;',
		'&Oslash;' => '&#216;',
		'&Ugrave;' => '&#217;',
		'&Uacute;' => '&#218;',
		'&Ucirc;' => '&#219;',
		'&Uuml;' => '&#220;',
		'&Yacute;' => '&#221;',
		'&THORN;' => '&#222;',
		'&szlig;' => '&#223;',
		'&agrave;' => '&#224;',
		'&aacute;' => '&#225;',
		'&acirc;' => '&#226;',
		'&atilde;' => '&#227;',
		'&auml;' => '&#228;',
		'&aring;' => '&#229;',
		'&aelig;' => '&#230;',
		'&ccedil;' => '&#231;',
		'&egrave;' => '&#232;',
		'&eacute;' => '&#233;',
		'&ecirc;' => '&#234;',
		'&euml;' => '&#235;',
		'&igrave;' => '&#236;',
		'&iacute;' => '&#237;',
		'&icirc;' => '&#238;',
		'&iuml;' => '&#239;',
		'&eth;' => '&#240;',
		'&ntilde;' => '&#241;',
		'&ograve;' => '&#242;',
		'&oacute;' => '&#243;',
		'&ocirc;' => '&#244;',
		'&otilde;' => '&#245;',
		'&ouml;' => '&#246;',
		'&divide;' => '&#247;',
		'&oslash;' => '&#248;',
		'&ugrave;' => '&#249;',
		'&uacute;' => '&#250;',
		'&ucirc;' => '&#251;',
		'&uuml;' => '&#252;',
		'&yacute;' => '&#253;',
		'&thorn;' => '&#254;',
		'&yuml;' => '&#255;',
		'&OElig;' => '&#338;',
		'&oelig;' => '&#339;',
		'&Scaron;' => '&#352;',
		'&scaron;' => '&#353;',
		'&Yuml;' => '&#376;',
		'&fnof;' => '&#402;',
		'&circ;' => '&#710;',
		'&tilde;' => '&#732;',
		'&Alpha;' => '&#913;',
		'&Beta;' => '&#914;',
		'&Gamma;' => '&#915;',
		'&Delta;' => '&#916;',
		'&Epsilon;' => '&#917;',
		'&Zeta;' => '&#918;',
		'&Eta;' => '&#919;',
		'&Theta;' => '&#920;',
		'&Iota;' => '&#921;',
		'&Kappa;' => '&#922;',
		'&Lambda;' => '&#923;',
		'&Mu;' => '&#924;',
		'&Nu;' => '&#925;',
		'&Xi;' => '&#926;',
		'&Omicron;' => '&#927;',
		'&Pi;' => '&#928;',
		'&Rho;' => '&#929;',
		'&Sigma;' => '&#931;',
		'&Tau;' => '&#932;',
		'&Upsilon;' => '&#933;',
		'&Phi;' => '&#934;',
		'&Chi;' => '&#935;',
		'&Psi;' => '&#936;',
		'&Omega;' => '&#937;',
		'&alpha;' => '&#945;',
		'&beta;' => '&#946;',
		'&gamma;' => '&#947;',
		'&delta;' => '&#948;',
		'&epsilon;' => '&#949;',
		'&zeta;' => '&#950;',
		'&eta;' => '&#951;',
		'&theta;' => '&#952;',
		'&iota;' => '&#953;',
		'&kappa;' => '&#954;',
		'&lambda;' => '&#955;',
		'&mu;' => '&#956;',
		'&nu;' => '&#957;',
		'&xi;' => '&#958;',
		'&omicron;' => '&#959;',
		'&pi;' => '&#960;',
		'&rho;' => '&#961;',
		'&sigmaf;' => '&#962;',
		'&sigma;' => '&#963;',
		'&tau;' => '&#964;',
		'&upsilon;' => '&#965;',
		'&phi;' => '&#966;',
		'&chi;' => '&#967;',
		'&psi;' => '&#968;',
		'&omega;' => '&#969;',
		'&thetasym;' => '&#977;',
		'&upsih;' => '&#978;',
		'&piv;' => '&#982;',
		'&ensp;' => '&#8194;',
		'&emsp;' => '&#8195;',
		'&thinsp;' => '&#8201;',
		'&zwnj;' => '&#8204;',
		'&zwj;' => '&#8205;',
		'&lrm;' => '&#8206;',
		'&rlm;' => '&#8207;',
		'&ndash;' => '&#8211;',
		'&mdash;' => '&#8212;',
		'&lsquo;' => '&#8216;',
		'&rsquo;' => '&#8217;',
		'&sbquo;' => '&#8218;',
		'&ldquo;' => '&#8220;',
		'&rdquo;' => '&#8221;',
		'&bdquo;' => '&#8222;',
		'&dagger;' => '&#8224;',
		'&Dagger;' => '&#8225;',
		'&bull;' => '&#8226;',
		'&hellip;' => '&#8230;',
		'&permil;' => '&#8240;',
		'&prime;' => '&#8242;',
		'&Prime;' => '&#8243;',
		'&lsaquo;' => '&#8249;',
		'&rsaquo;' => '&#8250;',
		'&oline;' => '&#8254;',
		'&frasl;' => '&#8260;',
		'&euro;' => '&#8364;',
		'&image;' => '&#8465;',
		'&weierp;' => '&#8472;',
		'&real;' => '&#8476;',
		'&trade;' => '&#8482;',
		'&alefsym;' => '&#8501;',
		'&crarr;' => '&#8629;',
		'&lArr;' => '&#8656;',
		'&uArr;' => '&#8657;',
		'&rArr;' => '&#8658;',
		'&dArr;' => '&#8659;',
		'&hArr;' => '&#8660;',
		'&forall;' => '&#8704;',
		'&part;' => '&#8706;',
		'&exist;' => '&#8707;',
		'&empty;' => '&#8709;',
		'&nabla;' => '&#8711;',
		'&isin;' => '&#8712;',
		'&notin;' => '&#8713;',
		'&ni;' => '&#8715;',
		'&prod;' => '&#8719;',
		'&sum;' => '&#8721;',
		'&minus;' => '&#8722;',
		'&lowast;' => '&#8727;',
		'&radic;' => '&#8730;',
		'&prop;' => '&#8733;',
		'&infin;' => '&#8734;',
		'&ang;' => '&#8736;',
		'&and;' => '&#8743;',
		'&or;' => '&#8744;',
		'&cap;' => '&#8745;',
		'&cup;' => '&#8746;',
		'&int;' => '&#8747;',
		'&there4;' => '&#8756;',
		'&sim;' => '&#8764;',
		'&cong;' => '&#8773;',
		'&asymp;' => '&#8776;',
		'&ne;' => '&#8800;',
		'&equiv;' => '&#8801;',
		'&le;' => '&#8804;',
		'&ge;' => '&#8805;',
		'&sub;' => '&#8834;',
		'&sup;' => '&#8835;',
		'&nsub;' => '&#8836;',
		'&sube;' => '&#8838;',
		'&supe;' => '&#8839;',
		'&oplus;' => '&#8853;',
		'&otimes;' => '&#8855;',
		'&perp;' => '&#8869;',
		'&sdot;' => '&#8901;',
		'&lceil;' => '&#8968;',
		'&rceil;' => '&#8969;',
		'&lfloor;' => '&#8970;',
		'&rfloor;' => '&#8971;',
		'&lang;' => '&#9001;',
		'&rang;' => '&#9002;',
		'&larr;' => '&#8592;',
		'&uarr;' => '&#8593;',
		'&rarr;' => '&#8594;',
		'&darr;' => '&#8595;',
		'&harr;' => '&#8596;',
		'&loz;' => '&#9674;',
		'&spades;' => '&#9824;',
		'&clubs;' => '&#9827;',
		'&hearts;' => '&#9829;',
		'&diams;' => '&#9830;'
	);

	return str_replace( array_keys($to_ncr), array_values($to_ncr), $text );
}

/**
 * Formats text for the editor.
 *
 * Generally the browsers treat everything inside a textarea as text, but
 * it is still a good idea to HTML entity encode `<`, `>` and `&` in the content.
 *
 * The filter {@see 'format_for_editor'} is applied here. If `$text` is empty the
 * filter will be applied to an empty string.
 *
 * @since 4.3.0
 *
 * @see _WP_Editors::editor()
 *
 * @param string $text           The text to be formatted.
 * @param string $default_editor The default editor for the current user.
 *                               It is usually either 'html' or 'tinymce'.
 * @return string The formatted text after filter is applied.
 */
function format_for_editor( $text, $default_editor = null ) {
	if ( $text ) {
		$text = htmlspecialchars( $text, ENT_NOQUOTES, get_option( 'blog_charset' ) );
	}

	/**
	 * Filters the text after it is formatted for the editor.
	 *
	 * @since 4.3.0
	 *
	 * @param string $text           The formatted text.
	 * @param string $default_editor The default editor for the current user.
	 *                               It is usually either 'html' or 'tinymce'.
	 */
	return apply_filters( 'format_for_editor', $text, $default_editor );
}

/**
 * Perform a deep string replace operation to ensure the values in $search are no longer present
 *
 * Repeats the replacement operation until it no longer replaces anything so as to remove "nested" values
 * e.g. $subject = '%0%0%0DDD', $search ='%0D', $result ='' rather than the '%0%0DD' that
 * str_replace would return
 *
 * @since 2.8.1
 * @access private
 *
 * @param string|array $search  The value being searched for, otherwise known as the needle.
 *                              An array may be used to designate multiple needles.
 * @param string       $subject The string being searched and replaced on, otherwise known as the haystack.
 * @return string The string with the replaced svalues.
 */
function _deep_replace( $search, $subject ) {
	$subject = (string) $subject;

	$count = 1;
	while ( $count ) {
		$subject = str_replace( $search, '', $subject, $count );
	}

	return $subject;
}

/**
 * Escapes data for use in a MySQL query.
 *
 * Usually you should prepare queries using wpdb::prepare().
 * Sometimes, spot-escaping is required or useful. One example
 * is preparing an array for use in an IN clause.
 *
 * NOTE: Since 4.8.3, '%' characters will be replaced with a placeholder string,
 * this prevents certain SQLi attacks from taking place. This change in behaviour
 * may cause issues for code that expects the return value of esc_sql() to be useable
 * for other purposes.
 *
 * @since 2.8.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string|array $data Unescaped data
 * @return string|array Escaped data
 */
function esc_sql( $data ) {
	global $wpdb;
	return $wpdb->_escape( $data );
}

// refactored. function esc_url( $url, $protocols = null, $_context = 'display' ) {}
// refactored. function esc_url_raw( $url, $protocols = null ) {}

/**
 * Convert entities, while preserving already-encoded entities.
 *
 * @link https://secure.php.net/htmlentities Borrowed from the PHP Manual user notes.
 *
 * @since 1.2.2
 *
 * @param string $myHTML The text to be converted.
 * @return string Converted text.
 */
function htmlentities2( $myHTML ) {
	$translation_table = get_html_translation_table( HTML_ENTITIES, ENT_QUOTES );
	$translation_table[chr(38)] = '&';
	return preg_replace( "/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/", "&amp;", strtr($myHTML, $translation_table) );
}

/**
 * Escape single quotes, htmlspecialchar " < > &, and fix line endings.
 *
 * Escapes text strings for echoing in JS. It is intended to be used for inline JS
 * (in a tag attribute, for example onclick="..."). Note that the strings have to
 * be in single quotes. The {@see 'js_escape'} filter is also applied here.
 *
 * @since 2.8.0
 *
 * @param string $text The text to be escaped.
 * @return string Escaped text.
 */
function esc_js( $text ) {
	$safe_text = wp_check_invalid_utf8( $text );
	$safe_text = _wp_specialchars( $safe_text, ENT_COMPAT );
	$safe_text = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", stripslashes( $safe_text ) );
	$safe_text = str_replace( "\r", '', $safe_text );
	$safe_text = str_replace( "\n", '\\n', addslashes( $safe_text ) );
	/**
	 * Filters a string cleaned and escaped for output in JavaScript.
	 *
	 * Text passed to esc_js() is stripped of invalid or special characters,
	 * and properly slashed for output.
	 *
	 * @since 2.0.6
	 *
	 * @param string $safe_text The text after it has been escaped.
 	 * @param string $text      The text prior to being escaped.
	 */
	return apply_filters( 'js_escape', $safe_text, $text );
}

// refactored. function esc_html( $text ) {}

/**
 * Escaping for HTML attributes.
 *
 * @since 2.8.0
 *
 * @param string $text
 * @return string
 */
function esc_attr( $text ) {
	$safe_text = wp_check_invalid_utf8( $text );
	$safe_text = _wp_specialchars( $safe_text, ENT_QUOTES );
	/**
	 * Filters a string cleaned and escaped for output in an HTML attribute.
	 *
	 * Text passed to esc_attr() is stripped of invalid or special characters
	 * before output.
	 *
	 * @since 2.0.6
	 *
	 * @param string $safe_text The text after it has been escaped.
 	 * @param string $text      The text prior to being escaped.
	 */
	return apply_filters( 'attribute_escape', $safe_text, $text );
}

/**
 * Escaping for textarea values.
 *
 * @since 3.1.0
 *
 * @param string $text
 * @return string
 */
function esc_textarea( $text ) {
	$safe_text = htmlspecialchars( $text, ENT_QUOTES, get_option( 'blog_charset' ) );
	/**
	 * Filters a string cleaned and escaped for output in a textarea element.
	 *
	 * @since 3.1.0
	 *
	 * @param string $safe_text The text after it has been escaped.
 	 * @param string $text      The text prior to being escaped.
	 */
	return apply_filters( 'esc_textarea', $safe_text, $text );
}

/**
 * Escape an HTML tag name.
 *
 * @since 2.5.0
 *
 * @param string $tag_name
 * @return string
 */
function tag_escape( $tag_name ) {
	$safe_tag = strtolower( preg_replace('/[^a-zA-Z0-9_:]/', '', $tag_name) );
	/**
	 * Filters a string cleaned and escaped for output as an HTML tag.
	 *
	 * @since 2.8.0
	 *
	 * @param string $safe_tag The tag name after it has been escaped.
 	 * @param string $tag_name The text before it was escaped.
	 */
	return apply_filters( 'tag_escape', $safe_tag, $tag_name );
}

/**
 * Convert full URL paths to absolute paths.
 *
 * Removes the http or https protocols and the domain. Keeps the path '/' at the
 * beginning, so it isn't a true relative link, but from the web root base.
 *
 * @since 2.1.0
 * @since 4.1.0 Support was added for relative URLs.
 *
 * @param string $link Full URL path.
 * @return string Absolute path.
 */
function wp_make_link_relative( $link ) {
	return preg_replace( '|^(https?:)?//[^/]+(/?.*)|i', '$2', $link );
}

// refactored. function sanitize_option( $option, $value ) {}
// :
// refactored. function wp_pre_kses_less_than_callback( $matches ) {}

/**
 * WordPress implementation of PHP sprintf() with filters.
 *
 * @since 2.5.0
 * @link https://secure.php.net/sprintf
 *
 * @param string $pattern   The string which formatted args are inserted.
 * @param mixed  $args ,... Arguments to be formatted into the $pattern string.
 * @return string The formatted string.
 */
function wp_sprintf( $pattern ) {
	$args = func_get_args();
	$len = strlen($pattern);
	$start = 0;
	$result = '';
	$arg_index = 0;
	while ( $len > $start ) {
		// Last character: append and break
		if ( strlen($pattern) - 1 == $start ) {
			$result .= substr($pattern, -1);
			break;
		}

		// Literal %: append and continue
		if ( substr($pattern, $start, 2) == '%%' ) {
			$start += 2;
			$result .= '%';
			continue;
		}

		// Get fragment before next %
		$end = strpos($pattern, '%', $start + 1);
		if ( false === $end )
			$end = $len;
		$fragment = substr($pattern, $start, $end - $start);

		// Fragment has a specifier
		if ( $pattern[$start] == '%' ) {
			// Find numbered arguments or take the next one in order
			if ( preg_match('/^%(\d+)\$/', $fragment, $matches) ) {
				$arg = isset($args[$matches[1]]) ? $args[$matches[1]] : '';
				$fragment = str_replace("%{$matches[1]}$", '%', $fragment);
			} else {
				++$arg_index;
				$arg = isset($args[$arg_index]) ? $args[$arg_index] : '';
			}

			/**
			 * Filters a fragment from the pattern passed to wp_sprintf().
			 *
			 * If the fragment is unchanged, then sprintf() will be run on the fragment.
			 *
			 * @since 2.5.0
			 *
			 * @param string $fragment A fragment from the pattern.
			 * @param string $arg      The argument.
			 */
			$_fragment = apply_filters( 'wp_sprintf', $fragment, $arg );
			if ( $_fragment != $fragment )
				$fragment = $_fragment;
			else
				$fragment = sprintf($fragment, strval($arg) );
		}

		// Append to result and move to next fragment
		$result .= $fragment;
		$start = $end;
	}
	return $result;
}

/**
 * Localize list items before the rest of the content.
 *
 * The '%l' must be at the first characters can then contain the rest of the
 * content. The list items will have ', ', ', and', and ' and ' added depending
 * on the amount of list items in the $args parameter.
 *
 * @since 2.5.0
 *
 * @param string $pattern Content containing '%l' at the beginning.
 * @param array  $args    List items to prepend to the content and replace '%l'.
 * @return string Localized list items and rest of the content.
 */
function wp_sprintf_l( $pattern, $args ) {
	// Not a match
	if ( substr($pattern, 0, 2) != '%l' )
		return $pattern;

	// Nothing to work with
	if ( empty($args) )
		return '';

	/**
	 * Filters the translated delimiters used by wp_sprintf_l().
	 * Placeholders (%s) are included to assist translators and then
	 * removed before the array of strings reaches the filter.
	 *
	 * Please note: Ampersands and entities should be avoided here.
	 *
	 * @since 2.5.0
	 *
	 * @param array $delimiters An array of translated delimiters.
	 */
	$l = apply_filters( 'wp_sprintf_l', array(
		/* translators: used to join items in a list with more than 2 items */
		'between'          => sprintf( __('%s, %s'), '', '' ),
		/* translators: used to join last two items in a list with more than 2 times */
		'between_last_two' => sprintf( __('%s, and %s'), '', '' ),
		/* translators: used to join items in a list with only 2 items */
		'between_only_two' => sprintf( __('%s and %s'), '', '' ),
	) );

	$args = (array) $args;
	$result = array_shift($args);
	if ( count($args) == 1 )
		$result .= $l['between_only_two'] . array_shift($args);
	// Loop when more than two args
	$i = count($args);
	while ( $i ) {
		$arg = array_shift($args);
		$i--;
		if ( 0 == $i )
			$result .= $l['between_last_two'] . $arg;
		else
			$result .= $l['between'] . $arg;
	}
	return $result . substr($pattern, 2);
}

/**
 * Safely extracts not more than the first $count characters from html string.
 *
 * UTF-8, tags and entities safe prefix extraction. Entities inside will *NOT*
 * be counted as one character. For example &amp; will be counted as 4, &lt; as
 * 3, etc.
 *
 * @since 2.5.0
 *
 * @param string $str   String to get the excerpt from.
 * @param int    $count Maximum number of characters to take.
 * @param string $more  Optional. What to append if $str needs to be trimmed. Defaults to empty string.
 * @return string The excerpt.
 */
function wp_html_excerpt( $str, $count, $more = null ) {
	if ( null === $more )
		$more = '';
	$str = wp_strip_all_tags( $str, true );
	$excerpt = mb_substr( $str, 0, $count );
	// remove part of an entity at the end
	$excerpt = preg_replace( '/&[^;\s]{0,6}$/', '', $excerpt );
	if ( $str != $excerpt )
		$excerpt = trim( $excerpt ) . $more;
	return $excerpt;
}

/**
 * Add a Base url to relative links in passed content.
 *
 * By default it supports the 'src' and 'href' attributes. However this can be
 * changed via the 3rd param.
 *
 * @since 2.7.0
 *
 * @global string $_links_add_base
 *
 * @param string $content String to search for links in.
 * @param string $base    The base URL to prefix to links.
 * @param array  $attrs   The attributes which should be processed.
 * @return string The processed content.
 */
function links_add_base_url( $content, $base, $attrs = array('src', 'href') ) {
	global $_links_add_base;
	$_links_add_base = $base;
	$attrs = implode('|', (array)$attrs);
	return preg_replace_callback( "!($attrs)=(['\"])(.+?)\\2!i", '_links_add_base', $content );
}

/**
 * Callback to add a base url to relative links in passed content.
 *
 * @since 2.7.0
 * @access private
 *
 * @global string $_links_add_base
 *
 * @param string $m The matched link.
 * @return string The processed link.
 */
function _links_add_base( $m ) {
	global $_links_add_base;
	//1 = attribute name  2 = quotation mark  3 = URL
	return $m[1] . '=' . $m[2] .
		( preg_match( '#^(\w{1,20}):#', $m[3], $protocol ) && in_array( $protocol[1], wp_allowed_protocols() ) ?
			$m[3] :
			WP_Http::make_absolute_url( $m[3], $_links_add_base )
		)
		. $m[2];
}

/**
 * Adds a Target attribute to all links in passed content.
 *
 * This function by default only applies to `<a>` tags, however this can be
 * modified by the 3rd param.
 *
 * *NOTE:* Any current target attributed will be stripped and replaced.
 *
 * @since 2.7.0
 *
 * @global string $_links_add_target
 *
 * @param string $content String to search for links in.
 * @param string $target  The Target to add to the links.
 * @param array  $tags    An array of tags to apply to.
 * @return string The processed content.
 */
function links_add_target( $content, $target = '_blank', $tags = array('a') ) {
	global $_links_add_target;
	$_links_add_target = $target;
	$tags = implode('|', (array)$tags);
	return preg_replace_callback( "!<($tags)([^>]*)>!i", '_links_add_target', $content );
}

/**
 * Callback to add a target attribute to all links in passed content.
 *
 * @since 2.7.0
 * @access private
 *
 * @global string $_links_add_target
 *
 * @param string $m The matched link.
 * @return string The processed link.
 */
function _links_add_target( $m ) {
	global $_links_add_target;
	$tag = $m[1];
	$link = preg_replace('|( target=([\'"])(.*?)\2)|i', '', $m[2]);
	return '<' . $tag . $link . ' target="' . esc_attr( $_links_add_target ) . '">';
}

/**
 * Normalize EOL characters and strip duplicate whitespace.
 *
 * @since 2.7.0
 *
 * @param string $str The string to normalize.
 * @return string The normalized string.
 */
function normalize_whitespace( $str ) {
	$str  = trim( $str );
	$str  = str_replace( "\r", "\n", $str );
	$str  = preg_replace( array( '/\n+/', '/[ \t]+/' ), array( "\n", ' ' ), $str );
	return $str;
}

// refactored. function wp_strip_all_tags($string, $remove_breaks = false) {}
// refactored. function sanitize_text_field( $str ) {}

/**
 * Sanitizes a multiline string from user input or from the database.
 *
 * The function is like sanitize_text_field(), but preserves
 * new lines (\n) and other whitespace, which are legitimate
 * input in textarea elements.
 *
 * @see sanitize_text_field()
 *
 * @since 4.7.0
 *
 * @param string $str String to sanitize.
 * @return string Sanitized string.
 */
function sanitize_textarea_field( $str ) {
	$filtered = _sanitize_text_fields( $str, true );

	/**
	 * Filters a sanitized textarea field string.
	 *
	 * @since 4.7.0
	 *
	 * @param string $filtered The sanitized string.
	 * @param string $str      The string prior to being sanitized.
	 */
	return apply_filters( 'sanitize_textarea_field', $filtered, $str );
}

// refactored. function _sanitize_text_fields( $str, $keep_newlines = false ) {}

/**
 * i18n friendly version of basename()
 *
 * @since 3.1.0
 *
 * @param string $path   A path.
 * @param string $suffix If the filename ends in suffix this will also be cut off.
 * @return string
 */
function wp_basename( $path, $suffix = '' ) {
	return urldecode( basename( str_replace( array( '%2F', '%5C' ), '/', urlencode( $path ) ), $suffix ) );
}

// refactored. function capital_P_dangit( $text ) {}
// refactored. function sanitize_mime_type( $mime_type ) {}

/**
 * Sanitize space or carriage return separated URLs that are used to send trackbacks.
 *
 * @since 3.4.0
 *
 * @param string $to_ping Space or carriage return separated URLs
 * @return string URLs starting with the http or https protocol, separated by a carriage return.
 */
function sanitize_trackback_urls( $to_ping ) {
	$urls_to_ping = preg_split( '/[\r\n\t ]/', trim( $to_ping ), -1, PREG_SPLIT_NO_EMPTY );
	foreach ( $urls_to_ping as $k => $url ) {
		if ( !preg_match( '#^https?://.#i', $url ) )
			unset( $urls_to_ping[$k] );
	}
	$urls_to_ping = array_map( 'esc_url_raw', $urls_to_ping );
	$urls_to_ping = implode( "\n", $urls_to_ping );
	/**
	 * Filters a list of trackback URLs following sanitization.
	 *
	 * The string returned here consists of a space or carriage return-delimited list
	 * of trackback URLs.
	 *
	 * @since 3.4.0
	 *
	 * @param string $urls_to_ping Sanitized space or carriage return separated URLs.
	 * @param string $to_ping      Space or carriage return separated URLs before sanitization.
	 */
	return apply_filters( 'sanitize_trackback_urls', $urls_to_ping, $to_ping );
}

// refactored. function wp_slash( $value ) {}

/**
 * Remove slashes from a string or array of strings.
 *
 * This should be used to remove slashes from data passed to core API that
 * expects data to be unslashed.
 *
 * @since 3.6.0
 *
 * @param string|array $value String or array of strings to unslash.
 * @return string|array Unslashed $value
 */
function wp_unslash( $value ) {
	return stripslashes_deep( $value );
}

/**
 * Extract and return the first URL from passed content.
 *
 * @since 3.6.0
 *
 * @param string $content A string which might contain a URL.
 * @return string|false The found URL.
 */
function get_url_in_content( $content ) {
	if ( empty( $content ) ) {
		return false;
	}

	if ( preg_match( '/<a\s[^>]*?href=([\'"])(.+?)\1/is', $content, $matches ) ) {
		return esc_url_raw( $matches[2] );
	}

	return false;
}

// refactored. function wp_spaces_regexp() {}

/**
 * Print the important emoji-related styles.
 *
 * @since 4.2.0
 *
 * @staticvar bool $printed
 */
function print_emoji_styles() {
	static $printed = false;

	if ( $printed ) {
		return;
	}

	$printed = true;
?>
<style type="text/css">
img.wp-smiley,
img.emoji {
	display: inline !important;
	border: none !important;
	box-shadow: none !important;
	height: 1em !important;
	width: 1em !important;
	margin: 0 .07em !important;
	vertical-align: -0.1em !important;
	background: none !important;
	padding: 0 !important;
}
</style>
<?php
}

/**
 * Print the inline Emoji detection script if it is not already printed.
 *
 * @since 4.2.0
 * @staticvar bool $printed
 */
function print_emoji_detection_script() {
	static $printed = false;

	if ( $printed ) {
		return;
	}

	$printed = true;

	_print_emoji_detection_script();
}

/**
 * Prints inline Emoji dection script
 *
 * @ignore
 * @since 4.6.0
 * @access private
 */
function _print_emoji_detection_script() {
	$settings = array(
		/**
		 * Filters the URL where emoji png images are hosted.
		 *
		 * @since 4.2.0
		 *
		 * @param string The emoji base URL for png images.
		 */
		'baseUrl' => apply_filters( 'emoji_url', 'https://s.w.org/images/core/emoji/11/72x72/' ),

		/**
		 * Filters the extension of the emoji png files.
		 *
		 * @since 4.2.0
		 *
		 * @param string The emoji extension for png files. Default .png.
		 */
		'ext' => apply_filters( 'emoji_ext', '.png' ),

		/**
		 * Filters the URL where emoji SVG images are hosted.
		 *
		 * @since 4.6.0
		 *
		 * @param string The emoji base URL for svg images.
		 */
		'svgUrl' => apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/11/svg/' ),

		/**
		 * Filters the extension of the emoji SVG files.
		 *
		 * @since 4.6.0
		 *
		 * @param string The emoji extension for svg files. Default .svg.
		 */
		'svgExt' => apply_filters( 'emoji_svg_ext', '.svg' ),
	);

	$version = 'ver=' . get_bloginfo( 'version' );

	if ( SCRIPT_DEBUG ) {
		$settings['source'] = array(
			/** This filter is documented in wp-includes/class.wp-scripts.php */
			'wpemoji' => apply_filters( 'script_loader_src', includes_url( "js/wp-emoji.js?$version" ), 'wpemoji' ),
			/** This filter is documented in wp-includes/class.wp-scripts.php */
			'twemoji' => apply_filters( 'script_loader_src', includes_url( "js/twemoji.js?$version" ), 'twemoji' ),
		);

		?>
		<script type="text/javascript">
			window._wpemojiSettings = <?php echo wp_json_encode( $settings ); ?>;
			<?php readfile( ABSPATH . WPINC . "/js/wp-emoji-loader.js" ); ?>
		</script>
		<?php
	} else {
		$settings['source'] = array(
			/** This filter is documented in wp-includes/class.wp-scripts.php */
			'concatemoji' => apply_filters( 'script_loader_src', includes_url( "js/wp-emoji-release.min.js?$version" ), 'concatemoji' ),
		);

		/*
		 * If you're looking at a src version of this file, you'll see an "include"
		 * statement below. This is used by the `grunt build` process to directly
		 * include a minified version of wp-emoji-loader.js, instead of using the
		 * readfile() method from above.
		 *
		 * If you're looking at a build version of this file, you'll see a string of
		 * minified JavaScript. If you need to debug it, please turn on SCRIPT_DEBUG
		 * and edit wp-emoji-loader.js directly.
		 */
		?>
		<script type="text/javascript">
			window._wpemojiSettings = <?php echo wp_json_encode( $settings ); ?>;
			!function(a,b,c){function d(a,b){var c=String.fromCharCode;l.clearRect(0,0,k.width,k.height),l.fillText(c.apply(this,a),0,0);var d=k.toDataURL();l.clearRect(0,0,k.width,k.height),l.fillText(c.apply(this,b),0,0);var e=k.toDataURL();return d===e}function e(a){var b;if(!l||!l.fillText)return!1;switch(l.textBaseline="top",l.font="600 32px Arial",a){case"flag":return!(b=d([55356,56826,55356,56819],[55356,56826,8203,55356,56819]))&&(b=d([55356,57332,56128,56423,56128,56418,56128,56421,56128,56430,56128,56423,56128,56447],[55356,57332,8203,56128,56423,8203,56128,56418,8203,56128,56421,8203,56128,56430,8203,56128,56423,8203,56128,56447]),!b);case"emoji":return b=d([55358,56760,9792,65039],[55358,56760,8203,9792,65039]),!b}return!1}function f(a){var c=b.createElement("script");c.src=a,c.defer=c.type="text/javascript",b.getElementsByTagName("head")[0].appendChild(c)}var g,h,i,j,k=b.createElement("canvas"),l=k.getContext&&k.getContext("2d");for(j=Array("flag","emoji"),c.supports={everything:!0,everythingExceptFlag:!0},i=0;i<j.length;i++)c.supports[j[i]]=e(j[i]),c.supports.everything=c.supports.everything&&c.supports[j[i]],"flag"!==j[i]&&(c.supports.everythingExceptFlag=c.supports.everythingExceptFlag&&c.supports[j[i]]);c.supports.everythingExceptFlag=c.supports.everythingExceptFlag&&!c.supports.flag,c.DOMReady=!1,c.readyCallback=function(){c.DOMReady=!0},c.supports.everything||(h=function(){c.readyCallback()},b.addEventListener?(b.addEventListener("DOMContentLoaded",h,!1),a.addEventListener("load",h,!1)):(a.attachEvent("onload",h),b.attachEvent("onreadystatechange",function(){"complete"===b.readyState&&c.readyCallback()})),g=c.source||{},g.concatemoji?f(g.concatemoji):g.wpemoji&&g.twemoji&&(f(g.twemoji),f(g.wpemoji)))}(window,document,window._wpemojiSettings);
		</script>
		<?php
	}
}

// refactored. function wp_encode_emoji( $content ) {}

/**
 * Convert emoji to a static img element.
 *
 * @since 4.2.0
 *
 * @param string $text The content to encode.
 * @return string The encoded content.
 */
function wp_staticize_emoji( $text ) {
	if ( false === strpos( $text, '&#x' ) ) {
		if ( ( function_exists( 'mb_check_encoding' ) && mb_check_encoding( $text, 'ASCII' ) ) || ! preg_match( '/[^\x00-\x7F]/', $text ) ) {
			// The text doesn't contain anything that might be emoji, so we can return early.
			return $text;
		} else {
			$encoded_text = wp_encode_emoji( $text );
			if ( $encoded_text === $text ) {
				return $encoded_text;
			}

			$text = $encoded_text;
		}
	}

	$emoji = _wp_emoji_list( 'entities' );

	// Quickly narrow down the list of emoji that might be in the text and need replacing.
	$possible_emoji = array();
	foreach( $emoji as $emojum ) {
		if ( false !== strpos( $text, $emojum ) ) {
			if ( version_compare( phpversion(), '5.4', '<' ) ) {
				$possible_emoji[ $emojum ] = html_entity_decode( $emojum, ENT_COMPAT, 'UTF-8' );
			} else {
				$possible_emoji[ $emojum ] = html_entity_decode( $emojum );
			}
		}
	}

	if ( ! $possible_emoji ) {
		return $text;
	}

	/** This filter is documented in wp-includes/formatting.php */
	$cdn_url = apply_filters( 'emoji_url', 'https://s.w.org/images/core/emoji/11/72x72/' );

	/** This filter is documented in wp-includes/formatting.php */
	$ext = apply_filters( 'emoji_ext', '.png' );

	$output = '';
	/*
	 * HTML loop taken from smiley function, which was taken from texturize function.
	 * It'll never be consolidated.
	 *
	 * First, capture the tags as well as in between.
	 */
	$textarr = preg_split( '/(<.*>)/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
	$stop = count( $textarr );

	// Ignore processing of specific tags.
	$tags_to_ignore = 'code|pre|style|script|textarea';
	$ignore_block_element = '';

	for ( $i = 0; $i < $stop; $i++ ) {
		$content = $textarr[$i];

		// If we're in an ignore block, wait until we find its closing tag.
		if ( '' == $ignore_block_element && preg_match( '/^<(' . $tags_to_ignore . ')>/', $content, $matches ) )  {
			$ignore_block_element = $matches[1];
		}

		// If it's not a tag and not in ignore block.
		if ( '' ==  $ignore_block_element && strlen( $content ) > 0 && '<' != $content[0] && false !== strpos( $content, '&#x' ) ) {
			foreach ( $possible_emoji as $emojum => $emoji_char ) {
				if ( false === strpos( $content, $emojum ) ) {
					continue;
				}

				$file = str_replace( ';&#x', '-', $emojum );
				$file = str_replace( array( '&#x', ';'), '', $file );

				$entity = sprintf( '<img src="%s" alt="%s" class="wp-smiley" style="height: 1em; max-height: 1em;" />', $cdn_url . $file . $ext, $emoji_char );

				$content = str_replace( $emojum, $entity, $content );
			}
		}

		// Did we exit ignore block.
		if ( '' != $ignore_block_element && '</' . $ignore_block_element . '>' == $content )  {
			$ignore_block_element = '';
		}

		$output .= $content;
	}

	// Finally, remove any stray U+FE0F characters
	$output = str_replace( '&#xfe0f;', '', $output );

	return $output;
}

/**
 * Convert emoji in emails into static images.
 *
 * @since 4.2.0
 *
 * @param array $mail The email data array.
 * @return array The email data array, with emoji in the message staticized.
 */
function wp_staticize_emoji_for_email( $mail ) {
	if ( ! isset( $mail['message'] ) ) {
		return $mail;
	}

	/*
	 * We can only transform the emoji into images if it's a text/html email.
	 * To do that, here's a cut down version of the same process that happens
	 * in wp_mail() - get the Content-Type from the headers, if there is one,
	 * then pass it through the wp_mail_content_type filter, in case a plugin
	 * is handling changing the Content-Type.
	 */
	$headers = array();
	if ( isset( $mail['headers'] ) ) {
		if ( is_array( $mail['headers'] ) ) {
			$headers = $mail['headers'];
		} else {
			$headers = explode( "\n", str_replace( "\r\n", "\n", $mail['headers'] ) );
		}
	}

	foreach ( $headers as $header ) {
		if ( strpos($header, ':') === false ) {
			continue;
		}

		// Explode them out.
		list( $name, $content ) = explode( ':', trim( $header ), 2 );

		// Cleanup crew.
		$name    = trim( $name    );
		$content = trim( $content );

		if ( 'content-type' === strtolower( $name ) ) {
			if ( strpos( $content, ';' ) !== false ) {
				list( $type, $charset ) = explode( ';', $content );
				$content_type = trim( $type );
			} else {
				$content_type = trim( $content );
			}
			break;
		}
	}

	// Set Content-Type if we don't have a content-type from the input headers.
	if ( ! isset( $content_type ) ) {
		$content_type = 'text/plain';
	}

	/** This filter is documented in wp-includes/pluggable.php */
	$content_type = apply_filters( 'wp_mail_content_type', $content_type );

	if ( 'text/html' === $content_type ) {
		$mail['message'] = wp_staticize_emoji( $mail['message'] );
	}

	return $mail;
}

// refactored. function _wp_emoji_list( $type = 'entities' ) {}

/**
 * Shorten a URL, to be used as link text.
 *
 * @since 1.2.0
 * @since 4.4.0 Moved to wp-includes/formatting.php from wp-admin/includes/misc.php and added $length param.
 *
 * @param string $url    URL to shorten.
 * @param int    $length Optional. Maximum length of the shortened URL. Default 35 characters.
 * @return string Shortened URL.
 */
function url_shorten( $url, $length = 35 ) {
	$stripped = str_replace( array( 'https://', 'http://', 'www.' ), '', $url );
	$short_url = untrailingslashit( $stripped );

	if ( strlen( $short_url ) > $length ) {
		$short_url = substr( $short_url, 0, $length - 3 ) . '&hellip;';
	}
	return $short_url;
}

/**
 * Sanitizes a hex color.
 *
 * Returns either '', a 3 or 6 digit hex color (with #), or nothing.
 * For sanitizing values without a #, see sanitize_hex_color_no_hash().
 *
 * @since 3.4.0
 *
 * @param string $color
 * @return string|void
 */
function sanitize_hex_color( $color ) {
	if ( '' === $color ) {
		return '';
	}

	// 3 or 6 hex digits, or the empty string.
	if ( preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
		return $color;
	}
}

/**
 * Sanitizes a hex color without a hash. Use sanitize_hex_color() when possible.
 *
 * Saving hex colors without a hash puts the burden of adding the hash on the
 * UI, which makes it difficult to use or upgrade to other color types such as
 * rgba, hsl, rgb, and html color names.
 *
 * Returns either '', a 3 or 6 digit hex color (without a #), or null.
 *
 * @since 3.4.0
 *
 * @param string $color
 * @return string|null
 */
function sanitize_hex_color_no_hash( $color ) {
	$color = ltrim( $color, '#' );

	if ( '' === $color ) {
		return '';
	}

	return sanitize_hex_color( '#' . $color ) ? $color : null;
}

/**
 * Ensures that any hex color is properly hashed.
 * Otherwise, returns value untouched.
 *
 * This method should only be necessary if using sanitize_hex_color_no_hash().
 *
 * @since 3.4.0
 *
 * @param string $color
 * @return string
 */
function maybe_hash_hex_color( $color ) {
	if ( $unhashed = sanitize_hex_color_no_hash( $color ) ) {
		return '#' . $unhashed;
	}

	return $color;
}
