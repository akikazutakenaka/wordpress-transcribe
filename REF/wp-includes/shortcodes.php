<?php
/**
 * WordPress API for creating bbcode-like tags or what WordPress calls "shortcodes".
 * The tag and attribute parsing or regular expression code is based on the Textpattern tag parser.
 *
 * A few examples are below:
 *
 * [shortcode /]
 * [shortcode foo="bar" baz="bing" /]
 * [shortcode foo="bar"]content[/shortcode]
 *
 * Shortcode tags support attributes and enclosed content, but does not entirely support inline shortcodes in other shortcodes.
 * You will have to call the shortcode parser in your function to account for that.
 *
 * {@internal Please be aware that the above note was made during the beta of WordPress 2.6 and in the future may not be accurate. Please update the note when it is no longer the case.}
 *
 * To apply shortcode tags to content:
 *
 *     $out = do_shortcode( $content );
 *
 * @link       https://codex.wordpress.org/Shortcode_API
 * @package    WordPress
 * @subpackage Shortcodes
 * @since      2.5.0
 */

/**
 * Container for storing shortcode tags and their hook to call for the shortcode.
 *
 * @since  2.5.0
 * @global array $shortcode_tags
 *
 * @var array
 */
$shortcode_tags = array();

/**
 * Adds a new shortcode.
 *
 * Care should be taken through prefixing or other means to ensure that the shortcode tag being added is unique and will not conflict with other, already-added shortcode tags.
 * In the event of a duplicated tag, the tag loaded last will take precedence.
 *
 * @since  2.5.0
 * @global array $shortcode_tags
 *
 * @param string   $tag      Shortcode tag to be searched in post content.
 * @param callable $callback The callback function to run when the shortcode is found.
 *                           Every shortcode callback is passed three parameters by default, including an array of attributes (`$atts`), the shortcode content or null if not set (`$content`), and finally the shortcode tag itself (`$shortcode_tag`), in that order.
 */
function add_shortcode( $tag, $callback )
{
	global $shortcode_tags;

	if ( '' == trim( $tag ) ) {
		$message = __( 'Invalid shortcode name: Empty name given.' );
		_doing_it_wrong( __FUNCTION__, $message, '4.4.0' );
		return;
	}

	if ( 0 !== preg_match( '@[<>&/\[\]\x00-\x20=]@', $tag ) ) {
		$message = sprintf( __( 'Invalid shortcode name: %1$s. Do not use spaces or reserved characters: %2$s' ), $tag, '& / < > [ ] =' );
		_doing_it_wrong( __FUNCTION__, $message, '4.4.0' );
		return;
	}

	$shortcode_tags[ $tag ] = $callback;
}

/**
 * Retrieve the shortcode regular expression for searching.
 *
 * The regular expression combines the shortcode tags in the regular expression in a regex class.
 *
 * The regular expression contains 6 different sub matches to help with parsing.
 *
 * 1 - An extra [ to allow for escaping shortcodes with double [[]].
 * 2 - The shortcode name.
 * 3 - The shortcode argument list.
 * 4 - The self closing /.
 * 5 - The content of a shortcode when it wraps some content.
 * 6 - An extra ] to allow for escaping shortcodes with double [[]].
 *
 * @since  2.5.0
 * @since  4.4.0 Added the `$tagnames` parameter.
 * @global array $shortcode_tags
 *
 * @param  array  $tagnames Optional.
 *                          List of shortcodes to find.
 *                          Defaults to all registered shortcodes.
 * @return string The shortcode search regular expression.
 */
function get_shortcode_regex( $tagnames = NULL )
{
	global $shortcode_tags;

	if ( empty( $tagnames ) ) {
		$tagnames = array_keys( $shortcode_tags );
	}

	$tagregexp = join( '|', array_map( 'preg_quote', $tagnames ) );

	/**
	 * WARNING!
	 * Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag().
	 * Also, see shortcode_unautop() and shortcode.js.
	 */
	return '\\['                             // Opening bracket.
		. '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]].
		. "($tagregexp)"                     // 2: Shortcode name.
		. '(?![\\w-])'                       // Not followed by word character or hyphen.
		. '('                                // 3: Unroll the loop: Inside the opening shortcode tag.
			. '[^\\]\\/]*'                   // Not a closing bracket or forward slash.
			. '(?:'
				. '\\/(?!\\])'               // A forward slash not followed by a closing bracket.
				. '[^\\]\\/]*'               // Not a closing bracket or forward slash.
			. ')*?'
		. ')'
		. '(?:'
			. '(\\/)'                        // 4. Self closing tag ...
			. '\\]'                          // ... and closing bracket.
		. '|'
			. '\\]'                          // Closing bracket.
			. '(?:'
				. '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags.
					. '[^\\[]*+'             // Not an opening bracket.
					. '(?:'
						. '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag.
						. '[^\\[]*+'         // Not an opening bracket.
					. ')*+'
				. ')'
				. '\\[\\/\\2\\]'             // Closing shortcode tag.
			. ')?'
		. ')'
		. '(\\]?)';                          // 6: Optional second closing bracket for escaping shortcodes: [[tag]].
}

