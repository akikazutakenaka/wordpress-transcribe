<?php
/**
 * kses 0.2.2 - HTML/XHTML filter that only allows some elements and attributes
 * Copyright (C) 2002, 2003, 2005  Ulf Harnhammar
 *
 * This program is free software and open source software; you can redistribute
 * it and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the License,
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin St, Fifth Floor, Boston, MA 02110-1301, USA
 * http://www.gnu.org/licenses/gpl.html
 *
 * [kses strips evil scripts!]
 *
 * Added wp_ prefix to avoid conflicts with existing kses users
 *
 * @version 0.2.2
 * @copyright (C) 2002, 2003, 2005
 * @author Ulf Harnhammar <http://advogato.org/person/metaur/>
 *
 * @package External
 * @subpackage KSES
 *
 */

/**
 * You can override this in a plugin.
 *
 * The {@see 'wp_kses_allowed_html'} filter is more powerful and supplies context.
 *
 * `CUSTOM_TAGS` is not recommended and should be considered deprecated.
 *
 * @see wp_kses_allowed_html()
 *
 * @since 1.2.0
 */
if ( ! defined( 'CUSTOM_TAGS' ) )
	define( 'CUSTOM_TAGS', false );

// Ensure that these variables are added to the global namespace
// (e.g. if using namespaces / autoload in the current PHP environment).
global $allowedposttags, $allowedtags, $allowedentitynames;

if ( ! CUSTOM_TAGS ) {
	// refactored. $allowedposttags = array();
	// :
	// refactored. $allowedposttags = array_map( '_wp_add_global_attributes', $allowedposttags );
} else {
	// refactored. $allowedtags = wp_kses_array_lc( $allowedtags );
	// refactored. $allowedposttags = wp_kses_array_lc( $allowedposttags );
}

// refactored. function wp_kses( $string, $allowed_html, $allowed_protocols = array() ) {}

/**
 * Filters one attribute only and ensures its value is allowed.
 *
 * This function has the advantage of being more secure than esc_attr() and can
 * escape data in some situations where wp_kses() must strip the whole attribute.
 *
 * @since 4.2.3
 *
 * @param string $string The 'whole' attribute, including name and value.
 * @param string $element The element name to which the attribute belongs.
 * @return string Filtered attribute.
 */
function wp_kses_one_attr( $string, $element ) {
	$uris = array('xmlns', 'profile', 'href', 'src', 'cite', 'classid', 'codebase', 'data', 'usemap', 'longdesc', 'action');
	$allowed_html = wp_kses_allowed_html( 'post' );
	$allowed_protocols = wp_allowed_protocols();
	$string = wp_kses_no_null( $string, array( 'slash_zero' => 'keep' ) );
	
	// Preserve leading and trailing whitespace.
	$matches = array();
	preg_match('/^\s*/', $string, $matches);
	$lead = $matches[0];
	preg_match('/\s*$/', $string, $matches);
	$trail = $matches[0];
	if ( empty( $trail ) ) {
		$string = substr( $string, strlen( $lead ) );
	} else {
		$string = substr( $string, strlen( $lead ), -strlen( $trail ) );
	}
	
	// Parse attribute name and value from input.
	$split = preg_split( '/\s*=\s*/', $string, 2 );
	$name = $split[0];
	if ( count( $split ) == 2 ) {
		$value = $split[1];

		// Remove quotes surrounding $value.
		// Also guarantee correct quoting in $string for this one attribute.
		if ( '' == $value ) {
			$quote = '';
		} else {
			$quote = $value[0];
		}
		if ( '"' == $quote || "'" == $quote ) {
			if ( substr( $value, -1 ) != $quote ) {
				return '';
			}
			$value = substr( $value, 1, -1 );
		} else {
			$quote = '"';
		}

		// Sanitize quotes, angle braces, and entities.
		$value = esc_attr( $value );

		// Sanitize URI values.
		if ( in_array( strtolower( $name ), $uris ) ) {
			$value = wp_kses_bad_protocol( $value, $allowed_protocols );
		}

		$string = "$name=$quote$value$quote";
		$vless = 'n';
	} else {
		$value = '';
		$vless = 'y';
	}
	
	// Sanitize attribute by name.
	wp_kses_attr_check( $name, $value, $string, $vless, $element, $allowed_html );

	// Restore whitespace.
	return $lead . $string . $trail;
}

