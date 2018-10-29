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
// wp-includes/taxonomy.php -> @NOW 011
}
