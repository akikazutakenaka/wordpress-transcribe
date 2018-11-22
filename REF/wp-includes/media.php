<?php
/**
 * WordPress API for media display.
 *
 * @package    WordPress
 * @subpackage Media
 */

/**
 * Retrieve additional image sizes.
 *
 * @since  4.7.0
 * @global array $_wp_additional_image_sizes
 *
 * @return array Additional images size data.
 */
function wp_get_additional_image_sizes()
{
	global $_wp_additional_image_sizes;

	if ( ! $_wp_additional_image_sizes ) {
		$_wp_additional_image_sizes = array();
	}

	return $_wp_additional_image_sizes;
}

/**
 * Scale down the default size of an image.
 *
 * This is so that the image is a better fit for the editor and theme.
 *
 * The `$size` parameter accepts either an array or a string.
 * The supported string values are 'thumb' or 'thumbnail' for the given thumbnail size or defaults at 128 width and 96 height in pixels.
 * Also supported for the string value is 'medium', 'medium_large' and 'full'.
 * The 'full' isn't actually supported, but any value other than the supported will result in the content_width or 500 if that is not set.
 *
 * Finally, there is a filter named {@see 'editor_max_image_size'}, that will be called on the calculated array for width and height, respectively.
 * The second parameter will be the value that was in the $size parameter.
 * The returned type for the hook is an array with the width as the first element and the height as the second element.
 *
 * @since  2.5.0
 * @global int $content_width
 *
 * @param  int          $width   Width of the image in pixels.
 * @param  int          $height  Height of the image in pixels.
 * @param  string|array $size    Optional.
 *                               Image size.
 *                               Accepts any valid image size, or an array of width and height values in pixels (in that order).
 *                               Default 'medium'.
 * @param  string       $context Optional.
 *                               Could be 'display' (like in a theme) or 'edit' (like inserting into an editor).
 *                               Default null.
 * @return array        Width and height of what the result image should resize to.
 */
function image_constrain_size_for_editor( $width, $height, $size = 'medium', $context = NULL )
{
	global $content_width;
	$_wp_additional_image_sizes = wp_get_additional_image_sizes();

	if ( ! $context ) {
		$context = is_admin()
			? 'edit'
			: 'display';
	}

	if ( is_array( $size ) ) {
		$max_width  = $size[0];
		$max_height = $size[1];
	} elseif ( $size == 'thumb' || $size == 'thumbnail' ) {
		$max_width  = intval( get_option( 'thumbnail_size_w' ) );
		$max_height = intval( get_option( 'thumbnail_size_h' ) );

		// Last chance thumbnail size defaults.
		if ( ! $max_width && ! $max_height ) {
			$max_width  = 128;
			$max_height = 96;
		}
	} elseif ( $size == 'medium' ) {
		$max_width  = intval( get_option( 'medium_size_w' ) );
		$max_height = intval( get_option( 'medium_size_h' ) );
	} elseif ( $size == 'medium_large' ) {
		$max_width  = intval( get_option( 'medium_large_size_w' ) );
		$max_height = intval( get_option( 'medium_large_size_h' ) );

		if ( intval( $content_width ) > 0 ) {
			$max_width = min( intval( $content_width ), $max_width );
		}
	} elseif ( $size == 'large' ) {
		/**
		 * We're inserting a large size image into the editor.
		 * If it's a really big image we'll scale it down to fit reasonably within the editor itself, and within the theme's content width if it's known.
		 * The user can resize it in the editor if they wish.
		 */
		$max_width  = intval( get_option( 'large_size_w' ) );
		$max_height = intval( get_option( 'large_size_h' ) );

		if ( intval( $content_width ) > 0 ) {
			$max_width = min( intval( $content_width ), $max_width );
		}
	} elseif ( ! empty( $_wp_additional_image_sizes ) && in_array( $size, array_keys( $_wp_additional_image_sizes ) ) ) {
		$max_width  = intval( $_wp_additional_image_sizes[ $size ]['width'] );
		$max_height = intval( $_wp_additional_image_sizes[ $size ]['height'] );

		/**
		 * Only in admin.
		 * Assume that theme authors know what they're doing.
		 */
		if ( intval( $content_width ) > 0 && 'edit' === $context ) {
			$max_width = min( intval( $content_width ), $max_width );
		}
	} else {
		// $size == 'full' has no constraint.
		$max_width  = $width;
		$max_height = $height;
	}

	/**
	 * Filters the maximum image size dimensions for the editor.
	 *
	 * @since 2.5.0
	 *
	 * @param array        $max_image_size An array with the width as the first element, and the height as the second element.
	 * @param string|array $size           Size of what the result image should be.
	 * @param string       $context        The context the image is being resized for.
	 *                                     Possible values are 'display' (like in a theme) or 'edit' (like inserting into an editor).
	 */
	list( $max_width, $max_height ) = apply_filters( 'editor_max_image_size', array( $max_width, $max_height ), $size, $context );

	return wp_constrain_dimensions( $width, $height, $max_width, $max_height );
}