/**
 * Return a list of allowed tags and attributes for a given context.
 *
 * @since 3.5.0
 *
 * @global array $allowedposttags
 * @global array $allowedtags
 * @global array $allowedentitynames
 *
 * @param string|array $context The context for which to retrieve tags.
 *                              Allowed values are post, strip, data, entities, or
 *                              the name of a field filter such as pre_user_description.
 * @return array List of allowed tags and their allowed attributes.
 */
function wp_kses_allowed_html( $context = '' ) {
	global $allowedposttags, $allowedtags, $allowedentitynames;

	if ( is_array( $context ) ) {
		/**
		 * Filters HTML elements allowed for a given context.
		 *
		 * @since 3.5.0
		 *
		 * @param array  $context      Context to judge allowed tags by.
		 * @param string $context_type Context type (explicit).
		 */
		return apply_filters( 'wp_kses_allowed_html', $context, 'explicit' );
	}

	switch ( $context ) {
		case 'post':
			/** This filter is documented in wp-includes/kses.php */
			return apply_filters( 'wp_kses_allowed_html', $allowedposttags, $context );

		case 'user_description':
		case 'pre_user_description':
			$tags = $allowedtags;
			$tags['a']['rel'] = true;
			/** This filter is documented in wp-includes/kses.php */
			return apply_filters( 'wp_kses_allowed_html', $tags, $context );

		case 'strip':
			/** This filter is documented in wp-includes/kses.php */
			return apply_filters( 'wp_kses_allowed_html', array(), $context );

		case 'entities':
			/** This filter is documented in wp-includes/kses.php */
			return apply_filters( 'wp_kses_allowed_html', $allowedentitynames, $context);

		case 'data':
		default:
			/** This filter is documented in wp-includes/kses.php */
			return apply_filters( 'wp_kses_allowed_html', $allowedtags, $context );
	}
}

// refactored. function wp_kses_hook( $string, $allowed_html, $allowed_protocols ) {}

/**
 * This function returns kses' version number.
 *
 * @since 1.0.0
 *
 * @return string KSES Version Number
 */
function wp_kses_version() {
	return '0.2.2';
}

// refactored. function wp_kses_split( $string, $allowed_html, $allowed_protocols ) {}
// refactored. function _wp_kses_split_callback( $match ) {}

/**
 * Callback for wp_kses_split for fixing malformed HTML tags.
 *
 * This function does a lot of work. It rejects some very malformed things like
 * <:::>. It returns an empty string, if the element isn't allowed (look ma, no
 * strip_tags()!). Otherwise it splits the tag into an element and an attribute
 * list.
 *
 * After the tag is split into an element and an attribute list, it is run
 * through another filter which will remove illegal attributes and once that is
 * completed, will be returned.
 *
 * @access private
 * @since 1.0.0
 *
 * @param string $string            Content to filter
 * @param array  $allowed_html      Allowed HTML elements
 * @param array  $allowed_protocols Allowed protocols to keep
 * @return string Fixed HTML element
 */
function wp_kses_split2($string, $allowed_html, $allowed_protocols) {
	$string = wp_kses_stripslashes($string);

	if (substr($string, 0, 1) != '<')
		return '&gt;';
	// It matched a ">" character

	if ( '<!--' == substr( $string, 0, 4 ) ) {
		$string = str_replace( array('<!--', '-->'), '', $string );
		while ( $string != ($newstring = wp_kses($string, $allowed_html, $allowed_protocols)) )
			$string = $newstring;
		if ( $string == '' )
			return '';
		// prevent multiple dashes in comments
		$string = preg_replace('/--+/', '-', $string);
		// prevent three dashes closing a comment
		$string = preg_replace('/-$/', '', $string);
		return "<!--{$string}-->";
	}
	// Allow HTML comments

	if (!preg_match('%^<\s*(/\s*)?([a-zA-Z0-9-]+)([^>]*)>?$%', $string, $matches))
		return '';
	// It's seriously malformed

	$slash = trim($matches[1]);
	$elem = $matches[2];
	$attrlist = $matches[3];

	if ( ! is_array( $allowed_html ) )
		$allowed_html = wp_kses_allowed_html( $allowed_html );

	if ( ! isset($allowed_html[strtolower($elem)]) )
		return '';
	// They are using a not allowed HTML element

	if ($slash != '')
		return "</$elem>";
	// No attributes are allowed for closing elements

	return wp_kses_attr( $elem, $attrlist, $allowed_html, $allowed_protocols );
}

