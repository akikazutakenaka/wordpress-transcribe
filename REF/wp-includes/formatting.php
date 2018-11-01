<?php
/**
 * Main WordPress Formatting API.
 *
 * Handles many functions for formatting output.
 *
 * @package WordPress
 */

/**
 * Replaces common plain text characters into formatted entities.
 *
 * As an example,
 *
 *     'cause today's effort makes it worth tomorrow's "holiday" ...
 *
 * Becomes:
 *
 *     &#8217;cause today&#8217;s effort makes it worth tomorrow&#8217;s &#8220;holiday&#8221; &#8230;
 *
 * Code within certain html blocks are skipped.
 *
 * Do not use this function before the {@see 'init'} action hook; everything will break.
 *
 * @since     0.71
 * @global    array  $wp_cockneyreplace Array of formatted entities for certain common phrases
 * @global    array  $shortcode_tags
 * @staticvar array  $static_characters
 * @staticvar array  $static_replacements
 * @staticvar array  $dynamic_characters
 * @staticvar array  $dynamic_replacements
 * @staticvar array  $default_no_texturize_tags
 * @staticvar array  $default_no_texturize_shortcodes
 * @staticvar bool   $run_texturize
 * @staticvar string $apos
 * @staticvar string $prime
 * @staticvar string $double_prime
 * @staticvar string $opening_quote
 * @staticvar string $closing_quote
 * @staticvar string $opening_single_quote
 * @staticvar string $closing_single_quote
 * @staticvar string $open_q_flag
 * @staticvar string $open_sq_flag
 * @staticvar string $apos_flag
 *
 * @param  string $text  The text to be formatted.
 * @param  bool   $reset Set to true for unit testing.
 *                       Translated patterns will reset.
 * @return string The string replaced with html entitites.
 */
