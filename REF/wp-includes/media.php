<?php
/**
 * WordPress API for media display.
 *
 * @package    WordPress
 * @subpackage Media
 */

/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/media.php
 * @NOW 008: wp-includes/media.php
 */

/**
 * Get an HTML img element representing an image attachment.
 *
 * While `$size` will accept an array, it is better to register a size with add_image_size() so that a cropped version is generated.
 * It's much more efficient than having to find the closest-sized image and then having the browser scale down the image.
 *
 * @since 2.5.0
 *
 * @param  int          $attachment_id Image attachment ID.
 * @param  string|array $size          Optional.
 *                                     Image size.
 *                                     Accepts any valid image size, or an array of width and height values in pixels (in that order).
 *                                     Default 'thumbnail'.
 * @param  bool         $icon          Optional.
 *                                     Whether the image should be treated as an icon.
 *                                     Default false.
 * @param  string|array $attr          Optional.
 *                                     Attributes for the image markup.
 *                                     Default empty.
 * @return string       HTML img element or empty string on failure.
 */
function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = FALSE, $attr = '' )
{
	$html = '';
	$image = wp_get_attachment_image_src( $attachment_id, $size, $icon );
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * @NOW 007: wp-includes/media.php
 * -> wp-includes/media.php
 */
}

/**
 * Returns a filtered list of WP-supported audio formats.
 *
 * @since 3.6.0
 *
 * @return array Supported audio formats.
 */
function wp_get_audio_extensions()
{
	/**
	 * Filters the list of supported audio formats.
	 *
	 * @since 3.6.0
	 *
	 * @param array $extensions An array of support audio formats.
	 *                          Defaults are 'mp3', 'ogg', 'flac', 'm4a', 'wav'.
	 */
	return apply_filters( 'wp_audio_extensions', array( 'mp3', 'ogg', 'flac', 'm4a', 'wav' ) );
}

/**
 * Returns a filtered list of WP-supported video formats.
 *
 * @since 3.6.0
 *
 * @return array List of supported video formats.
 */
function wp_get_video_extensions()
{
	/**
	 * Filters the list of supported video formats.
	 *
	 * @since 3.6.0
	 *
	 * @param array $extensions An array of support video formats.
	 *                          Defaults are 'mp4', 'm4v', 'webm', 'ogv', 'flv'.
	 */
	return apply_filters( 'wp_video_extensions', array( 'mp4', 'm4v', 'webm', 'ogv', 'flv' ) );
}

/**
 * Retrieve taxonomies attached to given the attachment.
 *
 * @since 2.5.0
 * @since 4.7.0 Introduced the `$output` parameter.
 *
 * @param  int|array|object $attachment Attachment ID, data array, or data object.
 * @param  string           $output     Output type.
 *                                      'names' to return an array of taxonomy names, or 'objects' to return an array of taxonomy objects.
 *                                      Default is 'names'.
 * @return array            Empty array on failure.
 *                          List of taxonomies on success.
 */
function get_attachment_taxonomies( $attachment, $output = 'names' )
{
	if ( is_int( $attachment ) ) {
		$attachment = get_post( $attachment );
	} elseif ( is_array( $attachment ) ) {
		$attachment = ( object ) $attachment;
	}

	if ( ! is_object( $attachment ) ) {
		return array();
	}

	$file = get_attached_file( $attachment->ID );
	$filename = basename( $file );
	$objects = array( 'attachment' );

	if ( FALSE !== strpos( $filename, '.' ) ) {
		$objects[] = 'attachment:' . substr( $filename, strrpos( $filename, '.' ) + 1 );
	}

	if ( ! empty( $attachment->post_mime_type ) ) {
		$objects[] = 'attachment:' . $attachment->post_mime_type;

		if ( FALSE !== strpos( $attachment->post_mime_type, '/' ) ) {
			foreach ( explode( '/', $attachment->post_mime_type ) as $token ) {
				if ( ! empty( $token ) ) {
					$objects[] = "attachment:$token";
				}
			}
		}
	}

	$taxonomies = array();

	foreach ( $objects as $object ) {
		if ( $taxes = get_object_taxonomies( $object, $output ) ) {
			$taxonomies = array_merge( $taxonomies, $taxes );
		}
	}

	if ( 'names' === $output ) {
		$taxonomies = array_unique( $taxonomies );
	}

	return $taxonomies;
}

/**
 * Retrieves all of the taxonomy names that are registered for attachments.
 *
 * Handles mime-type-specific taxonomies such as attachment:image and attachment:video.
 *
 * @since 3.5.0
 * @see   get_taxonomies()
 *
 * @param  string $output Optional.
 *                        The type of taxonomy output to return.
 *                        Accepts 'names' or 'objects'.
 *                        Default 'names'.
 * @return array  The names of all taxonomy of $object_type.
 */
function get_taxonomies_for_attachments( $output = 'names' )
{
	$taxonomies = array();

	foreach ( get_taxonomies( array(), 'objects' ) as $taxonomy ) {
		foreach ( $taxonomy->object_type as $object_type ) {
			if ( 'attachment' == $object_type || 0 === strpos( $object_type, 'attachment:' ) ) {
				if ( 'names' == $output ) {
					$taxonomies[] = $taxonomy->name;
				} else {
					$taxonomies[ $taxonomy->name ] = $taxonomy;
				}

				break;
			}
		}
	}

	return $taxonomies;
}