/**
 * Removes all attributes, if none are allowed for this element.
 *
 * If some are allowed it calls wp_kses_hair() to split them further, and then
 * it builds up new HTML code from the data that kses_hair() returns. It also
 * removes "<" and ">" characters, if there are any left. One more thing it does
 * is to check if the tag has a closing XHTML slash, and if it does, it puts one
 * in the returned code as well.
 *
 * @since 1.0.0
 *
 * @param string $element           HTML element/tag
 * @param string $attr              HTML attributes from HTML element to closing HTML element tag
 * @param array  $allowed_html      Allowed HTML elements
 * @param array  $allowed_protocols Allowed protocols to keep
 * @return string Sanitized HTML element
 */
function wp_kses_attr($element, $attr, $allowed_html, $allowed_protocols) {
	if ( ! is_array( $allowed_html ) )
		$allowed_html = wp_kses_allowed_html( $allowed_html );

	// Is there a closing XHTML slash at the end of the attributes?
	$xhtml_slash = '';
	if (preg_match('%\s*/\s*$%', $attr))
		$xhtml_slash = ' /';

	// Are any attributes allowed at all for this element?
	$element_low = strtolower( $element );
	if ( empty( $allowed_html[ $element_low ] ) || true === $allowed_html[ $element_low ] ) {
		return "<$element$xhtml_slash>";
	}

	// Split it
	$attrarr = wp_kses_hair($attr, $allowed_protocols);

	// Go through $attrarr, and save the allowed attributes for this element
	// in $attr2
	$attr2 = '';
	foreach ( $attrarr as $arreach ) {
		if ( wp_kses_attr_check( $arreach['name'], $arreach['value'], $arreach['whole'], $arreach['vless'], $element, $allowed_html ) ) {
			$attr2 .= ' '.$arreach['whole'];
		}
	}

	// Remove any "<" or ">" characters
	$attr2 = preg_replace('/[<>]/', '', $attr2);

	return "<$element$attr2$xhtml_slash>";
}

/**
 * Determine whether an attribute is allowed.
 *
 * @since 4.2.3
 *
 * @param string $name The attribute name. Returns empty string when not allowed.
 * @param string $value The attribute value. Returns a filtered value.
 * @param string $whole The name=value input. Returns filtered input.
 * @param string $vless 'y' when attribute like "enabled", otherwise 'n'.
 * @param string $element The name of the element to which this attribute belongs.
 * @param array $allowed_html The full list of allowed elements and attributes.
 * @return bool Is the attribute allowed?
 */
function wp_kses_attr_check( &$name, &$value, &$whole, $vless, $element, $allowed_html ) {
	$allowed_attr = $allowed_html[strtolower( $element )];

	$name_low = strtolower( $name );
	if ( ! isset( $allowed_attr[$name_low] ) || '' == $allowed_attr[$name_low] ) {
		$name = $value = $whole = '';
		return false;
	}

	if ( 'style' == $name_low ) {
		$new_value = safecss_filter_attr( $value );

		if ( empty( $new_value ) ) {
			$name = $value = $whole = '';
			return false;
		}

		$whole = str_replace( $value, $new_value, $whole );
		$value = $new_value;
	}

	if ( is_array( $allowed_attr[$name_low] ) ) {
		// there are some checks
		foreach ( $allowed_attr[$name_low] as $currkey => $currval ) {
			if ( ! wp_kses_check_attr_val( $value, $vless, $currkey, $currval ) ) {
				$name = $value = $whole = '';
				return false;
			}
		}
	}

	return true;
}

/**
 * Builds an attribute list from string containing attributes.
 *
 * This function does a lot of work. It parses an attribute list into an array
 * with attribute data, and tries to do the right thing even if it gets weird
 * input. It will add quotes around attribute values that don't have any quotes
 * or apostrophes around them, to make it easier to produce HTML code that will
 * conform to W3C's HTML specification. It will also remove bad URL protocols
 * from attribute values. It also reduces duplicate attributes by using the
 * attribute defined first (foo='bar' foo='baz' will result in foo='bar').
 *
 * @since 1.0.0
 *
 * @param string $attr              Attribute list from HTML element to closing HTML element tag
 * @param array  $allowed_protocols Allowed protocols to keep
 * @return array List of attributes after parsing
 */