/**
 * Scale an image to fit a particular size (such as 'thumb' or 'medium').
 *
 * Array with image url, width, height, and whether is intermediate size, in that order is returned on success is returned.
 * $is_intermediate is true if $url is a resized image, false if it is the original.
 *
 * The URL might be the original image, or it might be a resized version.
 * This function won't create a new resized copy, it will just return an already resized one if it exists.
 *
 * A plugin may use the {@see 'image_downsize'} filter to hook into and offer image resizing services for images.
 * The hook must return an array with the same elements that are returned in the function.
 * The first element being the URL to the new image that was resized.
 *
 * @since 2.5.0
 *
 * @param  int          $id   Attachment ID for image.
 * @param  array|string $size Optional.
 *                            Image size to scale to.
 *                            Accepts any valid image size, or an array of width and height values in pixels (in that order).
 *                            Default 'medium'.
 * @return false|array  Array containing the image URL, width, height, and boolean for whether the image is an intermediate size.
 *                            False on failure.
 */
function image_downsize( $id, $size = 'medium' )
{
	$is_image = wp_attachment_is_image( $id );

	/**
	 * Filters whether to preempt the output of image_downsize().
	 *
	 * Passing a truthy value to the filter will effectively short-circuit down-sizing the image, returning that value as output instead.
	 *
	 * @since 2.5.0
	 *
	 * @param bool         $downsize Whether to short-circuit the image downsize.
	 *                               Default false.
	 * @param int          $id       Attachment ID for image.
	 * @param array|string $size     Size of image.
	 *                               Image size or array of width and height values (in that order).
	 *                               Default 'medium'.
	 */
	if ( $out = apply_filters( 'image_downsize', FALSE, $id, $size ) ) {
		return $out;
	}

	$img_url = wp_get_attachment_url( $id );
	$meta = wp_get_attachment_metadata( $id );
	$width = $height = 0;
	$is_intermediate = FALSE;
	$img_url_basename = wp_basename( $img_url );

	/**
	 * If the file isn't an image, attempt to replace its URL with a rendered image from its meta.
	 * Otherwise, a non-image type could be returned.
	 */
	if ( ! $is_image ) {
		if ( ! empty( $meta['sizes'] ) ) {
			$img_url = str_replace( $img_url_basename, $meta['sizes']['full']['file'], $img_url );
			$img_url_basename = $meta['sizes']['full']['file'];
			$width = $meta['sizes']['full']['width'];
			$height = $meta['sizes']['full']['height'];
		} else {
			return FALSE;
		}
	}

	// Try for a new style intermediate size.
	if ( $intermediate = image_get_intermediate_size( $id, $size ) ) {
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/media.php
 * <- wp-includes/media.php
 * @NOW 009: wp-includes/media.php
 * -> wp-includes/media.php
 */
	}
}

/**
 * Calculates the new dimensions for a down-sampled image.
 *
 * If either width or height are empty, no constraint is applied on that dimension.
 *
 * @since 2.5.0
 *
 * @param  int   $current_width  Current width of the image.
 * @param  int   $current_height Current height of the image.
 * @param  int   $max_width      Optional.
 *                               Max width in pixels to constrain to.
 *                               Default 0.
 * @param  int   $max_height     Optional.
 *                               Max height in pixels to constrain to.
 *                               Default 0.
 * @return array First item is the width, the second item is the height.
 */
