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
 * Sanitize the current title when retrieving or displaying.
 *
 * Works like the_title(), except the parameters can be in a string or an array.
 * See the function for what can be override in the $args parameter.
 *
 * The title before it is displayed will have the tags stripped and esc_attr() before it is passed to the user or displayed.
 * The default as with the_title(), is to display the title.
 *
 * @since 2.3.0
 *
 * @param  string|array $args {
 *     Title attribute arguments.
 *     Optional.
 *
 *     @type string  $before Markup to prepend to the title.
 *                           Default empty.
 *     @type string  $after  Markup to append to the title.
 *                           Default empty.
 *     @type bool    $echo   Whether to echo or return the title.
 *                           Default true for echo.
 *     @type WP_Post $post   Current post object to retrieve the title for.
 * }
 * @return string|void  String when echo is false.
 */
function the_title_attribute( $args = '' )
{
	$defaults = array(
		'before' => '',
		'after'  => '',
		'echo'   => TRUE,
		'post'   => get_post()
	);
	$r = wp_parse_args( $args, $defaults );
	$title = get_the_title( $r['post'] );

	if ( strlen( $title ) == 0 ) {
		return;
	}

	$title = $r['before'] . $title . $r['after'];
	$title = esc_attr( strip_tags( $title ) );

	if ( $r['echo'] ) {
		echo $title;
	} else {
		return $title;
	}
}

/**
 * Retrieve post title.
 *
 * If the post is protected and the visitor is not an admin, then "Protected" will be displayed before the post title.
 * If the post is private, then "Private" will be located before the post title.
 *
 * @since 0.71
 *
 * @param  int|WP_Post $post Optional.
 *                           Post ID or WP_Post object.
 *                           Default is global $post.
 * @return string
 */
function get_the_title( $post = 0 )
{
	$post = get_post( $post );

	$title = isset( $post->post_title )
		? $post->post_title
		: '';

	$id = isset( $post->ID )
		? $post->ID
		: 0;

	if ( ! is_admin() ) {
		if ( ! empty( $post->post_password ) ) {
			/**
			 * Filters the text prepended to the post title for protected posts.
			 *
			 * The filter is only applied on the front end.
			 *
			 * @since 2.8.0
			 *
			 * @param string  $prepend Text displayed before the post title.
			 *                         Default 'Protected: %s'.
			 * @param WP_Post $post    Current post object.
			 */
			$protected_title_format = apply_filters( 'protected_title_format', __( 'Protected: %s' ), $post );

			$title = sprintf( $protected_title_format, $title );
		} elseif ( isset( $post->post_status ) && 'private' == $post->post_status ) {
			/**
			 * Filters the text prepended to the post title of private posts.
			 *
			 * The filter is only applied on the front end.
			 *
			 * @since 2.8.0
			 *
			 * @param string  $prepend Text displayed before the post title.
			 *                         Default 'Private: %s'.
			 * @param WP_Post $post    Current post object.
			 */
			$private_title_format = apply_filters( 'private_title_format', __( 'Private: %s' ), $post );

			$title = sprintf( $private_title_format, $title );
		}
	}

	/**
	 * Filters the post title.
	 *
	 * @since 0.71
	 *
	 * @param string $title The post title.
	 * @param int    $id    The post ID.
	 */
	return apply_filters( 'the_title', $title, $id );
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
	}

	$output = '';
	$has_teaser = FALSE;

	// If post password required and it doesn't match the cookie.
	if ( post_password_required( $post ) ) {
/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/formatting.php: wp_trim_excerpt( [string $text = ''] )
 * @NOW 006: wp-includes/post-template.php: get_the_content( [string $more_link_text = NULL [, bool $strip_teaser = FALSE]] )
 * ......->: wp-includes/post-template.php: post_password_required( [int|WP_Post|null $post = NULL] )
 */
	}
}

/**
 * Whether post requires password and correct password has been provided.
 *
 * @since 2.7.0
 *
 * @param  int|WP_Post|null $post An optional post.
 *                                Global $post used if not provided.
 * @return bool             False if a password is not required or the correct password cookie is present, true otherwise.
 */
function post_password_required( $post = NULL )
{
	$post = get_post( $post );

	if ( empty( $post->post_password ) ) {
		// This filter is documented in wp-includes/post-template.php
		return apply_filters( 'post_password_required', FALSE, $post );
	}

	if ( ! isset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] ) ) {
		// This filter is documented in wp-includes/post-template.php
		return apply_filters( 'post_password_required', TRUE, $post );
	}

	require_once ABSPATH . WPINC . '/class-phpass.php';
	$hasher = new PasswordHash( 8, TRUE );
	$hash = wp_unslash( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );

	$required = 0 !== strpos( $hash, '$P$B' )
		? TRUE
		: ! $hasher->CheckPassword( $post->post_password, $hash );
/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/formatting.php: wp_trim_excerpt( [string $text = ''] )
 * <-......: wp-includes/post-template.php: get_the_content( [string $more_link_text = NULL [, bool $strip_teaser = FALSE]] )
 * @NOW 007: wp-includes/post-template.php: post_password_required( [int|WP_Post|null $post = NULL] )
 */
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