/**
 * Search only inside HTML elements for shortcodes and process them.
 *
 * Any [ or ] characters remaining inside elements will be HTML encoded to prevent interference with shortcodes that are outside the elements.
 * Assumes $content processed by KSES already.
 * Users with unfiltered_html capability may get unexpected output if angle braces are nested in tags.
 *
 * @since 4.2.3
 *
 * @param  string $content     Content to search for shortcodes.
 * @param  bool   $ignore_html When true, all square braces inside elements will be encoded.
 * @param  array  $tagnames    List of shortcodes to find.
 * @return string Content with shortcodes filtered out.
 */
function do_shortcodes_in_html_tags( $content, $ignore_html, $tagnames )
{
	// Normalize entities in unfiltered HTML before adding placeholders.
	$trans = array(
		'&#91;' => '&#091;',
		'&#93;' => '&#093;'
	);
	$content = strtr( $content, $trans );
	$trans = array(
		'[' => '&#91;',
		']' => '&#93;'
	);
	$pattern = get_shortcode_regex( $tagnames );
	$textarr = wp_html_split( $content );

	foreach ( $textarr as &$element ) {
		if ( '' == $element || '<' !== $element[0] ) {
			continue;
		}

		$noopen = FALSE === strpos( $element, '[' );
		$noclose = FALSE === strpos( $element, ']' );

		if ( $noopen || $noclose ) {
			// This element does not contain shortcodes.
			if ( $noopen xor $noclose ) {
				// Need to encode stray [ or ] chars.
				$element = strtr( $element, $trans );
			}

			continue;
		}

		if ( $ignore_html || '<!--' === substr( $element, 0, 4 ) || '<![CDATA[' === substr( $element, 0, 9 ) ) {
			// Encode all [ and ] chars.
			$element = strtr( $element, $trans );
			continue;
		}

		$attributes = wp_kses_attr_parse( $element );
/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/formatting.php: wp_trim_excerpt( [string $text = ''] )
 * <-......: wp-includes/shortcodes.php: strip_shortcodes( string $content )
 * @NOW 007: wp-includes/shortcodes.php: do_shortcodes_in_html_tags( string $content, bool $ignore_html, array $tagnames )
 */
	}
}

/**
 * Combine user attributes with known attributes and fill in defaults when needed.
 *
 * The pairs should be considered to be all of the attributes which are supported by the caller and given as a list.
 * The returned attributes will only contain the attributes in the $pairs list.
 *
 * If the $atts list has unsupported attributes, then they will be ignored and removed from the final returned list.
 *
 * @since 2.5.0
 *
 * @param  array  $pairs     Entire list of supported attributes and their defaults.
 * @param  array  $atts      User defined attributes in shortcode tag.
 * @param  string $shortcode Optional.
 *                           The name of the shortcode, provided for context to enable filtering.
 * @return array  Combined and filtered attribute list.
 */
function shortcode_atts( $pairs, $atts, $shortcode = '' )
{
	$atts = ( array ) $atts;
	$out = array();

	foreach ( $pairs as $name => $default ) {
		$out[ $name ] = array_key_exists( $name, $atts )
			? $atts[ $name ]
			: $default;
	}

	/**
	 * Filters a shortcode's default attributes.
	 *
	 * If the third parameter of the shortcode_atts() function is present then this filter is available.
	 * The third parameter, $shortcode, is the name of the shortcode.
	 *
	 * @since 3.6.0
	 * @since 4.4.0 Added the `$shortcode` parameter.
	 *
	 * @param array  $out       The output array of shortcode attributes.
	 * @param array  $pairs     The supported attributes and their defaults.
	 * @param array  $atts      The user defined shortcode attributes.
	 * @param string $shortcode The shortcode name.
	 */
	if ( $shortcode ) {
		$out = apply_filters( "shortcode_atts_{$shortcode}", $out, $pairs, $atts, $shortcode );
	}

	return $out;
}

/**
 * Remove all shortcode tags from the given content.
 *
 * @since  2.5.0
 * @global array  $shortcode_tags
 *
 * @param  string $content Content to remove shortcode tags.
 * @return string Content without shortcode tags.
 */
function strip_shortcodes( $content )
{
	global $shortcode_tags;

	if ( FALSE === strpos( $content, '[' ) ) {
		return $content;
	}

	if ( empty( $shortcode_tags ) || ! is_array( $shortcode_tags ) ) {
		return $content;
	}

	// Find all registered tag names in $content.
	preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches );

	$tags_to_remove = array_keys( $shortcode_tags );

	/**
	 * Filters the list of shortcode tags to remove from the content.
	 *
	 * @since 4.7.0
	 *
	 * @param array  $tag_array Array of shortcode tags to remove.
	 * @param string $content   Content shortcodes are being removed from.
	 */
	$tags_to_remove = apply_filters( 'strip_shortcodes_tagnames', $tags_to_remove, $content );

	$tagnames = array_intersect( $tags_to_remove, $matches[1] );

	if ( empty( $tagnames ) ) {
		return $content;
	}

	$content = do_shortcodes_in_html_tags( $content, TRUE, $tagnames );
/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/formatting.php: wp_trim_excerpt( [string $text = ''] )
 * @NOW 006: wp-includes/shortcodes.php: strip_shortcodes( string $content )
 * ......->: wp-includes/shortcodes.php: do_shortcodes_in_html_tags( string $content, bool $ignore_html, array $tagnames )
 */
}