function wp_constrain_dimensions( $current_width, $current_height, $max_width = 0, $max_height = 0 )
{
	if ( ! $max_width && ! $max_height ) {
		return array( $current_width, $current_height );
	}

	$width_ratio = $height_ratio = 1.0;
	$did_width = $did_height = FALSE;

	if ( $max_width > 0 && $current_width > 0 && $current_width > $max_width ) {
		$width_ratio = $max_width / $current_width;
		$did_width = TRUE;
	}

	if ( $max_height > 0 && $current_height > 0 && $current_height > $max_height ) {
		$height_ratio = $max_height / $current_height;
		$did_height = TRUE;
	}

	// Calculate the larger/smaller ratios.
	$smaller_ratio = min( $width_ratio, $height_ratio );
	$larger_ratio  = max( $width_ratio, $height_ratio );

	$ratio = ( int ) round( $current_width * $larger_ratio ) > $max_width || ( int ) round( $current_height * $larger_ratio ) > $max_height
		? /**
		   * The larger ratio is too big.
		   * It would result in an overflow.
		   */
			$smaller_ratio
		: //The larger ratio fits, and is likely to be a more "snug" fit.
			$larger_ratio;

	// Very small dimensions may result in 0, 1 should be the minimum.
	$w = max( 1, ( int ) round( $current_width  * $ratio ) );
	$h = max( 1, ( int ) round( $current_height * $ratio ) );

	/**
	 * Sometimes, due to rounding, we'll end up with a result like this: 465x700 in a 177x177 box is 117.176... a pixel short.
	 * We also have issues with recursive calls resulting in an ever-changing result.
	 * Constraining to the result of a constraint should yield the original result.
	 * Thus we look for dimensions that are one pixel shy of the max value and bump them up.
	 */

	// Note: $did_width means it is possible $smaller_ratio == $width_ratio.
	if ( $did_width && $w == $max_width - 1 ) {
		$w = $max_width; // Round it up.
	}

	// Note: $did_height means it is possible $smaller_ratio == $height_ratio.
	if ( $did_height && $h == $max_height - 1 ) {
		$h = $max_height; // Round it up.
	}

	/**
	 * Filters dimensions to constrain down-sampled images to.
	 *
	 * @since 4.1.0
	 *
	 * @param array $dimensions     The image width and height.
	 * @param int   $current_width  The current width of the image.
	 * @param int   $current_height The current height of the image.
	 * @param int   $max_width      The maximum width permitted.
	 * @param int   $max_height     The maximum height permitted.
	 */
	return apply_filters( 'wp_constrain_dimensions', array( $w, $h ), $current_width, $current_height, $max_width, $max_height );
}

/**
 * Helper function to test if aspect ratios for two images match.
 *
 * @since 4.6.0
 *
 * @param  int  $source_width  Width of the first image in pixels.
 * @param  int  $source_height Height of the first image in pixels.
 * @param  int  $target_width  Width of the second image in pixels.
 * @param  int  $target_height Height of the second image in pixels.
 * @return bool True if aspect ratios match within 1px.
 *              False if not.
 */
function wp_image_matches_ratio( $source_width, $source_height, $target_width, $target_height )
{
	// To test for varying crops, we constrain the dimensions of the larger image to the dimensions of the smaller image and see if they match.
	if ( $source_width > $target_width ) {
		$constrained_size = wp_constrain_dimensions( $source_width, $source_height, $target_width );
		$expected_size = array( $target_width, $target_height );
	} else {
		$constrained_size = wp_constrain_dimensions( $target_width, $target_height, $source_width );
		$expected_size = array( $source_width, $source_height );
	}

	// If the image dimensions are within 1px of the expected size, we consider it a match.
	$matched = abs( $constrained_size[0] - $expected_size[0] ) <= 1 && abs( $constrained_size[1] - $expected_size[1] ) <= 1;

	return $matched;
}

/**
 * Retrieves the image's intermediate size (resized) path, width, and height.
 *
 * The $size parameter can be an array with the width and height respectively.
 * If the size matches the 'sizes' metadata array for width and height, then it will be used.
 * If there is no direct match, then the nearest image size larger than the specified size will be used.
 * If nothing is found, then the function will break out and return false.
 *
 * The metadata 'sizes' is used for compatible sizes that can be used for the parameter $size value.
 *
 * The url path will be given, when the $size parameter is a string.
 *
 * If you are passing an array for the $size, you should consider using add_image_size() so that a cropped version is generated.
 * It's much more efficient than having to find the closest-sized image and then having the browser scale down the image.
 *
 * @since 2.5.0
 *
 * @param  int          $post_id Attachment ID.
 * @param  array|string $size    Optional.
 *                               Image size.
 *                               Accepts any valid image size, or an array of width and height values in pixels (in that order).
 *                               Default 'thumbnail'.
 * @return false|array  $data {
 *     Array of file relative path, width, and height on success.
 *     Additionally includes absolute path and URL if registered size is passed to $size parameter.
 *     False on failure.
 *
 *     @type string $file   Image's path relative to uploads directory.
 *     @type int    $width  Width of image.
 *     @type int    $height Height of image.
 *     @type string $path   Image's absolute filesystem path.
 *     @type string $url    Image's URL.
 * }
 */