function wp_kses_hair($attr, $allowed_protocols) {
	$attrarr = array();
	$mode = 0;
	$attrname = '';
	$uris = array('xmlns', 'profile', 'href', 'src', 'cite', 'classid', 'codebase', 'data', 'usemap', 'longdesc', 'action');

	// Loop through the whole attribute list

	while (strlen($attr) != 0) {
		$working = 0; // Was the last operation successful?

		switch ($mode) {
			case 0 : // attribute name, href for instance

				if ( preg_match('/^([-a-zA-Z:]+)/', $attr, $match ) ) {
					$attrname = $match[1];
					$working = $mode = 1;
					$attr = preg_replace( '/^[-a-zA-Z:]+/', '', $attr );
				}

				break;

			case 1 : // equals sign or valueless ("selected")

				if (preg_match('/^\s*=\s*/', $attr)) // equals sign
					{
					$working = 1;
					$mode = 2;
					$attr = preg_replace('/^\s*=\s*/', '', $attr);
					break;
				}

				if (preg_match('/^\s+/', $attr)) // valueless
					{
					$working = 1;
					$mode = 0;
					if(false === array_key_exists($attrname, $attrarr)) {
						$attrarr[$attrname] = array ('name' => $attrname, 'value' => '', 'whole' => $attrname, 'vless' => 'y');
					}
					$attr = preg_replace('/^\s+/', '', $attr);
				}

				break;

			case 2 : // attribute value, a URL after href= for instance

				if (preg_match('%^"([^"]*)"(\s+|/?$)%', $attr, $match))
					// "value"
					{
					$thisval = $match[1];
					if ( in_array(strtolower($attrname), $uris) )
						$thisval = wp_kses_bad_protocol($thisval, $allowed_protocols);

					if(false === array_key_exists($attrname, $attrarr)) {
						$attrarr[$attrname] = array ('name' => $attrname, 'value' => $thisval, 'whole' => "$attrname=\"$thisval\"", 'vless' => 'n');
					}
					$working = 1;
					$mode = 0;
					$attr = preg_replace('/^"[^"]*"(\s+|$)/', '', $attr);
					break;
				}

				if (preg_match("%^'([^']*)'(\s+|/?$)%", $attr, $match))
					// 'value'
					{
					$thisval = $match[1];
					if ( in_array(strtolower($attrname), $uris) )
						$thisval = wp_kses_bad_protocol($thisval, $allowed_protocols);

					if(false === array_key_exists($attrname, $attrarr)) {
						$attrarr[$attrname] = array ('name' => $attrname, 'value' => $thisval, 'whole' => "$attrname='$thisval'", 'vless' => 'n');
					}
					$working = 1;
					$mode = 0;
					$attr = preg_replace("/^'[^']*'(\s+|$)/", '', $attr);
					break;
				}

				if (preg_match("%^([^\s\"']+)(\s+|/?$)%", $attr, $match))
					// value
					{
					$thisval = $match[1];
					if ( in_array(strtolower($attrname), $uris) )
						$thisval = wp_kses_bad_protocol($thisval, $allowed_protocols);

					if(false === array_key_exists($attrname, $attrarr)) {
						$attrarr[$attrname] = array ('name' => $attrname, 'value' => $thisval, 'whole' => "$attrname=\"$thisval\"", 'vless' => 'n');
					}
					// We add quotes to conform to W3C's HTML spec.
					$working = 1;
					$mode = 0;
					$attr = preg_replace("%^[^\s\"']+(\s+|$)%", '', $attr);
				}

				break;
		} // switch

		if ($working == 0) // not well formed, remove and try again
		{
			$attr = wp_kses_html_error($attr);
			$mode = 0;
		}
	} // while

	if ($mode == 1 && false === array_key_exists($attrname, $attrarr))
		// special case, for when the attribute list ends with a valueless
		// attribute like "selected"
		$attrarr[$attrname] = array ('name' => $attrname, 'value' => '', 'whole' => $attrname, 'vless' => 'y');

	return $attrarr;
}

/**
 * Finds all attributes of an HTML element.
 *
 * Does not modify input.  May return "evil" output.
 *
 * Based on wp_kses_split2() and wp_kses_attr()
 *
 * @since 4.2.3
 *
 * @param string $element HTML element/tag
 * @return array|bool List of attributes found in $element. Returns false on failure.
 */
