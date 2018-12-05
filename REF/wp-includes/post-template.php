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
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/formatting.php: wp_trim_excerpt( [string $text = ''] )
 * <-......: wp-includes/post-template.php: get_the_content( [string $more_link_text = NULL [, bool $strip_teaser = FALSE]] )
 * @NOW 007: wp-includes/post-template.php: the_title_attribute( [string|array $args = ''] )
 */

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
 * Retrieve the post content.
 *
 * @since  0.71
 * @global int   $page      Page number of a single post/page.
 * @global int   $more      Boolean indicator for whether single post/page is being viewed.
 * @global bool  $preview   Whether post/page is in preview mode.
 * @global array $pages     Array of all pages in post/page.
 *                          Each array element contains part of the content separated by the <!--nextpage--> tag.
 * @global int   $multipage Boolean indicator for whether multiple pages are in play.
 *
 * @param  string $more_link_text Optional.
 *                                Content for when there is more text.
 * @param  bool   $strip_teaser   Optional.
 *                                Strip teaser content before the more text.
 *                                Default is false.
 * @return string
 */
function get_the_content( $more_link_text = NULL, $strip_teaser = FALSE )
{
	global $page, $more, $preview, $pages, $multipage;
	$post = get_post();

	if ( NULL === $more_link_text ) {
		$more_link_text = sprintf( '<span aria-label="%1$s">%2$s</span>', sprintf( __( 'Continue reading %s' ), the_title_attribute( array( 'echo' => FALSE ) ) ), __( '(more&hellip;)' ) );
/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/formatting.php: wp_trim_excerpt( [string $text = ''] )
 * @NOW 006: wp-includes/post-template.php: get_the_content( [string $more_link_text = NULL [, bool $strip_teaser = FALSE]] )
 * ......->: wp-includes/post-template.php: the_title_attribute( [string|array $args = ''] )
 */
	}
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
