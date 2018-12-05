<?php
/**
 * WordPress Post Template Functions.
 *
 * Gets content for the current post in the loop.
 *
 * @package    WordPress
 * @subpackage Template
 */

/**
 * Retrieve the ID of the current item in the WordPress Loop.
 *
 * @since 2.1.0
 *
 * @return int|false The ID of the current item in the WordPress Loop.
 *                   False if $post is not set.
 */
function get_the_ID()
{
	$post = get_post();

	return ! empty( $post )
		? $post->ID
		: FALSE;
}

/**
 * Retrieves the Post Global Unique Identifier (guid).
 *
 * The guid will appear to be a link, but should not be used as an link to the post.
 * The reason you should not use it as a link, is because of moving the blog across domains.
 *
 * @since 1.5.0
 *
 * @param  int|WP_Post $post Optional.
 *                           Post ID or post object.
 *                           Default is global $post.
 * @return string
 */
function get_the_guid( $post = 0 )
{
	$post = get_post( $post );

	$guid = isset( $post->guid )
		? $post->guid
		: '';

	$id = isset( $post->ID )
		? $post->ID
		: 0;

	/**
	 * Filters the Global Unique Identifier (guid) of the post.
	 *
	 * @since 1.5.0
	 *
	 * @param string $guid Global Unique Identifier (guid) of the post.
	 * @param int    $id   The post ID.
	 */
	return apply_filters( 'get_the_guid', $guid, $id );
}

/**
 * Wrap attachment in paragraph tag before content.
 *
 * @since 2.0.0
 *
 * @param  string $content
 * @return string
 */
function prepend_attachment( $content )
{
	$post = get_post();

	if ( empty( $post->post_type ) || $post->post_type != 'attachment' ) {
		return $content;
	}

	if ( wp_attachment_is( 'video', $post ) ) {
		$meta = wp_get_attachment_metadata( get_the_ID() );
		$atts = array( 'src' => wp_get_attachment_url() );

		if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
			$atts['width'] = ( int ) $meta['width'];
			$atts['height'] = ( int ) $meta['height'];
		}

		if ( has_post_thumbnail() ) {
			$atts['poster'] = wp_get_attachment_url( get_post_thumbnail_id() );
		}

		$p = wp_video_shortcode( $atts );
	} elseif ( wp_attachment_is( 'audio', $post ) ) {
		$p = wp_audio_shortcode( $array( 'src' => wp_get_attachment_url() ) );
	} else {
		$p = '<p class="attachment">';

		// Show the medium sized image representation of the attachment if available, and link to the raw file.
		$p .= wp_get_attachment_link( 0, 'medium', FALSE );
		$p .= '</p>';
	}

	/**
	 * Filters the attachment markup to be prepended to the post content.
	 *
	 * @since 2.0.0
	 * @see   prepend_attachment()
	 *
	 * @param string $p The attachment HTML output.
	 */
	$p = apply_filters( 'prepend_attachment', $p );

	return "$p\n$content";
}