function wp_kses_attr_parse( $element ) {
	$valid = preg_match('%^(<\s*)(/\s*)?([a-zA-Z0-9]+\s*)([^>]*)(>?)$%', $element, $matches);
	if ( 1 !== $valid ) {
		return false;
	}

	$begin =  $matches[1];
	$slash =  $matches[2];
	$elname = $matches[3];
	$attr =   $matches[4];
	$end =    $matches[5];

	if ( '' !== $slash ) {
		// Closing elements do not get parsed.
		return false;
	}

	// Is there a closing XHTML slash at the end of the attributes?
	if ( 1 === preg_match( '%\s*/\s*$%', $attr, $matches ) ) {
		$xhtml_slash = $matches[0];
		$attr = substr( $attr, 0, -strlen( $xhtml_slash ) );
	} else {
		$xhtml_slash = '';
	}
	
	// Split it
	$attrarr = wp_kses_hair_parse( $attr );
	if ( false === $attrarr ) {
		return false;
	}

	// Make sure all input is returned by adding front and back matter.
	array_unshift( $attrarr, $begin . $slash . $elname );
	array_push( $attrarr, $xhtml_slash . $end );
	
	return $attrarr;
}

/**
 * Builds an attribute list from string containing attributes.
 *
 * Does not modify input.  May return "evil" output.
 * In case of unexpected input, returns false instead of stripping things.
 *
 * Based on wp_kses_hair() but does not return a multi-dimensional array.
 *
 * @since 4.2.3
 *
 * @param string $attr Attribute list from HTML element to closing HTML element tag
 * @return array|bool List of attributes found in $attr. Returns false on failure.
 */
function wp_kses_hair_parse( $attr ) {
	if ( '' === $attr ) {
		return array();
	}

	$regex =
	  '(?:'
	.     '[-a-zA-Z:]+'   // Attribute name.
	. '|'
	.     '\[\[?[^\[\]]+\]\]?' // Shortcode in the name position implies unfiltered_html.
	. ')'
	. '(?:'               // Attribute value.
	.     '\s*=\s*'       // All values begin with '='
	.     '(?:'
	.         '"[^"]*"'   // Double-quoted
	.     '|'
	.         "'[^']*'"   // Single-quoted
	.     '|'
	.         '[^\s"\']+' // Non-quoted
	.         '(?:\s|$)'  // Must have a space
	.     ')'
	. '|'
	.     '(?:\s|$)'      // If attribute has no value, space is required.
	. ')'
	. '\s*';              // Trailing space is optional except as mentioned above.

	// Although it is possible to reduce this procedure to a single regexp,
	// we must run that regexp twice to get exactly the expected result.

	$validation = "%^($regex)+$%";
	$extraction = "%$regex%";

	if ( 1 === preg_match( $validation, $attr ) ) {
		preg_match_all( $extraction, $attr, $attrarr );
		return $attrarr[0];
	} else {
		return false;
	}
}

/**
 * Performs different checks for attribute values.
 *
 * The currently implemented checks are "maxlen", "minlen", "maxval", "minval"
 * and "valueless".
 *
 * @since 1.0.0
 *
 * @param string $value      Attribute value
 * @param string $vless      Whether the value is valueless. Use 'y' or 'n'
 * @param string $checkname  What $checkvalue is checking for.
 * @param mixed  $checkvalue What constraint the value should pass
 * @return bool Whether check passes
 */
function wp_kses_check_attr_val($value, $vless, $checkname, $checkvalue) {
	$ok = true;

	switch (strtolower($checkname)) {
		case 'maxlen' :
			// The maxlen check makes sure that the attribute value has a length not
			// greater than the given value. This can be used to avoid Buffer Overflows
			// in WWW clients and various Internet servers.

			if (strlen($value) > $checkvalue)
				$ok = false;
			break;

		case 'minlen' :
			// The minlen check makes sure that the attribute value has a length not
			// smaller than the given value.

			if (strlen($value) < $checkvalue)
				$ok = false;
			break;

		case 'maxval' :
			// The maxval check does two things: it checks that the attribute value is
			// an integer from 0 and up, without an excessive amount of zeroes or
			// whitespace (to avoid Buffer Overflows). It also checks that the attribute
			// value is not greater than the given value.
			// This check can be used to avoid Denial of Service attacks.

			if (!preg_match('/^\s{0,6}[0-9]{1,6}\s{0,6}$/', $value))
				$ok = false;
			if ($value > $checkvalue)
				$ok = false;
			break;

		case 'minval' :
			// The minval check makes sure that the attribute value is a positive integer,
			// and that it is not smaller than the given value.

			if (!preg_match('/^\s{0,6}[0-9]{1,6}\s{0,6}$/', $value))
				$ok = false;
			if ($value < $checkvalue)
				$ok = false;
			break;

		case 'valueless' :
			// The valueless check makes sure if the attribute has a value
			// (like <a href="blah">) or not (<option selected>). If the given value
			// is a "y" or a "Y", the attribute must not have a value.
			// If the given value is an "n" or an "N", the attribute must have one.

			if (strtolower($checkvalue) != $vless)
				$ok = false;
			break;
	} // switch

	return $ok;
}