function image_get_intermediate_size( $post_id, $size = 'thumbnail' )
{
	if ( ! $size || ! is_array( $imagedata = wp_get_attachment_metadata( $post_id ) ) || empty( $imagedata['sizes'] ) ) {
		return FALSE;
	}

	$data = array();

	// Find the best match when '$size' is an array.
	if ( is_array( $size ) ) {
		$candidates = array();

		if ( ! isset( $imagedata['file'] ) && isset( $imagedata['sizes']['full'] ) ) {
			$imagedata['height'] = $imagedata['sizes']['full']['height'];
			$imagedata['width']  = $imagedata['sizes']['full']['width'];

			foreach ( $imagedata['sizes'] as $_size => $data ) {
				// If there's an exact match to an existing image size, short circuit.
				if ( $data['width'] == $size[0] && $data['height'] == $size[1] ) {
					$candidates[ $data['width'] * $data['height'] ] = $data;
					break;
				}

				// If it's not an exact match, consider larger sizes with the same aspect ratio.
				if ( $data['width'] >= $size[0] && $data['height'] >= $size[1] ) {
					// If '0' is passed to either size, we test ratios against the original file.
					$same_ratio = 0 === $size[0] || 0 === $size[1]
						? wp_image_matches_ratio( $data['width'], $data['height'], $imagedata['width'], $imagedata['height'] )
						: wp_image_matches_ratio( $data['width'], $data['height'], $size[0], $size[1] );

					if ( $same_ratio ) {
						$candidates[ $data['width'] * $data['height'] ] = $data;
					}
				}
			}

			if ( ! empty( $candidates ) ) {
				// Sort the array by size if we have more than one candidate.
				if ( 1 < count( $candidates ) ) {
					ksort( $candidates );
				}

				$data = array_shift( $candidates );
			} elseif ( ! empty( $imagedata['sizes']['thumbnail'] ) && $imagedata['sizes']['thumbnail']['width'] >= $size[0] && $imagedata['sizes']['thumbnail']['width'] >= $size[1] ) {
				// When the size requested is smaller than the thumbnail dimensions, we fall back to the thumbnail size to maintain backwards compatibility with pre 4.6 versions of WordPress.
				$data = $imagedata['sizes']['thumbnail'];
			} else {
				return FALSE;
			}

			// Constrain the width and height attributes to the requested values.
			list( $data['width'], $data['height'] ) = image_constrain_size_for_editor( $data['width'], $data['height'], $size );
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/media.php
 * <- wp-includes/media.php
 * <- wp-includes/media.php
 * @NOW 010: wp-includes/media.php
 */
		}
	}
}

/**
 * Retrieve an image to represent an attachment.
 *
 * A mime icon for files, thumbnail or intermediate size for images.
 *
 * The returned array contains four values: the URL of the attachment image src, the width of the image file, the height of the image file, and a boolean representing whether the returned array describes an intermediate (generated) image size or the original, full-sized upload.
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
 * @return false|array  Returns an array (url, width, height, is_intermediate), or false, if no image is available.
 */
function wp_get_attachment_image_src( $attachment_id, $size = 'thumbnail', $icon = FALSE )
{
	// Get a thumbnail or intermediate image if there is one.
	$image = image_downsize( $attachment_id, $size );
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/media.php
 * @NOW 008: wp-includes/media.php
 * -> wp-includes/media.php
 */
}

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
 * Get the attachment path relative to the upload directory.
 *
 * @since  4.4.1
 * @access private
 *
 * @param  string $file Attachment file name.
 * @return string Attachment path relative to the upload directory.
 */
function _wp_get_attachment_relative_path( $file )
{
	$dirname = dirname( $file );

	if ( '.' === $dirname ) {
		return '';
	}

	if ( FALSE !== strpos( $dirname, 'wp-content/uploads' ) ) {
		// Get the directory name relative to the upload directory (back compat for pre-2.7 uploads).
		$dirname = substr( $dirname, strpos( $dirname, 'wp-contenet/uploads' ) + 18 );
		$dirname = ltrim( $dirname, '/' );
	}

	return $dirname;
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
