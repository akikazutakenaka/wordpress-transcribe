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