/**
 * Sanitize string from bad protocols.
 *
 * This function removes all non-allowed protocols from the beginning of
 * $string. It ignores whitespace and the case of the letters, and it does
 * understand HTML entities. It does its work in a while loop, so it won't be
 * fooled by a string like "javascript:javascript:alert(57)".
 *
 * @since 1.0.0
 *
 * @param string $string            Content to filter bad protocols from
 * @param array  $allowed_protocols Allowed protocols to keep
 * @return string Filtered content
 */
function wp_kses_bad_protocol($string, $allowed_protocols) {
	$string = wp_kses_no_null($string);
	$iterations = 0;

	do {
		$original_string = $string;
		$string = wp_kses_bad_protocol_once($string, $allowed_protocols);
	} while ( $original_string != $string && ++$iterations < 6 );

	if ( $original_string != $string )
		return '';

	return $string;
}

// refactored. function wp_kses_no_null( $string, $options = null ) {}

/**
 * Strips slashes from in front of quotes.
 *
 * This function changes the character sequence \" to just ". It leaves all
 * other slashes alone. It's really weird, but the quoting from
 * preg_replace(//e) seems to require this.
 *
 * @since 1.0.0
 *
 * @param string $string String to strip slashes
 * @return string Fixed string with quoted slashes
 */
function wp_kses_stripslashes($string) {
	return preg_replace('%\\\\"%', '"', $string);
}

// refactored. function wp_kses_array_lc($inarray) {}

/**
 * Handles parsing errors in wp_kses_hair().
 *
 * The general plan is to remove everything to and including some whitespace,
 * but it deals with quotes and apostrophes as well.
 *
 * @since 1.0.0
 *
 * @param string $string
 * @return string
 */
function wp_kses_html_error($string) {
	return preg_replace('/^("[^"]*("|$)|\'[^\']*(\'|$)|\S)*\s*/', '', $string);
}

/**
 * Sanitizes content from bad protocols and other characters.
 *
 * This function searches for URL protocols at the beginning of $string, while
 * handling whitespace and HTML entities.
 *
 * @since 1.0.0
 *
 * @param string $string            Content to check for bad protocols
 * @param string $allowed_protocols Allowed protocols
 * @return string Sanitized content
 */
function wp_kses_bad_protocol_once($string, $allowed_protocols, $count = 1 ) {
	$string2 = preg_split( '/:|&#0*58;|&#x0*3a;/i', $string, 2 );
	if ( isset($string2[1]) && ! preg_match('%/\?%', $string2[0]) ) {
		$string = trim( $string2[1] );
		$protocol = wp_kses_bad_protocol_once2( $string2[0], $allowed_protocols );
		if ( 'feed:' == $protocol ) {
			if ( $count > 2 )
				return '';
			$string = wp_kses_bad_protocol_once( $string, $allowed_protocols, ++$count );
			if ( empty( $string ) )
				return $string;
		}
		$string = $protocol . $string;
	}

	return $string;
}

/**
 * Callback for wp_kses_bad_protocol_once() regular expression.
 *
 * This function processes URL protocols, checks to see if they're in the
 * whitelist or not, and returns different data depending on the answer.
 *
 * @access private
 * @since 1.0.0
 *
 * @param string $string            URI scheme to check against the whitelist
 * @param string $allowed_protocols Allowed protocols
 * @return string Sanitized content
 */
function wp_kses_bad_protocol_once2( $string, $allowed_protocols ) {
	$string2 = wp_kses_decode_entities($string);
	$string2 = preg_replace('/\s/', '', $string2);
	$string2 = wp_kses_no_null($string2);
	$string2 = strtolower($string2);

	$allowed = false;
	foreach ( (array) $allowed_protocols as $one_protocol )
		if ( strtolower($one_protocol) == $string2 ) {
			$allowed = true;
			break;
		}

	if ($allowed)
		return "$string2:";
	else
		return '';
}

