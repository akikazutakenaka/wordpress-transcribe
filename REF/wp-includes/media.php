<?php
/**
 * WordPress API for media display.
 *
 * @package    WordPress
 * @subpackage Media
 */

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