function wptexturize( $text, $reset = FALSE )
{
	global $wp_cockneyreplace, $shortcode_tags;
	static $static_characters = NULL, $static_replacements = NULL, $dynamic_characters = NULL, $dynamic_replacements = NULL, $default_no_texturize_tags = NULL, $default_no_texturize_shortcodes = NULL, $run_texturize = TRUE, $apos = NULL, $prime = NULL, $double_prime = NULL, $opening_quote = NULL, $closing_quote = NULL, $opening_single_quote = NULL, $closing_single_quote = NULL, $open_q_flag = '<!--oq-->', $open_sq_flag = '<!--osq-->', $apos_flag = '<!--apos-->';

	// If there's nothing to do, just stop.
	if ( empty( $text ) || FALSE === $run_texturize ) {
		return $text;
	}

	/**
	 * Set up static variables.
	 * Run once only.
	 */
	if ( $reset || ! isset( $static_characters ) ) {
		/**
		 * Filters whether to skip running wptexturize().
		 *
		 * Passing false to the filter will effectively short-circuit wptexturize().
		 * Returning the original text passed to the function instead.
		 *
		 * The filter runs only once, the first time wptexturize() is called.
		 *
		 * @since 4.0.0
		 * @see   wptexturize()
		 *
		 * @param bool $run_texturize Whether to short-circuit wptexturize().
		 */
		$run_texturize = apply_filters( 'run_wptexturize', $run_texturize );

		if ( FALSE === $run_texturize ) {
			return $text;
		}

		$opening_quote = _x( '&#8220;', 'opening curly double quote' );
		$closing_quote = _x( '&#8221;', 'closing curly double quote' );
		$apos = _x( '&#8217;', 'apostrophe' );
		$prime = _x( '&#8242;', 'prime' );
		$double_prime = _x( '&#8243;', 'double prime' );
		$opening_single_quote = _x( '&#8216;', 'opening curly single quote' );
		$closing_single_quote = _x( '&#8217;', 'closing curly single quote' );
		$en_dash = _x( '&#8211;', 'en dash' );
		$em_dash = _x( '&#8212;', 'em dash' );
		$default_no_texturize_tags = array( 'pre', 'code', 'kbd', 'style', 'script', 'tt' );
		$default_no_texturize_shortcodes = array( 'code' );

		// If a plugin has provided an autocorrect array, use it
		if ( isset( $wp_cockneyreplace ) ) {
			$cockney = array_keys( $wp_cockneyreplace );
			$cockneyreplace = array_values( $wp_cockneyreplace );
		} else {
			$cockney = explode( ',', _x( "'tain't,'twere,'twas,'tis,'twill,'til,'bout,'nuff,'round,'cause,'em", 'Comma-separated list of words to texturize in your language' ) );
			$cockneyreplace = explode( ',', _x( '&#8217;tain&#8217;t,&#8217;twere,&#8217;twas,&#8217;tis,&#8217;twill,&#8217;til,&#8217;bout,&#8217;nuff,&#8217;round,&#8217;cause,&#8217;em', 'Comma-separated list of replacement words in your language' ) );
		}

		$static_characters = array_merge( array( '...', '``', '\'\'', ' (tm)' ), $cockney );
		$static_replacements = array_merge( array( '&#8230;', $opening_quote, $closing_quote, ' &#8482;' ), $cockneyreplace );

		/**
		 * Pattern-based replacements of characters.
		 * Sort the remaining patterns into several arrays for performance tuning.
		 */
		$dynamic_characters = array(
			'apos'  => array(),
			'quote' => array(),
			'dash'  => array()
		);
		$dynamic_replacements = array(
			'apos'  => array(),
			'quote' => array(),
			'dash'  => array()
		);
		$dynamic = array();
		$spaces = wp_spaces_regexp();

		// '99' and '99" are ambiguous among other patterns; assume it's an abbreviated year at the end of a quotation.
		if ( "'" !== $apos || "'" !== $closing_single_quote ) {
			$dynamic[ '/\'(\d\d)\'(?=\Z|[.,:;!?)}\-\]]|&gt;|' . $spaces . ')/' ] = $apos_flag . '$1' . $closing_single_quote;
		}

		if ( "'" !== $apos || '"' !== $closing_quote ) {
			$dynamic[ '/\'(\d\d)"(?=\Z|[.,:;!?)}\-\]]|&gt;|' . $spaces . ')/' ] = $apos_flag . '$1' . $closing_quote;
		}

		// '99 '99s '99's (apostrophe) But never '9 or '99% or '999 or '99.0.
		if ( "'" !== $apos ) {
			$dynamic['/\'(?=\d\d(?:\Z|(?![%\d]|[.,]\d)))/'] = $apos_flag;
		}

		// Quoted Numbers like '0.42'
		if ( "'" !== $opening_single_quote && "'" !== $closing_single_quote ) {
			$dynamic[ '/(?<=\A|' . $spaces . ')\'(\d[.,\d]*)\'/' ] = $open_sq_flag . '$1' . $closing_single_quote;
		}

		// Single quote at start, or preceded by (, {, <, [, ", -, or spaces.
		if ( "'" !== $opening_single_quote ) {
			$dynamic[ ' /(?<=\A|[([{"\-]|&lt;|' . $spaces . ')\'/' ] = $open_sq_flag;
		}

		/**
		 * Apostrophe in a word.
		 * No spaces, double apostrophes, or other punctuation.
		 */
		if ( "'" !== $apos ) {
			$dynamic[ '/(?<!' . $spaces . ')\'(?!\Z|[.,:;!?"\'(){}[\]\-]|&[lg]t;|' . $spaces . ')/' ] = $apos_flag;
		}

		$dynamic_characters['apos'] = array_keys( $dynamic );
		$dynamic_replacements['apos'] = array_values( $dynamic );
		$dynamic = array();

		// Quoted Numbers like "42"
		if ( '"' !== $opening_quote && '"' !== $closing_quote ) {
			$dynamic[ '/(?<=\A|' . $spaces . ')"(\d[.,\d]*)"/' ] = $open_q_flag . '$1' . $closing_quote;
		}

		// Double quote at start, or preceded by (, {, <, [, -, or spaces, and not followed by spaces.
		if ( '"' !== $opening_quote ) {
			$dynamic[ '/(?<=\A|[([{\-]|&lt;|' . $spaces . ')"(?!' . $spaces . ')/' ] = $open_q_flag;
		}

		$dynamic_characters['quote'] = array_keys( $dynamic );
		$dynamic_replacements['quote'] = array_values( $dynamic );
		$dynamic = array();

		// Dashes and spaces
		$dynamic['/---/'] = $em_dash;
		$dynamic[ '/(?<=^|' . $spaces . ')--(?=$|' . $spaces . ')/' ] = $em_dash;
		$dynamic['/(?<!xn)--/'] = $en_dash;
		$dynamic[ '/(?<=^|' . $spaces . ')-(?=$|' . $spaces . ')/' ] = $en_dash;
		$dynamic_characters['dash'] = array_keys( $dynamic );
		$dynamic_replacements['dash'] = array_values( $dynamic );
	}

	// Must do this every time in case plugins use these filters in a context sensitive manner.

	/**
	 * Filters the list of HTML elements not to texturize.
	 *
	 * @since 2.8.0
	 *
	 * @param array $default_no_texturize_tags An array of HTML element names.
	 */
	$no_texturize_tags = apply_filters( 'no_texturize_tags', $default_no_texturize_tags );

	/**
	 * Filters the list of shortcodes not to texturize.
	 *
	 * @since 2.8.0
	 *
	 * @param array $default_no_texturize_shortcodes An array of shortcode names.
	 */
	$no_texturize_shortcodes = apply_filters( 'no_texturize_shortcodes', $default_no_texturize_shortcodes );

	$no_texturize_tags_stack = array();
	$no_texturize_shortcodes_stack = array();

	// Look for shortcodes and HTML elements.
	preg_match_all( '@\[/?([^<>&/\[\]\x00-\x20=]++)@', $text, $matches );
	$tagnames = array_intersect( array_keys( $shortcode_tags ), $matches[1] );
	$found_shortcodes = ! empty( $tagnames );

	$shortcode_regex = $found_shortcodes
		? _get_wptexturize_shortcode_regex( $tagnames )
		: '';

	$regex = _get_wptexturize_split_regex( $shortcode_regex );
	$textarr = preg_split( $regex, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

	foreach ( $textarr as &$url ) {
		// Only call _wptexturize_pushpop_element if $curl is a delimiter.
		$first = $curl[0];

		if ( '<' === $first ) {
			if ( '<!--' === substr( $curl, 0, 4 ) ) {
				// This is an HTML comment delimiter.
				continue;
			} else {
				// This is an HTML element delimiter.

				// Replace each & with &#038; unelss it already looks like an entity.
				$curl = preg_replace( '/&(?!#(?:\d+|x[a-f0-9]+);|[a-z1-4]{1,8};)/i', '&#038;', $curl );

				_wptexturize_pushpop_element( $curl, $no_texturize_tags_stack, $no_texturize_tags );
			}
		} elseif ( '' === trim( $curl ) ) {
			/**
			 * This is a newline between delimiters.
			 * Performance improves when we check this.
			 */
			continue;
		} elseif ( '[' === $first && $found_shortcodes && 1 === preg_match( '/^' . $shortcode_regex . '$/', $curl ) ) {
			// This is a shortcode delimiter.
			if ( '[[' !== substr( $curl, 0, 2 ) && ']]' !== substr( $curl, -2 ) ) {
				// Looks like a normal shortcode.
				_wptexturize_pushpop_element( $curl, $no_texturize_shortcodes_stack, $no_texturize_shortcodes );
			} else {
				// Looks like an escaped shortcode.
				continue;
			}
		} elseif ( empty( $no_texturize_shortcodes_stack ) && empty( $no_texturize_tags_stack ) ) {
			/**
			 * This is neither a delimiter, nor is this content inside of no_texturize pairs.
			 * Do texturize
			 */
			$curl = str_replace( $static_characters, $static_replacements, $curl );

			if ( FALSE !== strpos( $curl, "'" ) ) {
				$curl = preg_replace( $dynamic_characters['apos'], $dynamic_replacements['apos'], $curl );
				$curl = wptexturize_primes( $curl, "'", $prime, $open_sq_flag, $closing_single_quote );
				$curl = str_replace( $apos_flag, $apos, $curl );
				$curl = str_replace( $open_sq_flag, $opening_single_quote, $curl );
			}

			if ( FALSE !== strpos( $curl, '"' ) ) {
				$curl = preg_replace( $dynamic_characters['quote'], $dynamic_replacements['quote'], $curl );
				$curl = wptexturize_primes( $curl, '"', $double_prime, $open_q_flag, $closing_quote );
				$curl = str_replace( $open_q_flag, $opening_quote, $curl );
			}

			if ( FALSE !== strpos( $curl, '-' ) ) {
				$curl = preg_replace( $dynamic_characters['dash'], $dynamic_replacements['dash'], $curl );
			}

			// 9x9 (times), but never 0x9999
			if ( 1 === preg_match( '/(?<=\d)x\d/', $curl ) ) {
				// Searching for a digit is 10 times more expensive than for the x, so we avoid doing this one!
				$curl = preg_replace( '/\b(\d(?(?<=0)[\d\.,]+|[\d\.,]*))x(\d[\d.,]*)\b/', '$1&#215;$2', $curl );
			}

			// Replace each & with &#038; unless it already looks like an entity.
		}
	}

	return implode( '', $textarr );
}

/**
 * Implements a logic tree to determine whether or not "7'." represents seven feet, then converts the special char into either a prime char or a closing quote char.
 *
 * @since 4.3.0
 *
 * @param  string $haystack    The plain text to be searched.
 * @param  string $needle      The character to search for such as ' or ".
 * @param  string $prime       The prime char to use for replacement.
 * @param  string $open_quote  The opening quote char.
 *                             Opening quote replacement must be accomplished already.
 * @param  string $close_quote The closing quote char to use for replacement.
 * @return string The $haystack value after primes and quotes replacements.
 */
function wptexturize_primes( $haystack, $needle, $prime, $open_quote, $close_quote )
{
	$spaces = wp_spaces_regexp();
	$flag = '<!--wp-prime-or-quote-->';
	$quote_pattern = "/$needle(?=\\Z|[.,:;!?)}\\-\\]]|&gt;|" . $spaces . ")/";
	$prime_pattern    = "/(?<=\\d)$needle/";
	$flag_after_digit = "/(?<=\\d)$flag/";
	$flag_no_digit    = "/(?<!\\d)$flag/";
	$sentenes = explode( $open_quote, $haystack );

	foreach ( $sentences as $key => &$sentence ) {
		if ( FALSE === strpos( $sentence, $needle ) ) {
			continue;
		} elseif ( 0 !== $key && 0 === substr_count( $sentence, $close_quote ) ) {
			$sentence = preg_replace( $quote_pattern, $flag, $sentence, -1, $count );

			if ( $count > 1 ) {
				/**
				 * This sentence appears to have multiple closing quotes.
				 * Attempt Vulcan logic.
				 */
				$sentence = preg_replace( $flag_no_digit, $close_quote, $sentence, -1, $count2 );

				if ( 0 === $count2 ) {
					// Try looking for a quote followed by a period.
					$count2 = substr_count( $sentence, "$flag." );

					$pos = $count2 > 0
						? // Assume the rightmost quote-period match is the end of quotation.
							strrpos( $sentence, "$flag." )
						: /**
						   * When all else fails, make the rightmost candidate a closing quote.
						   * This is most likely to be problematic in the context of bug #18549.
						   */
							strrpos( $sentence, $flag );

					$sentence = substr_replace( $sentence, $close_quote, $pos, strlen( $flag ) );
				}

				// Use conventional replacement on any remaining primes and quotes.
				$sentence = preg_replace( $prime_pattern, $prime, $sentence );
				$sentence = preg_replace( $flag_after_digit, $prime, $sentence );
				$sentence = str_replace( $flag, $close_quote, $sentence );
			} elseif ( 1 == $count ) {
				// Found only one closing quote candidate, so give it priority over primes.
				$sentence = str_replace( $flag, $close_quote, $sentence );
				$sentence = preg_replace( $prime_pattern, $prime, $sentence );
			} else {
				/**
				 * No closing quotes found.
				 * Just run primes pattern.
				 */
				$sentence = preg_replace( $prime_pattern, $prime, $sentence );
			}
		} else {
			$sentence = preg_replace( $prime_pattern, $prime, $sentence );
			$sentence = preg_replace( $quote_pattern, $close_quote, $sentence );
		}

		if ( '"' == $needle && FALSE !== strpos( $sentence, '"' ) ) {
			$sentence = str_replace( '"', $close_quote, $sentence );
		}
	}

	return implode( $open_quote, $sentences );
}

/**
 * Search for disabled element tags.
 * Push element to stack on tag open and pop on tag close.
 *
 * Assumes first char of $text is tag opening and last char is tag closing.
 * Assumes second char of $text is optionally '/' to indicate closing as in </html>.
 *
 * @since  2.9.0
 * @access private
 *
 * @param string $text              Text to check.
 *                                  Must be a tag like `<html>` or `[shortcode]`.
 * @param array  $stack             List of open tag elements.
 * @param array  $disabled_elements The tag names to match against.
 *                                  Spaces are not allowed in tag names.
 */
function _wptexturize_pushpop_element( $text, &$stack, $disabled_elements )
{
	// Is it an opening tag or closing tag?
	if ( isset( $text[1] ) && '/' !== $text[1] ) {
		$opening_tag = TRUE;
		$name_offset = 1;
	} elseif ( 0 == count( $stack ) ) {
		/**
		 * Stack is empty.
		 * Just stop.
		 */
		return;
	} else {
		$opening_tag = FALSE;
		$name_offset = 2;
	}

	// Parse out the tag name.
	$space = strpos( $text, ' ' );

	if ( FALSE === $space ) {
		$space = -1;
	} else {
		$space -= $name_offset;
	}

	$tag = substr( $text, $name_offset, $space );

	// Handle disabled tags.
	if ( in_array( $tag, $disabled_elements ) ) {
		if ( $opening_tag ) {
			/**
			 * This disables texturize until we find a closing tag of our type (e.g. <pre>) even if there was invalid nesting before that.
			 *
			 * Example: in the case <pre>sadsadasd</code>"baba"</pre>
			 *          "baba" won't be texturize
			 */
			array_push( $stack, $tag );
		} elseif ( end( $stack ) == $tag ) {
			array_pop( $stack );
		}
	}
}

/**
 * Replaces double line-breaks with paragraph elements.
 *
 * A group of regex replaces used to identify text formatted with newlines and replace double line-breaks with HTML paragraph tags.
 * The remaining line-breaks after conversion become <<br />> tags, unless $br is set to '0' or 'false'.
 *
 * @since 0.71
 *
 * @param  string $pee The text which has to be formatted.
 * @param  bool   $br  Optional.
 *                      If set, this will convert all remaining line-breaks after paragraphing.
 *                      Default true.
 * @return string Text which has been converted into correct paragraph tags.
 */
function wpautop( $pee, $br = TRUE )
{
	$pre_tags = array();

	if ( trim( $pee ) === '' ) {
		return '';
	}

	// Just to make things a little easier, pad the end.
	$pee = $pee . "\n";

	/**
	 * Pre tags shouldn't be touched by autop.
	 * Replace pre tags with placeholders and bring them back after autop.
	 */
	if ( strpos( $pee, '<pre' ) !== FALSE ) {
		$pee_parts = explode( '</pre>', $pee );
		$last_pee = array_pop( $pee_parts );
		$pee = '';
		$i = 0;

		foreach ( $pee_parts as $pee_part ) {
			$start = strpos( $pee_part, '<pre' );

			// Malformed HTML?
			if ( $start === FALSE ) {
				$pee .= $pee_part;
				continue;
			}

			$name = "<pre wp-pre-tag-$i></pre>";
			$pre_tags[ $name ] = substr( $pee_part, $start ) . '</pre>';
			$pee .= substr( $pee_part, 0, $start ) . $name;
			$i++;
		}

		$pee .= $last_pee;
	}

	// Change multiple <br>s into two line breaks, which will turn into paragraphs.
	$pee = preg_replace( '|<br\s*/?>\s*<br\s*/?>|', "\n\n", $pee );

	$allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';

	// Add a double line break above block-level opening tags.
	$pee = preg_replace( '!(<' . $allblocks . '[\s/>])!', "\n\n$1", $pee );

	// Add a double line break below block-level closing tags.
	$pee = preg_replace( '!(</' . $allblock . '>)!', "$1\n\n", $pee );

	// Standardize newline characters to "\n".
	$pee = str_replace( array( "\r\n", "\r" ), "\n", $pee );

	// Find newlines in all elements and add placeholders.
	$pee = wp_replace_in_html_tags( $pee, array( "\n" => " <!-- wpnl --> " ) );

	// Collapse line breaks before and after <option> elements so they don't get autop'd.
	if ( strpos( $pee, '<option' ) !== FALSE ) {
		$pee = preg_replace( '|\s*<option|', '<option', $pee );
		$pee = preg_replace( '|</option>\s*|', '</option>', $pee );
	}

	// Collapse line breaks inside <object> elements, before <param> and <embed> elements so they don't get autop'd.
	if ( strpos( $pee, '</object>' ) !== FALSE ) {
		$pee = preg_replace( '|(<object[^>]*>)\s*|', '$1', $pee );
		$pee = preg_replace( '|\s*</object>|', '</object>', $pee );
		$pee = preg_replace( '%\s*(</?(?:param|embed)[^>]*>)\s*%', '$1', $pee );
	}

	// Collapse line breaks inside <audio> and <video> elements, before and after <source> and <track> elements.
	if ( strpos( $pee, '<source' ) !== FALSE || strpos( $pee, '<track' ) !== FALSE ) {
		$pee = preg_replace( '%([<\[](?:audio|video)[^>\]]*[>\]])\s*%', '$1', $pee );
		$pee = preg_replace( '%\s*([<\[]/(?:audio|video)[>\]])%', '$1', $pee );
		$pee = preg_replace( '%\s*(<(?:source|track)[^>]*>)\s*%', '$1', $pee );
	}

	// Collapse line breaks before and after <figcaption> elements.
	if ( strpos( $pee, '<figcaption' ) !== FALSE ) {
		$pee = preg_replace( '|\s*(<figcaption[^>]*>)|', '$1', $pee );
		$pee = preg_replace( '|</figcaption>\s*|', '</figcaption>', $pee );
	}

	// Remove more than two contiguous line breaks.
	$pee = preg_replace( "/\n\n+/", "\n\n", $pee );

	// Split up the contents into an array of strings, separated by double line breaks.
	$pees = preg_split( '/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY );

	// Reset $pee prior to rebuilding.
	$pee = '';

	// Rebuild the content as a string, wrapping every bit with a <p>.
	foreach ( $pees as $tinkle ) {
		$pee .= '<p>' . trim( $tinkle, "\n" ) . "</p>\n";
	}

	// Under certain strange conditions it could create a P of entirely whitespace.
	$pee = preg_replace( '|<p>\s*</p>|', '', $pee );

	// Add a closing <p> inside <div>, <address>, or <form> tag if missing.
	$pee = preg_replace( '!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee );

	// If an opening or closing block element tag is wrapped in a <p>, unwrap it.
	$pee = preg_replace( '!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee );

	// In some cases <li> may get wrapped in <p>, fix them.
	$pee = preg_replace( "|<p>(<li.+?)</p>|", "$1", $pee );

	// If a <blockquote> is wrapped with a <p>, move it inside the <blockquote>.
	$pee = preg_replace( '|<p><blockquote([^>]*)>|i', "<blockquote>$1><p>", $pee );
	$pee = str_replace( '</blockquote></p>', '</p></blockquote>', $pee );

	// If an opening or closing block element tag is preceded by an opening <p> tag, remove it.
	$pee = preg_replace( '!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee );

	// If an opening or closing block element tag is followed by a closing <p> tag, remove it.
	$pee = preg_replace( '!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee );

	// Optionally insert line breaks.
	if ( $br ) {
		// Replace newlines that shouldn't be touched with a placeholder.
		$pee = preg_replace_callback( '/<(script|style).*?<\/\\1>/s', '_autop_newline_preservation_helper', $pee );

		// Normalize <br>
		$pee = str_replace( array( '<br>', '<br/>' ), '<br />', $pee );

		// Replace any new line characters that aren't preceded by a <br /> with a <br />.
		$pee = preg_replace( '|(?<!<br />)\s*\n|', "<br />\n", $pee );

		// Replace newline placeholders with newlines.
		$pee = str_replace( '<WPPreserveNewline />', "\n", $pee );
	}

	// If a <br /> tag is after an opening or closing block tag, remove it.
	$pee = preg_replace( '!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee );

	// If a <br /> tag is before a subset of opening or closing block tags, remove it.
	$pee = preg_replace( '!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee );
	$pee = preg_replace( "|\n</p>$|", '</p>', $pee );

	// Replace placeholder <pre> tags with their original content.
	if ( ! empty( $pre_tags ) ) {
		$pee = str_replace( array_keys( $pre_tags ), array_values( $pre_tags ), $pee );
	}

	// Restore newlines in all elements.
	if ( FALSE !== strpos( $pee, '<!-- wpnl -->' ) ) {
		$pee = str_replace( array( ' <!-- wpnl --> ', '<!-- wpnl -->' ), "\n", $pee );
	}

	return $pee;
}

/**
 * Separate HTML elements and comments from the text.
 *
 * @since 4.2.4
 *
 * @param  string $input The text which has to be formatted.
 * @return array  The formatted text.
 */
function wp_html_split( $input )
{
	return preg_split( get_html_split_regex(), $input, -1, PREG_SPLIT_DELIM_CAPTURE );
}

/**
 * Retrieve the regular expression for an HTML element.
 *
 * @since     4.4.0
 * @staticvar string $regex
 *
 * @return string The regular expression.
 */
function get_html_split_regex()
{
	static $regex;

	if ( ! isset( $regex ) ) {
		$comments = '!'     // Start of comment, after the <.
			. '(?:'         // Unroll the loop: Consume everything until --> is found.
				. '-(?!->)' // Dash not followed by end of comment.
				. '[^\-]*+' // Consume non-dashes.
			. ')*+'         // Loop possessively.
			. '(?:-->)?';   // End of comment. If not found, match all input.
		$cdata = '!\[CDATA\[' // Start of comment, after the <.
			. '[^\]]*+'       // Consume non-].
			. '(?:'           // Unroll the loop: Consume everything until ]]> is found.
				. '](?!]>)'   // One ] not followed by end of comment.
				. '[^\]]*+'   // Consume non-].
			. ')*+'           // Loop possessively.
			. '(?:]]>)?';     // End of comment. If not found, match all input.
		$escaped = '(?='       // Is the element escaped?
				. '!--'
				. '|'
				. '!\[CDATA\['
			. ')'
			. '(?(?=!-)'       // If yes, which type?
				. $comments
				. '|'
				. $cdata
			. ')';
		$regex = '/('           // Capture the entire match.
				. '<'           // Find start of element.
				. '(?'          // Conditional expression follows.
					. $escaped  // Find end of escaped element.
					. '|'       // ... else ...
					. '[^>]*>?' // Find end of normal element.
				. ')'
			. ')/';
	}

	return $regex;
}

/**
 * Retrieve the combined regular expression for HTML and shortcodes.
 *
 * @access    private
 * @ignore
 * @internal  This function will be removed in 4.5.0 per Shortcode API Roadmap.
 * @since     4.4.0
 * @staticvar string $html_regex
 *
 * @param  string $shortcode_regex The result from _get_wptexturize_shortcode_regex().
 *                                 Optional.
 * @return string The regular expression.
 */
function _get_wptexturize_split_regex( $shortcode_regex = '' )
{
	static $html_regex;

	if ( ! isset( $html_regex ) ) {
		$comment_regex = '!' // Start of comment, after the <.
			. '(?:'          // Unroll the loop: Consume everything until --> is found.
				. '-(?!->)'  // Dash not followed by end of comment.
				. '[^\-]*+'  // Consume non-dashes.
			. ')*+'          // Loop possessively.
			. '(?:-->)?';    // End of comment. If not found, match all input.
		$html_regex = '<'        // Find start of element.
			. '(?(?=!--'         // Is this a comment?
				. $comment_regex // Find end of comment.
				. '|'
				. '[^>]*>?'      // Find end of element. If not found, match all input.
			. ')';
	}

	$regex = empty( $shortcode_regex )
		? '/(' . $html_regex . ')/'
		: '/(' . $html_regex . '|' . $shortcode_regex . ')/';

	return $regex;
}

/**
 * Retrieve the regular expression for shortcodes.
 *
 * @access   private
 * @ignore
 * @internal This function will be removed in 4.5.0 per Shortcode API Roadmap.
 * @since    4.4.0
 *
 * @param  array  $tagnames List of shortcodes to find.
 * @return string The regular expression.
 */
function _get_wptexturize_shortcode_regex( $tagnames )
{
	$tagregexp = join( '|', array_map( 'preg_quote', $tagnames ) );
	$tagregexp = "(?:$tagregexp)(?=[\\s\\]\\/])"; // Excerpt of get_shortcode_regex().
	$regex = '\['           // Find start of shortcode.
		. '[\/\[]?'         // Shortcodes may begin with [/ or [[
		. $tagregexp        // Only match registered shortcodes, because performance.
		. '(?:'
			. '[^\[\]<>]+'  // Shortcodes do not contain other shortcodes. Quantifier critical.
			. '|'
			. '<[^\[\]>]*>' // HTML elements permitted. Prevents matching ] before >.
		. ')*+'             // Possessive critical.
		. '\]'              // Find end of shortcode.
		. '\]?';            // Shortcodes may end with ]]
	return $regex;
}

/**
 * Replace characters or phrases within HTML elements only.
 *
 * @since 4.2.3
 *
 * @param  string $haystack      The text which has to be formatted.
 * @param  array  $replace_pairs In the form array('from' => 'to', ...).
 * @return string The formatted text.
 */
function wp_replace_in_html_tags( $haystack, $replace_pairs )
{
	// Find all elements.
	$textarr = wp_html_split( $haystack );
	$changed = FALSE;

	// Optimize when searching for one item.
	if ( 1 === count( $replace_pairs ) ) {
		// Extract $needle and $replace.
		foreach ( $replace_pairs as $needle => $replace );

		// Loop through delimiters (elements) only.
		for ( $i = 1, $c = count( $textarr ); $i < $c; $i += 2 ) {
			if ( FALSE !== strpos( $textarr[ $i ], $needle ) ) {
				$textarr[ $i ] = str_replace( $needle, $replace, $textarr[ $i ] );
				$changed = TRUE;
			}
		}
	} else {
		// Extract all $needles.
		$needles = array_keys( $replace_pairs );

		// Loop through delimiters (elements) only.
		for ( $i = 1, $c = count( $textarr ); $i < $c; $i += 2 ) {
			foreach ( $needles as $needle ) {
				if ( FALSE !== strpos( $textarr[ $i ], $needle ) ) {
					$textarr[ $i ] = strtr( $textarr[ $i ], $replace_pairs );
					$changed = TRUE;

					// After one strtr() break out of the foreach loop and look at next element.
					break;
				}
			}
		}
	}

	if ( $changed ) {
		$haystack = implode( $textarr );
	}

	return $haystack;
}

/**
 * Newline preservation help function for wpautop
 *
 * @since  3.1.0
 * @access private
 *
 * @param  array  $matches preg_replace_callback matches array.
 * @return string
 */
function _autop_newline_preservation_helper( $matches )
{
	return str_replace( "\n", "<WPPreserveNewline />", $matches[0] );
}

/**
 * Don't auto-p wrap shortcodes that stand alone.
 *
 * Ensures that shortcodes are not wrapped in `<p>...</p>`.
 *
 * @since  2.9.0
 * @global array $shortcode_tags
 *
 * @param  string $pee The content.
 * @return string The filtered content.
 */
function shortcode_unautop( $pee )
{
	global $shortcode_tags;

	if ( empty( $shortcode_tags ) || ! is_array( $shortcode_tags ) ) {
		return $pee;
	}

	$tagregexp = join( '|', array_map( 'preg_quote', array_keys( $shortcode_tags ) ) );
	$spaces = wp_spaces_regexp();
	$pattern = '/'
		. '<p>'                              // Opening paragraph
		. '(?:' . $spaces . ')*+'            // Optional leading whitespace
		. '('                                // 1: The shortcode
			. '\\['                          // Opening bracket
			. "($tagregexp)"                 // 2: Shortcode name
			. '(?![\\w-])'                   // Not followed by word character or hyphen - Unroll the loop: Inside the opening shortcode tag
			. '[^\\]\\/]*'                   // Not a closing bracket or forward slash
			. '(?:'
				. '\\/(?!\\])'               // A forward slash not followed by a closing bracket
				. '[^\\]\\/]*'               // Not a closing bracket or forward slash
			. ')*?'
			. '(?:'
				. '\\/\\]'                   // Self closing tag and closing bracket
				. '|'
				. '\\]'                      // Closing bracket
				'(?:'                        // Unroll the loop: Optionally, anything between the opening and closing shortcode tags
					'[^\\[]*+'               // not an opening bracket
					'(?:'
						. '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
						. '[^\\[]*+'         // Not an opening bracket
					. ')*+'
					. '\\[\\/\\2\\]'         // Closing shortcode tag
				. ')?'
			. ')'
		. ')'
		. '(?:' . $spaces . ')*+'            // Optional trailing whitespace
		. '<\\/p>'                           // Closing paragraph
		. '/';
	return preg_replace( $pattern, '$1', $pee );
}

/**
 * Checks to see if a string is utf8 encoded.
 *
 * NOTE: This function checks for 5-Byte sequences, UTF8 has Bytes Sequences with a maximu length of 4.
 *
 * @author bmorel@ssi.fr (modified)
 * @since  1.2.1
 *
 * @param  string $str The string to be checked.
 * @return bool   True if $str fits a UTF-8 model, false otherwise.
 */
function seems_utf8( $str )
{
	mbstring_binary_safe_encoding();
	$length = strlen( $str );
	reset_mbstring_encoding();

	for ( $i = 0; $i < $length; $i++ ) {
		$c = ord( $str[ $i ] );

		if ( $c < 0x80 ) {
			$n = 0; // 0bbbbbbb
		} elseif ( ( $c & 0xE0 ) == 0xC0 ) {
			$n = 1; // 110bbbbb
		} elseif ( ( $c & 0xF0 ) == 0xE0 ) {
			$n = 2; // 1110bbbb
		} elseif ( ( $c & 0xF8 ) == 0xF0 ) {
			$n = 3; // 11110bbb
		} elseif ( ( $c & 0xFC ) == 0xF8 ) {
			$n = 4; // 111110bb
		} elseif ( ( $c & 0xFE ) == 0xFC ) {
			$n = 5; // 1111110b
		} else {
			return FALSE; // Does not match any model.
		}

		for ( $j = 0; $j < $n; $j++ ) {
			// n bytes matching 10bbbbbb follow?
			if ( ++$i == $length || ( ord( $str[ $i ] ) & 0xC0 ) != 0x80 ) {
				return FALSE;
			}
		}
	}

	return TRUE;
}

/**
 * Converts a number of special characters into their HTML entities.
 *
 * Specifically deals with: &, <, >, ", and '.
 *
 * $quote_style can be set to ENT_COMPAT to encode " to $quot;, or ENT_QUOTES to do both.
 * Default is ENT_NOQUOTES where no quotes are encoded.
 *
 * @since     1.2.2
 * @access    private
 * @staticvar string $_charset
 *
 * @param  string     $string        The text which is to be encoded.
 * @param  int|string $quote_style   Optional.
 *                                   Converts double quotes if set to ENT_COMPAT, both single and double if set to ENT_QUOTES or none if set to ENT_NOQUOTES.
 *                                   Also compatible with old values; converting single quotes if set to 'single', double if set to 'double' or both if otherwise set.
 *                                   Default is ENT_NOQUOTES.
 * @param  string     $charset       Optional.
 *                                   The character encoding of the string.
 *                                   Default is false.
 * @param  bool       $double_encode Optional.
 *                                   Whether to encode existing html entities.
 *                                   Default is false.
 * @return string     The encoded text with HTML entities.
 */
function _wp_specialchars( $string, $quote_style = ENT_NOQUOTES, $charset = FALSE, $double_encode = FALSE )
{
	$string = ( string ) $string;

	if ( 0 === strlen( $string ) ) {
		return '';
	}

	// Don't bother if there are no specialchars - saves some processing.
	if ( ! preg_match( '/[&<>"\']/', $string ) ) {
		return $string;
	}

	// Account for the previous behaviour of the function when the $quote_style is not an accepted value.
	if ( empty( $quote_style ) ) {
		$quote_style = ENT_NOQUOTES;
	} elseif ( ! in_array( $quote_style, array( 0, 2, 3, 'single', 'double' ), TRUE ) ) {
		$quote_style = ENT_QUOTES;
	}

	// Store the site charset as a static to avoid multiple calls to wp_load_alloptions().
	if ( ! $charset ) {
		static $_charset = NULL;

		if ( ! isset( $_charset ) ) {
			$alloptions = wp_load_alloptions();

			$_charset = isset( $alloptions['blog_charset'] )
				? $alloptions['blog_charset']
				: '';
		}

		$charset = $_charset;
	}

	if ( in_array( $charset, array( 'utf8', 'utf-8', 'UTF8' ) ) ) {
		$charset = 'UTF-8';
	}

	$_quote_style = $quote_style;

	if ( $quote_style === 'double' ) {
		$quote_style = ENT_COMPAT;
		$_quote_style = ENT_COMPAT;
	} elseif ( $quote_style === 'single' ) {
		$quote_style = ENT_NOQUOTES;
	}

	if ( ! $double_encode ) {
		/**
		 * Guarantee every &entity; is valid, convert &garbage; into &amp;garbage;.
		 * This is required for PHP < 5.4.0 because ENT_HTML401 flag is unavailable.
		 */
		$string = wp_kses_normalize_entities( $string );
	}

	$string = @ htmlspecialchars( $string, $quote_style, $charset, $double_encode );

	// Back-compat.
	if ( 'single' === $_quote_style ) {
		$string = str_replace( "'", '&#039;', $string );
	}

	return $string;
}

/**
 * Checks for invalid UTF8 in a string.
 *
 * @since     2.8.0
 * @staticvar bool $is_utf8
 * @staticvar bool $utf8_pcre
 *
 * @param  string $string The text which is to be checked.
 * @param  bool   $strip  Optional.
 *                        Whether to attempt to strip out invalid UTF8.
 *                        Default is false.
 * @return string The checked text.
 */
function wp_check_invalid_utf8( $string, $strip = FALSE )
{
	$string = ( string ) $string;

	if ( 0 === strlen( $string ) ) {
		return '';
	}

	// Store the site charset as a static to avoid multiple calls to get_option().
	static $is_utf8 = NULL;

	if ( ! isset( $is_utf8 ) ) {
		$is_utf8 = in_array( get_option( 'blog_charset' ), array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' ) );
	}

	if ( ! $is_utf8 ) {
		return $string;
	}

	// Check for support for utf8 in the installed PCRE library once and store the result in a static.
	static $utf8_pcre = NULL;

	if ( ! isset( $utf8_pcre ) ) {
		$utf8_pcre = @ preg_match( '/^./u', 'a' );
	}

	// We can't demand utf8 in the PCRE installation, so just return the string in those cases.
	if ( ! $utf8_pcre ) {
		return $string;
	}

	// preg_match fails when it encounters invalid UTF8 in $string.
	if ( 1 === @ preg_match( '/^./us', $string ) ) {
		return $string;
	}

	// Attempt to strip the bad chars if requested (not recommended).
	if ( $strip && function_exists( 'iconv' ) ) {
		return iconv( 'utf-8', 'utf-8', $string );
	}

	return '';
}

/**
 * Converts all accent characters to ASCII characters.
 *
 * If there are no accent characters, then the string given is just returned.
 *
 * **Accent characters converted:**
 *
 * - Currency signs
 * - Decompositions for Latin-1 Supplement
 * - Decompositions for Latin Extended-A
 * - Decompositions for Latin Extended-B
 * - Vowels with diacritic (Chinese, Hanyu Pinyin)
 * - German (`de_DE`), German formal (`de_DE_formal`), German (Switzerland) formal (`de_CH`), and German (Switzerland) informal (`de_CH_informal`) locales
 * - Danish (`da_DK`) locale
 * - Catalan (`ca`) locale
 * - Serbian (`sr_RS`) and Bosinan (`bs_BA`) locales
 *
 * @since 1.2.1
 * @since 4.6.0 Added locale support for `de_CH`, `de_CH_informal`, and `ca`.
 * @since 4.7.0 Added locale support for `sr_RS`.
 * @since 4.8.0 Added locale support for `bs_BA`.
 *
 * @param  string $string Text that might have accent characters.
 * @return string Filtered string with replaced "nice" characters.
 */
function remove_accents( $string )
{
	if ( ! preg_match( '/[\x80-\xFF]/', $string ) ) {
		return $string;
	}

	if ( seems_utf8( $string ) ) {
		$chars = array(
			// Decompositions for Latin-1 Supplement
			'ª' => 'a',
			'º' => 'o',
			'À' => 'A',
			'Á' => 'A',
			'Â' => 'A',
			'Ã' => 'A',
			'Ä' => 'A',
			'Å' => 'A',
			'Æ' => 'AE',
			'Ç' => 'C',
			'È' => 'E',
			'É' => 'E',
			'Ê' => 'E',
			'Ë' => 'E',
			'Ì' => 'I',
			'Í' => 'I',
			'Î' => 'I',
			'Ï' => 'I',
			'Ð' => 'D',
			'Ñ' => 'N',
			'Ò' => 'O',
			'Ó' => 'O',
			'Ô' => 'O',
			'Õ' => 'O',
			'Ö' => 'O',
			'Ù' => 'U',
			'Ú' => 'U',
			'Û' => 'U',
			'Ü' => 'U',
			'Ý' => 'Y',
			'Þ' => 'TH',
			'ß' => 's',
			'à' => 'a',
			'á' => 'a',
			'â' => 'a',
			'ã' => 'a',
			'ä' => 'a',
			'å' => 'a',
			'æ' => 'ae',
			'ç' => 'c',
			'è' => 'e',
			'é' => 'e',
			'ê' => 'e',
			'ë' => 'e',
			'ì' => 'i',
			'í' => 'i',
			'î' => 'i',
			'ï' => 'i',
			'ð' => 'd',
			'ñ' => 'n',
			'ò' => 'o',
			'ó' => 'o',
			'ô' => 'o',
			'õ' => 'o',
			'ö' => 'o',
			'ø' => 'o',
			'ù' => 'u',
			'ú' => 'u',
			'û' => 'u',
			'ü' => 'u',
			'ý' => 'y',
			'þ' => 'th',
			'ÿ' => 'y',
			'Ø' => 'O',

			// Decompositions for Latin Extended-A
			'Ā' => 'A',
			'ā' => 'a',
			'Ă' => 'A',
			'ă' => 'a',
			'Ą' => 'A',
			'ą' => 'a',
			'Ć' => 'C',
			'ć' => 'c',
			'Ĉ' => 'C',
			'ĉ' => 'c',
			'Ċ' => 'C',
			'ċ' => 'c',
			'Č' => 'C',
			'č' => 'c',
			'Ď' => 'D',
			'ď' => 'd',
			'Đ' => 'D',
			'đ' => 'd',
			'Ē' => 'E',
			'ē' => 'e',
			'Ĕ' => 'E',
			'ĕ' => 'e',
			'Ė' => 'E',
			'ė' => 'e',
			'Ę' => 'E',
			'ę' => 'e',
			'Ě' => 'E',
			'ě' => 'e',
			'Ĝ' => 'G',
			'ĝ' => 'g',
			'Ğ' => 'G',
			'ğ' => 'g',
			'Ġ' => 'G',
			'ġ' => 'g',
			'Ģ' => 'G',
			'ģ' => 'g',
			'Ĥ' => 'H',
			'ĥ' => 'h',
			'Ħ' => 'H',
			'ħ' => 'h',
			'Ĩ' => 'I',
			'ĩ' => 'i',
			'Ī' => 'I',
			'ī' => 'i',
			'Ĭ' => 'I',
			'ĭ' => 'i',
			'Į' => 'I',
			'į' => 'i',
			'İ' => 'I',
			'ı' => 'i',
			'Ĳ' => 'IJ',
			'ĳ' => 'ij',
			'Ĵ' => 'J',
			'ĵ' => 'j',
			'Ķ' => 'K',
			'ķ' => 'k',
			'ĸ' => 'k',
			'Ĺ' => 'L',
			'ĺ' => 'l',
			'Ļ' => 'L',
			'ļ' => 'l',
			'Ľ' => 'L',
			'ľ' => 'l',
			'Ŀ' => 'L',
			'ŀ' => 'l',
			'Ł' => 'L',
			'ł' => 'l',
			'Ń' => 'N',
			'ń' => 'n',
			'Ņ' => 'N',
			'ņ' => 'n',
			'Ň' => 'N',
			'ň' => 'n',
			'ŉ' => 'n',
			'Ŋ' => 'N',
			'ŋ' => 'n',
			'Ō' => 'O',
			'ō' => 'o',
			'Ŏ' => 'O',
			'ŏ' => 'o',
			'Ő' => 'O',
			'ő' => 'o',
			'Œ' => 'OE',
			'œ' => 'oe',
			'Ŕ' => 'R',
			'ŕ' => 'r',
			'Ŗ' => 'R',
			'ŗ' => 'r',
			'Ř' => 'R',
			'ř' => 'r',
			'Ś' => 'S',
			'ś' => 's',
			'Ŝ' => 'S',
			'ŝ' => 's',
			'Ş' => 'S',
			'ş' => 's',
			'Š' => 'S',
			'š' => 's',
			'Ţ' => 'T',
			'ţ' => 't',
			'Ť' => 'T',
			'ť' => 't',
			'Ŧ' => 'T',
			'ŧ' => 't',
			'Ũ' => 'U',
			'ũ' => 'u',
			'Ū' => 'U',
			'ū' => 'u',
			'Ŭ' => 'U',
			'ŭ' => 'u',
			'Ů' => 'U',
			'ů' => 'u',
			'Ű' => 'U',
			'ű' => 'u',
			'Ų' => 'U',
			'ų' => 'u',
			'Ŵ' => 'W',
			'ŵ' => 'w',
			'Ŷ' => 'Y',
			'ŷ' => 'y',
			'Ÿ' => 'Y',
			'Ź' => 'Z',
			'ź' => 'z',
			'Ż' => 'Z',
			'ż' => 'z',
			'Ž' => 'Z',
			'ž' => 'z',
			'ſ' => 's',

			// Decompositions for Latin Extended-B
			'Ș' => 'S',
			'ș' => 's',
			'Ț' => 'T',
			'ț' => 't',

			// Euro Sign
			'€' => 'E',

			// GBP (Pound) Sign
			'£' => '',

			// Vowels with diacritic (Vietnamese)
			// unmarked
			'Ơ' => 'O',
			'ơ' => 'o',
			'Ư' => 'U',
			'ư' => 'u',

			// grave accent
			'Ầ' => 'A',
			'ầ' => 'a',
			'Ằ' => 'A',
			'ằ' => 'a',
			'Ề' => 'E',
			'ề' => 'e',
			'Ồ' => 'O',
			'ồ' => 'o',
			'Ờ' => 'O',
			'ờ' => 'o',
			'Ừ' => 'U',
			'ừ' => 'u',
			'Ỳ' => 'Y',
			'ỳ' => 'y',

			// hook
			'Ả' => 'A',
			'ả' => 'a',
			'Ẩ' => 'A',
			'ẩ' => 'a',
			'Ẳ' => 'A',
			'ẳ' => 'a',
			'Ẻ' => 'E',
			'ẻ' => 'e',
			'Ể' => 'E',
			'ể' => 'e',
			'Ỉ' => 'I',
			'ỉ' => 'i',
			'Ỏ' => 'O',
			'ỏ' => 'o',
			'Ổ' => 'O',
			'ổ' => 'o',
			'Ở' => 'O',
			'ở' => 'o',
			'Ủ' => 'U',
			'ủ' => 'u',
			'Ử' => 'U',
			'ử' => 'u',
			'Ỷ' => 'Y',
			'ỷ' => 'y',

			// tilde
			'Ẫ' => 'A',
			'ẫ' => 'a',
			'Ẵ' => 'A',
			'ẵ' => 'a',
			'Ẽ' => 'E',
			'ẽ' => 'e',
			'Ễ' => 'E',
			'ễ' => 'e',
			'Ỗ' => 'O',
			'ỗ' => 'o',
			'Ỡ' => 'O',
			'ỡ' => 'o',
			'Ữ' => 'U',
			'ữ' => 'u',
			'Ỹ' => 'Y',
			'ỹ' => 'y',

			// acute accent
			'Ấ' => 'A',
			'ấ' => 'a',
			'Ắ' => 'A',
			'ắ' => 'a',
			'Ế' => 'E',
			'ế' => 'e',
			'Ố' => 'O',
			'ố' => 'o',
			'Ớ' => 'O',
			'ớ' => 'o',
			'Ứ' => 'U',
			'ứ' => 'u',

			// dot below
			'Ạ' => 'A',
			'ạ' => 'a',
			'Ậ' => 'A',
			'ậ' => 'a',
			'Ặ' => 'A',
			'ặ' => 'a',
			'Ẹ' => 'E',
			'ẹ' => 'e',
			'Ệ' => 'E',
			'ệ' => 'e',
			'Ị' => 'I',
			'ị' => 'i',
			'Ọ' => 'O',
			'ọ' => 'o',
			'Ộ' => 'O',
			'ộ' => 'o',
			'Ợ' => 'O',
			'ợ' => 'o',
			'Ụ' => 'U',
			'ụ' => 'u',
			'Ự' => 'U',
			'ự' => 'u',
			'Ỵ' => 'Y',
			'ỵ' => 'y',

			// Vowels with diacritic (Chinese, Hanyu Pinyin)
			'ɑ' => 'a',

			// macron
			'Ǖ' => 'U',
			'ǖ' => 'u',

			// acute accent
			'Ǘ' => 'U',
			'ǘ' => 'u',

			// caron
			'Ǎ' => 'A',
			'ǎ' => 'a',
			'Ǐ' => 'I',
			'ǐ' => 'i',
			'Ǒ' => 'O',
			'ǒ' => 'o',
			'Ǔ' => 'U',
			'ǔ' => 'u',
			'Ǚ' => 'U',
			'ǚ' => 'u',

			// grave accent
			'Ǜ' => 'U',
			'ǜ' => 'u'
		);

		// Used for locale-specific rules.
		$locale = get_locale();

		if ( 'de_DE' == $locale || 'de_DE_formal' == $locale || 'de_CH' == $locale || 'de_CH_informal' == $locale ) {
			$chars['Ä'] = 'Ae';
			$chars['ä'] = 'ae';
			$chars['Ö'] = 'Oe';
			$chars['ö'] = 'oe';
			$chars['Ü'] = 'Ue';
			$chars['ü'] = 'ue';
			$chars['ß'] = 'ss';
		} elseif ( 'da_DK' === $locale ) {
			$chars['Æ'] = 'Ae';
 			$chars['æ'] = 'ae';
			$chars['Ø'] = 'Oe';
			$chars['ø'] = 'oe';
			$chars['Å'] = 'Aa';
			$chars['å'] = 'aa';
		} elseif ( 'ca' === $locale ) {
			$chars['l·l'] = 'll';
		} elseif ( 'sr_RS' === $locale || 'bs_BA' === $locale ) {
			$chars['Đ'] = 'DJ';
			$chars['đ'] = 'dj';
		}

		$string = strtr( $string, $chars );
	} else {
		$chars = array();

		// Assume ISO-8859-1 if not UTF-8
		$chars['in'] = "\x80\x83\x8a\x8e\x9a\x9e"
			. "\x9f\xa2\xa5\xb5\xc0\xc1\xc2"
			. "\xc3\xc4\xc5\xc7\xc8\xc9\xca"
			. "\xcb\xcc\xcd\xce\xcf\xd1\xd2"
			. "\xd3\xd4\xd5\xd6\xd8\xd9\xda"
			. "\xdb\xdc\xdd\xe0\xe1\xe2\xe3"
			. "\xe4\xe5\xe7\xe8\xe9\xea\xeb"
			. "\xec\xed\xee\xef\xf1\xf2\xf3"
			. "\xf4\xf5\xf6\xf8\xf9\xfa\xfb"
			. "\xfc\xfd\xff";
		$chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";
		$string = strtr( $string, $chars['in'], $chars['out'] );
		$double_chars = array();
		$double_chars['in'] = array( "\x8c", "\x9c", "\xc6", "\xd0", "\xde", "\xdf", "\xe6", "\xf0", "\xfe" );
		$double_chars['out'] = array( 'OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th' );
		$string = str_replace( $double_chars['in'], $double_chars['out'], $string );
	}

	return $string;
}

/**
 * Sanitizes a username, stripping out unsafe characters.
 *
 * Removes tags, octets, entities, and if strict is enabled, will only keep alphanumeric, _, space, ., -, @.
 * After sanitizing, it passes the username, raw username (the username in the parameter), and the value of $strict as paramters for the {@see 'sanitize_user'} filter.
 *
 * @since 2.0.0
 *
 * @param  string $username The username to be sanitized.
 * @param  bool   $strict   If set limits $username to specific characters.
 *                          Default false.
 * @return string The sanitized username, after passing through filters.
 */
function sanitize_user( $username, $strict = FALSE )
{
	$raw_username = $username;
	$username = wp_strip_all_tags( $username );
	$username = remove_accents( $username );

	// Kill octets.
	$username = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '', $username );

	// Kill entities.
	$username = preg_replace( '/&.+?;/', '', $username );

	// If strict, reduce to ASCII for max portability.
	if ( $strict ) {
		$username = preg_replace( '|[^a-z0-9 _.\-@]|i', '', $username );
	}

	$username = trim( $username );

	// Consolidate contiguous whitespace
	$username = preg_replace( '|\s+|', ' ', $username );

	/**
	 * Filters a sanitized username string.
	 *
	 * @since 2.0.1
	 *
	 * @param string $username     Sanitized username.
	 * @param string $raw_username The username prior to sanitization.
	 * @param bool   $strict       Whether to limit the sanitization to specific characters.
	 *                             Default false.
	 */
	return apply_filters( 'sanitize_user', $username, $raw_username, $strict );
}

/**
 * Sanitizes a string key.
 *
 * Keys are used as internal identifiers.
 * Lowercase alphanumeric characters, dashes and underscores are allowed.
 *
 * @since 3.0.0
 *
 * @param  string $key String key
 * @return string Sanitized key
 */
function sanitize_key( $key )
{
	$raw_key = $key;
	$key = strtolower( $key );
	$key = preg_replace( '/[^a-z0-9_\-]/', '', $key );

	/**
	 * Filters a sanitized key string.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key     Sanitized key.
	 * @param string $raw_key The key prior to sanitization.
	 */
	return apply_filters( 'sanitize_key', $key, $raw_key );
}

/**
 * Sanitizes a title, or returns a fallback title.
 *
 * Specifically, HTML and PHP tags are stripped.
 * Further actions can be added via the plugin API.
 * If $title is empty and $fallback_title is set, the latter will be used.
 *
 * @since 1.0.0
 *
 * @param  string $title          The string to be sanitized.
 * @param  string $fallback_title Optional.
 *                                A title to use if $title is empty.
 * @param  string $context        Optional.
 *                                The operation for which the string is sanitized.
 * @return string The sanitized string.
 */
function sanitize_title( $title, $fallback_title = '', $context = 'save' )
{
	$raw_title = $title;

	if ( 'save' == $context ) {
		$title = remove_accents( $title );
	}

	/**
	 * Filters a sanitized title string.
	 *
	 * @since 1.2.0
	 *
	 * @param string $title     Sanitized title.
	 * @param string $raw_title The title prior to sanitization.
	 * @param string $context   The context for which the title is being sanitized.
	 */
	$title = apply_filters( 'sanitize_title', $title, $raw_title, $context );

	if ( '' === $title || FALSE === $title ) {
		$title = $fallback_title;
	}

	return $title;
}

// wp-includes/class-wp-term-query.php -> @NOW 014

/**
 * Converts lone & characters into `&#038;` (a.k.a. `&amp;`)
 *
 * @since 0.71
 *
 * @param  string $content    String of characters to be converted.
 * @param  string $deprecated Not used.
 * @return string Converted string.
 */
function convert_chars( $content, $deprecated = '' )
{
	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '0.71' );
	}

	if ( strpos( $content, '&' ) !== FALSE ) {
		$content = preg_replace( '/&([^#])(?![a-z1-4]{1,8};)/i', '&#038;$1', $content );
	}

	return $content;
}

/**
 * Converts invalid Unicode references range to valid range.
 *
 * @since 4.3.0
 *
 * @param  string $content String with entities that need converting.
 * @return string Convert string.
 */
function convert_invalid_entities( $content )
{
	$wp_htmltranswinuni = array(
		'&#128;' => '&#8364;', // the Euro sign
		'&#129;' => '',
		'&#130;' => '&#8218;', // these are Windows CP1252 specific characters
		'&#131;' => '&#402;',  // they would look weird on non-Windows browsers
		'&#132;' => '&#8222;',
		'&#133;' => '&#8230;',
		'&#134;' => '&#8224;',
		'&#135;' => '&#8225;',
		'&#136;' => '&#710;',
		'&#137;' => '&#8240;',
		'&#138;' => '&#352;',
		'&#139;' => '&#8249;',
		'&#140;' => '&#338;',
		'&#141;' => '',
		'&#142;' => '&#381;',
		'&#143;' => '',
		'&#144;' => '',
		'&#145;' => '&#8216;',
		'&#146;' => '&#8217;',
		'&#147;' => '&#8220;',
		'&#148;' => '&#8221;',
		'&#149;' => '&#8226;',
		'&#150;' => '&#8211;',
		'&#151;' => '&#8212;',
		'&#152;' => '&#732;',
		'&#153;' => '&#8482;',
		'&#154;' => '&#353;',
		'&#155;' => '&#8250;',
		'&#156;' => '&#339;',
		'&#157;' => '',
		'&#158;' => '&#382;',
		'&#159;' => '&#376;'
	);

	if ( strpos( $content, '&#1' ) !== FALSE ) {
		$content = strtr( $content, $wp_htmltranswinuni );
	}

	return $content;
}

/**
 * Balances tags if forced to, or if the 'use_balanceTags' option is set to true.
 *
 * @since 0.71
 *
 * @param  string $text  Text to be balanced.
 * @param  bool   $force If true, forces balancing, ignoring the value of the option.
 *                       Default false.
 * @return string Balanced text.
 */
function balanceTags( $text, $force = FALSE )
{
	return $force || get_option( 'use_balanceTags' ) == 1
		? force_balance_tags( $text )
		: $text;
}

/**
 * Balances tags of string using a modified stack.
 *
 * @since     2.0.4
 * @author    Leonard Lin <leonard@acm.org>
 * @license   GPL
 * @copyright November 4, 2001
 * @version   1.1
 * @todo      Make better - change loop condition to $text in 1.2
 * @internal  Modified by Scott Reilly (coffee2code) 02 Aug 2004
 *     1.1 Fixed handling of append/stack pop order of end text
 *         Added Cleaning Hooks
 *     1.0 First Version
 *
 * @param  string $text Text to be balanced.
 * @return string Balanced text.
 */
function force_balance_tags( $text )
{
	$tagstack = array();
	$stacksize = 0;
	$tagqueue = '';
	$newtext = '';

	// Known single-entity/self-closing tags
	$single_tags = array( 'area', 'base', 'basefont', 'br', 'col', 'command', 'embed', 'frame', 'hr', 'img', 'input', 'isindex', 'link', 'meta', 'param', 'source' );

	// Tags that can be immediately nested within themselves
	$nestable_tags = array( 'blockquote', 'div', 'object', 'q', 'span' );

	// WP bug fix for comments - in case you REALLY meant to type '< !--'
	$text = str_replace( '< !--', '<    !--', $text );

	// WP bug fix for LOVE <3 (and other situations with '<' before a number)
	$text = preg_replace( '#<([0-9]{1})#', '&lt;$1', $text );

	while ( preg_match( "/<(\/?[\w:]*)\s*([^>]*)>/", $text, $regex ) ) {
		$newtext .= $tagqueue;
		$i = strpos( $text, $regex[0] );
		$l = strlen( $regex[0] );

		// Clear the shifter
		$tagqueue = '';

		// Pop or push
		if ( isset( $regex[1][0] ) && '/' == $regex[1][0] ) { // End tag
			$tag = strtolower( substr( $regex[1], 1 ) );

			if ( $stacksize <= 0 ) {
				// If too many closing tags
				$tag = ''; // or close to be safe $tag = '/' . $tag;
			} elseif ( $tagstack[ $stacksize - 1 ] == $tag ) {
				// Found closing tag
				$tag = '</' . $tag . '>'; // Close tag

				// Pop
				array_pop( $tagstack );

				$stacksize--;
			} else {
				// Closing tag not at top, search for it
				for ( $j = $stacksize - 1; $j >= 0; $j-- ) {
					if ( $tagstack[ $j ] == $tag ) {
						// Add tag to tagqueue
						for ( $k = $stacksize - 1; $k >= $j; $k-- ) {
							$tagqueue .= '</' . array_pop( $tagstack ) . '>';
							$stacksize--;
						}

						break;
					}
				}

				$tag = '';
			}
		} else { // Begin tag
			$tag = strtolower( $regex[1] );

			// Tag cleaning
			if ( '' == $tag ) {
				// If it's an empty tag "< >", do nothing
			} elseif ( substr( $regex[2], -1 ) == '/' ) {
				// Elseif it presents itself as a self-closing tag...
				if ( ! in_array( $tag, $single_tags ) ) {
					// ...but it isn't a known single-entity self-closing tag, then don't let it be treated as such and immediately close it with a closing tag (the tag will encapsulate no text as a result)
					$regex[2] = trim( substr( $regex[2], 0, -1 ) ) . "></$tag";
				}
			} elseif ( in_array( $tag, $single_tags ) ) {
				// Elseif it's a known single-entity tag but it doesn't close itself, do so
				$regex[2] .= '/';
			} else {
				// Else it's not a single-entity tag
				if ( $stacksize > 0 && ! in_array( $tag, $nestable_tags ) && $tagstack[ $stacksize - 1 ] == $tag ) {
					// If the top of the stack is the same as the tag we want to push, close previous tag
					$tagqueue = '</' . array_pop( $tagstack ) . '>';
					$stacksize--;
				}

				$stacksize = array_push( $tagstack, $tag );
			}

			// Attributes
			$attributes = $regex[2];

			if ( ! empty( $attributes ) && $attributes[0] != '>' ) {
				$attributes = ' ' . $attributes;
			}

			$tag = '<' . $tag . $attributes . '>';

			// If already queuing a close tag, then put this tag on, too
			if ( ! empty( $tagqueue ) ) {
				$tagqueue .= $tag;
				$tag = '';
			}
		}

		$newtext .= substr( $text, 0, $i ) . $tag;
		$text = substr( $text, $i + $l );
	}

	// Clear tag queue
	$newtext .= $tagqueue;

	// Add remaining text
	$newtext .= $text;

	// Empty stack
	while ( $x = array_pop( $tagstack ) ) {
		$newtext .= '</' . $x . '>'; // Add remaining tags to close
	}

	// WP fix for the bug with HTML comments
	$newtext = str_replace( "< !--", "<!--", $newtext );
	$newtext = str_replace( "<    !--", "< !--", $newtext );

	return $newtext;
}

/**
 * Acts on text which is about to be edited.
 *
 * The $content is run through esc_textarea(), which uses htmlspecialchars() to convert special characters to HTML entities.
 * If `$richedit` is set to true, it is simply a holder for the {@see 'format_to_edit'} filter.
 *
 * @since 0.71
 * @since 4.4.0 The `$richedit` parameter was renamed to `$rich_text` for clarity.
 *
 * @param  string $content   The text about to be edited.
 * @param  bool   $rich_text Optional.
 *                           Whether `$content` should be considered rich text, in which case it would not be passed through esc_textarea().
 *                           Default false.
 * @return string The text after the filter (and possibly htmlspecialchars()) has been run.
 */
function format_to_edit( $content, $rich_text = FALSE )
{
	/**
	 * Filters the text to be formatted for editing.
	 *
	 * @since 1.2.0
	 *
	 * @param string $content The text, prior to formatting for editing.
	 */
	$content = apply_filters( 'format_to_edit', $content );

	if ( ! $rich_text ) {
		$content = esc_textarea( $content );
	}

	return $content;
}

/**
 * Appends a trailing slash.
 *
 * Will remove trailing forward and backslashes if it exists already before adding a trailing forward slash.
 * This prevents double slashing a string or path.
 *
 * The primary use of this is for paths and thus should be used for paths.
 * It is not restricted to paths and offers no specific path support.
 *
 * @since 1.2.0
 *
 * @param  string $string What to add the trailing slash to.
 * @return string String with trailing slash added.
 */
function trailingslashit( $string )
{
	return untrailingslashit( $string ) . '/';
}

/**
 * Removes trailing forward slashes and backslashes if they exist.
 *
 * The primary use of this is for paths and thus should be used for paths.
 * It is not restricted to paths and offers no specific path support.
 *
 * @since 2.2.0
 *
 * @param  string $string What to remove the trailing slashes from.
 * @return string String without the trailing slashes.
 */
function untrailingslashit( $string )
{
	return rtrim( $string, '/\\' );
}

/**
 * Navigates through an array, object, or scalar, and removes slashes from the values.
 *
 * @since 2.0.0
 *
 * @param  mixed $value The value to be stripped.
 * @return mixed Stripped value.
 */
function stripslashes_deep( $value )
{
	return map_deep( $value, 'stripslashes_from_strings_only' );
}

/**
 * Callback function for `stripslashes_deep()` which strips slashed from strings.
 *
 * @since 4.4.0
 *
 * @param  mixed $value The array or string to be stripped.
 * @return mixed $value The stripped value.
 */
function stripslashes_from_strings_only( $value )
{
	return is_string( $value )
		? stripslashes( $value )
		: $value;
}

/**
 * Verifies that an email is valid.
 *
 * Does not grok i18n domains.
 * Not RFC compliant.
 *
 * @since 0.71
 *
 * @param  string      $email      Email address to verify.
 * @param  bool        $deprecated Deprecated.
 * @return string|bool Either false or the valid email address.
 */
function is_email( $email, $deprecated = FALSE )
{
	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '3.0.0' );
	}

	// Test for the minimum length the email can be.
	if ( strlen( $email ) < 6 ) {
		/**
		 * Filters whether an email address is valid.
		 *
		 * This filter is evaluated under several different contexts, such as 'email_too_short', 'email_no_at', 'local_invalid_chars', 'domain_period_sequence', 'domain_period_limits', 'domain_no_periods', 'sub_hyphen_limits', 'sub_invalid_chars', or no specific context.
		 *
		 * @since 2.8.0
		 *
		 * @param bool   $is_email Whether the email address has passed the is_email() checks.
		 *                         Default false.
		 * @param string $email    The email address being checked.
		 * @param string $context  Context under which the email was tested.
		 */
		return apply_filters( 'is_email', FALSE, $email, 'email_too_short' );
	}

	// Test for an @ character after the first position.
	if ( strpos( $email, '@', 1 ) === FALSE ) {
		// This filter is documented in wp-includes/formatting.php
		return apply_filters( 'is_email', FALSE, $email, 'email_no_at' );
	}

	// Split out the local and domain parts.
	list( $local, $domain ) = explode( '@', $email, 2 );

	// LOCAL PART

	// Test for invalid characters.
	if ( ! preg_match( '/^[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~\.-]+$/', $local ) ) {
		// This filter is documented in wp-includes/formatting.php
		return apply_filters( 'is_email', FALSE, $email, 'local_invalid_chars' );
	}

	// DOMAIN PART

	// Test for sequences of periods.
	if ( preg_match( '/\.{2,}/', $domain ) ) {
		// This filter is documented in wp-includes/formatting.php
		return apply_filters( 'is_email', FALSE, $email, 'domain_period_sequence' );
	}

	// Test for leading and trailing periods and whitespace.
	if ( trim( $domain, " \t\n\r\0\x0B." ) !== $domain ) {
		// This filter is documented in wp-includes/formatting.php
		return apply_filters( 'is_email', FALSE, $email, 'domain_period_limits' );
	}

	// Split the domain into subs.
	$subs = explode( '.', $domain );

	// Assume the domain will have at least two subs.
	if ( 2 > count( $subs ) ) {
		// This filter is documented in wp-includes/formatting.php
		return apply_filters( 'is_email', FALSE, $email, 'domain_no_periods' );
	}

	// Loop through each sub.
	foreach ( $subs as $sub ) {
		// Test for leading and trailing hyphens and whitespace.
		if ( trim( $sub, " \t\n\r\0\x0B-" ) !== $sub ) {
			// This filter is documented in wp-includes/formatting.php
			return apply_filters( 'is_email', FALSE, $email, 'sub_hyphen_limits' );
		}

		// Test for invalid characters.
		if ( ! preg_match( '/^[a-z0-9-]+$/i', $sub ) ) {
			// This filter is documented in wp-includes/formatting.php
			return apply_filters( 'is_email', FALSE, $email, 'sub_invalid_chars' );
		}
	}

	// Congratulations your email made it!
	// This filter is documented in wp-includes/formatting.php
	return apply_filters( 'is_email', $email, $email, NULL );
}

/**
 * Strips out all characters that are not allowable in an email.
 *
 * @since 1.5.0
 *
 * @param  string $email Email address to filter.
 * @return string Filtered email address.
 */
function sanitize_email( $email )
{
	// Test for the minimum length the email can be.
	if ( strlen( $email ) < 6 ) {
		/**
		 * Filters a sanitized email address.
		 *
		 * This filter is evaluated under several contexts, including 'email_too_short', 'email_no_at', 'local_invalid_chars', 'domain_period_sequence', 'domain_period_limits', 'domain_no_periods', 'domain_no_valid_subs', or no context.
		 *
		 * @since 2.8.0
		 *
		 * @param string $email   The sanitized email address.
		 * @param string $email   The email address, as provided to sanitize_email().
		 * @param string $message A message to pass to the user.
		 */
		return apply_filters( 'sanitize_email', '', $email, 'email_too_short' );
	}

	// Test for an @ character after the first position.
	if ( strpos( $email, '@', 1 ) === FALSE ) {
		// This filter is documented in wp-includes/formatting.php
		return apply_filters( 'sanitize_email', '', $email, 'email_no_at' );
	}

	// Split out the local and domain parts.
	list( $local, $domain ) = explode( '@', $email, 2 );

	// LOCAL PART

	// Test for invalid characters.
	$local = preg_replace( '/[^a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~\.-]/', '', $local );

	if ( '' === $local ) {
		// This filter is documented in wp-includes/formatting.php
		return apply_filters( 'sanitize_email', '', $email, 'local_invalid_chars' );
	}

	// DOMAIN PART

	// Test for sequences of periods.
	$domain = preg_replace( '/\.{2,}/', '', $domain );

	if ( '' === $domain ) {
		// This filter is documented in wp-includes/formatting.php
		return apply_filters( 'sanitize_email', '', $email, 'domain_period_sequence' );
	}

	// Test for leading and trailing periods and whitespaces.
	$domain = trim( $domain, " \t\n\r\0\x0B." );

	if ( '' === $domain ) {
		// This filter is documented in wp-includes/formatting.php
		return apply_filters( 'sanitize_email', '', $email, 'domain_period_limits' );
	}

	// Split the domain into subs.
	$subs = explode( '.', $domain );

	// Assume the domain will have at least two subs.
	if ( 2 > count( $subs ) ) {
		// This filter is documented in wp-includes/formatting.php
		return apply_filters( 'sanitize_email', '', $email, 'domain_no_periods' );
	}

	// Create an array that will contain valid subs.
	$new_subs = array();

	// Loop through each sub.
	foreach ( $subs as $sub ) {
		// Test for leading and trailing hyphens.
		$sub = trim( $sub, " \t\n\r\0\x0B-" );

		// Test for invalid characters.
		$sub = preg_replace( '/[^a-z0-9-]+/i', '', $sub );

		// If there's anything left, add it to the valid subs.
		if ( '' !== $sub ) {
			$new_subs[] = $sub;
		}
	}

	// If there aren't 2 or more valid subs.
	if ( 2 > count( $new_subs ) ) {
		// This filter is documented in wp-includes/formatting.php
		return apply_filters( 'sanitize_email', '', $email, 'domain_no_valid_subs' );
	}

	// Join valid subs into the new domain.
	$domain = join( '.', $new_subs );

	// Put the email back together.
	$email = $local . '@' . $domain;

	// Congratulations your email made it!
	// This filter is documented in wp-includes/formatting.php
	return apply_filters( 'sanitize_email', $email, $email, NULL );
}

/**
 * Checks and cleans a URL.
 *
 * A number of characters are removed from the URL.
 * If the URL is for displaying (the default behaviour) ampersands are also replaced.
 * The {@see 'clean_url'} filter is applied to the returned cleaned URL.
 *
 * @since 2.8.0
 *
 * @param  string $url       The URL to be cleaned.
 * @param  array  $protocols Optional.
 *                           An array of acceptable protocols.
 *                           Defaults to return value of wp_allowed_protocols().
 * @param  string $_context  Private.
 *                           Use esc_url_raw() for database usage.
 * @return string The cleaned $url after the {@see 'clean_url'} filter is applied.
 */
function esc_url( $url, $protocols = NULL, $_context = 'display' )
{
	$original_url = $url;

	if ( '' == $url ) {
		return $url;
	}

	$url = str_replace( ' ', '%20', $url );
	$url = preg_replace( '|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url );

	if ( '' === $url ) {
		return $url;
	}

	$url = str_replace( ';//', '://', $url );

	// If the URL doesn't appear to contain a scheme, we presume it needs http:// prepended (unless a relative link starting with /, # or ? or a php file).
	if ( strpos( $url, ':' ) === FALSE && ! in_array( $url[0], array( '/', '#', '?' ) ) && ! preg_match( '/^[a-z0-9-]+?\.php/i', $url ) ) {
		$url = 'http://' . $url;
	}

	// Replace ampersands and single quotes only when displaying.
	if ( 'display' == $_context ) {
		$url = wp_kses_normalize_entities( $url );
		$url = str_replace( '&amp;', '&#038;', $url );
		$url = str_replace( "'", '&#039;', $url );
	}

	if ( FALSE !== strpos( $url, '[' ) || FALSE !== strpos( $url, ']' ) ) {
		$parsed = wp_parse_url( $url );
		$front = '';

		if ( isset( $parsed['scheme'] ) ) {
			$front .= $parsed['scheme'] . '://';
		} elseif ( '/' === $url[0] ) {
			$front .= '//';
		}

		if ( isset( $parsed['user'] ) ) {
			$front .= $parsed['user'];
		}

		if ( isset( $parsed['pass'] ) ) {
			$front .= ':' . $parsed['pass'];
		}

		if ( isset( $parsed['user'] ) || isset( $parsed['pass'] ) ) {
			$front .= '@';
		}

		if ( isset( $parsed['host'] ) ) {
			$front .= $parsed['host'];
		}

		if ( isset( $parsed['port'] ) ) {
			$front .= ':' . $parsed['port'];
		}

		$end_dirty = str_replace( $front, '', $url );
		$end_clean = str_replace( array( '[', ']' ), array( '%5B', '%5D' ), $end_dirty );
		$url       = str_replace( $end_dirty, $end_clean, $url );
	}

	if ( '/' === $url[0] ) {
		$good_protocol_url = $url;
	} else {
		if ( ! is_array( $protocols ) ) {
			$protocols = wp_allowed_protocols();
		}

		$good_protocol_url = wp_kses_bad_protocol( $url, $protocols );

		if ( strtolower( $good_protocol_url ) != strtolower( $url ) ) {
			return '';
		}
	}

	/**
	 * Filters a string cleaned and escaped for output as a URL.
	 *
	 * @since 2.3.0
	 *
	 * @param string $good_protocol_url The cleaned URL to be returned.
	 * @param string $original_url      The URL prior to cleaning.
	 * @param string $_context          If 'display', replace ampersands and single quotes only.
	 */
	return apply_filters( 'clean_url', $good_protocol_url, $original_url, $_context );
}

/**
 * Performs esc_url() for database usage.
 *
 * @since  2.8.0
 *
 * @param  string $url       The URL to be cleaned.
 * @param  array  $protocols An array of acceptable protocols.
 * @return string The cleaned URL.
 */
function esc_url_raw( $url, $protocols = NULL )
{
	return esc_url( $url, $protocols, 'db' );
}

/**
 * Escape single quotes, htmlspecialchar " < > &, and fix line endings.
 *
 * Escapes text strings for echoing in JS.
 * It is intended to be used for inline JS (in a tag attribute, for example onclick="...").
 * Note that the strings have to be in single quotes.
 * The {@see 'js_escape'} filter is also applied here.
 *
 * @since 2.8.0
 *
 * @param  string $text The text to be escaped.
 * @return string Escaped text.
 */
function esc_js( $text )
{
	$safe_text = wp_check_invalid_utf8( $text );
	$safe_text = _wp_specialchars( $safe_text, ENT_COMPAT );
	$safe_text = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", stripslashes( $safe_text ) );
	$safe_text = str_replace( "\r", '', $safe_text );
	$safe_text = str_replace( "\n", '\\n', addslashes( $safe_text ) );

	/**
	 * Filters a string cleaned and escaped for output in JavaScript.
	 *
	 * Text passed to esc_js() is stripped of invalid or special characters, and properly slashed for output.
	 *
	 * @since 2.0.6
	 *
	 * @param string $safe_text The text after it has been escaped.
	 * @param string $text      The text prior to being escaped.
	 */
	return apply_filters( 'js_escape', $safe_text, $text );
}

/**
 * Escaping for HTML blocks.
 *
 * @since 2.8.0
 *
 * @param  string $text
 * @return string
 */
function esc_html( $text )
{
	$safe_text = wp_check_invalid_utf8( $text );
	$safe_text = _wp_specialchars( $safe_text, ENT_QUOTES );

	/**
	 * Filters a string cleaned and escaped for output in HTML.
	 *
	 * Text passed to esc_html() is stripped of invalid or special characters before output.
	 *
	 * @since 2.8.0
	 *
	 * @param string $safe_text The text after it has been escaped.
	 * @param string $text      The text prior to being escaped.
	 */
	return apply_filters( 'esc_html', $safe_text, $text );
}

/**
 * Escaping for HTML attributes.
 *
 * @since 2.8.0
 *
 * @param  string $text
 * @return string
 */
function esc_attr( $text )
{
	$safe_text = wp_check_invalid_utf8( $text );
	$safe_text = _wp_specialchars( $safe_text, ENT_QUOTES );

	/**
	 * Filters a string cleaned and escaped for output in an HTML attribute.
	 *
	 * Text passed to esc_attr() is stripped of invalid or special characters before output.
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
 * @param  string $text
 * @return string
 */
function esc_textarea( $text )
{
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
 * Sanitises various option values based on the nature of the option.
 *
 * This is basically a switch statement which will pass $value through a number of functions depending on the $option.
 *
 * @since  2.0.5
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param  string $option The name of the option.
 * @param  string $value  The unsanitised value.
 * @return string Sanitized value.
 */
function sanitize_option( $option, $value )
{
	global $wpdb;
	$original_value = $value;
	$error = '';

	switch ( $option ) {
		case 'admin_email':
		case 'new_admin_email':
			$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

			if ( is_wp_error( $value ) ) {
				$error = $value->get_error_message();
			} else {
				$value = sanitize_email( $value );

				if ( ! is_email( $value ) ) {
					$error = __( 'The email address entered did not appear to be a valid email address. Please enter a valid email address.' );
				}
			}

			break;

		case 'thumbnail_size_w':
		case 'thumbnail_size_h':
		case 'medium_size_w':
		case 'medium_size_h':
		case 'medium_large_size_w':
		case 'medium_large_size_h':
		case 'large_size_w':
		case 'large_size_h':
		case 'mailserver_port':
		case 'comment_max_links':
		case 'page_on_front':
		case 'page_for_posts':
		case 'rss_excerpt_length':
		case 'default_category':
		case 'default_email_category':
		case 'default_link_category':
		case 'close_comments_days_old':
		case 'comments_per_page':
		case 'thread_comments_depth':
		case 'users_can_register':
		case 'start_of_week':
		case 'site_icon':
			$value = absint( $value );
			break;

		case 'posts_per_page':
		case 'posts_per_rss':
			$value = ( int ) $value;

			if ( empty( $value ) ) {
				$value = 1;
			}

			if ( $value < -1 ) {
				$value = abs( $value );
			}

			break;

		case 'default_ping_status':
		case 'default_comment_status':
			// Options that if not there have 0 value but need to be something like "closed".
			if ( $value == '0' || $value == '' ) {
				$value = 'closed';
			}

			break;

		case 'blogdescription':
		case 'blogname':
			$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

			if ( $value !== $original_value ) {
				$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', wp_encode_emoji( $original_value ) );
			}

			if ( is_wp_error( $value ) ) {
				$error = $value->get_error_message();
			} else {
				$value = esc_html( $value );
			}

			break;

		case 'blog_charset':
			$value = preg_replace( '/[^a-zA-Z0-9_-]/', '', $value ); // Strips slashes
			break;

		case 'blog_public':
			/**
			 * This is the value if the settings checkbox is not checked on POST.
			 * Don't rely on this.
			 */
			$value = NULL === $value
				? 1
				: intval( $value );

			break;

		case 'date_format':
		case 'time_format':
		case 'mailserver_url':
		case 'mailserver_login':
		case 'mailserver_pass':
		case 'upload_path':
			$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

			if ( is_wp_error( $value ) ) {
				$error = $value->get_error_message();
			} else {
				$value = strip_tags( $value );
				$value = wp_kses_data( $value );
			}

			break;

		case 'ping_sites':
			$value = explode( "\n", $value );
			$value = array_filter( array_map( 'trim', $value ) );
			$value = array_filter( array_map( 'esc_url_raw', $value ) );
			$value = implode( "\n", $value );
			break;

		case 'gmt_offset':
			$value = preg_replace( '/^[0-9:.-]/', '', $value ); // Strip slashes.
			break;

		case 'siteurl':
			$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

			if ( is_wp_error( $value ) ) {
				$error = $value->get_error_message();
			} else {
				if ( preg_match( '#http(s?)://(.+)#i', $value ) ) {
					$value = esc_url_raw( $value );
				} else {
					$error = __( 'The WordPress address you entered did not appear to be a valid URL. Please enter a valid URL.' );
				}
			}

			break;

		case 'home':
			$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

			if ( is_wp_error( $value ) ) {
				$error = $value->get_error_message();
			} else {
				if ( preg_match( '#http(s?)://(.+)#i', $value ) ) {
					$value = esc_url_raw( $value );
				} else {
					$error = __( 'The Site address you entered did not appear to be a valid URL. Please enter a valid URL.' );
				}
			}

			break;

		case 'WPLANG':
			$allowed = get_available_languages();

			if ( ! is_multisite() && defined( 'WPLANG' ) && '' !== WPLANG && 'en_US' !== WPLANG ) {
				$allowed[] = WPLANG;
			}

			if ( ! in_array( $value, $allowed ) && ! empty( $value ) ) {
				$value = get_option( $option );
			}

			break;

		case 'illegal_names':
			$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

			if ( is_wp_error( $value ) ) {
				$error = $value->get_error_message();
			} else {
				if ( ! is_array( $value ) ) {
					$value = explode( ' ', $value );
				}

				$value = array_values( array_filter( array_map( 'trim', $value ) ) );

				if ( ! $value ) {
					$value = '';
				}
			}

			break;

		case 'limited_email_domains':
		case 'banned_email_domains':
			$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

			if ( is_wp_error( $value ) ) {
				$error = $value->get_error_message();
			} else {
				if ( ! is_array( $value ) ) {
					$value = explode( "\n", $value );
				}

				$domains = array_values( array_filter( array_map( 'trim', $value ) ) );
				$value = array();

				foreach ( $domains as $domain ) {
					if ( ! preg_match( '/(--|\.\.)/', $domain ) && preg_match( '|^([a-zA-Z0-9-\.])+$|', $domain ) ) {
						$value[] = $domain;
					}
				}

				if ( ! $value ) {
					$value = '';
				}
			}

			break;

		case 'timezone_string':
			$allowed_zones = timezone_identifiers_list();

			if ( ! in_array( $value, $allowed_zones ) && ! empty( $value ) ) {
				$error = __( 'The timezone you have entered is not valid. Please select a valid timezone.' );
			}

			break;

		case 'permalink_structure':
		case 'category_base':
		case 'tag_base':
			$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

			if ( is_wp_error( $value ) ) {
				$error = $value->get_error_message();
			} else {
				$value = esc_url_raw( $value );
				$value = str_replace( 'http://', '', $value );
			}

			if ( 'permalink_structure' === $option && '' !== $value && ! preg_match( '/%[^\/%]+%/', $value ) ) {
				$error = sprintf( __( 'A structure tag is required when using custom permalinks. <a href="%s">Learn more</a>' ), __( 'https://codex.wordpress.org/Using_Permalinks#Choosing_your_permalink_structure' ) );
			}

			break;

		case 'default_role':
			if ( ! get_role( $value ) && get_role( 'subscriber' ) ) {
				$value = 'subscriber';
			}

			break;

		case 'moderation_keys':
		case 'blacklist_keys':
			$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

			if ( is_wp_error( $value ) ) {
				$error = $value->get_error_message();
			} else {
				$value = explode( "\n", $value );
				$value = array_filter( array_map( 'trim', $value ) );
				$value = array_unique( $value );
				$value = implode( "\n", $value );
			}

			break;
	}

	if ( ! empty( $error ) ) {
		$value = get_option( $option );

		if ( function_exists( 'add_settings_error' ) ) {
			add_settings_error( $option, "invalid_{$option}", $error );
		}
	}

	/**
	 * Filters an option value following sanitization.
	 *
	 * @since 2.3.0
	 * @since 4.3.0 Added the `$original_value` parameter.
	 *
	 * @param string $value          The sanitized option value.
	 * @param string $option         The option name.
	 * @param string $original_value The original value passed to the function.
	 */
	return apply_filters( "sanitize_option_{$option}", $value, $option, $original_value );
}

/**
 * Maps a function to all non-iterable elements of an array or an object.
 *
 * This is similar to `array_walk_recursive()` but acts upon objects too.
 *
 * @since 4.4.0
 *
 * @param  mixed    $value    The array, object, or scalar.
 * @param  callable $callback The function to map onto $value.
 * @return mixed    The value with the callback applied to all non-arrays and non-objects inside it.
 */
function map_deep( $value, $callback )
{
	if ( is_array( $value ) ) {
		foreach ( $value as $index => $item ) {
			$value[ $index ] = map_deep( $item, $callback );
		}
	} elseif ( is_object( $value ) ) {
		$object_vars = get_object_vars( $value );

		foreach ( $object_vars as $property_name => $property_value ) {
			$value->$property_name = map_deep( $property_value, $callback );
		}
	} else {
		$value = call_user_func( $callback, $value );
	}

	return $value;
}

/**
 * Parses a string into variables to be stored in an array.
 *
 * Uses {@link https://secure.php.net/parse_str parse_str()} and stripslashes if {@link https://secure.php.net/magic_quotes magic_quotes_gpc} is on.
 *
 * @since 2.2.1
 *
 * @param string $string The string to be parsed.
 * @param array  $array  Variables will be stored in this array.
 */
function wp_parse_str( $string, &$array )
{
	parse_str( $string, $array );

	if ( get_magic_quotes_gpc() ) {
		$array = stripslashes_deep( $array );
	}

	/**
	 * Filters the array of variables derived from a parsed string.
	 *
	 * @since 2.3.0
	 *
	 * @param array $array The array populated with variables.
	 */
	$array = apply_filters( 'wp_parse_str', $array );
}

/**
 * Convert lone less than signs.
 *
 * KSES already converts lone greater than signs.
 *
 * @since 2.3.0
 *
 * @param  string $text Text to be converted.
 * @return string Converted text.
 */
function wp_pre_kses_less_than( $text )
{
	return preg_replace_callback( '%<[^>]*?((?=<)|>|$)%', 'wp_pre_kses_less_than_callback', $text );
}

/**
 * Callback function used by preg_replace.
 *
 * @since 2.3.0
 *
 * @param  array  $matches Populated by matches to preg_replace.
 * @return string The text returned after esc_html if needed.
 */
function wp_pre_kses_less_than_callback( $matches )
{
	if ( FALSE === strpos( $matches[0], '>' ) ) {
		return esc_html( $matches[0] );
	}

	return $matches[0];
}

/**
 * Properly strip all HTML tags including script and style.
 *
 * This differs from strip_tags() because it removes the contents of the `<script>` and `<style>` tags.
 * E.g. `strip_tags( '<script>something</script>' )` will return 'something'.
 * wp_strip_all_tags will return ''.
 *
 * @since 2.9.0
 *
 * @param  string $string        String containing HTML tags.
 * @param  bool   $remove_breaks Optional.
 *                               Whether to remove left over line breaks and white space chars.
 * @return string The processed string.
 */
function wp_strip_all_tags( $string, $remove_breaks = FALSE )
{
	$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
	$string = strip_tags( $string );

	if ( $remove_breaks ) {
		$string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
	}

	return trim( $string );
}

/**
 * Sanitizes a string from user input or from the database.
 *
 * - Checks for invalid UTF-8,
 * - Converts single `<` characters to entities
 * - Strips all tags
 * - Removes line breaks, tabs, and extra whitespace
 * - Strips octets
 *
 * @since 2.9.0
 * @see   sanitize_textarea_field()
 * @see   wp_check_invalid_utf8()
 * @see   wp_strip_all_tags()
 *
 * @param  string $str String to sanitize.
 * @return string Sanitized string.
 */
function sanitize_text_field( $str )
{
	$filtered = _sanitize_text_field( $str, FALSE );

	/**
	 * Filters a sanitized textarea field string.
	 *
	 * @since 4.7.0
	 *
	 * @param string $filtered The sanitized string.
	 * @param string $str      The string prior to being sanitized.
	 */
	return apply_filters( 'sanitize_text_field', $filtered, $str );
}

/**
 * Internal helper function to sanitize a string from user input or from the db
 *
 * @since  4.7.0
 * @access private
 *
 * @param  string $str           String to sanitize.
 * @param  bool   $keep_newlines Optional.
 *                               Whether to keep newlines.
 *                               Default false.
 * @return string Sanitized string.
 */
function _sanitize_text_fields( $str, $keep_newlines = FALSE )
{
	$filtered = wp_check_invalid_utf8( $str );

	if ( strpos( $filtered, '<' ) !== FALSE ) {
		$filtered = wp_pre_kses_less_than( $filtered );

		// This will strip extra whitespace for us.
		$filtered = wp_strip_all_tags( $filtered, FALSE );

		// Use html entities in a special case to make sure no later newline stripping stage could lead to a functional tag.
		$filtered = str_replace( "<\n", "&lt;\n", $filtered );
	}

	if ( ! $keep_newlines ) {
		$filtered = preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
	}

	$filtered = trim( $filtered );
	$found = FALSE;

	while ( preg_match( '/%[a-f0-9]{2}/i', $filtered, $match ) ) {
		$filtered = str_replace( $match[0], '', $filtered );
		$found = TRUE;
	}

	if ( $found ) {
		// Strip out the whitespace that may now exist after removing the octets.
		$filtered = trim( preg_replace( '/ +/', ' ', $filtered ) );
	}

	return $filtered;
}

/**
 * Forever eliminate "Wordpress" from the planet (or at least the little bit we can influence).
 *
 * Violating our coding standards for a good function name.
 *
 * @since     3.0.0
 * @staticvar string|false $dblq
 *
 * @param  string $text The text to be modified.
 * @return string The modified text.
 */
function capital_P_dangit( $text )
{
	// Simple replacement for titles.
	$current_filter = current_filter();

	if ( 'the_title' === $current_filter || 'wp_title' === $current_filter ) {
		return str_replace( 'Wordpress', 'WordPress', $text );
	}

	/**
	 * Still here?
	 * Use the more judicious replacement.
	 */
	static $dblq = FALSE;

	if ( FALSE === $dblq ) {
		$dblq = _x( '&#8220;', 'opening curly double quote' );
	}

	return str_replace( array( ' Wordpress', '&#8216;Wordpress', $dblq . 'Wordpress', '>Wordpress', '(Wordpress' ), array( ' WordPress', '&#8216;WordPress', $dblq . 'WordPress', '>WordPress', '(WordPress' ), $text );
}

/**
 * Sanitize a mime type
 *
 * @since 3.1.3
 *
 * @param  string $mime_type Mime type
 * @return string Sanitized mime type
 */
function sanitize_mime_type( $mime_type )
{
	$sani_mime_type = preg_replace( '/[^-+*.a-zA-Z0-9\/]/', '', $mime_type );

	/**
	 * Filters a mime type following sanitization.
	 *
	 * @since 3.1.3
	 *
	 * @param string $sani_mime_type The sanitized mime type.
	 * @param string $mime_type      The mime type prior to sanitization.
	 */
	return apply_filters( 'sanitize_mime_type', $sani_mime_type, $mime_type );
}

/**
 * Add slashes to a string or array of strings.
 *
 * This should be used when preparing data for core API that expects slashed data.
 * This should not be used to escape data going directly into an SQL query.
 *
 * @since 3.6.0
 *
 * @param  string|array $value String or array of strings to slash.
 * @return string|array Slashed $value.
 */
function wp_slash( $value )
{
	if ( is_array( $value ) ) {
		foreach ( $value as $k => $v ) {
			$value[ $k ] = is_array( $v )
				? wp_slash( $v )
				: addslashes( $v );
		}
	} else {
		$value = addslashes( $value );
	}

	return $value;
}

/**
 * Returns the regexp for common whitespace characters.
 *
 * By default, spaces include new lines, tabs, nbsp entities, and the UTF-8 nbsp.
 * This is designed to replace the PCRE \s sequence.
 * In ticket #22692, that sequence was found to be unreliable due to random inclusion of the A0 byte.
 *
 * @since     4.0.0
 * @staticvar string $spaces
 *
 * @return string The spaces regexp.
 */
function wp_spaces_regexp()
{
	static $spaces = '';

	if ( empty( $spaces ) ) {
		/**
		 * Filters the regexp for common whitespace characters.
		 *
		 * This string is substituted for the \s sequence as needed in regular expressions.
		 * For websites not written in English, different characters may represent whitespace.
		 * For websites not encoded in UTF-8, the 0xC2 0xA0 sequence may not be in use.
		 *
		 * @since 4.0.0
		 *
		 * @param string $spaces Regexp pattern for matching common whitespace characters.
		 */
		$spaces = apply_filters( 'wp_spaces_regexp', '[\r\n\t ]|\xC2\xA0|&nbsp;' );
	}

	return $spaces;
}

/**
 * Convert emoji characters to their equivalent HTML entity.
 *
 * This allows us to store emoji in a DB using the utf8 character set.
 *
 * @since 4.2.0
 *
 * @param  string $content The content to encode.
 * @return string The encoded content.
 */
function wp_encode_emoji( $content )
{
	$emoji = _wp_emoji_list( 'partials' );

	foreach ( $emoji as $emojum ) {
		$emoji_char = version_compare( phpversion(), '5.4', '<' )
			? html_entity_decode( $emojum, ENT_COMPAT, 'UTF-8' )
			: html_entity_decode( $emojum );

		if ( FALSE !== strpos( $content, $emoji_char ) ) {
			$content = preg_replace( "/$emoji_char/", $emojum, $content );
		}
	}

	return $content;
}

/**
 * Returns a arrays of emoji data.
 *
 * These arrays automatically built from the regex in twemoji.js - if they need to be updated, you should update the regex there, then run the `grunt precommit:emoji` job.
 *
 * @since  4.9.0
 * @access private
 *
 * @param  string $type Optional.
 *                      Which array type to return.
 *                      Accepts 'partials' or 'entities', default 'entities'.
 * @return array  An array to match all emoji that WordPress recognises.
 */
function _wp_emoji_list( $type = 'entities' )
{
	// Do not remove the START/END comments - they're used to find where to insert the arrays.

	// START: emoji arrays
	$entities = array( '&#x1f469;&#x200d;&#x2764;&#xfe0f;&#x200d;&#x1f48b;&#x200d;&#x1f469;', '&#x1f469;&#x200d;&#x2764;&#xfe0f;&#x200d;&#x1f48b;&#x200d;&#x1f468;', '&#x1f468;&#x200d;&#x2764;&#xfe0f;&#x200d;&#x1f48b;&#x200d;&#x1f468;', '&#x1f3f4;&#xe0067;&#xe0062;&#xe0073;&#xe0063;&#xe0074;&#xe007f;', '&#x1f3f4;&#xe0067;&#xe0062;&#xe0077;&#xe006c;&#xe0073;&#xe007f;', '&#x1f3f4;&#xe0067;&#xe0062;&#xe0065;&#xe006e;&#xe0067;&#xe007f;', '&#x1f469;&#x200d;&#x1f469;&#x200d;&#x1f467;&#x200d;&#x1f466;', '&#x1f468;&#x200d;&#x1f468;&#x200d;&#x1f466;&#x200d;&#x1f466;', '&#x1f469;&#x200d;&#x1f469;&#x200d;&#x1f466;&#x200d;&#x1f466;', '&#x1f468;&#x200d;&#x1f468;&#x200d;&#x1f467;&#x200d;&#x1f466;', '&#x1f468;&#x200d;&#x1f468;&#x200d;&#x1f467;&#x200d;&#x1f467;', '&#x1f468;&#x200d;&#x1f469;&#x200d;&#x1f467;&#x200d;&#x1f467;', '&#x1f468;&#x200d;&#x1f469;&#x200d;&#x1f466;&#x200d;&#x1f466;', '&#x1f469;&#x200d;&#x1f469;&#x200d;&#x1f467;&#x200d;&#x1f467;', '&#x1f468;&#x200d;&#x1f469;&#x200d;&#x1f467;&#x200d;&#x1f466;', '&#x1f468;&#x200d;&#x2764;&#xfe0f;&#x200d;&#x1f468;', '&#x1f469;&#x200d;&#x2764;&#xfe0f;&#x200d;&#x1f468;', '&#x1f469;&#x200d;&#x2764;&#xfe0f;&#x200d;&#x1f469;', '&#x1f469;&#x200d;&#x1f469;&#x200d;&#x1f466;', '&#x1f469;&#x200d;&#x1f466;&#x200d;&#x1f466;', '&#x1f469;&#x200d;&#x1f467;&#x200d;&#x1f466;', '&#x1f469;&#x200d;&#x1f467;&#x200d;&#x1f467;', '&#x1f468;&#x200d;&#x1f468;&#x200d;&#x1f466;', '&#x1f468;&#x200d;&#x1f469;&#x200d;&#x1f467;', '&#x1f468;&#x200d;&#x1f467;&#x200d;&#x1f467;', '&#x1f468;&#x200d;&#x1f467;&#x200d;&#x1f466;', '&#x1f469;&#x200d;&#x1f469;&#x200d;&#x1f467;', '&#x1f468;&#x200d;&#x1f469;&#x200d;&#x1f466;', '&#x1f468;&#x200d;&#x1f468;&#x200d;&#x1f467;', '&#x1f468;&#x200d;&#x1f466;&#x200d;&#x1f466;', '&#x1f935;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f3cb;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f3cb;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f3cb;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f3cb;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f3cb;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f3cb;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f3cb;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f3cb;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f3cb;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f3cc;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f3cc;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f3cc;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f3cc;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f3cc;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f3cc;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f3cc;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f3cc;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f3cc;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f3cc;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f482;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f3c3;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f3c3;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f9dd;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f9dd;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f9dd;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f9dd;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f9dd;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f9dd;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f9dd;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f9dd;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f9dd;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f9dd;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f9dc;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f9dc;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f9dc;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f9dc;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f9dc;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f9dc;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f9dc;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f9dc;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f468;&#x1f3fb;&#x200d;&#x2695;&#xfe0f;', '&#x1f468;&#x1f3fb;&#x200d;&#x2696;&#xfe0f;', '&#x1f468;&#x1f3fb;&#x200d;&#x2708;&#xfe0f;', '&#x1f9dc;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f9dc;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f9db;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f9db;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f9db;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f9db;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f9db;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f9db;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f9db;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f9db;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f9db;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f9db;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f9da;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f9da;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f9da;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f9da;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f9da;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f468;&#x1f3fc;&#x200d;&#x2695;&#xfe0f;', '&#x1f468;&#x1f3fc;&#x200d;&#x2696;&#xfe0f;', '&#x1f468;&#x1f3fc;&#x200d;&#x2708;&#xfe0f;', '&#x1f9da;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f9da;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f9da;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f9da;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f9da;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d9;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d9;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d9;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d9;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d9;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d9;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d9;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d9;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d9;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d9;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d8;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d8;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f468;&#x1f3fd;&#x200d;&#x2695;&#xfe0f;', '&#x1f468;&#x1f3fd;&#x200d;&#x2696;&#xfe0f;', '&#x1f468;&#x1f3fd;&#x200d;&#x2708;&#xfe0f;', '&#x1f9d8;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d8;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d8;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d8;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d8;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d8;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d8;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d8;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d7;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d7;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d7;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d7;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d7;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d7;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d7;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d7;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d7;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f468;&#x1f3fe;&#x200d;&#x2695;&#xfe0f;', '&#x1f468;&#x1f3fe;&#x200d;&#x2696;&#xfe0f;', '&#x1f468;&#x1f3fe;&#x200d;&#x2708;&#xfe0f;', '&#x1f9d7;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d6;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d6;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d6;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d6;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d6;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d6;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d6;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d6;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d6;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d6;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f9b9;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f9b9;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f9b9;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f9b9;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f9b9;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f9b9;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f468;&#x1f3ff;&#x200d;&#x2695;&#xfe0f;', '&#x1f468;&#x1f3ff;&#x200d;&#x2696;&#xfe0f;', '&#x1f468;&#x1f3ff;&#x200d;&#x2708;&#xfe0f;', '&#x1f9b9;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f9b9;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f9b9;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f9b9;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f9b8;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f9b8;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f9b8;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f3c3;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f9b8;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f3c3;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f3c3;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f9b8;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f3c3;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f3c3;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f3c3;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f3c4;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f3c4;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f3c4;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f3c4;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f3c4;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f3c4;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f3c4;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f9b8;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f9b8;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f9b8;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f9b8;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f9b8;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f93e;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f93e;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f93e;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f93e;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f93e;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f93e;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f93e;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f93e;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f3c4;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f3c4;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f93e;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f93e;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f93d;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f93d;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f93d;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f93d;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f93d;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f93d;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f93d;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f93d;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f93d;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f93d;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f939;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f939;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f939;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f939;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f939;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f469;&#x1f3fb;&#x200d;&#x2695;&#xfe0f;', '&#x1f469;&#x1f3fb;&#x200d;&#x2696;&#xfe0f;', '&#x1f469;&#x1f3fb;&#x200d;&#x2708;&#xfe0f;', '&#x1f939;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f939;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f939;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f939;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f939;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f938;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f938;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f938;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f938;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f938;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f938;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f938;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f938;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f938;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f938;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f937;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f937;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f469;&#x1f3fc;&#x200d;&#x2695;&#xfe0f;', '&#x1f469;&#x1f3fc;&#x200d;&#x2696;&#xfe0f;', '&#x1f469;&#x1f3fc;&#x200d;&#x2708;&#xfe0f;', '&#x1f937;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f937;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f937;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f937;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f937;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f937;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f937;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f937;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f935;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f3c3;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f935;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f935;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f935;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f935;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f935;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f935;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f935;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f469;&#x1f3fd;&#x200d;&#x2695;&#xfe0f;', '&#x1f469;&#x1f3fd;&#x200d;&#x2696;&#xfe0f;', '&#x1f469;&#x1f3fd;&#x200d;&#x2708;&#xfe0f;', '&#x1f935;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f926;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f926;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f926;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f926;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f926;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f926;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f926;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f926;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f926;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f926;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b6;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b6;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b6;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b6;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b6;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b6;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f469;&#x1f3fe;&#x200d;&#x2695;&#xfe0f;', '&#x1f469;&#x1f3fe;&#x200d;&#x2696;&#xfe0f;', '&#x1f469;&#x1f3fe;&#x200d;&#x2708;&#xfe0f;', '&#x1f6b6;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b6;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b6;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b6;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b5;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b5;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b5;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b5;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b5;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b5;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b5;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b5;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b5;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b5;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b4;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b4;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b4;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f469;&#x1f3ff;&#x200d;&#x2695;&#xfe0f;', '&#x1f469;&#x1f3ff;&#x200d;&#x2696;&#xfe0f;', '&#x1f469;&#x1f3ff;&#x200d;&#x2708;&#xfe0f;', '&#x1f6b4;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b4;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b4;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b4;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b4;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b4;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b4;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f3c4;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f6a3;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f3ca;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f3ca;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f6a3;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f3ca;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f3ca;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f3ca;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f3ca;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f3ca;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f6a3;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f6a3;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f6a3;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f6a3;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f6a3;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f6a3;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f6a3;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f6a3;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f64e;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f64e;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f64e;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f64e;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f64e;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f3ca;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f3ca;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f3ca;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f3cb;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f46e;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f46e;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f46e;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f46e;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f46e;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f46e;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f46e;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f46e;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f46e;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f46e;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f64e;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f64e;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f64e;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f64e;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f471;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f471;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f471;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f471;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f471;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f471;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f471;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f471;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f471;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f471;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f64e;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f64d;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f473;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f473;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f473;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f473;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f473;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f473;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f473;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f473;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f473;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f473;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f64d;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f64d;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f477;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f477;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f477;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f477;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f477;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f477;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f477;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f477;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f477;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f477;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f64d;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f64d;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f481;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f481;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f481;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f481;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f481;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f481;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f481;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f481;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f481;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f481;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f64d;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f64d;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f3c3;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f482;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f482;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f482;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f482;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f482;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f482;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f482;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f482;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f482;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f64d;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f64d;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f486;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f486;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f486;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f486;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f486;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f486;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f486;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f486;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f486;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f486;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f64d;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f64b;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f487;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f487;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f487;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f487;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f487;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f487;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f487;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f487;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f487;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f487;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f64b;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f64b;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f574;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f574;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f574;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f574;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f574;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f574;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f574;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f574;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f574;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f574;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f575;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f575;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f575;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f575;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f575;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f575;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f575;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f575;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f575;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f575;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f645;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f645;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f645;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f645;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f645;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f645;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f645;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f645;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f645;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f645;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f64b;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f64b;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f646;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f646;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f646;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f646;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f646;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f646;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f646;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f646;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f646;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f646;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f64b;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f64b;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f647;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f647;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f647;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f647;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x1f647;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x1f647;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x1f647;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f647;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x1f647;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x1f647;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f64b;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x1f64b;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x1f64b;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x1f574;&#xfe0f;&#x200d;&#x2640;&#xfe0f;', '&#x1f574;&#xfe0f;&#x200d;&#x2642;&#xfe0f;', '&#x1f3cb;&#xfe0f;&#x200d;&#x2640;&#xfe0f;', '&#x1f3cb;&#xfe0f;&#x200d;&#x2642;&#xfe0f;', '&#x26f9;&#x1f3fe;&#x200d;&#x2640;&#xfe0f;', '&#x1f3cc;&#xfe0f;&#x200d;&#x2640;&#xfe0f;', '&#x1f3cc;&#xfe0f;&#x200d;&#x2642;&#xfe0f;', '&#x26f9;&#x1f3fb;&#x200d;&#x2642;&#xfe0f;', '&#x26f9;&#x1f3fb;&#x200d;&#x2640;&#xfe0f;', '&#x26f9;&#x1f3fe;&#x200d;&#x2642;&#xfe0f;', '&#x26f9;&#x1f3ff;&#x200d;&#x2640;&#xfe0f;', '&#x26f9;&#x1f3ff;&#x200d;&#x2642;&#xfe0f;', '&#x1f575;&#xfe0f;&#x200d;&#x2640;&#xfe0f;', '&#x1f575;&#xfe0f;&#x200d;&#x2642;&#xfe0f;', '&#x26f9;&#x1f3fd;&#x200d;&#x2640;&#xfe0f;', '&#x26f9;&#x1f3fd;&#x200d;&#x2642;&#xfe0f;', '&#x26f9;&#x1f3fc;&#x200d;&#x2640;&#xfe0f;', '&#x26f9;&#x1f3fc;&#x200d;&#x2642;&#xfe0f;', '&#x26f9;&#xfe0f;&#x200d;&#x2640;&#xfe0f;', '&#x26f9;&#xfe0f;&#x200d;&#x2642;&#xfe0f;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f3a8;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f33e;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f373;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f393;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f3a4;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f3a8;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f3eb;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f3ed;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f4bb;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f4bc;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f527;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f52c;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f680;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f692;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f9b0;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f9b1;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f9b2;', '&#x1f468;&#x1f3fb;&#x200d;&#x1f9b3;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f33e;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f373;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f393;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f3a4;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f3a8;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f3eb;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f3ed;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f4bb;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f4bc;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f527;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f52c;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f680;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f692;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f9b0;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f9b1;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f9b2;', '&#x1f468;&#x1f3fc;&#x200d;&#x1f9b3;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f33e;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f373;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f393;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f3a4;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f3a8;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f3eb;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f3ed;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f4bb;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f4bc;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f527;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f52c;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f680;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f692;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f9b0;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f9b1;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f9b2;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f9b3;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f9b2;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f9b1;', '&#x1f468;&#x1f3fd;&#x200d;&#x1f9b3;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f33e;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f9b0;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f692;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f680;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f52c;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f527;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f4bc;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f4bb;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f3ed;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f3eb;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f3a8;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f373;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f393;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f3a4;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f393;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f373;', '&#x1f469;&#x1f3ff;&#x200d;&#x1f33e;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f9b3;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f9b2;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f9b1;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f9b0;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f692;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f680;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f3a4;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f3a8;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f52c;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f527;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f4bc;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f4bb;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f3ed;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f3eb;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f3a8;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f3a4;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f393;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f373;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f3eb;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f3ed;', '&#x1f469;&#x1f3fe;&#x200d;&#x1f33e;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f9b3;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f9b2;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f9b1;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f9b0;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f692;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f680;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f52c;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f527;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f4bc;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f4bb;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f4bc;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f4bb;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f3ed;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f3eb;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f3a8;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f3a4;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f393;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f373;', '&#x1f469;&#x1f3fd;&#x200d;&#x1f33e;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f9b3;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f9b2;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f527;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f52c;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f9b1;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f9b0;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f692;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f680;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f52c;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f527;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f4bc;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f4bb;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f3ed;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f3eb;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f680;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f692;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f3a8;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f3a4;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f393;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f373;', '&#x1f469;&#x1f3fc;&#x200d;&#x1f33e;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f9b3;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f9b2;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f9b1;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f9b0;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f692;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f9b0;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f9b1;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f9b2;', '&#x1f468;&#x1f3fe;&#x200d;&#x1f9b3;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f680;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f52c;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f527;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f4bc;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f4bb;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f3ed;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f3eb;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f33e;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f3a4;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f393;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f373;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f393;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f373;', '&#x1f469;&#x1f3fb;&#x200d;&#x1f33e;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f3a4;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f3a8;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f3eb;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f3ed;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f4bb;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f4bc;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f527;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f52c;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f680;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f692;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f9b0;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f9b1;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f9b2;', '&#x1f468;&#x1f3ff;&#x200d;&#x1f9b3;', '&#x1f3f3;&#xfe0f;&#x200d;&#x1f308;', '&#x1f469;&#x200d;&#x2696;&#xfe0f;', '&#x1f9b8;&#x200d;&#x2642;&#xfe0f;', '&#x1f93e;&#x200d;&#x2642;&#xfe0f;', '&#x1f93e;&#x200d;&#x2640;&#xfe0f;', '&#x1f9b9;&#x200d;&#x2640;&#xfe0f;', '&#x1f9b9;&#x200d;&#x2642;&#xfe0f;', '&#x1f468;&#x200d;&#x2695;&#xfe0f;', '&#x1f468;&#x200d;&#x2696;&#xfe0f;', '&#x1f468;&#x200d;&#x2708;&#xfe0f;', '&#x1f93d;&#x200d;&#x2642;&#xfe0f;', '&#x1f93d;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d6;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d6;&#x200d;&#x2642;&#xfe0f;', '&#x1f647;&#x200d;&#x2640;&#xfe0f;', '&#x1f93c;&#x200d;&#x2642;&#xfe0f;', '&#x1f93c;&#x200d;&#x2640;&#xfe0f;', '&#x1f939;&#x200d;&#x2642;&#xfe0f;', '&#x1f939;&#x200d;&#x2640;&#xfe0f;', '&#x1f938;&#x200d;&#x2642;&#xfe0f;', '&#x1f938;&#x200d;&#x2640;&#xfe0f;', '&#x1f937;&#x200d;&#x2642;&#xfe0f;', '&#x1f937;&#x200d;&#x2640;&#xfe0f;', '&#x1f935;&#x200d;&#x2642;&#xfe0f;', '&#x1f9d7;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d7;&#x200d;&#x2642;&#xfe0f;', '&#x1f935;&#x200d;&#x2640;&#xfe0f;', '&#x1f926;&#x200d;&#x2642;&#xfe0f;', '&#x1f926;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b6;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b6;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b5;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b5;&#x200d;&#x2640;&#xfe0f;', '&#x1f6b4;&#x200d;&#x2642;&#xfe0f;', '&#x1f6b4;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d8;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d8;&#x200d;&#x2642;&#xfe0f;', '&#x1f6a3;&#x200d;&#x2642;&#xfe0f;', '&#x1f6a3;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d9;&#x200d;&#x2640;&#xfe0f;', '&#x1f9d9;&#x200d;&#x2642;&#xfe0f;', '&#x1f64e;&#x200d;&#x2642;&#xfe0f;', '&#x1f64e;&#x200d;&#x2640;&#xfe0f;', '&#x1f9da;&#x200d;&#x2640;&#xfe0f;', '&#x1f9da;&#x200d;&#x2642;&#xfe0f;', '&#x1f469;&#x200d;&#x2695;&#xfe0f;', '&#x1f9b8;&#x200d;&#x2640;&#xfe0f;', '&#x1f469;&#x200d;&#x2708;&#xfe0f;', '&#x1f46e;&#x200d;&#x2640;&#xfe0f;', '&#x1f46e;&#x200d;&#x2642;&#xfe0f;', '&#x1f46f;&#x200d;&#x2640;&#xfe0f;', '&#x1f46f;&#x200d;&#x2642;&#xfe0f;', '&#x1f471;&#x200d;&#x2640;&#xfe0f;', '&#x1f9db;&#x200d;&#x2640;&#xfe0f;', '&#x1f9db;&#x200d;&#x2642;&#xfe0f;', '&#x1f64d;&#x200d;&#x2642;&#xfe0f;', '&#x1f64d;&#x200d;&#x2640;&#xfe0f;', '&#x1f471;&#x200d;&#x2642;&#xfe0f;', '&#x1f473;&#x200d;&#x2640;&#xfe0f;', '&#x1f473;&#x200d;&#x2642;&#xfe0f;', '&#x1f477;&#x200d;&#x2640;&#xfe0f;', '&#x1f477;&#x200d;&#x2642;&#xfe0f;', '&#x1f481;&#x200d;&#x2640;&#xfe0f;', '&#x1f481;&#x200d;&#x2642;&#xfe0f;', '&#x1f482;&#x200d;&#x2640;&#xfe0f;', '&#x1f9dc;&#x200d;&#x2640;&#xfe0f;', '&#x1f9dc;&#x200d;&#x2642;&#xfe0f;', '&#x1f482;&#x200d;&#x2642;&#xfe0f;', '&#x1f486;&#x200d;&#x2640;&#xfe0f;', '&#x1f64b;&#x200d;&#x2642;&#xfe0f;', '&#x1f64b;&#x200d;&#x2640;&#xfe0f;', '&#x1f486;&#x200d;&#x2642;&#xfe0f;', '&#x1f487;&#x200d;&#x2640;&#xfe0f;', '&#x1f487;&#x200d;&#x2642;&#xfe0f;', '&#x1f645;&#x200d;&#x2640;&#xfe0f;', '&#x1f645;&#x200d;&#x2642;&#xfe0f;', '&#x1f9dd;&#x200d;&#x2640;&#xfe0f;', '&#x1f9dd;&#x200d;&#x2642;&#xfe0f;', '&#x1f9de;&#x200d;&#x2640;&#xfe0f;', '&#x1f9de;&#x200d;&#x2642;&#xfe0f;', '&#x1f9df;&#x200d;&#x2640;&#xfe0f;', '&#x1f9df;&#x200d;&#x2642;&#xfe0f;', '&#x1f3f4;&#x200d;&#x2620;&#xfe0f;', '&#x1f647;&#x200d;&#x2642;&#xfe0f;', '&#x1f646;&#x200d;&#x2640;&#xfe0f;', '&#x1f646;&#x200d;&#x2642;&#xfe0f;', '&#x1f3ca;&#x200d;&#x2642;&#xfe0f;', '&#x1f3ca;&#x200d;&#x2640;&#xfe0f;', '&#x1f3c4;&#x200d;&#x2642;&#xfe0f;', '&#x1f3c4;&#x200d;&#x2640;&#xfe0f;', '&#x1f3c3;&#x200d;&#x2642;&#xfe0f;', '&#x1f3c3;&#x200d;&#x2640;&#xfe0f;', '&#x1f468;&#x200d;&#x1f3a8;', '&#x1f469;&#x200d;&#x1f373;', '&#x1f469;&#x200d;&#x1f393;', '&#x1f469;&#x200d;&#x1f3a4;', '&#x1f469;&#x200d;&#x1f3a8;', '&#x1f469;&#x200d;&#x1f3eb;', '&#x1f469;&#x200d;&#x1f3ed;', '&#x1f468;&#x200d;&#x1f4bb;', '&#x1f468;&#x200d;&#x1f692;', '&#x1f469;&#x200d;&#x1f466;', '&#x1f468;&#x200d;&#x1f467;', '&#x1f468;&#x200d;&#x1f9b0;', '&#x1f468;&#x200d;&#x1f9b1;', '&#x1f469;&#x200d;&#x1f4bb;', '&#x1f469;&#x200d;&#x1f4bc;', '&#x1f469;&#x200d;&#x1f527;', '&#x1f469;&#x200d;&#x1f52c;', '&#x1f469;&#x200d;&#x1f680;', '&#x1f469;&#x200d;&#x1f692;', '&#x1f469;&#x200d;&#x1f9b0;', '&#x1f469;&#x200d;&#x1f9b1;', '&#x1f441;&#x200d;&#x1f5e8;', '&#x1f468;&#x200d;&#x1f9b2;', '&#x1f468;&#x200d;&#x1f9b3;', '&#x1f468;&#x200d;&#x1f466;', '&#x1f468;&#x200d;&#x1f3a4;', '&#x1f469;&#x200d;&#x1f9b2;', '&#x1f469;&#x200d;&#x1f9b3;', '&#x1f468;&#x200d;&#x1f393;', '&#x1f468;&#x200d;&#x1f373;', '&#x1f468;&#x200d;&#x1f33e;', '&#x1f468;&#x200d;&#x1f4bc;', '&#x1f468;&#x200d;&#x1f527;', '&#x1f468;&#x200d;&#x1f52c;', '&#x1f468;&#x200d;&#x1f680;', '&#x1f468;&#x200d;&#x1f3ed;', '&#x1f469;&#x200d;&#x1f33e;', '&#x1f468;&#x200d;&#x1f3eb;', '&#x1f469;&#x200d;&#x1f467;', '&#x1f446;&#x1f3ff;', '&#x1f447;&#x1f3fb;', '&#x1f477;&#x1f3fc;', '&#x1f447;&#x1f3fc;', '&#x1f447;&#x1f3fd;', '&#x1f477;&#x1f3fd;', '&#x1f447;&#x1f3fe;', '&#x1f447;&#x1f3ff;', '&#x1f477;&#x1f3fe;', '&#x1f448;&#x1f3fb;', '&#x1f448;&#x1f3fc;', '&#x1f477;&#x1f3ff;', '&#x1f448;&#x1f3fd;', '&#x1f448;&#x1f3fe;', '&#x1f478;&#x1f3fb;', '&#x1f478;&#x1f3fc;', '&#x1f478;&#x1f3fd;', '&#x1f478;&#x1f3fe;', '&#x1f478;&#x1f3ff;', '&#x1f47c;&#x1f3fb;', '&#x1f47c;&#x1f3fc;', '&#x1f47c;&#x1f3fd;', '&#x1f47c;&#x1f3fe;', '&#x1f47c;&#x1f3ff;', '&#x1f448;&#x1f3ff;', '&#x1f449;&#x1f3fb;', '&#x1f481;&#x1f3fb;', '&#x1f449;&#x1f3fc;', '&#x1f449;&#x1f3fd;', '&#x1f481;&#x1f3fc;', '&#x1f449;&#x1f3fe;', '&#x1f449;&#x1f3ff;', '&#x1f481;&#x1f3fd;', '&#x1f44a;&#x1f3fb;', '&#x1f44a;&#x1f3fc;', '&#x1f481;&#x1f3fe;', '&#x1f44a;&#x1f3fd;', '&#x1f44a;&#x1f3fe;', '&#x1f481;&#x1f3ff;', '&#x1f44a;&#x1f3ff;', '&#x1f44b;&#x1f3fb;', '&#x1f44b;&#x1f3fc;', '&#x1f44b;&#x1f3fd;', '&#x1f482;&#x1f3fb;', '&#x1f44b;&#x1f3fe;', '&#x1f44b;&#x1f3ff;', '&#x1f482;&#x1f3fc;', '&#x1f44c;&#x1f3fb;', '&#x1f44c;&#x1f3fc;', '&#x1f482;&#x1f3fd;', '&#x1f44c;&#x1f3fd;', '&#x1f44c;&#x1f3fe;', '&#x1f482;&#x1f3fe;', '&#x1f44c;&#x1f3ff;', '&#x1f44d;&#x1f3fb;', '&#x1f482;&#x1f3ff;', '&#x1f44d;&#x1f3fc;', '&#x1f44d;&#x1f3fd;', '&#x1f483;&#x1f3fb;', '&#x1f483;&#x1f3fc;', '&#x1f483;&#x1f3fd;', '&#x1f483;&#x1f3fe;', '&#x1f483;&#x1f3ff;', '&#x1f485;&#x1f3fb;', '&#x1f485;&#x1f3fc;', '&#x1f485;&#x1f3fd;', '&#x1f485;&#x1f3fe;', '&#x1f485;&#x1f3ff;', '&#x1f44d;&#x1f3fe;', '&#x1f44d;&#x1f3ff;', '&#x1f486;&#x1f3fb;', '&#x1f44e;&#x1f3fb;', '&#x1f44e;&#x1f3fc;', '&#x1f486;&#x1f3fc;', '&#x1f44e;&#x1f3fd;', '&#x1f44e;&#x1f3fe;', '&#x1f486;&#x1f3fd;', '&#x1f44e;&#x1f3ff;', '&#x1f44f;&#x1f3fb;', '&#x1f486;&#x1f3fe;', '&#x1f44f;&#x1f3fc;', '&#x1f44f;&#x1f3fd;', '&#x1f486;&#x1f3ff;', '&#x1f44f;&#x1f3fe;', '&#x1f44f;&#x1f3ff;', '&#x1f450;&#x1f3fb;', '&#x1f450;&#x1f3fc;', '&#x1f487;&#x1f3fb;', '&#x1f450;&#x1f3fd;', '&#x1f450;&#x1f3fe;', '&#x1f487;&#x1f3fc;', '&#x1f450;&#x1f3ff;', '&#x1f466;&#x1f3fb;', '&#x1f487;&#x1f3fd;', '&#x1f466;&#x1f3fc;', '&#x1f466;&#x1f3fd;', '&#x1f487;&#x1f3fe;', '&#x1f466;&#x1f3fe;', '&#x1f466;&#x1f3ff;', '&#x1f487;&#x1f3ff;', '&#x1f467;&#x1f3fb;', '&#x1f467;&#x1f3fc;', '&#x1f4aa;&#x1f3fb;', '&#x1f4aa;&#x1f3fc;', '&#x1f4aa;&#x1f3fd;', '&#x1f4aa;&#x1f3fe;', '&#x1f4aa;&#x1f3ff;', '&#x1f467;&#x1f3fd;', '&#x1f467;&#x1f3fe;', '&#x1f574;&#x1f3fb;', '&#x1f467;&#x1f3ff;', '&#x1f1ea;&#x1f1ea;', '&#x1f574;&#x1f3fc;', '&#x1f1ea;&#x1f1ec;', '&#x1f1ea;&#x1f1ed;', '&#x1f574;&#x1f3fd;', '&#x1f1ea;&#x1f1f7;', '&#x1f1ea;&#x1f1f8;', '&#x1f574;&#x1f3fe;', '&#x1f1ea;&#x1f1f9;', '&#x1f1ea;&#x1f1fa;', '&#x1f574;&#x1f3ff;', '&#x1f1eb;&#x1f1ee;', '&#x1f1eb;&#x1f1ef;', '&#x1f1eb;&#x1f1f0;', '&#x1f1eb;&#x1f1f2;', '&#x1f575;&#x1f3fb;', '&#x1f1eb;&#x1f1f4;', '&#x1f1eb;&#x1f1f7;', '&#x1f575;&#x1f3fc;', '&#x1f1ec;&#x1f1e6;', '&#x1f1ec;&#x1f1e7;', '&#x1f575;&#x1f3fd;', '&#x1f1ec;&#x1f1e9;', '&#x1f1ec;&#x1f1ea;', '&#x1f575;&#x1f3fe;', '&#x1f1ec;&#x1f1eb;', '&#x1f1ec;&#x1f1ec;', '&#x1f575;&#x1f3ff;', '&#x1f1ec;&#x1f1ed;', '&#x1f468;&#x1f3fb;', '&#x1f57a;&#x1f3fb;', '&#x1f57a;&#x1f3fc;', '&#x1f57a;&#x1f3fd;', '&#x1f57a;&#x1f3fe;', '&#x1f57a;&#x1f3ff;', '&#x1f590;&#x1f3fb;', '&#x1f590;&#x1f3fc;', '&#x1f590;&#x1f3fd;', '&#x1f590;&#x1f3fe;', '&#x1f590;&#x1f3ff;', '&#x1f595;&#x1f3fb;', '&#x1f595;&#x1f3fc;', '&#x1f595;&#x1f3fd;', '&#x1f595;&#x1f3fe;', '&#x1f595;&#x1f3ff;', '&#x1f596;&#x1f3fb;', '&#x1f596;&#x1f3fc;', '&#x1f596;&#x1f3fd;', '&#x1f596;&#x1f3fe;', '&#x1f596;&#x1f3ff;', '&#x1f1ec;&#x1f1ee;', '&#x1f1ec;&#x1f1f1;', '&#x1f645;&#x1f3fb;', '&#x1f1ec;&#x1f1f2;', '&#x1f1ec;&#x1f1f3;', '&#x1f645;&#x1f3fc;', '&#x1f1ec;&#x1f1f5;', '&#x1f1ec;&#x1f1f6;', '&#x1f645;&#x1f3fd;', '&#x1f1ec;&#x1f1f7;', '&#x1f1ec;&#x1f1f8;', '&#x1f645;&#x1f3fe;', '&#x1f1ec;&#x1f1f9;', '&#x1f1ec;&#x1f1fa;', '&#x1f645;&#x1f3ff;', '&#x1f1ec;&#x1f1fc;', '&#x1f1ec;&#x1f1fe;', '&#x1f1ed;&#x1f1f0;', '&#x1f1ed;&#x1f1f2;', '&#x1f646;&#x1f3fb;', '&#x1f1ed;&#x1f1f3;', '&#x1f1ed;&#x1f1f7;', '&#x1f646;&#x1f3fc;', '&#x1f1ed;&#x1f1f9;', '&#x1f1ed;&#x1f1fa;', '&#x1f646;&#x1f3fd;', '&#x1f1ee;&#x1f1e8;', '&#x1f1ee;&#x1f1e9;', '&#x1f646;&#x1f3fe;', '&#x1f468;&#x1f3fc;', '&#x1f1ee;&#x1f1ea;', '&#x1f646;&#x1f3ff;', '&#x1f1ee;&#x1f1f1;', '&#x1f1ee;&#x1f1f2;', '&#x1f1ee;&#x1f1f3;', '&#x1f1ee;&#x1f1f4;', '&#x1f647;&#x1f3fb;', '&#x1f1ee;&#x1f1f6;', '&#x1f1ee;&#x1f1f7;', '&#x1f647;&#x1f3fc;', '&#x1f1ee;&#x1f1f8;', '&#x1f1ee;&#x1f1f9;', '&#x1f647;&#x1f3fd;', '&#x1f1ef;&#x1f1ea;', '&#x1f1ef;&#x1f1f2;', '&#x1f647;&#x1f3fe;', '&#x1f1ef;&#x1f1f4;', '&#x1f1ef;&#x1f1f5;', '&#x1f647;&#x1f3ff;', '&#x1f1f0;&#x1f1ea;', '&#x1f1f0;&#x1f1ec;', '&#x1f1f0;&#x1f1ed;', '&#x1f1f0;&#x1f1ee;', '&#x1f64b;&#x1f3fb;', '&#x1f1f0;&#x1f1f2;', '&#x1f1f0;&#x1f1f3;', '&#x1f64b;&#x1f3fc;', '&#x1f1f0;&#x1f1f5;', '&#x1f468;&#x1f3fd;', '&#x1f64b;&#x1f3fd;', '&#x1f1f0;&#x1f1f7;', '&#x1f1f0;&#x1f1fc;', '&#x1f64b;&#x1f3fe;', '&#x1f1f0;&#x1f1fe;', '&#x1f1f0;&#x1f1ff;', '&#x1f64b;&#x1f3ff;', '&#x1f1f1;&#x1f1e6;', '&#x1f1f1;&#x1f1e7;', '&#x1f64c;&#x1f3fb;', '&#x1f64c;&#x1f3fc;', '&#x1f64c;&#x1f3fd;', '&#x1f64c;&#x1f3fe;', '&#x1f64c;&#x1f3ff;', '&#x1f1f1;&#x1f1e8;', '&#x1f1f1;&#x1f1ee;', '&#x1f64d;&#x1f3fb;', '&#x1f1f1;&#x1f1f0;', '&#x1f1f1;&#x1f1f7;', '&#x1f64d;&#x1f3fc;', '&#x1f1f1;&#x1f1f8;', '&#x1f1f1;&#x1f1f9;', '&#x1f64d;&#x1f3fd;', '&#x1f1f1;&#x1f1fa;', '&#x1f1f1;&#x1f1fb;', '&#x1f64d;&#x1f3fe;', '&#x1f1f1;&#x1f1fe;', '&#x1f1f2;&#x1f1e6;', '&#x1f64d;&#x1f3ff;', '&#x1f1f2;&#x1f1e8;', '&#x1f1f2;&#x1f1e9;', '&#x1f1f2;&#x1f1ea;', '&#x1f1f2;&#x1f1eb;', '&#x1f64e;&#x1f3fb;', '&#x1f468;&#x1f3fe;', '&#x1f1f2;&#x1f1ec;', '&#x1f64e;&#x1f3fc;', '&#x1f1f2;&#x1f1ed;', '&#x1f1f2;&#x1f1f0;', '&#x1f64e;&#x1f3fd;', '&#x1f1f2;&#x1f1f1;', '&#x1f1f2;&#x1f1f2;', '&#x1f64e;&#x1f3fe;', '&#x1f1f2;&#x1f1f3;', '&#x1f1f2;&#x1f1f4;', '&#x1f64e;&#x1f3ff;', '&#x1f1f2;&#x1f1f5;', '&#x1f1f2;&#x1f1f6;', '&#x1f64f;&#x1f3fb;', '&#x1f64f;&#x1f3fc;', '&#x1f64f;&#x1f3fd;', '&#x1f64f;&#x1f3fe;', '&#x1f64f;&#x1f3ff;', '&#x1f1f2;&#x1f1f7;', '&#x1f1f2;&#x1f1f8;', '&#x1f6a3;&#x1f3fb;', '&#x1f1f2;&#x1f1f9;', '&#x1f1f2;&#x1f1fa;', '&#x1f6a3;&#x1f3fc;', '&#x1f1f2;&#x1f1fb;', '&#x1f1f2;&#x1f1fc;', '&#x1f6a3;&#x1f3fd;', '&#x1f1f2;&#x1f1fd;', '&#x1f1f2;&#x1f1fe;', '&#x1f6a3;&#x1f3fe;', '&#x1f1f2;&#x1f1ff;', '&#x1f1f3;&#x1f1e6;', '&#x1f6a3;&#x1f3ff;', '&#x1f1f3;&#x1f1e8;', '&#x1f468;&#x1f3ff;', '&#x1f1f3;&#x1f1ea;', '&#x1f1f3;&#x1f1eb;', '&#x1f6b4;&#x1f3fb;', '&#x1f1f3;&#x1f1ec;', '&#x1f1f3;&#x1f1ee;', '&#x1f6b4;&#x1f3fc;', '&#x1f1f3;&#x1f1f1;', '&#x1f1f3;&#x1f1f4;', '&#x1f6b4;&#x1f3fd;', '&#x1f1f3;&#x1f1f5;', '&#x1f1f3;&#x1f1f7;', '&#x1f6b4;&#x1f3fe;', '&#x1f1f3;&#x1f1fa;', '&#x1f1f3;&#x1f1ff;', '&#x1f6b4;&#x1f3ff;', '&#x1f1f4;&#x1f1f2;', '&#x1f1f5;&#x1f1e6;', '&#x1f1f5;&#x1f1ea;', '&#x1f1f5;&#x1f1eb;', '&#x1f6b5;&#x1f3fb;', '&#x1f1f5;&#x1f1ec;', '&#x1f1f5;&#x1f1ed;', '&#x1f6b5;&#x1f3fc;', '&#x1f1f5;&#x1f1f0;', '&#x1f1f5;&#x1f1f1;', '&#x1f6b5;&#x1f3fd;', '&#x1f1f5;&#x1f1f2;', '&#x1f1f5;&#x1f1f3;', '&#x1f6b5;&#x1f3fe;', '&#x1f1f5;&#x1f1f7;', '&#x1f1f5;&#x1f1f8;', '&#x1f6b5;&#x1f3ff;', '&#x1f1f5;&#x1f1f9;', '&#x1f1f5;&#x1f1fc;', '&#x1f1f5;&#x1f1fe;', '&#x1f1f6;&#x1f1e6;', '&#x1f6b6;&#x1f3fb;', '&#x1f1f7;&#x1f1ea;', '&#x1f1f7;&#x1f1f4;', '&#x1f6b6;&#x1f3fc;', '&#x1f1f7;&#x1f1f8;', '&#x1f1f7;&#x1f1fa;', '&#x1f6b6;&#x1f3fd;', '&#x1f1f7;&#x1f1fc;', '&#x1f1f8;&#x1f1e6;', '&#x1f6b6;&#x1f3fe;', '&#x1f1f8;&#x1f1e7;', '&#x1f1f8;&#x1f1e8;', '&#x1f6b6;&#x1f3ff;', '&#x1f1f8;&#x1f1e9;', '&#x1f1f8;&#x1f1ea;', '&#x1f6c0;&#x1f3fb;', '&#x1f6c0;&#x1f3fc;', '&#x1f6c0;&#x1f3fd;', '&#x1f6c0;&#x1f3fe;', '&#x1f6c0;&#x1f3ff;', '&#x1f6cc;&#x1f3fb;', '&#x1f6cc;&#x1f3fc;', '&#x1f6cc;&#x1f3fd;', '&#x1f6cc;&#x1f3fe;', '&#x1f6cc;&#x1f3ff;', '&#x1f918;&#x1f3fb;', '&#x1f918;&#x1f3fc;', '&#x1f918;&#x1f3fd;', '&#x1f918;&#x1f3fe;', '&#x1f918;&#x1f3ff;', '&#x1f919;&#x1f3fb;', '&#x1f919;&#x1f3fc;', '&#x1f919;&#x1f3fd;', '&#x1f919;&#x1f3fe;', '&#x1f919;&#x1f3ff;', '&#x1f91a;&#x1f3fb;', '&#x1f91a;&#x1f3fc;', '&#x1f91a;&#x1f3fd;', '&#x1f91a;&#x1f3fe;', '&#x1f91a;&#x1f3ff;', '&#x1f91b;&#x1f3fb;', '&#x1f91b;&#x1f3fc;', '&#x1f91b;&#x1f3fd;', '&#x1f91b;&#x1f3fe;', '&#x1f91b;&#x1f3ff;', '&#x1f91c;&#x1f3fb;', '&#x1f91c;&#x1f3fc;', '&#x1f91c;&#x1f3fd;', '&#x1f91c;&#x1f3fe;', '&#x1f91c;&#x1f3ff;', '&#x1f91e;&#x1f3fb;', '&#x1f91e;&#x1f3fc;', '&#x1f91e;&#x1f3fd;', '&#x1f91e;&#x1f3fe;', '&#x1f91e;&#x1f3ff;', '&#x1f91f;&#x1f3fb;', '&#x1f91f;&#x1f3fc;', '&#x1f91f;&#x1f3fd;', '&#x1f91f;&#x1f3fe;', '&#x1f91f;&#x1f3ff;', '&#x1f1f8;&#x1f1ec;', '&#x1f1f8;&#x1f1ed;', '&#x1f926;&#x1f3fb;', '&#x1f1f8;&#x1f1ee;', '&#x1f1f8;&#x1f1ef;', '&#x1f926;&#x1f3fc;', '&#x1f1f8;&#x1f1f0;', '&#x1f1e6;&#x1f1e9;', '&#x1f926;&#x1f3fd;', '&#x1f1f8;&#x1f1f2;', '&#x1f1f8;&#x1f1f3;', '&#x1f926;&#x1f3fe;', '&#x1f1f8;&#x1f1f4;', '&#x1f1f8;&#x1f1f7;', '&#x1f926;&#x1f3ff;', '&#x1f1f8;&#x1f1f8;', '&#x1f1f8;&#x1f1f9;', '&#x1f930;&#x1f3fb;', '&#x1f930;&#x1f3fc;', '&#x1f930;&#x1f3fd;', '&#x1f930;&#x1f3fe;', '&#x1f930;&#x1f3ff;', '&#x1f931;&#x1f3fb;', '&#x1f931;&#x1f3fc;', '&#x1f931;&#x1f3fd;', '&#x1f931;&#x1f3fe;', '&#x1f931;&#x1f3ff;', '&#x1f932;&#x1f3fb;', '&#x1f932;&#x1f3fc;', '&#x1f932;&#x1f3fd;', '&#x1f932;&#x1f3fe;', '&#x1f932;&#x1f3ff;', '&#x1f933;&#x1f3fb;', '&#x1f933;&#x1f3fc;', '&#x1f933;&#x1f3fd;', '&#x1f933;&#x1f3fe;', '&#x1f933;&#x1f3ff;', '&#x1f934;&#x1f3fb;', '&#x1f934;&#x1f3fc;', '&#x1f934;&#x1f3fd;', '&#x1f934;&#x1f3fe;', '&#x1f934;&#x1f3ff;', '&#x1f1f8;&#x1f1fb;', '&#x1f1f8;&#x1f1fd;', '&#x1f935;&#x1f3fb;', '&#x1f1f8;&#x1f1fe;', '&#x1f1f8;&#x1f1ff;', '&#x1f935;&#x1f3fc;', '&#x1f1f9;&#x1f1e6;', '&#x1f1f9;&#x1f1e8;', '&#x1f935;&#x1f3fd;', '&#x1f1f9;&#x1f1e9;', '&#x1f1f9;&#x1f1eb;', '&#x1f935;&#x1f3fe;', '&#x1f1f9;&#x1f1ec;', '&#x1f469;&#x1f3fb;', '&#x1f935;&#x1f3ff;', '&#x1f1f9;&#x1f1ed;', '&#x1f1f9;&#x1f1ef;', '&#x1f936;&#x1f3fb;', '&#x1f936;&#x1f3fc;', '&#x1f936;&#x1f3fd;', '&#x1f936;&#x1f3fe;', '&#x1f936;&#x1f3ff;', '&#x1f1f9;&#x1f1f0;', '&#x1f1f9;&#x1f1f1;', '&#x1f937;&#x1f3fb;', '&#x1f1f9;&#x1f1f2;', '&#x1f1f9;&#x1f1f3;', '&#x1f937;&#x1f3fc;', '&#x1f1f9;&#x1f1f4;', '&#x1f1f9;&#x1f1f7;', '&#x1f937;&#x1f3fd;', '&#x1f1f9;&#x1f1f9;', '&#x1f1f9;&#x1f1fb;', '&#x1f937;&#x1f3fe;', '&#x1f1f9;&#x1f1fc;', '&#x1f1f9;&#x1f1ff;', '&#x1f937;&#x1f3ff;', '&#x1f1fa;&#x1f1e6;', '&#x1f1fa;&#x1f1ec;', '&#x1f1fa;&#x1f1f2;', '&#x1f1fa;&#x1f1f3;', '&#x1f938;&#x1f3fb;', '&#x1f1fa;&#x1f1f8;', '&#x1f1fa;&#x1f1fe;', '&#x1f938;&#x1f3fc;', '&#x1f1fa;&#x1f1ff;', '&#x1f1fb;&#x1f1e6;', '&#x1f938;&#x1f3fd;', '&#x1f469;&#x1f3fc;', '&#x1f1fb;&#x1f1e8;', '&#x1f938;&#x1f3fe;', '&#x1f1fb;&#x1f1ea;', '&#x1f1fb;&#x1f1ec;', '&#x1f938;&#x1f3ff;', '&#x1f1fb;&#x1f1ee;', '&#x1f1fb;&#x1f1f3;', '&#x1f1fb;&#x1f1fa;', '&#x1f1fc;&#x1f1eb;', '&#x1f939;&#x1f3fb;', '&#x1f1fc;&#x1f1f8;', '&#x1f1fd;&#x1f1f0;', '&#x1f939;&#x1f3fc;', '&#x1f1fe;&#x1f1ea;', '&#x1f1fe;&#x1f1f9;', '&#x1f939;&#x1f3fd;', '&#x1f1ff;&#x1f1e6;', '&#x1f1ff;&#x1f1f2;', '&#x1f939;&#x1f3fe;', '&#x1f1ff;&#x1f1fc;', '&#x1f385;&#x1f3fb;', '&#x1f939;&#x1f3ff;', '&#x1f385;&#x1f3fc;', '&#x1f385;&#x1f3fd;', '&#x1f385;&#x1f3fe;', '&#x1f385;&#x1f3ff;', '&#x1f3c2;&#x1f3fb;', '&#x1f469;&#x1f3fd;', '&#x1f93d;&#x1f3fb;', '&#x1f3c2;&#x1f3fc;', '&#x1f3c2;&#x1f3fd;', '&#x1f93d;&#x1f3fc;', '&#x1f3c2;&#x1f3fe;', '&#x1f3c2;&#x1f3ff;', '&#x1f93d;&#x1f3fd;', '&#x1f1e6;&#x1f1e8;', '&#x1f1e6;&#x1f1ea;', '&#x1f93d;&#x1f3fe;', '&#x1f3c3;&#x1f3fb;', '&#x1f1e6;&#x1f1eb;', '&#x1f93d;&#x1f3ff;', '&#x1f1e6;&#x1f1ec;', '&#x1f3c3;&#x1f3fc;', '&#x1f1e6;&#x1f1ee;', '&#x1f1e6;&#x1f1f1;', '&#x1f93e;&#x1f3fb;', '&#x1f3c3;&#x1f3fd;', '&#x1f1e6;&#x1f1f2;', '&#x1f93e;&#x1f3fc;', '&#x1f1e6;&#x1f1f4;', '&#x1f3c3;&#x1f3fe;', '&#x1f93e;&#x1f3fd;', '&#x1f1e6;&#x1f1f6;', '&#x1f1e6;&#x1f1f7;', '&#x1f93e;&#x1f3fe;', '&#x1f3c3;&#x1f3ff;', '&#x1f1e6;&#x1f1f8;', '&#x1f93e;&#x1f3ff;', '&#x1f469;&#x1f3fe;', '&#x1f1e6;&#x1f1f9;', '&#x1f9b5;&#x1f3fb;', '&#x1f9b5;&#x1f3fc;', '&#x1f9b5;&#x1f3fd;', '&#x1f9b5;&#x1f3fe;', '&#x1f9b5;&#x1f3ff;', '&#x1f9b6;&#x1f3fb;', '&#x1f9b6;&#x1f3fc;', '&#x1f9b6;&#x1f3fd;', '&#x1f9b6;&#x1f3fe;', '&#x1f9b6;&#x1f3ff;', '&#x1f1e6;&#x1f1fa;', '&#x1f1e6;&#x1f1fc;', '&#x1f9b8;&#x1f3fb;', '&#x1f3c4;&#x1f3fb;', '&#x1f1e6;&#x1f1fd;', '&#x1f9b8;&#x1f3fc;', '&#x1f1e6;&#x1f1ff;', '&#x1f3c4;&#x1f3fc;', '&#x1f9b8;&#x1f3fd;', '&#x1f1e7;&#x1f1e6;', '&#x1f1e7;&#x1f1e7;', '&#x1f9b8;&#x1f3fe;', '&#x1f3c4;&#x1f3fd;', '&#x1f1e7;&#x1f1e9;', '&#x1f9b8;&#x1f3ff;', '&#x1f1e7;&#x1f1ea;', '&#x1f3c4;&#x1f3fe;', '&#x1f1e7;&#x1f1eb;', '&#x1f1e7;&#x1f1ec;', '&#x1f9b9;&#x1f3fb;', '&#x1f3c4;&#x1f3ff;', '&#x1f1e7;&#x1f1ed;', '&#x1f9b9;&#x1f3fc;', '&#x1f1e7;&#x1f1ee;', '&#x1f3c7;&#x1f3fb;', '&#x1f9b9;&#x1f3fd;', '&#x1f3c7;&#x1f3fc;', '&#x1f469;&#x1f3ff;', '&#x1f9b9;&#x1f3fe;', '&#x1f3c7;&#x1f3fd;', '&#x1f3c7;&#x1f3fe;', '&#x1f9b9;&#x1f3ff;', '&#x1f3c7;&#x1f3ff;', '&#x1f1e7;&#x1f1ef;', '&#x1f9d1;&#x1f3fb;', '&#x1f9d1;&#x1f3fc;', '&#x1f9d1;&#x1f3fd;', '&#x1f9d1;&#x1f3fe;', '&#x1f9d1;&#x1f3ff;', '&#x1f9d2;&#x1f3fb;', '&#x1f9d2;&#x1f3fc;', '&#x1f9d2;&#x1f3fd;', '&#x1f9d2;&#x1f3fe;', '&#x1f9d2;&#x1f3ff;', '&#x1f9d3;&#x1f3fb;', '&#x1f9d3;&#x1f3fc;', '&#x1f9d3;&#x1f3fd;', '&#x1f9d3;&#x1f3fe;', '&#x1f9d3;&#x1f3ff;', '&#x1f9d4;&#x1f3fb;', '&#x1f9d4;&#x1f3fc;', '&#x1f9d4;&#x1f3fd;', '&#x1f9d4;&#x1f3fe;', '&#x1f9d4;&#x1f3ff;', '&#x1f9d5;&#x1f3fb;', '&#x1f9d5;&#x1f3fc;', '&#x1f9d5;&#x1f3fd;', '&#x1f9d5;&#x1f3fe;', '&#x1f9d5;&#x1f3ff;', '&#x1f1e7;&#x1f1f1;', '&#x1f3ca;&#x1f3fb;', '&#x1f9d6;&#x1f3fb;', '&#x1f1e7;&#x1f1f2;', '&#x1f1e7;&#x1f1f3;', '&#x1f9d6;&#x1f3fc;', '&#x1f3ca;&#x1f3fc;', '&#x1f1e7;&#x1f1f4;', '&#x1f9d6;&#x1f3fd;', '&#x1f1e7;&#x1f1f6;', '&#x1f3ca;&#x1f3fd;', '&#x1f9d6;&#x1f3fe;', '&#x1f1e7;&#x1f1f7;', '&#x1f1e7;&#x1f1f8;', '&#x1f9d6;&#x1f3ff;', '&#x1f3ca;&#x1f3fe;', '&#x1f1e7;&#x1f1f9;', '&#x1f1e7;&#x1f1fb;', '&#x1f3ca;&#x1f3ff;', '&#x1f9d7;&#x1f3fb;', '&#x1f1e7;&#x1f1fc;', '&#x1f1e7;&#x1f1fe;', '&#x1f9d7;&#x1f3fc;', '&#x1f1e7;&#x1f1ff;', '&#x1f1e8;&#x1f1e6;', '&#x1f9d7;&#x1f3fd;', '&#x1f3cb;&#x1f3fb;', '&#x1f1e8;&#x1f1e8;', '&#x1f9d7;&#x1f3fe;', '&#x1f1e8;&#x1f1e9;', '&#x1f3cb;&#x1f3fc;', '&#x1f9d7;&#x1f3ff;', '&#x1f1e8;&#x1f1eb;', '&#x1f1e8;&#x1f1ec;', '&#x1f3cb;&#x1f3fd;', '&#x1f1e8;&#x1f1ed;', '&#x1f9d8;&#x1f3fb;', '&#x1f1e8;&#x1f1ee;', '&#x1f3cb;&#x1f3fe;', '&#x1f9d8;&#x1f3fc;', '&#x1f1e8;&#x1f1f0;', '&#x1f1e8;&#x1f1f1;', '&#x1f9d8;&#x1f3fd;', '&#x1f3cb;&#x1f3ff;', '&#x1f1e8;&#x1f1f2;', '&#x1f9d8;&#x1f3fe;', '&#x1f46e;&#x1f3fb;', '&#x1f1e8;&#x1f1f3;', '&#x1f9d8;&#x1f3ff;', '&#x1f1e8;&#x1f1f4;', '&#x1f46e;&#x1f3fc;', '&#x1f1e8;&#x1f1f5;', '&#x1f3cc;&#x1f3fb;', '&#x1f9d9;&#x1f3fb;', '&#x1f46e;&#x1f3fd;', '&#x1f1e8;&#x1f1f7;', '&#x1f9d9;&#x1f3fc;', '&#x1f1e8;&#x1f1fa;', '&#x1f46e;&#x1f3fe;', '&#x1f9d9;&#x1f3fd;', '&#x1f3cc;&#x1f3fc;', '&#x1f1e8;&#x1f1fb;', '&#x1f9d9;&#x1f3fe;', '&#x1f46e;&#x1f3ff;', '&#x1f1e8;&#x1f1fc;', '&#x1f9d9;&#x1f3ff;', '&#x1f3cc;&#x1f3fd;', '&#x1f1e8;&#x1f1fd;', '&#x1f1e8;&#x1f1fe;', '&#x1f470;&#x1f3fb;', '&#x1f9da;&#x1f3fb;', '&#x1f470;&#x1f3fc;', '&#x1f470;&#x1f3fd;', '&#x1f9da;&#x1f3fc;', '&#x1f470;&#x1f3fe;', '&#x1f470;&#x1f3ff;', '&#x1f9da;&#x1f3fd;', '&#x1f3cc;&#x1f3fe;', '&#x1f1e8;&#x1f1ff;', '&#x1f9da;&#x1f3fe;', '&#x1f471;&#x1f3fb;', '&#x1f1e9;&#x1f1ea;', '&#x1f9da;&#x1f3ff;', '&#x1f3cc;&#x1f3ff;', '&#x1f471;&#x1f3fc;', '&#x1f1e9;&#x1f1ec;', '&#x1f1e9;&#x1f1ef;', '&#x1f9db;&#x1f3fb;', '&#x1f471;&#x1f3fd;', '&#x1f1e9;&#x1f1f0;', '&#x1f9db;&#x1f3fc;', '&#x1f1e9;&#x1f1f2;', '&#x1f471;&#x1f3fe;', '&#x1f9db;&#x1f3fd;', '&#x1f1e9;&#x1f1f4;', '&#x1f1e9;&#x1f1ff;', '&#x1f9db;&#x1f3fe;', '&#x1f471;&#x1f3ff;', '&#x1f1ea;&#x1f1e6;', '&#x1f9db;&#x1f3ff;', '&#x1f1ea;&#x1f1e8;', '&#x1f472;&#x1f3fb;', '&#x1f472;&#x1f3fc;', '&#x1f472;&#x1f3fd;', '&#x1f9dc;&#x1f3fb;', '&#x1f472;&#x1f3fe;', '&#x1f472;&#x1f3ff;', '&#x1f9dc;&#x1f3fc;', '&#x1f442;&#x1f3fb;', '&#x1f442;&#x1f3fc;', '&#x1f9dc;&#x1f3fd;', '&#x1f473;&#x1f3fb;', '&#x1f442;&#x1f3fd;', '&#x1f9dc;&#x1f3fe;', '&#x1f442;&#x1f3fe;', '&#x1f473;&#x1f3fc;', '&#x1f9dc;&#x1f3ff;', '&#x1f442;&#x1f3ff;', '&#x1f443;&#x1f3fb;', '&#x1f473;&#x1f3fd;', '&#x1f443;&#x1f3fc;', '&#x1f9dd;&#x1f3fb;', '&#x1f443;&#x1f3fd;', '&#x1f473;&#x1f3fe;', '&#x1f9dd;&#x1f3fc;', '&#x1f443;&#x1f3fe;', '&#x1f443;&#x1f3ff;', '&#x1f9dd;&#x1f3fd;', '&#x1f473;&#x1f3ff;', '&#x1f446;&#x1f3fb;', '&#x1f9dd;&#x1f3fe;', '&#x1f446;&#x1f3fc;', '&#x1f474;&#x1f3fb;', '&#x1f9dd;&#x1f3ff;', '&#x1f474;&#x1f3fc;', '&#x1f474;&#x1f3fd;', '&#x1f474;&#x1f3fe;', '&#x1f474;&#x1f3ff;', '&#x1f475;&#x1f3fb;', '&#x1f475;&#x1f3fc;', '&#x1f475;&#x1f3fd;', '&#x1f475;&#x1f3fe;', '&#x1f475;&#x1f3ff;', '&#x1f476;&#x1f3fb;', '&#x1f476;&#x1f3fc;', '&#x1f476;&#x1f3fd;', '&#x1f476;&#x1f3fe;', '&#x1f476;&#x1f3ff;', '&#x1f446;&#x1f3fd;', '&#x1f446;&#x1f3fe;', '&#x1f477;&#x1f3fb;', '&#x1f1f8;&#x1f1f1;', '&#x270d;&#x1f3ff;', '&#x26f9;&#x1f3fb;', '&#x270d;&#x1f3fe;', '&#x270d;&#x1f3fd;', '&#x270d;&#x1f3fc;', '&#x270d;&#x1f3fb;', '&#x270c;&#x1f3ff;', '&#x270c;&#x1f3fe;', '&#x270c;&#x1f3fd;', '&#x270c;&#x1f3fc;', '&#x270c;&#x1f3fb;', '&#x270b;&#x1f3ff;', '&#x270b;&#x1f3fe;', '&#x270b;&#x1f3fd;', '&#x270b;&#x1f3fc;', '&#x270b;&#x1f3fb;', '&#x270a;&#x1f3ff;', '&#x270a;&#x1f3fe;', '&#x270a;&#x1f3fd;', '&#x270a;&#x1f3fc;', '&#x270a;&#x1f3fb;', '&#x26f7;&#x1f3fd;', '&#x26f7;&#x1f3fe;', '&#x26f9;&#x1f3ff;', '&#x261d;&#x1f3ff;', '&#x261d;&#x1f3fe;', '&#x26f9;&#x1f3fe;', '&#x261d;&#x1f3fd;', '&#x261d;&#x1f3fc;', '&#x26f9;&#x1f3fd;', '&#x261d;&#x1f3fb;', '&#x26f7;&#x1f3ff;', '&#x26f9;&#x1f3fc;', '&#x26f7;&#x1f3fb;', '&#x26f7;&#x1f3fc;', '&#x34;&#x20e3;', '&#x23;&#x20e3;', '&#x30;&#x20e3;', '&#x31;&#x20e3;', '&#x32;&#x20e3;', '&#x33;&#x20e3;', '&#x2a;&#x20e3;', '&#x35;&#x20e3;', '&#x36;&#x20e3;', '&#x37;&#x20e3;', '&#x38;&#x20e3;', '&#x39;&#x20e3;', '&#x1f0cf;', '&#x1f57a;', '&#x1f587;', '&#x1f58a;', '&#x1f58b;', '&#x1f58c;', '&#x1f58d;', '&#x1f004;', '&#x1f1fe;', '&#x1f1e6;', '&#x1f170;', '&#x1f171;', '&#x1f590;', '&#x1f1ff;', '&#x1f201;', '&#x1f202;', '&#x1f3c4;', '&#x1f3c5;', '&#x1f595;', '&#x1f3c6;', '&#x1f21a;', '&#x1f22f;', '&#x1f232;', '&#x1f233;', '&#x1f596;', '&#x1f5a4;', '&#x1f5a5;', '&#x1f5a8;', '&#x1f5b1;', '&#x1f5b2;', '&#x1f5bc;', '&#x1f5c2;', '&#x1f5c3;', '&#x1f5c4;', '&#x1f5d1;', '&#x1f5d2;', '&#x1f5d3;', '&#x1f5dc;', '&#x1f5dd;', '&#x1f5de;', '&#x1f5e1;', '&#x1f5e3;', '&#x1f5e8;', '&#x1f5ef;', '&#x1f5f3;', '&#x1f5fa;', '&#x1f5fb;', '&#x1f5fc;', '&#x1f5fd;', '&#x1f5fe;', '&#x1f5ff;', '&#x1f600;', '&#x1f601;', '&#x1f602;', '&#x1f603;', '&#x1f604;', '&#x1f605;', '&#x1f606;', '&#x1f607;', '&#x1f608;', '&#x1f609;', '&#x1f60a;', '&#x1f60b;', '&#x1f60c;', '&#x1f60d;', '&#x1f60e;', '&#x1f60f;', '&#x1f610;', '&#x1f611;', '&#x1f612;', '&#x1f613;', '&#x1f614;', '&#x1f615;', '&#x1f616;', '&#x1f617;', '&#x1f618;', '&#x1f619;', '&#x1f61a;', '&#x1f61b;', '&#x1f61c;', '&#x1f61d;', '&#x1f61e;', '&#x1f61f;', '&#x1f620;', '&#x1f621;', '&#x1f622;', '&#x1f623;', '&#x1f624;', '&#x1f625;', '&#x1f626;', '&#x1f627;', '&#x1f628;', '&#x1f629;', '&#x1f62a;', '&#x1f62b;', '&#x1f62c;', '&#x1f62d;', '&#x1f62e;', '&#x1f62f;', '&#x1f630;', '&#x1f631;', '&#x1f632;', '&#x1f633;', '&#x1f634;', '&#x1f635;', '&#x1f636;', '&#x1f637;', '&#x1f638;', '&#x1f639;', '&#x1f63a;', '&#x1f63b;', '&#x1f63c;', '&#x1f63d;', '&#x1f63e;', '&#x1f63f;', '&#x1f640;', '&#x1f641;', '&#x1f642;', '&#x1f643;', '&#x1f644;', '&#x1f234;', '&#x1f3c7;', '&#x1f3c8;', '&#x1f3c9;', '&#x1f235;', '&#x1f236;', '&#x1f237;', '&#x1f238;', '&#x1f239;', '&#x1f23a;', '&#x1f250;', '&#x1f251;', '&#x1f300;', '&#x1f301;', '&#x1f302;', '&#x1f303;', '&#x1f304;', '&#x1f645;', '&#x1f305;', '&#x1f306;', '&#x1f307;', '&#x1f308;', '&#x1f3ca;', '&#x1f309;', '&#x1f30a;', '&#x1f30b;', '&#x1f30c;', '&#x1f468;', '&#x1f30d;', '&#x1f30e;', '&#x1f30f;', '&#x1f310;', '&#x1f311;', '&#x1f312;', '&#x1f313;', '&#x1f646;', '&#x1f314;', '&#x1f315;', '&#x1f316;', '&#x1f317;', '&#x1f318;', '&#x1f319;', '&#x1f3cb;', '&#x1f31a;', '&#x1f31b;', '&#x1f31c;', '&#x1f31d;', '&#x1f31e;', '&#x1f31f;', '&#x1f320;', '&#x1f321;', '&#x1f324;', '&#x1f325;', '&#x1f647;', '&#x1f648;', '&#x1f649;', '&#x1f64a;', '&#x1f326;', '&#x1f327;', '&#x1f328;', '&#x1f329;', '&#x1f32a;', '&#x1f32b;', '&#x1f32c;', '&#x1f3cc;', '&#x1f3cd;', '&#x1f3ce;', '&#x1f3cf;', '&#x1f3d0;', '&#x1f3d1;', '&#x1f3d2;', '&#x1f3d3;', '&#x1f3d4;', '&#x1f3d5;', '&#x1f64b;', '&#x1f3d6;', '&#x1f3d7;', '&#x1f3d8;', '&#x1f3d9;', '&#x1f3da;', '&#x1f64c;', '&#x1f3db;', '&#x1f3dc;', '&#x1f3dd;', '&#x1f3de;', '&#x1f3df;', '&#x1f3e0;', '&#x1f3e1;', '&#x1f3e2;', '&#x1f3e3;', '&#x1f3e4;', '&#x1f3e5;', '&#x1f3e6;', '&#x1f3e7;', '&#x1f3e8;', '&#x1f3e9;', '&#x1f3ea;', '&#x1f3eb;', '&#x1f64d;', '&#x1f3ec;', '&#x1f3ed;', '&#x1f3ee;', '&#x1f3ef;', '&#x1f3f0;', '&#x1f32d;', '&#x1f3f3;', '&#x1f32e;', '&#x1f32f;', '&#x1f330;', '&#x1f331;', '&#x1f3f4;', '&#x1f3f5;', '&#x1f3f7;', '&#x1f3f8;', '&#x1f3f9;', '&#x1f3fa;', '&#x1f64e;', '&#x1f3fb;', '&#x1f3fc;', '&#x1f3fd;', '&#x1f3fe;', '&#x1f3ff;', '&#x1f64f;', '&#x1f680;', '&#x1f681;', '&#x1f682;', '&#x1f683;', '&#x1f684;', '&#x1f685;', '&#x1f686;', '&#x1f687;', '&#x1f688;', '&#x1f689;', '&#x1f68a;', '&#x1f68b;', '&#x1f68c;', '&#x1f68d;', '&#x1f68e;', '&#x1f68f;', '&#x1f690;', '&#x1f691;', '&#x1f692;', '&#x1f693;', '&#x1f694;', '&#x1f695;', '&#x1f696;', '&#x1f697;', '&#x1f698;', '&#x1f699;', '&#x1f69a;', '&#x1f69b;', '&#x1f69c;', '&#x1f69d;', '&#x1f69e;', '&#x1f69f;', '&#x1f6a0;', '&#x1f6a1;', '&#x1f6a2;', '&#x1f400;', '&#x1f401;', '&#x1f402;', '&#x1f403;', '&#x1f404;', '&#x1f405;', '&#x1f406;', '&#x1f407;', '&#x1f408;', '&#x1f409;', '&#x1f40a;', '&#x1f40b;', '&#x1f40c;', '&#x1f40d;', '&#x1f40e;', '&#x1f40f;', '&#x1f410;', '&#x1f6a3;', '&#x1f6a4;', '&#x1f6a5;', '&#x1f6a6;', '&#x1f6a7;', '&#x1f6a8;', '&#x1f6a9;', '&#x1f6aa;', '&#x1f6ab;', '&#x1f6ac;', '&#x1f6ad;', '&#x1f6ae;', '&#x1f6af;', '&#x1f6b0;', '&#x1f6b1;', '&#x1f6b2;', '&#x1f6b3;', '&#x1f411;', '&#x1f412;', '&#x1f413;', '&#x1f414;', '&#x1f415;', '&#x1f416;', '&#x1f417;', '&#x1f418;', '&#x1f419;', '&#x1f41a;', '&#x1f41b;', '&#x1f41c;', '&#x1f41d;', '&#x1f41e;', '&#x1f41f;', '&#x1f420;', '&#x1f421;', '&#x1f6b4;', '&#x1f422;', '&#x1f423;', '&#x1f424;', '&#x1f425;', '&#x1f426;', '&#x1f427;', '&#x1f428;', '&#x1f429;', '&#x1f42a;', '&#x1f42b;', '&#x1f42c;', '&#x1f42d;', '&#x1f42e;', '&#x1f42f;', '&#x1f430;', '&#x1f431;', '&#x1f432;', '&#x1f6b5;', '&#x1f433;', '&#x1f434;', '&#x1f435;', '&#x1f469;', '&#x1f46a;', '&#x1f46b;', '&#x1f46c;', '&#x1f46d;', '&#x1f436;', '&#x1f437;', '&#x1f438;', '&#x1f439;', '&#x1f43a;', '&#x1f43b;', '&#x1f43c;', '&#x1f43d;', '&#x1f43e;', '&#x1f6b6;', '&#x1f6b7;', '&#x1f6b8;', '&#x1f6b9;', '&#x1f6ba;', '&#x1f6bb;', '&#x1f6bc;', '&#x1f6bd;', '&#x1f6be;', '&#x1f6bf;', '&#x1f43f;', '&#x1f440;', '&#x1f332;', '&#x1f441;', '&#x1f333;', '&#x1f6c0;', '&#x1f6c1;', '&#x1f6c2;', '&#x1f6c3;', '&#x1f6c4;', '&#x1f6c5;', '&#x1f6cb;', '&#x1f334;', '&#x1f335;', '&#x1f336;', '&#x1f46e;', '&#x1f337;', '&#x1f6cc;', '&#x1f6cd;', '&#x1f6ce;', '&#x1f6cf;', '&#x1f6d0;', '&#x1f6d1;', '&#x1f6d2;', '&#x1f6e0;', '&#x1f6e1;', '&#x1f6e2;', '&#x1f6e3;', '&#x1f6e4;', '&#x1f6e5;', '&#x1f6e9;', '&#x1f6eb;', '&#x1f6ec;', '&#x1f6f0;', '&#x1f6f3;', '&#x1f6f4;', '&#x1f6f5;', '&#x1f6f6;', '&#x1f6f7;', '&#x1f6f8;', '&#x1f6f9;', '&#x1f910;', '&#x1f911;', '&#x1f912;', '&#x1f913;', '&#x1f914;', '&#x1f915;', '&#x1f916;', '&#x1f917;', '&#x1f442;', '&#x1f46f;', '&#x1f338;', '&#x1f339;', '&#x1f33a;', '&#x1f918;', '&#x1f33b;', '&#x1f33c;', '&#x1f470;', '&#x1f443;', '&#x1f444;', '&#x1f919;', '&#x1f445;', '&#x1f33d;', '&#x1f33e;', '&#x1f33f;', '&#x1f340;', '&#x1f91a;', '&#x1f341;', '&#x1f446;', '&#x1f342;', '&#x1f343;', '&#x1f344;', '&#x1f91b;', '&#x1f345;', '&#x1f346;', '&#x1f447;', '&#x1f347;', '&#x1f348;', '&#x1f91c;', '&#x1f91d;', '&#x1f471;', '&#x1f349;', '&#x1f34a;', '&#x1f34b;', '&#x1f448;', '&#x1f91e;', '&#x1f34c;', '&#x1f472;', '&#x1f34d;', '&#x1f34e;', '&#x1f34f;', '&#x1f91f;', '&#x1f920;', '&#x1f921;', '&#x1f922;', '&#x1f923;', '&#x1f924;', '&#x1f925;', '&#x1f350;', '&#x1f449;', '&#x1f351;', '&#x1f352;', '&#x1f353;', '&#x1f354;', '&#x1f355;', '&#x1f44a;', '&#x1f356;', '&#x1f357;', '&#x1f358;', '&#x1f359;', '&#x1f35a;', '&#x1f44b;', '&#x1f473;', '&#x1f35b;', '&#x1f35c;', '&#x1f926;', '&#x1f927;', '&#x1f928;', '&#x1f929;', '&#x1f92a;', '&#x1f92b;', '&#x1f92c;', '&#x1f92d;', '&#x1f92e;', '&#x1f92f;', '&#x1f35d;', '&#x1f35e;', '&#x1f35f;', '&#x1f474;', '&#x1f44c;', '&#x1f930;', '&#x1f360;', '&#x1f361;', '&#x1f362;', '&#x1f363;', '&#x1f475;', '&#x1f931;', '&#x1f364;', '&#x1f44d;', '&#x1f365;', '&#x1f366;', '&#x1f367;', '&#x1f932;', '&#x1f476;', '&#x1f368;', '&#x1f369;', '&#x1f44e;', '&#x1f36a;', '&#x1f933;', '&#x1f36b;', '&#x1f36c;', '&#x1f36d;', '&#x1f36e;', '&#x1f44f;', '&#x1f934;', '&#x1f36f;', '&#x1f370;', '&#x1f371;', '&#x1f372;', '&#x1f373;', '&#x1f450;', '&#x1f451;', '&#x1f452;', '&#x1f477;', '&#x1f453;', '&#x1f454;', '&#x1f455;', '&#x1f456;', '&#x1f457;', '&#x1f478;', '&#x1f479;', '&#x1f47a;', '&#x1f935;', '&#x1f47b;', '&#x1f458;', '&#x1f459;', '&#x1f45a;', '&#x1f45b;', '&#x1f936;', '&#x1f45c;', '&#x1f47c;', '&#x1f47d;', '&#x1f47e;', '&#x1f47f;', '&#x1f480;', '&#x1f45d;', '&#x1f45e;', '&#x1f45f;', '&#x1f460;', '&#x1f461;', '&#x1f462;', '&#x1f463;', '&#x1f464;', '&#x1f465;', '&#x1f374;', '&#x1f375;', '&#x1f937;', '&#x1f376;', '&#x1f377;', '&#x1f378;', '&#x1f466;', '&#x1f379;', '&#x1f37a;', '&#x1f481;', '&#x1f37b;', '&#x1f37c;', '&#x1f37d;', '&#x1f467;', '&#x1f37e;', '&#x1f37f;', '&#x1f380;', '&#x1f381;', '&#x1f382;', '&#x1f383;', '&#x1f938;', '&#x1f384;', '&#x1f1f5;', '&#x1f17e;', '&#x1f1f6;', '&#x1f1f2;', '&#x1f17f;', '&#x1f385;', '&#x1f482;', '&#x1f386;', '&#x1f387;', '&#x1f388;', '&#x1f389;', '&#x1f38a;', '&#x1f483;', '&#x1f484;', '&#x1f38b;', '&#x1f38c;', '&#x1f939;', '&#x1f93a;', '&#x1f38d;', '&#x1f38e;', '&#x1f93c;', '&#x1f38f;', '&#x1f485;', '&#x1f390;', '&#x1f391;', '&#x1f392;', '&#x1f393;', '&#x1f396;', '&#x1f397;', '&#x1f399;', '&#x1f39a;', '&#x1f39b;', '&#x1f39e;', '&#x1f39f;', '&#x1f3a0;', '&#x1f3a1;', '&#x1f3a2;', '&#x1f3a3;', '&#x1f93d;', '&#x1f3a4;', '&#x1f3a5;', '&#x1f486;', '&#x1f3a6;', '&#x1f3a7;', '&#x1f3a8;', '&#x1f3a9;', '&#x1f3aa;', '&#x1f3ab;', '&#x1f3ac;', '&#x1f3ad;', '&#x1f3ae;', '&#x1f3af;', '&#x1f3b0;', '&#x1f3b1;', '&#x1f3b2;', '&#x1f3b3;', '&#x1f93e;', '&#x1f940;', '&#x1f941;', '&#x1f942;', '&#x1f943;', '&#x1f944;', '&#x1f945;', '&#x1f947;', '&#x1f948;', '&#x1f949;', '&#x1f94a;', '&#x1f94b;', '&#x1f94c;', '&#x1f94d;', '&#x1f94e;', '&#x1f94f;', '&#x1f950;', '&#x1f951;', '&#x1f952;', '&#x1f953;', '&#x1f954;', '&#x1f955;', '&#x1f956;', '&#x1f957;', '&#x1f958;', '&#x1f959;', '&#x1f95a;', '&#x1f95b;', '&#x1f95c;', '&#x1f95d;', '&#x1f95e;', '&#x1f95f;', '&#x1f960;', '&#x1f961;', '&#x1f962;', '&#x1f963;', '&#x1f964;', '&#x1f965;', '&#x1f966;', '&#x1f967;', '&#x1f968;', '&#x1f969;', '&#x1f96a;', '&#x1f96b;', '&#x1f96c;', '&#x1f96d;', '&#x1f96e;', '&#x1f96f;', '&#x1f970;', '&#x1f973;', '&#x1f974;', '&#x1f975;', '&#x1f976;', '&#x1f97a;', '&#x1f97c;', '&#x1f97d;', '&#x1f97e;', '&#x1f97f;', '&#x1f980;', '&#x1f981;', '&#x1f982;', '&#x1f983;', '&#x1f984;', '&#x1f985;', '&#x1f986;', '&#x1f987;', '&#x1f988;', '&#x1f989;', '&#x1f98a;', '&#x1f98b;', '&#x1f98c;', '&#x1f98d;', '&#x1f98e;', '&#x1f98f;', '&#x1f990;', '&#x1f991;', '&#x1f992;', '&#x1f993;', '&#x1f994;', '&#x1f995;', '&#x1f996;', '&#x1f997;', '&#x1f998;', '&#x1f999;', '&#x1f99a;', '&#x1f99b;', '&#x1f99c;', '&#x1f99d;', '&#x1f99e;', '&#x1f99f;', '&#x1f9a0;', '&#x1f9a1;', '&#x1f9a2;', '&#x1f9b4;', '&#x1f3b4;', '&#x1f3b5;', '&#x1f3b6;', '&#x1f487;', '&#x1f488;', '&#x1f9b5;', '&#x1f489;', '&#x1f48a;', '&#x1f48b;', '&#x1f48c;', '&#x1f48d;', '&#x1f9b6;', '&#x1f9b7;', '&#x1f48e;', '&#x1f48f;', '&#x1f490;', '&#x1f491;', '&#x1f492;', '&#x1f493;', '&#x1f494;', '&#x1f495;', '&#x1f496;', '&#x1f497;', '&#x1f498;', '&#x1f499;', '&#x1f49a;', '&#x1f49b;', '&#x1f49c;', '&#x1f49d;', '&#x1f49e;', '&#x1f9b8;', '&#x1f49f;', '&#x1f4a0;', '&#x1f4a1;', '&#x1f4a2;', '&#x1f4a3;', '&#x1f4a4;', '&#x1f4a5;', '&#x1f4a6;', '&#x1f4a7;', '&#x1f4a8;', '&#x1f4a9;', '&#x1f3b7;', '&#x1f3b8;', '&#x1f3b9;', '&#x1f3ba;', '&#x1f3bb;', '&#x1f4aa;', '&#x1f9b9;', '&#x1f9c0;', '&#x1f9c1;', '&#x1f9c2;', '&#x1f9d0;', '&#x1f4ab;', '&#x1f4ac;', '&#x1f4ad;', '&#x1f4ae;', '&#x1f4af;', '&#x1f9d1;', '&#x1f4b0;', '&#x1f4b1;', '&#x1f4b2;', '&#x1f4b3;', '&#x1f4b4;', '&#x1f9d2;', '&#x1f4b5;', '&#x1f4b6;', '&#x1f4b7;', '&#x1f4b8;', '&#x1f4b9;', '&#x1f9d3;', '&#x1f4ba;', '&#x1f4bb;', '&#x1f4bc;', '&#x1f4bd;', '&#x1f4be;', '&#x1f9d4;', '&#x1f4bf;', '&#x1f4c0;', '&#x1f4c1;', '&#x1f4c2;', '&#x1f4c3;', '&#x1f9d5;', '&#x1f4c4;', '&#x1f4c5;', '&#x1f4c6;', '&#x1f4c7;', '&#x1f4c8;', '&#x1f4c9;', '&#x1f4ca;', '&#x1f4cb;', '&#x1f4cc;', '&#x1f4cd;', '&#x1f4ce;', '&#x1f4cf;', '&#x1f4d0;', '&#x1f4d1;', '&#x1f4d2;', '&#x1f4d3;', '&#x1f4d4;', '&#x1f9d6;', '&#x1f4d5;', '&#x1f4d6;', '&#x1f4d7;', '&#x1f4d8;', '&#x1f4d9;', '&#x1f4da;', '&#x1f4db;', '&#x1f4dc;', '&#x1f4dd;', '&#x1f4de;', '&#x1f4df;', '&#x1f4e0;', '&#x1f4e1;', '&#x1f4e2;', '&#x1f4e3;', '&#x1f4e4;', '&#x1f4e5;', '&#x1f9d7;', '&#x1f4e6;', '&#x1f4e7;', '&#x1f4e8;', '&#x1f4e9;', '&#x1f4ea;', '&#x1f4eb;', '&#x1f4ec;', '&#x1f4ed;', '&#x1f4ee;', '&#x1f4ef;', '&#x1f4f0;', '&#x1f4f1;', '&#x1f4f2;', '&#x1f4f3;', '&#x1f4f4;', '&#x1f4f5;', '&#x1f4f6;', '&#x1f9d8;', '&#x1f4f7;', '&#x1f4f8;', '&#x1f4f9;', '&#x1f4fa;', '&#x1f4fb;', '&#x1f4fc;', '&#x1f4fd;', '&#x1f4ff;', '&#x1f500;', '&#x1f501;', '&#x1f502;', '&#x1f503;', '&#x1f504;', '&#x1f505;', '&#x1f506;', '&#x1f507;', '&#x1f508;', '&#x1f9d9;', '&#x1f509;', '&#x1f50a;', '&#x1f50b;', '&#x1f50c;', '&#x1f50d;', '&#x1f50e;', '&#x1f50f;', '&#x1f510;', '&#x1f511;', '&#x1f512;', '&#x1f513;', '&#x1f514;', '&#x1f515;', '&#x1f516;', '&#x1f517;', '&#x1f518;', '&#x1f519;', '&#x1f9da;', '&#x1f51a;', '&#x1f51b;', '&#x1f51c;', '&#x1f51d;', '&#x1f51e;', '&#x1f51f;', '&#x1f520;', '&#x1f521;', '&#x1f522;', '&#x1f523;', '&#x1f524;', '&#x1f525;', '&#x1f526;', '&#x1f527;', '&#x1f528;', '&#x1f529;', '&#x1f52a;', '&#x1f9db;', '&#x1f52b;', '&#x1f52c;', '&#x1f52d;', '&#x1f52e;', '&#x1f52f;', '&#x1f530;', '&#x1f531;', '&#x1f532;', '&#x1f533;', '&#x1f534;', '&#x1f535;', '&#x1f536;', '&#x1f537;', '&#x1f538;', '&#x1f539;', '&#x1f53a;', '&#x1f53b;', '&#x1f9dc;', '&#x1f53c;', '&#x1f53d;', '&#x1f549;', '&#x1f54a;', '&#x1f54b;', '&#x1f54c;', '&#x1f54d;', '&#x1f54e;', '&#x1f550;', '&#x1f551;', '&#x1f552;', '&#x1f553;', '&#x1f554;', '&#x1f555;', '&#x1f556;', '&#x1f557;', '&#x1f558;', '&#x1f9dd;', '&#x1f559;', '&#x1f55a;', '&#x1f9de;', '&#x1f55b;', '&#x1f55c;', '&#x1f9df;', '&#x1f9e0;', '&#x1f9e1;', '&#x1f9e2;', '&#x1f9e3;', '&#x1f9e4;', '&#x1f9e5;', '&#x1f9e6;', '&#x1f9e7;', '&#x1f9e8;', '&#x1f9e9;', '&#x1f9ea;', '&#x1f9eb;', '&#x1f9ec;', '&#x1f9ed;', '&#x1f9ee;', '&#x1f9ef;', '&#x1f9f0;', '&#x1f9f1;', '&#x1f9f2;', '&#x1f9f3;', '&#x1f9f4;', '&#x1f9f5;', '&#x1f9f6;', '&#x1f9f7;', '&#x1f9f8;', '&#x1f9f9;', '&#x1f9fa;', '&#x1f9fb;', '&#x1f9fc;', '&#x1f9fd;', '&#x1f9fe;', '&#x1f9ff;', '&#x1f55d;', '&#x1f55e;', '&#x1f55f;', '&#x1f560;', '&#x1f561;', '&#x1f562;', '&#x1f563;', '&#x1f564;', '&#x1f565;', '&#x1f566;', '&#x1f567;', '&#x1f56f;', '&#x1f570;', '&#x1f573;', '&#x1f3bc;', '&#x1f3bd;', '&#x1f3be;', '&#x1f3bf;', '&#x1f3c0;', '&#x1f3c1;', '&#x1f1e7;', '&#x1f1ee;', '&#x1f1ea;', '&#x1f1f7;', '&#x1f1f1;', '&#x1f3c2;', '&#x1f18e;', '&#x1f191;', '&#x1f1e8;', '&#x1f1f9;', '&#x1f1ef;', '&#x1f574;', '&#x1f192;', '&#x1f1ec;', '&#x1f193;', '&#x1f1f3;', '&#x1f194;', '&#x1f1f4;', '&#x1f1fa;', '&#x1f1eb;', '&#x1f195;', '&#x1f196;', '&#x1f197;', '&#x1f1ed;', '&#x1f3c3;', '&#x1f198;', '&#x1f1e9;', '&#x1f1fb;', '&#x1f1f0;', '&#x1f575;', '&#x1f576;', '&#x1f577;', '&#x1f578;', '&#x1f579;', '&#x1f199;', '&#x1f1fc;', '&#x1f19a;', '&#x1f1fd;', '&#x1f1f8;', '&#x25ab;', '&#x2626;', '&#x262e;', '&#x262f;', '&#x2638;', '&#x2639;', '&#x263a;', '&#x2640;', '&#x2642;', '&#x2648;', '&#x2649;', '&#x264a;', '&#x264b;', '&#x264c;', '&#x264d;', '&#x264e;', '&#x264f;', '&#x2650;', '&#x2651;', '&#x2652;', '&#x2653;', '&#x265f;', '&#x2660;', '&#x2663;', '&#x2665;', '&#x2666;', '&#x2668;', '&#x267b;', '&#x267e;', '&#x267f;', '&#x2692;', '&#x2693;', '&#x2694;', '&#x2695;', '&#x2696;', '&#x2697;', '&#x2699;', '&#x269b;', '&#x269c;', '&#x26a0;', '&#x26a1;', '&#x26aa;', '&#x26ab;', '&#x26b0;', '&#x26b1;', '&#x26bd;', '&#x26be;', '&#x26c4;', '&#x26c5;', '&#x26c8;', '&#x26ce;', '&#x26cf;', '&#x26d1;', '&#x26d3;', '&#x26d4;', '&#x26e9;', '&#x26ea;', '&#x26f0;', '&#x26f1;', '&#x26f2;', '&#x26f3;', '&#x26f4;', '&#x26f5;', '&#x2623;', '&#x2622;', '&#x2620;', '&#x261d;', '&#x2618;', '&#x26f7;', '&#x26f8;', '&#x2615;', '&#x2614;', '&#x2611;', '&#x260e;', '&#x2604;', '&#x2603;', '&#x2602;', '&#x2601;', '&#x2600;', '&#x25fe;', '&#x25fd;', '&#x25fc;', '&#x25fb;', '&#x25c0;', '&#x25b6;', '&#x262a;', '&#x25aa;', '&#x26f9;', '&#x26fa;', '&#x26fd;', '&#x2702;', '&#x2705;', '&#x2708;', '&#x2709;', '&#x24c2;', '&#x23fa;', '&#x23f9;', '&#x23f8;', '&#x23f3;', '&#x270a;', '&#x23f2;', '&#x23f1;', '&#x23f0;', '&#x23ef;', '&#x23ee;', '&#x270b;', '&#x23ed;', '&#x23ec;', '&#x23eb;', '&#x23ea;', '&#x23e9;', '&#x270c;', '&#x23cf;', '&#x2328;', '&#x231b;', '&#x231a;', '&#x21aa;', '&#x270d;', '&#x270f;', '&#x2712;', '&#x2714;', '&#x2716;', '&#x271d;', '&#x2721;', '&#x2728;', '&#x2733;', '&#x2734;', '&#x2744;', '&#x2747;', '&#x274c;', '&#x274e;', '&#x2753;', '&#x2754;', '&#x2755;', '&#x2757;', '&#x2763;', '&#x2764;', '&#x2795;', '&#x2796;', '&#x2797;', '&#x27a1;', '&#x27b0;', '&#x27bf;', '&#x2934;', '&#x2935;', '&#x21a9;', '&#x2b05;', '&#x2b06;', '&#x2b07;', '&#x2b1b;', '&#x2b1c;', '&#x2b50;', '&#x2b55;', '&#x2199;', '&#x3030;', '&#x303d;', '&#x2198;', '&#x2197;', '&#x3297;', '&#x3299;', '&#x2196;', '&#x2195;', '&#x2194;', '&#x2139;', '&#x2122;', '&#x2049;', '&#x203c;', '&#xe50a;' );
	$partials = array( '&#x1f004;', '&#x1f0cf;', '&#x1f170;', '&#x1f171;', '&#x1f17e;', '&#x1f17f;', '&#x1f18e;', '&#x1f191;', '&#x1f192;', '&#x1f193;', '&#x1f194;', '&#x1f195;', '&#x1f196;', '&#x1f197;', '&#x1f198;', '&#x1f199;', '&#x1f19a;', '&#x1f1e6;', '&#x1f1e8;', '&#x1f1e9;', '&#x1f1ea;', '&#x1f1eb;', '&#x1f1ec;', '&#x1f1ee;', '&#x1f1f1;', '&#x1f1f2;', '&#x1f1f4;', '&#x1f1f6;', '&#x1f1f7;', '&#x1f1f8;', '&#x1f1f9;', '&#x1f1fa;', '&#x1f1fc;', '&#x1f1fd;', '&#x1f1ff;', '&#x1f1e7;', '&#x1f1ed;', '&#x1f1ef;', '&#x1f1f3;', '&#x1f1fb;', '&#x1f1fe;', '&#x1f1f0;', '&#x1f1f5;', '&#x1f201;', '&#x1f202;', '&#x1f21a;', '&#x1f22f;', '&#x1f232;', '&#x1f233;', '&#x1f234;', '&#x1f235;', '&#x1f236;', '&#x1f237;', '&#x1f238;', '&#x1f239;', '&#x1f23a;', '&#x1f250;', '&#x1f251;', '&#x1f300;', '&#x1f301;', '&#x1f302;', '&#x1f303;', '&#x1f304;', '&#x1f305;', '&#x1f306;', '&#x1f307;', '&#x1f308;', '&#x1f309;', '&#x1f30a;', '&#x1f30b;', '&#x1f30c;', '&#x1f30d;', '&#x1f30e;', '&#x1f30f;', '&#x1f310;', '&#x1f311;', '&#x1f312;', '&#x1f313;', '&#x1f314;', '&#x1f315;', '&#x1f316;', '&#x1f317;', '&#x1f318;', '&#x1f319;', '&#x1f31a;', '&#x1f31b;', '&#x1f31c;', '&#x1f31d;', '&#x1f31e;', '&#x1f31f;', '&#x1f320;', '&#x1f321;', '&#x1f324;', '&#x1f325;', '&#x1f326;', '&#x1f327;', '&#x1f328;', '&#x1f329;', '&#x1f32a;', '&#x1f32b;', '&#x1f32c;', '&#x1f32d;', '&#x1f32e;', '&#x1f32f;', '&#x1f330;', '&#x1f331;', '&#x1f332;', '&#x1f333;', '&#x1f334;', '&#x1f335;', '&#x1f336;', '&#x1f337;', '&#x1f338;', '&#x1f339;', '&#x1f33a;', '&#x1f33b;', '&#x1f33c;', '&#x1f33d;', '&#x1f33e;', '&#x1f33f;', '&#x1f340;', '&#x1f341;', '&#x1f342;', '&#x1f343;', '&#x1f344;', '&#x1f345;', '&#x1f346;', '&#x1f347;', '&#x1f348;', '&#x1f349;', '&#x1f34a;', '&#x1f34b;', '&#x1f34c;', '&#x1f34d;', '&#x1f34e;', '&#x1f34f;', '&#x1f350;', '&#x1f351;', '&#x1f352;', '&#x1f353;', '&#x1f354;', '&#x1f355;', '&#x1f356;', '&#x1f357;', '&#x1f358;', '&#x1f359;', '&#x1f35a;', '&#x1f35b;', '&#x1f35c;', '&#x1f35d;', '&#x1f35e;', '&#x1f35f;', '&#x1f360;', '&#x1f361;', '&#x1f362;', '&#x1f363;', '&#x1f364;', '&#x1f365;', '&#x1f366;', '&#x1f367;', '&#x1f368;', '&#x1f369;', '&#x1f36a;', '&#x1f36b;', '&#x1f36c;', '&#x1f36d;', '&#x1f36e;', '&#x1f36f;', '&#x1f370;', '&#x1f371;', '&#x1f372;', '&#x1f373;', '&#x1f374;', '&#x1f375;', '&#x1f376;', '&#x1f377;', '&#x1f378;', '&#x1f379;', '&#x1f37a;', '&#x1f37b;', '&#x1f37c;', '&#x1f37d;', '&#x1f37e;', '&#x1f37f;', '&#x1f380;', '&#x1f381;', '&#x1f382;', '&#x1f383;', '&#x1f384;', '&#x1f385;', '&#x1f3fb;', '&#x1f3fc;', '&#x1f3fd;', '&#x1f3fe;', '&#x1f3ff;', '&#x1f386;', '&#x1f387;', '&#x1f388;', '&#x1f389;', '&#x1f38a;', '&#x1f38b;', '&#x1f38c;', '&#x1f38d;', '&#x1f38e;', '&#x1f38f;', '&#x1f390;', '&#x1f391;', '&#x1f392;', '&#x1f393;', '&#x1f396;', '&#x1f397;', '&#x1f399;', '&#x1f39a;', '&#x1f39b;', '&#x1f39e;', '&#x1f39f;', '&#x1f3a0;', '&#x1f3a1;', '&#x1f3a2;', '&#x1f3a3;', '&#x1f3a4;', '&#x1f3a5;', '&#x1f3a6;', '&#x1f3a7;', '&#x1f3a8;', '&#x1f3a9;', '&#x1f3aa;', '&#x1f3ab;', '&#x1f3ac;', '&#x1f3ad;', '&#x1f3ae;', '&#x1f3af;', '&#x1f3b0;', '&#x1f3b1;', '&#x1f3b2;', '&#x1f3b3;', '&#x1f3b4;', '&#x1f3b5;', '&#x1f3b6;', '&#x1f3b7;', '&#x1f3b8;', '&#x1f3b9;', '&#x1f3ba;', '&#x1f3bb;', '&#x1f3bc;', '&#x1f3bd;', '&#x1f3be;', '&#x1f3bf;', '&#x1f3c0;', '&#x1f3c1;', '&#x1f3c2;', '&#x1f3c3;', '&#x200d;', '&#x2640;', '&#xfe0f;', '&#x2642;', '&#x1f3c4;', '&#x1f3c5;', '&#x1f3c6;', '&#x1f3c7;', '&#x1f3c8;', '&#x1f3c9;', '&#x1f3ca;', '&#x1f3cb;', '&#x1f3cc;', '&#x1f3cd;', '&#x1f3ce;', '&#x1f3cf;', '&#x1f3d0;', '&#x1f3d1;', '&#x1f3d2;', '&#x1f3d3;', '&#x1f3d4;', '&#x1f3d5;', '&#x1f3d6;', '&#x1f3d7;', '&#x1f3d8;', '&#x1f3d9;', '&#x1f3da;', '&#x1f3db;', '&#x1f3dc;', '&#x1f3dd;', '&#x1f3de;', '&#x1f3df;', '&#x1f3e0;', '&#x1f3e1;', '&#x1f3e2;', '&#x1f3e3;', '&#x1f3e4;', '&#x1f3e5;', '&#x1f3e6;', '&#x1f3e7;', '&#x1f3e8;', '&#x1f3e9;', '&#x1f3ea;', '&#x1f3eb;', '&#x1f3ec;', '&#x1f3ed;', '&#x1f3ee;', '&#x1f3ef;', '&#x1f3f0;', '&#x1f3f3;', '&#x1f3f4;', '&#x2620;', '&#xe0067;', '&#xe0062;', '&#xe0065;', '&#xe006e;', '&#xe007f;', '&#xe0073;', '&#xe0063;', '&#xe0074;', '&#xe0077;', '&#xe006c;', '&#x1f3f5;', '&#x1f3f7;', '&#x1f3f8;', '&#x1f3f9;', '&#x1f3fa;', '&#x1f400;', '&#x1f401;', '&#x1f402;', '&#x1f403;', '&#x1f404;', '&#x1f405;', '&#x1f406;', '&#x1f407;', '&#x1f408;', '&#x1f409;', '&#x1f40a;', '&#x1f40b;', '&#x1f40c;', '&#x1f40d;', '&#x1f40e;', '&#x1f40f;', '&#x1f410;', '&#x1f411;', '&#x1f412;', '&#x1f413;', '&#x1f414;', '&#x1f415;', '&#x1f416;', '&#x1f417;', '&#x1f418;', '&#x1f419;', '&#x1f41a;', '&#x1f41b;', '&#x1f41c;', '&#x1f41d;', '&#x1f41e;', '&#x1f41f;', '&#x1f420;', '&#x1f421;', '&#x1f422;', '&#x1f423;', '&#x1f424;', '&#x1f425;', '&#x1f426;', '&#x1f427;', '&#x1f428;', '&#x1f429;', '&#x1f42a;', '&#x1f42b;', '&#x1f42c;', '&#x1f42d;', '&#x1f42e;', '&#x1f42f;', '&#x1f430;', '&#x1f431;', '&#x1f432;', '&#x1f433;', '&#x1f434;', '&#x1f435;', '&#x1f436;', '&#x1f437;', '&#x1f438;', '&#x1f439;', '&#x1f43a;', '&#x1f43b;', '&#x1f43c;', '&#x1f43d;', '&#x1f43e;', '&#x1f43f;', '&#x1f440;', '&#x1f441;', '&#x1f5e8;', '&#x1f442;', '&#x1f443;', '&#x1f444;', '&#x1f445;', '&#x1f446;', '&#x1f447;', '&#x1f448;', '&#x1f449;', '&#x1f44a;', '&#x1f44b;', '&#x1f44c;', '&#x1f44d;', '&#x1f44e;', '&#x1f44f;', '&#x1f450;', '&#x1f451;', '&#x1f452;', '&#x1f453;', '&#x1f454;', '&#x1f455;', '&#x1f456;', '&#x1f457;', '&#x1f458;', '&#x1f459;', '&#x1f45a;', '&#x1f45b;', '&#x1f45c;', '&#x1f45d;', '&#x1f45e;', '&#x1f45f;', '&#x1f460;', '&#x1f461;', '&#x1f462;', '&#x1f463;', '&#x1f464;', '&#x1f465;', '&#x1f466;', '&#x1f467;', '&#x1f468;', '&#x1f4bb;', '&#x1f4bc;', '&#x1f527;', '&#x1f52c;', '&#x1f680;', '&#x1f692;', '&#x1f9b0;', '&#x1f9b1;', '&#x1f9b2;', '&#x1f9b3;', '&#x2695;', '&#x2696;', '&#x2708;', '&#x1f469;', '&#x2764;', '&#x1f48b;', '&#x1f46a;', '&#x1f46b;', '&#x1f46c;', '&#x1f46d;', '&#x1f46e;', '&#x1f46f;', '&#x1f470;', '&#x1f471;', '&#x1f472;', '&#x1f473;', '&#x1f474;', '&#x1f475;', '&#x1f476;', '&#x1f477;', '&#x1f478;', '&#x1f479;', '&#x1f47a;', '&#x1f47b;', '&#x1f47c;', '&#x1f47d;', '&#x1f47e;', '&#x1f47f;', '&#x1f480;', '&#x1f481;', '&#x1f482;', '&#x1f483;', '&#x1f484;', '&#x1f485;', '&#x1f486;', '&#x1f487;', '&#x1f488;', '&#x1f489;', '&#x1f48a;', '&#x1f48c;', '&#x1f48d;', '&#x1f48e;', '&#x1f48f;', '&#x1f490;', '&#x1f491;', '&#x1f492;', '&#x1f493;', '&#x1f494;', '&#x1f495;', '&#x1f496;', '&#x1f497;', '&#x1f498;', '&#x1f499;', '&#x1f49a;', '&#x1f49b;', '&#x1f49c;', '&#x1f49d;', '&#x1f49e;', '&#x1f49f;', '&#x1f4a0;', '&#x1f4a1;', '&#x1f4a2;', '&#x1f4a3;', '&#x1f4a4;', '&#x1f4a5;', '&#x1f4a6;', '&#x1f4a7;', '&#x1f4a8;', '&#x1f4a9;', '&#x1f4aa;', '&#x1f4ab;', '&#x1f4ac;', '&#x1f4ad;', '&#x1f4ae;', '&#x1f4af;', '&#x1f4b0;', '&#x1f4b1;', '&#x1f4b2;', '&#x1f4b3;', '&#x1f4b4;', '&#x1f4b5;', '&#x1f4b6;', '&#x1f4b7;', '&#x1f4b8;', '&#x1f4b9;', '&#x1f4ba;', '&#x1f4bd;', '&#x1f4be;', '&#x1f4bf;', '&#x1f4c0;', '&#x1f4c1;', '&#x1f4c2;', '&#x1f4c3;', '&#x1f4c4;', '&#x1f4c5;', '&#x1f4c6;', '&#x1f4c7;', '&#x1f4c8;', '&#x1f4c9;', '&#x1f4ca;', '&#x1f4cb;', '&#x1f4cc;', '&#x1f4cd;', '&#x1f4ce;', '&#x1f4cf;', '&#x1f4d0;', '&#x1f4d1;', '&#x1f4d2;', '&#x1f4d3;', '&#x1f4d4;', '&#x1f4d5;', '&#x1f4d6;', '&#x1f4d7;', '&#x1f4d8;', '&#x1f4d9;', '&#x1f4da;', '&#x1f4db;', '&#x1f4dc;', '&#x1f4dd;', '&#x1f4de;', '&#x1f4df;', '&#x1f4e0;', '&#x1f4e1;', '&#x1f4e2;', '&#x1f4e3;', '&#x1f4e4;', '&#x1f4e5;', '&#x1f4e6;', '&#x1f4e7;', '&#x1f4e8;', '&#x1f4e9;', '&#x1f4ea;', '&#x1f4eb;', '&#x1f4ec;', '&#x1f4ed;', '&#x1f4ee;', '&#x1f4ef;', '&#x1f4f0;', '&#x1f4f1;', '&#x1f4f2;', '&#x1f4f3;', '&#x1f4f4;', '&#x1f4f5;', '&#x1f4f6;', '&#x1f4f7;', '&#x1f4f8;', '&#x1f4f9;', '&#x1f4fa;', '&#x1f4fb;', '&#x1f4fc;', '&#x1f4fd;', '&#x1f4ff;', '&#x1f500;', '&#x1f501;', '&#x1f502;', '&#x1f503;', '&#x1f504;', '&#x1f505;', '&#x1f506;', '&#x1f507;', '&#x1f508;', '&#x1f509;', '&#x1f50a;', '&#x1f50b;', '&#x1f50c;', '&#x1f50d;', '&#x1f50e;', '&#x1f50f;', '&#x1f510;', '&#x1f511;', '&#x1f512;', '&#x1f513;', '&#x1f514;', '&#x1f515;', '&#x1f516;', '&#x1f517;', '&#x1f518;', '&#x1f519;', '&#x1f51a;', '&#x1f51b;', '&#x1f51c;', '&#x1f51d;', '&#x1f51e;', '&#x1f51f;', '&#x1f520;', '&#x1f521;', '&#x1f522;', '&#x1f523;', '&#x1f524;', '&#x1f525;', '&#x1f526;', '&#x1f528;', '&#x1f529;', '&#x1f52a;', '&#x1f52b;', '&#x1f52d;', '&#x1f52e;', '&#x1f52f;', '&#x1f530;', '&#x1f531;', '&#x1f532;', '&#x1f533;', '&#x1f534;', '&#x1f535;', '&#x1f536;', '&#x1f537;', '&#x1f538;', '&#x1f539;', '&#x1f53a;', '&#x1f53b;', '&#x1f53c;', '&#x1f53d;', '&#x1f549;', '&#x1f54a;', '&#x1f54b;', '&#x1f54c;', '&#x1f54d;', '&#x1f54e;', '&#x1f550;', '&#x1f551;', '&#x1f552;', '&#x1f553;', '&#x1f554;', '&#x1f555;', '&#x1f556;', '&#x1f557;', '&#x1f558;', '&#x1f559;', '&#x1f55a;', '&#x1f55b;', '&#x1f55c;', '&#x1f55d;', '&#x1f55e;', '&#x1f55f;', '&#x1f560;', '&#x1f561;', '&#x1f562;', '&#x1f563;', '&#x1f564;', '&#x1f565;', '&#x1f566;', '&#x1f567;', '&#x1f56f;', '&#x1f570;', '&#x1f573;', '&#x1f574;', '&#x1f575;', '&#x1f576;', '&#x1f577;', '&#x1f578;', '&#x1f579;', '&#x1f57a;', '&#x1f587;', '&#x1f58a;', '&#x1f58b;', '&#x1f58c;', '&#x1f58d;', '&#x1f590;', '&#x1f595;', '&#x1f596;', '&#x1f5a4;', '&#x1f5a5;', '&#x1f5a8;', '&#x1f5b1;', '&#x1f5b2;', '&#x1f5bc;', '&#x1f5c2;', '&#x1f5c3;', '&#x1f5c4;', '&#x1f5d1;', '&#x1f5d2;', '&#x1f5d3;', '&#x1f5dc;', '&#x1f5dd;', '&#x1f5de;', '&#x1f5e1;', '&#x1f5e3;', '&#x1f5ef;', '&#x1f5f3;', '&#x1f5fa;', '&#x1f5fb;', '&#x1f5fc;', '&#x1f5fd;', '&#x1f5fe;', '&#x1f5ff;', '&#x1f600;', '&#x1f601;', '&#x1f602;', '&#x1f603;', '&#x1f604;', '&#x1f605;', '&#x1f606;', '&#x1f607;', '&#x1f608;', '&#x1f609;', '&#x1f60a;', '&#x1f60b;', '&#x1f60c;', '&#x1f60d;', '&#x1f60e;', '&#x1f60f;', '&#x1f610;', '&#x1f611;', '&#x1f612;', '&#x1f613;', '&#x1f614;', '&#x1f615;', '&#x1f616;', '&#x1f617;', '&#x1f618;', '&#x1f619;', '&#x1f61a;', '&#x1f61b;', '&#x1f61c;', '&#x1f61d;', '&#x1f61e;', '&#x1f61f;', '&#x1f620;', '&#x1f621;', '&#x1f622;', '&#x1f623;', '&#x1f624;', '&#x1f625;', '&#x1f626;', '&#x1f627;', '&#x1f628;', '&#x1f629;', '&#x1f62a;', '&#x1f62b;', '&#x1f62c;', '&#x1f62d;', '&#x1f62e;', '&#x1f62f;', '&#x1f630;', '&#x1f631;', '&#x1f632;', '&#x1f633;', '&#x1f634;', '&#x1f635;', '&#x1f636;', '&#x1f637;', '&#x1f638;', '&#x1f639;', '&#x1f63a;', '&#x1f63b;', '&#x1f63c;', '&#x1f63d;', '&#x1f63e;', '&#x1f63f;', '&#x1f640;', '&#x1f641;', '&#x1f642;', '&#x1f643;', '&#x1f644;', '&#x1f645;', '&#x1f646;', '&#x1f647;', '&#x1f648;', '&#x1f649;', '&#x1f64a;', '&#x1f64b;', '&#x1f64c;', '&#x1f64d;', '&#x1f64e;', '&#x1f64f;', '&#x1f681;', '&#x1f682;', '&#x1f683;', '&#x1f684;', '&#x1f685;', '&#x1f686;', '&#x1f687;', '&#x1f688;', '&#x1f689;', '&#x1f68a;', '&#x1f68b;', '&#x1f68c;', '&#x1f68d;', '&#x1f68e;', '&#x1f68f;', '&#x1f690;', '&#x1f691;', '&#x1f693;', '&#x1f694;', '&#x1f695;', '&#x1f696;', '&#x1f697;', '&#x1f698;', '&#x1f699;', '&#x1f69a;', '&#x1f69b;', '&#x1f69c;', '&#x1f69d;', '&#x1f69e;', '&#x1f69f;', '&#x1f6a0;', '&#x1f6a1;', '&#x1f6a2;', '&#x1f6a3;', '&#x1f6a4;', '&#x1f6a5;', '&#x1f6a6;', '&#x1f6a7;', '&#x1f6a8;', '&#x1f6a9;', '&#x1f6aa;', '&#x1f6ab;', '&#x1f6ac;', '&#x1f6ad;', '&#x1f6ae;', '&#x1f6af;', '&#x1f6b0;', '&#x1f6b1;', '&#x1f6b2;', '&#x1f6b3;', '&#x1f6b4;', '&#x1f6b5;', '&#x1f6b6;', '&#x1f6b7;', '&#x1f6b8;', '&#x1f6b9;', '&#x1f6ba;', '&#x1f6bb;', '&#x1f6bc;', '&#x1f6bd;', '&#x1f6be;', '&#x1f6bf;', '&#x1f6c0;', '&#x1f6c1;', '&#x1f6c2;', '&#x1f6c3;', '&#x1f6c4;', '&#x1f6c5;', '&#x1f6cb;', '&#x1f6cc;', '&#x1f6cd;', '&#x1f6ce;', '&#x1f6cf;', '&#x1f6d0;', '&#x1f6d1;', '&#x1f6d2;', '&#x1f6e0;', '&#x1f6e1;', '&#x1f6e2;', '&#x1f6e3;', '&#x1f6e4;', '&#x1f6e5;', '&#x1f6e9;', '&#x1f6eb;', '&#x1f6ec;', '&#x1f6f0;', '&#x1f6f3;', '&#x1f6f4;', '&#x1f6f5;', '&#x1f6f6;', '&#x1f6f7;', '&#x1f6f8;', '&#x1f6f9;', '&#x1f910;', '&#x1f911;', '&#x1f912;', '&#x1f913;', '&#x1f914;', '&#x1f915;', '&#x1f916;', '&#x1f917;', '&#x1f918;', '&#x1f919;', '&#x1f91a;', '&#x1f91b;', '&#x1f91c;', '&#x1f91d;', '&#x1f91e;', '&#x1f91f;', '&#x1f920;', '&#x1f921;', '&#x1f922;', '&#x1f923;', '&#x1f924;', '&#x1f925;', '&#x1f926;', '&#x1f927;', '&#x1f928;', '&#x1f929;', '&#x1f92a;', '&#x1f92b;', '&#x1f92c;', '&#x1f92d;', '&#x1f92e;', '&#x1f92f;', '&#x1f930;', '&#x1f931;', '&#x1f932;', '&#x1f933;', '&#x1f934;', '&#x1f935;', '&#x1f936;', '&#x1f937;', '&#x1f938;', '&#x1f939;', '&#x1f93a;', '&#x1f93c;', '&#x1f93d;', '&#x1f93e;', '&#x1f940;', '&#x1f941;', '&#x1f942;', '&#x1f943;', '&#x1f944;', '&#x1f945;', '&#x1f947;', '&#x1f948;', '&#x1f949;', '&#x1f94a;', '&#x1f94b;', '&#x1f94c;', '&#x1f94d;', '&#x1f94e;', '&#x1f94f;', '&#x1f950;', '&#x1f951;', '&#x1f952;', '&#x1f953;', '&#x1f954;', '&#x1f955;', '&#x1f956;', '&#x1f957;', '&#x1f958;', '&#x1f959;', '&#x1f95a;', '&#x1f95b;', '&#x1f95c;', '&#x1f95d;', '&#x1f95e;', '&#x1f95f;', '&#x1f960;', '&#x1f961;', '&#x1f962;', '&#x1f963;', '&#x1f964;', '&#x1f965;', '&#x1f966;', '&#x1f967;', '&#x1f968;', '&#x1f969;', '&#x1f96a;', '&#x1f96b;', '&#x1f96c;', '&#x1f96d;', '&#x1f96e;', '&#x1f96f;', '&#x1f970;', '&#x1f973;', '&#x1f974;', '&#x1f975;', '&#x1f976;', '&#x1f97a;', '&#x1f97c;', '&#x1f97d;', '&#x1f97e;', '&#x1f97f;', '&#x1f980;', '&#x1f981;', '&#x1f982;', '&#x1f983;', '&#x1f984;', '&#x1f985;', '&#x1f986;', '&#x1f987;', '&#x1f988;', '&#x1f989;', '&#x1f98a;', '&#x1f98b;', '&#x1f98c;', '&#x1f98d;', '&#x1f98e;', '&#x1f98f;', '&#x1f990;', '&#x1f991;', '&#x1f992;', '&#x1f993;', '&#x1f994;', '&#x1f995;', '&#x1f996;', '&#x1f997;', '&#x1f998;', '&#x1f999;', '&#x1f99a;', '&#x1f99b;', '&#x1f99c;', '&#x1f99d;', '&#x1f99e;', '&#x1f99f;', '&#x1f9a0;', '&#x1f9a1;', '&#x1f9a2;', '&#x1f9b4;', '&#x1f9b5;', '&#x1f9b6;', '&#x1f9b7;', '&#x1f9b8;', '&#x1f9b9;', '&#x1f9c0;', '&#x1f9c1;', '&#x1f9c2;', '&#x1f9d0;', '&#x1f9d1;', '&#x1f9d2;', '&#x1f9d3;', '&#x1f9d4;', '&#x1f9d5;', '&#x1f9d6;', '&#x1f9d7;', '&#x1f9d8;', '&#x1f9d9;', '&#x1f9da;', '&#x1f9db;', '&#x1f9dc;', '&#x1f9dd;', '&#x1f9de;', '&#x1f9df;', '&#x1f9e0;', '&#x1f9e1;', '&#x1f9e2;', '&#x1f9e3;', '&#x1f9e4;', '&#x1f9e5;', '&#x1f9e6;', '&#x1f9e7;', '&#x1f9e8;', '&#x1f9e9;', '&#x1f9ea;', '&#x1f9eb;', '&#x1f9ec;', '&#x1f9ed;', '&#x1f9ee;', '&#x1f9ef;', '&#x1f9f0;', '&#x1f9f1;', '&#x1f9f2;', '&#x1f9f3;', '&#x1f9f4;', '&#x1f9f5;', '&#x1f9f6;', '&#x1f9f7;', '&#x1f9f8;', '&#x1f9f9;', '&#x1f9fa;', '&#x1f9fb;', '&#x1f9fc;', '&#x1f9fd;', '&#x1f9fe;', '&#x1f9ff;', '&#x203c;', '&#x2049;', '&#x2122;', '&#x2139;', '&#x2194;', '&#x2195;', '&#x2196;', '&#x2197;', '&#x2198;', '&#x2199;', '&#x21a9;', '&#x21aa;', '&#x20e3;', '&#x231a;', '&#x231b;', '&#x2328;', '&#x23cf;', '&#x23e9;', '&#x23ea;', '&#x23eb;', '&#x23ec;', '&#x23ed;', '&#x23ee;', '&#x23ef;', '&#x23f0;', '&#x23f1;', '&#x23f2;', '&#x23f3;', '&#x23f8;', '&#x23f9;', '&#x23fa;', '&#x24c2;', '&#x25aa;', '&#x25ab;', '&#x25b6;', '&#x25c0;', '&#x25fb;', '&#x25fc;', '&#x25fd;', '&#x25fe;', '&#x2600;', '&#x2601;', '&#x2602;', '&#x2603;', '&#x2604;', '&#x260e;', '&#x2611;', '&#x2614;', '&#x2615;', '&#x2618;', '&#x261d;', '&#x2622;', '&#x2623;', '&#x2626;', '&#x262a;', '&#x262e;', '&#x262f;', '&#x2638;', '&#x2639;', '&#x263a;', '&#x2648;', '&#x2649;', '&#x264a;', '&#x264b;', '&#x264c;', '&#x264d;', '&#x264e;', '&#x264f;', '&#x2650;', '&#x2651;', '&#x2652;', '&#x2653;', '&#x265f;', '&#x2660;', '&#x2663;', '&#x2665;', '&#x2666;', '&#x2668;', '&#x267b;', '&#x267e;', '&#x267f;', '&#x2692;', '&#x2693;', '&#x2694;', '&#x2697;', '&#x2699;', '&#x269b;', '&#x269c;', '&#x26a0;', '&#x26a1;', '&#x26aa;', '&#x26ab;', '&#x26b0;', '&#x26b1;', '&#x26bd;', '&#x26be;', '&#x26c4;', '&#x26c5;', '&#x26c8;', '&#x26ce;', '&#x26cf;', '&#x26d1;', '&#x26d3;', '&#x26d4;', '&#x26e9;', '&#x26ea;', '&#x26f0;', '&#x26f1;', '&#x26f2;', '&#x26f3;', '&#x26f4;', '&#x26f5;', '&#x26f7;', '&#x26f8;', '&#x26f9;', '&#x26fa;', '&#x26fd;', '&#x2702;', '&#x2705;', '&#x2709;', '&#x270a;', '&#x270b;', '&#x270c;', '&#x270d;', '&#x270f;', '&#x2712;', '&#x2714;', '&#x2716;', '&#x271d;', '&#x2721;', '&#x2728;', '&#x2733;', '&#x2734;', '&#x2744;', '&#x2747;', '&#x274c;', '&#x274e;', '&#x2753;', '&#x2754;', '&#x2755;', '&#x2757;', '&#x2763;', '&#x2795;', '&#x2796;', '&#x2797;', '&#x27a1;', '&#x27b0;', '&#x27bf;', '&#x2934;', '&#x2935;', '&#x2b05;', '&#x2b06;', '&#x2b07;', '&#x2b1b;', '&#x2b1c;', '&#x2b50;', '&#x2b55;', '&#x3030;', '&#x303d;', '&#x3297;', '&#x3299;', '&#xe50a;' );
	// END: emoji arrays

	return 'entities' === $type
		? $entities
		: $partials;
}
