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
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/post-template.php: prepend_attachment( string $content )
 * <-......: wp-includes/media.php: wp_video_shortcode( array $attr [, string $content = ''] )
 * @NOW 007: wp-includes/shortcodes.php: shortcode_atts( array $pairs, array $atts [, string $shortcode = ''] )
 */