// refactored. function wp_kses_normalize_entities($string) {}
// :
// refactored. function valid_unicode($i) {}

/**
 * Convert all entities to their character counterparts.
 *
 * This function decodes numeric HTML entities (`&#65;` and `&#x41;`).
 * It doesn't do anything with other entities like &auml;, but we don't
 * need them in the URL protocol whitelisting system anyway.
 *
 * @since 1.0.0
 *
 * @param string $string Content to change entities
 * @return string Content after decoded entities
 */
function wp_kses_decode_entities($string) {
	$string = preg_replace_callback('/&#([0-9]+);/', '_wp_kses_decode_entities_chr', $string);
	$string = preg_replace_callback('/&#[Xx]([0-9A-Fa-f]+);/', '_wp_kses_decode_entities_chr_hexdec', $string);

	return $string;
}

/**
 * Regex callback for wp_kses_decode_entities()
 *
 * @since 2.9.0
 *
 * @param array $match preg match
 * @return string
 */
function _wp_kses_decode_entities_chr( $match ) {
	return chr( $match[1] );
}

/**
 * Regex callback for wp_kses_decode_entities()
 *
 * @since 2.9.0
 *
 * @param array $match preg match
 * @return string
 */
function _wp_kses_decode_entities_chr_hexdec( $match ) {
	return chr( hexdec( $match[1] ) );
}

/**
 * Sanitize content with allowed HTML Kses rules.
 *
 * @since 1.0.0
 *
 * @param string $data Content to filter, expected to be escaped with slashes
 * @return string Filtered content
 */
function wp_filter_kses( $data ) {
	return addslashes( wp_kses( stripslashes( $data ), current_filter() ) );
}

// refactored. function wp_kses_data( $data ) {}

/**
 * Sanitize content for allowed HTML tags for post content.
 *
 * Post content refers to the page contents of the 'post' type and not $_POST
 * data from forms.
 *
 * @since 2.0.0
 *
 * @param string $data Post content to filter, expected to be escaped with slashes
 * @return string Filtered post content with allowed HTML tags and attributes intact.
 */
function wp_filter_post_kses( $data ) {
	return addslashes( wp_kses( stripslashes( $data ), 'post' ) );
}

/**
 * Sanitize content for allowed HTML tags for post content.
 *
 * Post content refers to the page contents of the 'post' type and not $_POST
 * data from forms.
 *
 * @since 2.9.0
 *
 * @param string $data Post content to filter
 * @return string Filtered post content with allowed HTML tags and attributes intact.
 */
function wp_kses_post( $data ) {
	return wp_kses( $data, 'post' );
}

/**
 * Navigates through an array, object, or scalar, and sanitizes content for
 * allowed HTML tags for post content.
 *
 * @since 4.4.2
 *
 * @see map_deep()
 *
 * @param mixed $data The array, object, or scalar value to inspect.
 * @return mixed The filtered content.
 */
function wp_kses_post_deep( $data ) {
	return map_deep( $data, 'wp_kses_post' );
}

/**
 * Strips all of the HTML in the content.
 *
 * @since 2.1.0
 *
 * @param string $data Content to strip all HTML from
 * @return string Filtered content without any HTML
 */
function wp_filter_nohtml_kses( $data ) {
	return addslashes( wp_kses( stripslashes( $data ), 'strip' ) );
}

/**
 * Adds all Kses input form content filters.
 *
 * All hooks have default priority. The wp_filter_kses() function is added to
 * the 'pre_comment_content' and 'title_save_pre' hooks.
 *
 * The wp_filter_post_kses() function is added to the 'content_save_pre',
 * 'excerpt_save_pre', and 'content_filtered_save_pre' hooks.
 *
 * @since 2.0.0
 */
function kses_init_filters() {
	// Normal filtering
	add_filter('title_save_pre', 'wp_filter_kses');

	// Comment filtering
	if ( current_user_can( 'unfiltered_html' ) )
		add_filter( 'pre_comment_content', 'wp_filter_post_kses' );
	else
		add_filter( 'pre_comment_content', 'wp_filter_kses' );

	// Post filtering
	add_filter('content_save_pre', 'wp_filter_post_kses');
	add_filter('excerpt_save_pre', 'wp_filter_post_kses');
	add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
}

/**
 * Removes all Kses input form content filters.
 *
 * A quick procedural method to removing all of the filters that kses uses for
 * content in WordPress Loop.
 *
 * Does not remove the kses_init() function from {@see 'init'} hook (priority is
 * default). Also does not remove kses_init() function from {@see 'set_current_user'}
 * hook (priority is also default).
 *
 * @since 2.0.6
 */
function kses_remove_filters() {
	// Normal filtering
	remove_filter('title_save_pre', 'wp_filter_kses');

	// Comment filtering
	remove_filter( 'pre_comment_content', 'wp_filter_post_kses' );
	remove_filter( 'pre_comment_content', 'wp_filter_kses' );

	// Post filtering
	remove_filter('content_save_pre', 'wp_filter_post_kses');
	remove_filter('excerpt_save_pre', 'wp_filter_post_kses');
	remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
}

/**
 * Sets up most of the Kses filters for input form content.
 *
 * If you remove the kses_init() function from {@see 'init'} hook and
 * {@see 'set_current_user'} (priority is default), then none of the Kses filter hooks
 * will be added.
 *
 * First removes all of the Kses filters in case the current user does not need
 * to have Kses filter the content. If the user does not have unfiltered_html
 * capability, then Kses filters are added.
 *
 * @since 2.0.0
 */
function kses_init() {
	kses_remove_filters();

	if ( ! current_user_can( 'unfiltered_html' ) ) {
		kses_init_filters();
	}
}

/**
 * Inline CSS filter
 *
 * @since 2.8.1
 *
 * @param string $css        A string of CSS rules.
 * @param string $deprecated Not used.
 * @return string            Filtered string of CSS rules.
 */
function safecss_filter_attr( $css, $deprecated = '' ) {
	if ( !empty( $deprecated ) )
		_deprecated_argument( __FUNCTION__, '2.8.1' ); // Never implemented

	$css = wp_kses_no_null($css);
	$css = str_replace(array("\n","\r","\t"), '', $css);

	if ( preg_match( '%[\\\\(&=}]|/\*%', $css ) ) // remove any inline css containing \ ( & } = or comments
		return '';

	$css_array = explode( ';', trim( $css ) );

	/**
	 * Filters list of allowed CSS attributes.
	 *
	 * @since 2.8.1
	 * @since 4.4.0 Added support for `min-height`, `max-height`, `min-width`, and `max-width`.
	 * @since 4.6.0 Added support for `list-style-type`.
	 *
	 * @param array $attr List of allowed CSS attributes.
	 */
	$allowed_attr = apply_filters( 'safe_style_css', array(
		'background',
		'background-color',

		'border',
		'border-width',
		'border-color',
		'border-style',
		'border-right',
		'border-right-color',
		'border-right-style',
		'border-right-width',
		'border-bottom',
		'border-bottom-color',
		'border-bottom-style',
		'border-bottom-width',
		'border-left',
		'border-left-color',
		'border-left-style',
		'border-left-width',
		'border-top',
		'border-top-color',
		'border-top-style',
		'border-top-width',

		'border-spacing',
		'border-collapse',
		'caption-side',

		'color',
		'font',
		'font-family',
		'font-size',
		'font-style',
		'font-variant',
		'font-weight',
		'letter-spacing',
		'line-height',
		'text-decoration',
		'text-indent',
		'text-align',

		'height',
		'min-height',
		'max-height',

		'width',
		'min-width',
		'max-width',

		'margin',
		'margin-right',
		'margin-bottom',
		'margin-left',
		'margin-top',

		'padding',
		'padding-right',
		'padding-bottom',
		'padding-left',
		'padding-top',

		'clear',
		'cursor',
		'direction',
		'float',
		'overflow',
		'vertical-align',
		'list-style-type',
	) );

	if ( empty($allowed_attr) )
		return $css;

	$css = '';
	foreach ( $css_array as $css_item ) {
		if ( $css_item == '' )
			continue;
		$css_item = trim( $css_item );
		$found = false;
		if ( strpos( $css_item, ':' ) === false ) {
			$found = true;
		} else {
			$parts = explode( ':', $css_item );
			if ( in_array( trim( $parts[0] ), $allowed_attr ) )
				$found = true;
		}
		if ( $found ) {
			if( $css != '' )
				$css .= ';';
			$css .= $css_item;
		}
	}

	return $css;
}

// refactored. function _wp_add_global_attributes( $value ) {}
