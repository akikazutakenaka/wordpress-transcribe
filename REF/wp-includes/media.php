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
 * Retrieve width and height attributes using given width and height values.
 *
 * Both attributes are required in the sense that both parameters must have a value, but are optional in that if you set them to false or null, then they will not be added to the returned string.
 *
 * You can set the value using a string, but it will only take numeric values.
 * If you wish to put 'px' after the numbers, then it will be stripped out of the return.
 *
 * @since 2.5.0
 *
 * @param  int|string $width  Image width in pixels.
 * @param  int|string $height Image height in pixels.
 * @return string     HTML attributes for width and, or height.
 */
function image_hwstring( $width, $height )
{
	$out = '';

	if ( $width ) {
		$out .= 'width="' . intval( $width ) . '" ';
	}

	if ( $height ) {
		$out .= 'height="' . intval( $height ) . '" ';
	}

	return $out;
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
		$img_url = str_replace( $img_url_basename, $intermediate['file'], $img_url );
		$width  = $intermediate['width'];
		$height = $intermediate['height'];
		$is_intermediate = TRUE;
	} elseif ( $size == 'thumbnail' ) {
		// Fall back to the old thumbnail.
		if ( ( $thumb_file = wp_get_attachment_thumb_file( $id ) ) && $info = getimagesize( $thumb_file ) ) {
			$img_url = str_replace( $img_url_basename, wp_basename( $thumb_file ), $img_url );
			$width  = $info[0];
			$height = $info[1];
			$is_intermediate = TRUE;
		}
	}

	if ( ! $width && ! $height && isset( $meta['width'], $meta['height'] ) ) {
		// Any other type: use the real image
		$width  = $meta['width'];
		$height = $meta['height'];
	}

	if ( $img_url ) {
		// We have the actual image size, but might need to further constrain it if content_width is narrower.
		list( $width, $height ) = image_constrain_size_for_editor( $width, $height, $size );

		return array( $img_url, $width, $height, $is_intermediate );
	}

	return FALSE;
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
		}

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
	} elseif ( ! empty( $imagedata['sizes'][ $size ] ) ) {
		$data = $imagedata['sizes'][ $size ];
	}

	// If we still don't have a match at this point, return false.
	if ( empty( $data ) ) {
		return FALSE;
	}

	// Include the full filesystem path of the intermediate file.
	if ( empty( $data['path'] ) && ! empty( $data['file'] ) && ! empty( $imagedata['file'] ) ) {
		$file_url = wp_get_attachment_url( $post_id );
		$data['path'] = path_join( dirname( $imagedata['file'] ), $data['file'] );
		$data['url']  = path_join( dirname( $file_url ), $data['file'] );
	}

	/**
	 * Filters the output of image_get_intermediate_size()
	 *
	 * @since 4.4.0
	 * @see   image_get_intermediate_size()
	 *
	 * @param array        $data    Array of file relative path, width, and height on success.
	 *                              May also include file absolute path and URL.
	 * @param int          $post_id The post_id of the image attachment.
	 * @param string|array $size    Registered image size or flat array of initially-requested height and width dimensions (in that order).
	 */
	return apply_filters( 'image_get_intermediate_size', $data, $post_id, $size );
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

	if ( ! $image ) {
		$src = FALSE;

		if ( $icon && $src = wp_mime_type_icon( $attachment_id ) ) {
			// This filter is documented in wp-includes/post.php
			$icon_dir = apply_filters( 'icon_dir', ABSPATH . WPINC . '/images/media' );

			$src_file = $icon_dir . '/' . wp_basename( $src );
			@ list( $width, $height ) = getimagesize( $src_file );
		}

		if ( $src && $width && $height ) {
			$image = array( $src, $width, $height );
		}
	}

	/**
	 * Filters the image src result.
	 *
	 * @since 4.3.0
	 *
	 * @param array|false  $image         Either array with src, width & height, icon src, or false.
	 * @param int          $attachment_id Image attachment ID.
	 * @param string|array $size          Size of image.
	 *                                    Image size or array of width and height values (in that order).
	 *                                    Default 'thumbnail'.
	 * @param bool         $icon          Whether the image should be treated as an icon.
	 *                                    Default false.
	 */
	return apply_filters( 'wp_get_attachment_image_src', $image, $attachment_id, $size, $icon );
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

	if ( $image ) {
		list( $src, $width, $height ) = $image;
		$hwstring = image_hwstring( $width, $height );
		$size_class = $size;

		if ( is_array( $size_class ) ) {
			$size_class = join( 'x', $size_class );
		}

		$attachment = get_post( $attachment_id );
		$default_attr = array(
			'src'   => $src,
			'class' => "attachment-$size_class size-$size_class",
			'alt'   => trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', TRUE ) ) )
		);
		$attr = wp_parse_args( $attr, $default_attr );

		// Generate 'srcset' and 'sizes' if not already present.
		if ( empty( $attr['srcset'] ) ) {
			$image_meta = wp_get_attachment_metadata( $attachment_id );

			if ( is_array( $image_meta ) ) {
				$size_array = array( absint( $width ), absint( $height ) );
				$srcset = wp_calculate_image_srcset( $size_array, $src, $image_meta, $attachment_id );
				$sizes = wp_calculate_image_sizes( $size_array, $src, $image_meta, $attachment_id );

				if ( $srcset
				  && ( $sizes || ! empty( $attr['sizes'] ) ) ) {
					$attr['srcset'] = $srcset;

					if ( empty( $attr['sizes'] ) ) {
						$attr['sizes'] = $sizes;
					}
				}
			}
		}

		/**
		 * Filters the list of attachment image attributes.
		 *
		 * @since 2.8.0
		 *
		 * @param array        $attr       Attributes for the image markup.
		 * @param WP_Post      $attachment Image attachment post.
		 * @param string|array $size       Requested size.
		 *                                 Image size or array of width and height values (in that order).
		 *                                 Default 'thumbnail'.
		 */
		$attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $attachment, $size );

		$attr = array_map( 'esc_attr', $attr );
		$html = rtrim( "<img $hwstring" );

		foreach ( $attr as $name => $value ) {
			$html .= " $name=" . '"' . $value . '"';
		}

		$html .= ' />';
	}

	return $html;
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
 * Get the image size as array from its meta data.
 *
 * Used for responsive images.
 *
 * @since  4.4.0
 * @access private
 *
 * @param  string     $size_name  Image size.
 *                                Accepts any valid image size name ('thumbnail', 'medium', etc.)
 * @param  array      $image_meta The image meta data.
 * @return array|bool Array of width and height values in pixels (in that order) or false if the size doesn't exist.
 */
function _wp_get_image_size_from_meta( $size_name, $image_meta )
{
	return $size_name === 'full'
		? array(
				absint( $image_meta['width'] ),
				absint( $image_meta['height'] )
			)
		: ( ! empty( $image_meta['sizes'][ $size_name ] )
			? array(
					absint( $image_meta['sizes'][ $size_name ]['width'] ),
					absint( $image_meta['sizes'][ $size_name ]['height'] )
				)
			: FALSE );
}

/**
 * A helper function to calculate the image sources to include in a 'srcset' attribute.
 *
 * @since 4.4.0
 *
 * @param  array       $size_array    Array of width and height values in pixels (in that order).
 * @param  string      $image_src     The 'src' of the image.
 * @param  array       $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
 * @param  int         $attachment_id Optional.
 *                                    The image attachment ID to pass to the filter.
 *                                    Default 0.
 * @return string|bool The 'srcset' attribute value.
 *                     False on error or when only one source exists.
 */
function wp_calculate_image_srcset( $size_array, $image_src, $image_meta, $attachment_id = 0 )
{
	/**
	 * Let plugins pre-filter the image meta to be able to fix inconsistencies in the stored data.
	 *
	 * @since 4.5.0
	 *
	 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
	 * @param array  $size_array    Array of width and height values in pixels (in that order).
	 * @param string $image_src     The 'src' of the image.
	 * @param int    $attachment_id The image attachment ID or 0 if not supplied.
	 */
	$image_meta = apply_filters( 'wp_calculate_image_srcset_meta', $image_meta, $size_array, $image_src, $attachment_id );

	if ( empty( $image_meta['sizes'] ) || ! isset( $image_meta['file'] ) || strlen( $image_meta['file'] ) < 4 ) {
		return FALSE;
	}

	$image_sizes = $image_meta['sizes'];

	// Get the width and height of the image.
	$image_width = ( int ) $size_array[0];
	$image_height = ( int ) $size_array[1];

	// Bail early if error/no width.
	if ( $image_width < 1 ) {
		return FALSE;
	}

	$image_basename = wp_basename( $image_meta['file'] );

	/**
	 * WordPress flattens animated GIFs into one frame when generating intermediate sizes.
	 * To avoid hiding animation in user content, if src is a full size GIF, a srcset attribute is not generated.
	 * If src is an intermediate size GIF, the full size is excluded from srcset to keep a flattened GIF from becoming animated.
	 */
	if ( ! isset( $image_sizes['thumbnail']['mime-type'] ) || 'image/gif' !== $image_sizes['thumbnail']['mime-type'] ) {
		$image_sizes[] = array(
			'width'  => $image_meta['width'],
			'height' => $image_meta['height'],
			'file'   => $image_basename
		);
	} elseif ( strpos( $image_src, $image_meta['file'] ) ) {
		return FALSE;
	}

	// Retrieve the uploads sub-directory from the full size image.
	$dirname = _wp_get_attachment_relative_path( $image_meta['file'] );

	if ( $dirname ) {
		$dirname = trailingslashit( $dirname );
	}

	$upload_dir = wp_get_upload_dir();
	$image_baseurl = trailingslashit( $upload_dir['baseurl'] )  . $dirname;

	// If currently on HTTPS, prefer HTTPS URLs when we know they're supported by the domain (which is to say, when they share the domain name of the current request).
	if ( is_ssl() && 'https' !== substr( $image_baseurl, 0, 5 ) && parse_url( $image_baseurl, PHP_URL_HOST ) === $_SERVER['HTTP_HOST'] ) {
		$image_baseurl = set_url_scheme( $image_baseurl, 'https' );
	}

	/**
	 * Images that have been edited in WordPress after being uploaded will contain a unique hash.
	 * Look for that has and use it later to filter out images that are leftovers from previous versions.
	 */
	$image_edited = preg_match( '/-e[0-9]{13}/', wp_basename( $image_src ), $image_edit_hash );

	/**
	 * Filters the maximum image width to be included in a 'srcset' attribute.
	 *
	 * @since 4.4.0
	 *
	 * @param int   $max_width  The maximum image width to be included in the 'srcset'.
	 *                          Default '1600'.
	 * @param array $size_array Array of width and height values in pixels (in that order).
	 */
	$max_srcset_image_width = apply_filters( 'max_srcset_image_width', 1600, $size_array );

	// Array to hold URL candidates.
	$sources = array();

	/**
	 * To make sure the ID matches our image src, we will check to see if any sizes in our attachment meta match our $image_src.
	 * If no matches are found we don't return a srcset to avoid serving an incorrect image.
	 * See #35045.
	 */
	$src_matched = FALSE;

	/**
	 * Loop through available images.
	 * Only use images that are resized versions of the same edit.
	 */
	foreach ( $image_sizes as $image ) {
		$is_src = FALSE;

		// Check if image meta isn't corrupted.
		if ( ! is_array( $image ) ) {
			continue;
		}

		// If the file name is part of the `src`, we've confirmed a match.
		if ( ! $src_matched && FALSE !== strpos( $image_src, $dirname . $image['file'] ) ) {
			$src_matched = $is_src = TRUE;
		}

		// Filter out images that are from previous edits.
		if ( $image_edited && ! strpos( $image['file'], $image_edit_hash[0] ) ) {
			continue;
		}

		// Filters out images that are wider than '$max_srcset_image_width' unless that file is in the 'src' attribute.
		if ( $max_srcset_image_width && $image['width'] > $max_srcset_image_width && ! $is_src ) {
			continue;
		}

		// If the image dimensions are within 1px of the expected size, use it.
		if ( wp_image_matches_ratio( $image_width, $image_height, $image['width'], $image['height'] ) ) {
			// Add the URL, descriptor, and value to the sources array to be returned.
			$source = array(
				'url'        => $image_baseurl . $image['file'],
				'descriptor' => 'w',
				'value'      => $image['width']
			);

			/**
			 * The 'src' image has to be the first in the 'srcset', because of a bug in iOS8.
			 * See #35030.
			 */
			if ( $is_src ) {
				$sources = array( $image['width'] => $source ) + $sources;
			} else {
				$sources[ $image['width'] ] = $source;
			}
		}
	}

	/**
	 * Filters an image's 'srcset' sources.
	 *
	 * @since 4.4.0
	 *
	 * @param array  $sources {
	 *     One or more arrays of source data to include in the 'srcset'.
	 *
	 *     @type array $width {
	 *         @type string $url        The URL of an image source.
	 *         @type string $descriptor The descriptor type used in the image candidate string, either 'w' or 'x'.
	 *         @type int    $value      The source width if paired with a 'w' descriptor, or a pixel density value if paired with an 'x' descriptor.
	 *     }
	 * }
	 * @param array  $size_array    Array of width and height values in pixels (in that order).
	 * @param string $image_src     The 'src' of the image.
	 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
	 * @param int    $attachment_id Image attachment ID or 0.
	 */
	$sources = apply_filters( 'wp_calculate_image_srcset', $sources, $size_array, $image_src, $image_meta, $attachment_id );

	// Only return a 'srcset' value if there is more than one source.
	if ( ! $src_matched || ! is_array( $sources ) || count( $sources ) < 2 ) {
		return FALSE;
	}

	$srcset = '';

	foreach ( $sources as $source ) {
		$srcset .= str_replace( ' ', '%20', $source['url'] ) . ' ' . $source['value'] . $source['descriptor'] . ', ';
	}

	return rtrim( $srcset, ', ' );
}

/**
 * Creates a 'sizes' attribute value for an image.
 *
 * @since 4.4.0
 *
 * @param  array|string $size          Image size to retrieve.
 *                                     Accepts any valid image size, or an array of width and height values in pixels (in that order).
 *                                     Default 'medium'.
 * @param  string       $image_src     Optional.
 *                                     The URL to the image file.
 *                                     Default null.
 * @param  array        $image_meta    Optional.
 *                                     The image meta data as returned by 'wp_get_attachment_metadata()'.
 *                                     Default null.
 * @param  int          $attachment_id Optional.
 *                                     Image attachment ID.
 *                                     Either `$image_meta` or `$attachment_id` is needed when using the image size name as argument for `$size`.
 *                                     Default 0.
 * @return string|bool  A valid source size value for use in a 'sizes' attribute or false.
 */
function wp_calculate_image_sizes( $size, $image_src = NULL, $image_meta = NULL, $attachment_id = 0 )
{
	$width = 0;

	if ( is_array( $size ) ) {
		$width = absint( $size[0] );
	} elseif ( is_string( $size ) ) {
		if ( ! $image_meta && $attachment_id ) {
			$image_meta = wp_get_attachment_metadata( $attachment_id );
		}

		if ( is_array( $image_meta ) ) {
			$size_array = _wp_get_image_size_from_meta( $size, $image_meta );

			if ( $size_array ) {
				$width = absint( $size_array[0] );
			}
		}
	}

	if ( ! $width ) {
		return FALSE;
	}

	// Setup the default 'sizes' attribute.
	$sizes = sprintf( '(max-width: %1$dpx) 100vw, %1$dpx', $width );

	/**
	 * Filters the output of 'wp_calculate_image_sizes()'.
	 *
	 * @since 4.4.0
	 *
	 * @param string       $sizes         A source size value for use in a 'sizes' attribute.
	 * @param array|string $size          Requested size.
	 *                                    Image size or array of width and height values in pixels (in that order).
	 * @param string|null  $image_src     The URL to the image file or null.
	 * @param array|null   $image_meta    The image meta data as returned by wp_get_attachment_metadata() or null.
	 * @param int          $attachment_id Image attachment ID of the original image or 0.
	 */
	return apply_filters( 'wp_calculate_image_sizes', $sizes, $size, $image_src, $image_meta, $attachment_id );
}

/**
 * Filters 'img' elements in post content to add 'srcset' and 'sizes' attributes.
 *
 * @since 4.4.0
 * @see   wp_image_add_srcset_and_sizes()
 *
 * @param  string $content The raw post content to be filtered.
 * @return string Converted content with 'srcset' and 'sizes' attributes added to images.
 */
function wp_make_content_images_responsive( $content )
{
	if ( ! preg_match_all( '/<img [^>]+>/', $content, $matches ) ) {
		return $content;
	}

	$selected_images = $attachment_ids = array();

	foreach ( $matches[0] as $image ) {
		if ( FALSE === strpos( $image, ' srcset=' ) && preg_match( '/wp-image-([0-9]+)/i', $image, $class_id ) && ( $attachment_id = absint( $class_id[1] ) ) ) {
			/**
			 * If exactly the same image tag is used more than once, overwrite it.
			 * All identical tags will be replaced later with 'str_replace()'.
			 */
			$selected_images[ $image ] = $attachment_id;

			// Overwrite the ID when the same image is included more than once.
			$attachment_ids[ $attachment_id ] = TRUE;
		}
	}

	if ( count( $attachment_ids ) > 1 ) {
		// Warm the object cache with post and meta information for all found images to avoid making individual database calls.
		_prime_post_caches( array_keys( $attachment_ids ), FALSE, TRUE );
	}

	foreach ( $selected_images as $image => $attachment_id ) {
		$image_meta = wp_get_attachment_metadata( $attachment_id );
		$content = str_replace( $image, wp_image_add_srcset_and_sizes( $image, $image_meta, $attachment_id ), $content );
	}

	return $content;
}

/**
 * Adds 'srcset' and 'sizes' attributes to an existing 'img' element.
 *
 * @since 4.4.0
 * @see   wp_calculate_image_srcset()
 * @see   wp_calculate_image_sizes()
 *
 * @param  string $image         An HTML 'img' element to be filtered.
 * @param  array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
 * @param  int    $attachment_id Image attachment ID.
 * @return string Converted 'img' element with 'srcset' and 'sizes' attributes added.
 */
function wp_image_add_srcset_and_sizes( $image, $image_meta, $attachment_id )
{
	// Ensure the image meta exists.
	if ( empty( $image_meta['sizes'] ) ) {
		return $image;
	}

	$image_src = preg_match( '/src="([^"]+)"/', $image, $match_src )
		? $match_src[1]
		: '';

	list( $image_src ) = explode( '?', $image_src );

	// Return early if we couldn't get the image source.
	if ( ! $image_src ) {
		return $image;
	}

	// Bail early if an image has been inserted and later edited.
	if ( preg_match( '/-e[0-9]{13}/', $image_meta['file'], $img_edit_hash ) && strpos( wp_basename( $image_src ), $img_edit_hash[0] ) === FALSE ) {
		return $image;
	}

	$width  = preg_match( '/ width="([0-9]+)"/', $image, $match_width )
		? ( int ) $match_width[1]
		: 0;

	$height = preg_match( '/ height="([0-9]+)"/', $image, $match_height )
		? ( int ) $match_height[1]
		: 0;

	if ( ! $width || ! $height ) {
		// If attempts to parse the size value failed, attempt to use the image meta data to match the image file name from 'src' against the available sizes for an attachment.
		$image_filename = wp_basename( $image_src );

		if ( $image_filename === wp_basename( $image_meta['file'] ) ) {
			$width = ( int ) $image_meta['width'];
			$height = ( int ) $image_meta['height'];
		} else {
			foreach ( $image_meta['sizes'] as $image_size_data ) {
				if ( $image_filename === $image_size_data['file'] ) {
					$width = ( int ) $image_size_data['width'];
					$height = ( int ) $image_size_data['height'];
					break;
				}
			}
		}
	}

	if ( ! $width || ! $height ) {
		return $image;
	}

	$size_array = array( $width, $height );
	$srcset = wp_calculate_image_srcset( $size_array, $image_src, $image_meta, $attachment_id );

	if ( $srcset ) {
		// Check if there is already a 'sizes' attribute.
		$sizes = strpos( $image, ' sizes=' );

		if ( ! $sizes ) {
			$sizes = wp_calculate_image_sizes( $size_array, $image_src, $image_meta, $attachment_id );
		}
	}

	if ( $srcset && $sizes ) {
		// Format the 'srcset' and 'sizes' string and escape attributes.
		$attr = sprintf( ' srcset="%s"', esc_attr( $srcset ) );

		if ( is_string( $sizes ) ) {
			$attr .= sprintf( ' sizes="%s"', esc_attr( $sizes ) );
		}

		// Add 'srcset' and 'sizes' attributes to the image markup.
		$image = preg_replace( '/<img ([^>]+?)[\/ ]*>/', '<img $1' . $attr . ' />', $image );
	}

	return $image;
}
/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/media.php: wp_make_content_images_responsive( string $content )
 */

/**
 * Provides a No-JS Flash fallback as a last resort for audio / video.
 *
 * @since 3.6.0
 *
 * @param  string $url The media element URL.
 * @return string Fallback HTML.
 */
function wp_mediaelement_fallback( $url )
{
	/**
	 * Filters the Mediaelement fallback output for no-JS.
	 *
	 * @since 3.6.0
	 *
	 * @param string $output Fallback output for no-JS.
	 * @param string $url    Media file URL.
	 */
	return apply_filters( 'wp_mediaelement_fallback', sprintf( '<a href="%1$s">%1$s</a>', esc_url( $url ) ), $url );
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
 * Builds the Audio shortcode output.
 *
 * This implements the functionality of the Audio Shortcode for displaying WordPress mp3s in a post.
 *
 * @since     3.6.0
 * @staticvar int $instance
 *
 * @param  array       $attr {
 *     Attributes of the audio shortcode.
 *
 *     @type string $src      URL to the source of the audio file.
 *                            Default empty.
 *     @type string $loop     The 'loop' attribute for the `<audio>` element.
 *                            Default empty.
 *     @type string $autoplay The 'autoplay' attribute for the `<audio>` element.
 *                            Default empty.
 *     @type string $preload  The 'preload' attribute for the `<audio>` element.
 *                            Default 'none'.
 *     @type string $class    The 'class' attribute for the `<audio>` element.
 *                            Default 'wp-audio-shortcode'.
 *     @type string $style    The 'style' attribute for the `<audio>` element.
 *                            Default 'width: 100%;'.
 * }
 * @param  string      $content Shortcode content.
 * @return string|void HTML content to display audio.
 */
function wp_audio_shortcode( $attr, $content = '' )
{
	$post_id = get_post()
		? get_the_ID()
		: 0;

	static $instance = 0;
	$instance++;

	/**
	 * Filters the default audio shortcode output.
	 *
	 * If the filtered output isn't empty, it will be used instead of generating the default audio template.
	 *
	 * @since 3.6.0
	 *
	 * @param string $html     Empty variable to be replaced with shortcode markup.
	 * @param array  $attr     Attributes of the shortcode.
	 *                         @see wp_audio_shortcode()
	 * @param string $content  Shortcode content.
	 * @param int    $instance Unique numeric ID of this audio shortcode instance.
	 */
	$override = apply_filters( 'wp_audio_shortcode_override', '', $attr, $content, $instance );

	if ( '' !== $override ) {
		return $override;
	}

	$audio = NULL;
	$default_types = wp_get_audio_extensions();
	$defaults_atts = array(
		'src'      => '',
		'loop'     => '',
		'autoplay' => '',
		'preload'  => 'none',
		'class'    => 'wp-audio-shortcode',
		'style'    => 'width: 100%;'
	);

	foreach ( $default_types as $type ) {
		$defaults_atts[ $type ] = '';
	}

	$atts = shortcode_atts( $defaults_atts, $attr, 'audio' );
	$primary = FALSE;

	if ( ! empty( $atts['src'] ) ) {
		$type = wp_check_filetype( $atts['src'], wp_get_mime_types() );

		if ( ! in_array( strtolower( $type['ext'] ), $default_types ) ) {
			return sprintf( '<a class="wp-embedded-audio" href="%s">%s</a>', esc_url( $atts['src'] ), esc_html( $atts['src'] ) );
		}

		$primary = TRUE;
		array_unshift( $default_types, 'src' );
	} else {
		foreach ( $default_types as $ext ) {
			if ( ! empty( $atts[ $ext ] ) ) {
				$type = wp_check_filetype( $atts[ $ext ], wp_get_mime_types() );

				if ( strtolower( $type['ext'] ) === $ext ) {
					$primary = TRUE;
				}
			}
		}
	}

	if ( ! $primary ) {
		$audios = get_attached_media( 'audio', $post_id );

		if ( empty( $audios ) ) {
			return;
		}

		$audio = reset( $audios );
		$atts['src'] = wp_get_attachment_url( $audio->ID );

		if ( empty( $atts['src'] ) ) {
			return;
		}

		array_unshift( $default_types, 'src' );
	}

	/**
	 * Filters the media library used for the audio shortcode.
	 *
	 * @since 3.6.0
	 *
	 * @param string $library Media library used for the audio shortcode.
	 */
	$library = apply_filters( 'wp_audio_shortcode_library', 'mediaelement' );

	if ( 'mediaelement' === $library && did_action( 'init' ) ) {
		wp_enqueue_style( 'wp-mediaelement' );
		wp_enqueue_script( 'wp-mediaelement' );
	}

	/**
	 * Filters the class attribute for the audio shortcode output container.
	 *
	 * @since 3.6.0
	 * @since 4.9.0 The `$atts` parameter was added.
	 *
	 * @param string $class CSS class or list of space-separated classes.
	 * @param array  $atts  Array of audio shortcode attributes.
	 */
	$atts['class'] = apply_filters( 'wp_audio_shortcode_class', $atts['class'], $atts );

	$html_atts = array(
		'class'    => $atts['class'],
		'id'       => sprintf( 'audio-%d-%d', $post_id, $instance ),
		'loop'     => wp_validate_boolean( $atts['loop'] ),
		'autoplay' => wp_validate_boolean( $atts['autoplay'] ),
		'preload'  => $atts['preload'],
		'style'    => $atts['style']
	);

	// These ones should just be omitted altogether if they are blank.
	foreach ( array( 'loop', 'autoplay', 'preload' ) as $a ) {
		if ( empty( $html_atts[ $a ] ) ) {
			unset( $html_atts[ $a ] );
		}
	}

	$attr_strings = array();

	foreach ( $html_atts as $k => $v ) {
		$attr_strings[] = $k . '="' . esc_attr( $v ) . '"';
	}

	$html = '';

	if ( 'mediaelement' === $library && 1 === $instance ) {
		$html .= "<!--[if lt IE 9]><script>document.createElement('audio');</script><![endif]-->\n";
	}

	$html .= sprintf( '<audio %s controls="controls">', join( ' ', $attr_strings ) );
	$fileurl = '';
	$source = '<source type="%s" src="%s" />';

	foreach ( $default_types as $fallback ) {
		if ( ! empty( $atts[ $fallback ] ) ) {
			if ( empty( $fileurl ) ) {
				$fileurl = $atts[ $fallback ];
			}

			$type = wp_check_filetype( $atts[ $fallback ], wp_get_mime_types() );
			$url = add_query_arg( '_', $instance, $atts[ $fallback ] );
			$html .= sprintf( $source, $type['type'], esc_url( $url ) );
		}
	}

	if ( 'mediaelement' === $library ) {
		$html .= wp_mediaelement_fallback( $fileurl );
	}

	$html .= '</audio>';

	/**
	 * Filters the audio shortcode output.
	 *
	 * @since 3.6.0
	 *
	 * @param string $html    Audio shortcode HTML output.
	 * @param array  $atts    Array of audio shortcode attributes.
	 * @param string $audio   Audio file.
	 * @param int    $post_id Post ID.
	 * @param string $library Media library used for the audio shortcode.
	 */
	return apply_filters( 'wp_audio_shortcode', $html, $atts, $audio, $post_id, $library );
}

add_shortcode( 'audio', 'wp_audio_shortcode' );

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
 * Builds the Video shortcode output.
 *
 * This implements the functionality of the Video Shortcode for displaying WordPress mp4s in a post.
 *
 * @since     3.6.0
 * @global    int $content_width
 * @staticvar int $instance
 *
 * @param  array       $attr {
 *     Attributes of the shortcode.
 *
 *     @type string $src      URL to the source of the video file.
 *                            Default empty.
 *     @type int    $height   Height of the video embed in pixels.
 *                            Default 360.
 *     @type int    $width    Width of the video embed in pixels.
 *                            Default $content_width or 640.
 *     @type string $poster   The 'poster' attribute for the `<video>` element.
 *                            Default empty.
 *     @type string $loop     The 'loop' attribute for the `<video>` element.
 *                            Default empty.
 *     @type string $autoplay The 'autoplay' attribute for the `<video>` element.
 *                            Default empty.
 *     @type string $preload  The 'preload' attribute for the `<video>` element.
 *                            Default 'metadata'.
 *     @type string $class    The 'class' attribute for the `<video>` element.
 *                            Default 'wp-video-shortcode'.
 * }
 * @param  string      $content Shortcode content.
 * @return string|void HTML content to display video.
 */
function wp_video_shortcode( $attr, $content = '' )
{
	global $content_width;

	$post_id = get_post()
		? get_the_ID()
		: 0;

	static $instance = 0;
	$instance++;

	/**
	 * Filters the default video shortcode output.
	 *
	 * If the filtered output isn't empty, it will be used instead of generating the default video template.
	 *
	 * @since 3.6.0
	 * @see   wp_video_shortcode()
	 *
	 * @param string $html     Empty variable to be replaced with shortcode markup.
	 * @param array  $attr     Attributes of the shortcode.
	 *                         @see wp_video_shortcode()
	 * @param string $content  Video shortcode content.
	 * @param int    $instance Unique numeric ID of this video shortcode instance.
	 */
	$override = apply_filters( 'wp_video_shortcode_override', '', $attr, $content, $instance );

	if ( '' !== $override ) {
		return $override;
	}

	$video = NULL;
	$default_types = wp_get_video_extensions();
	$defaults_atts = array(
		'src'      => '',
		'poster'   => '',
		'loop'     => '',
		'autoplay' => '',
		'preload'  => 'metadata',
		'width'    => 640,
		'height'   => 360,
		'class'    => 'wp-video-shortcode'
	);

	foreach ( $default_types as $type ) {
		$defaults_atts[ $type ] = '';
	}

	$atts = shortcode_atts( $defaults_atts, $attr, 'video' );

	if ( is_admin() ) {
		// Shrink the video so it isn't huge in the admin.
		if ( $atts['width'] > $defaults_atts['width'] ) {
			$atts['height'] = round( ( $atts['height'] * $defaults_atts['width'] ) / $atts['width'] );
			$atts['width'] = $defaults_atts['width'];
		}
	} else {
		// If the video is bigger than the theme.
		if ( ! empty( $content_width ) && $atts['width'] > $content_width ) {
			$atts['height'] = round( ( $atts['height'] * $content_width ) / $atts['width'] );
			$atts['width'] = $content_width;
		}
	}

	$is_vimeo = $is_youtube = FALSE;
	$yt_pattern = '#^https?://(?:www\.)?(?:youtube\.com/watch|youtu\.be/)#';
	$vimeo_pattern = '#^https?://(.+\.)?vimeo\.com/.*#';
	$primary = FALSE;

	if ( ! empty( $atts['src'] ) ) {
		$is_vimeo = preg_match( $vimeo_pattern, $atts['src'] );
		$is_youtube = preg_match( $yt_pattern, $atts['src'] );

		if ( ! $is_youtube && ! $is_vimeo ) {
			$type = wp_check_filetype( $atts['src'], wp_get_mime_types() );

			if ( ! in_array( strtolower( $type['ext'] ), $default_types ) ) {
				return sprintf( '<a class="wp-embedded-video" href="%s">%s</a>', esc_url( $atts['src'] ), esc_html( $atts['src'] ) );
			}
		}

		if ( $is_vimeo ) {
			wp_enqueue_script( 'mediaelement-vimeo' );
		}

		$primary = TRUE;
		array_unshift( $default_types, 'src' );
	} else {
		foreach ( $default_types as $ext ) {
			if ( ! empty( $atts[ $ext ] ) ) {
				$type = wp_check_filetype( $atts[ $ext ], wp_get_mime_types() );

				if ( strtolower( $type['ext'] ) === $ext ) {
					$primary = TRUE;
				}
			}
		}
	}

	if ( ! $primary ) {
		$videos = get_attached_media( 'video', $post_id );

		if ( empty( $videos ) ) {
			return;
		}

		$video = reset( $videos );
		$atts['src'] = wp_get_attachment_url( $video->ID );

		if ( empty( $atts['src'] ) ) {
			return;
		}

		array_unshift( $default_types, 'src' );
	}

	/**
	 * Filters the media library used for the video shortcode.
	 *
	 * @since 3.6.0
	 *
	 * @param string $library Media library used for the video shortcode.
	 */
	$library = apply_filters( 'wp_video_shortcode_library', 'mediaelement' );

	if ( 'mediaelement' === $library && did_action( 'init' ) ) {
		wp_enqueue_style( 'wp-mediaelement' );
		wp_enqueue_script( 'wp-mediaelement' );
		wp_enqueue_script( 'mediaelement-vimeo' );
	}

	// Mediaelement has issues with some URL formats for Vimeo and YouTube, so update the URL to prevent the ME.js player from breaking.
	if ( 'mediaelement' === $library ) {
		if ( $is_youtube ) {
			// Remove `feature` query arg and force SSL - see #40866.
			$atts['src'] = remove_query_arg( 'feature', $atts['src'] );
			$atts['src'] = set_url_scheme( $atts['src'], 'https' );
		} elseif ( $is_vimeo ) {
			// Remove all query arguments and force SSL - see #40866.
			$parsed_vimeo_url = wp_parse_url( $atts['src'] );
			$vimeo_src = 'https://' . $parsed_vimeo_url['host'] . $parsed_vimeo_url['path'];

			// Add loop param for mejs bug - see #40977, not needed after #39686.
			$loop = $atts['loop']
				? 1
				: 0;

			$atts['src'] = add_query_arg( 'loop', $loop, $vimeo_src );
		}
	}

	/**
	 * Filters the class attribute for the video shortcode output container.
	 *
	 * @since 3.6.0
	 * @since 4.9.0 The `$atts` parameter was added.
	 *
	 * @param string $class CSS class or list of space-separated classes.
	 * @param array  $atts  Array of video shortcode attributes.
	 */
	$atts['class'] = apply_filters( 'wp_video_shortcode_class', $atts['class'], $atts );

	$html_atts = array(
		'class'    => $atts['class'],
		'id'       => sprintf( 'video-%d-%d', $post_id, $instance ),
		'width'    => absint( $atts['width'] ),
		'height'   => absint( $atts['height'] ),
		'poster'   => esc_url( $atts['poster'] ),
		'loop'     => wp_validate_boolean( $atts['loop'] ),
		'autoplay' => wp_validate_boolean( $atts['autoplay'] ),
		'preload'  => $atts['preload']
	);

	// These ones should just be omitted altogether if they are blank.
	foreach ( array( 'poster', 'loop', 'autoplay', 'preload' ) as $a ) {
		if ( empty( $html_atts[ $a ] ) ) {
			unset( $html_atts[ $a ] );
		}
	}

	$attr_strings = array();

	foreach ( $html_atts as $k => $v ) {
		$attr_strings[] = $k . '="' . esc_attr( $v ) . '"';
	}

	$html = '';

	if ( 'mediaelement' === $library && 1 === $instance ) {
		$html .= "<!--[if lt IE 9]><script>document.createElement('video');</script><![endif]-->\n";
	}

	$html .= sprintf( '<video %s controls="controls">', join( ' ', $attr_strings ) );
	$fileurl = '';
	$source = '<source type="%s" src="%s" />';

	foreach ( $default_types as $fallback ) {
		if ( ! empty( $atts[ $fallback ] ) ) {
			if ( empty( $fileurl ) ) {
				$fileurl = $atts[ $fallback ];
			}

			$type = 'src' === $fallback && $is_youtube
				? array( 'type' => 'video/youtube' )
				: ( 'src' === $fallback && $is_vimeo
					? array( 'type' => 'video/vimeo' )
					: wp_check_filetype( $atts[ $fallback ], wp_get_mime_types() ) );

			$url = add_query_arg( '_', $instance, $atts[ $fallback ] );
			$html .= sprintf( $source, $type['type'], esc_url( $url ) );
		}
	}

	if ( ! empty( $content ) ) {
		if ( FALSE !== strpos( $content, "\n" ) ) {
			$content = str_replace( array( "\r\n", "\n", "\t" ), '', $content );
		}

		$html .= trim( $content );
	}

	if ( 'mediaelement' === $library ) {
		$html .= wp_mediaelement_fallback( $fileurl );
	}

	$html .= '</video>';
	$width_rule = '';

	if ( ! empty( $atts['width'] ) ) {
		$width_rule = sprintf( 'width: %dpx;', $atts['width'] );
	}

	$output = sprintf( '<div style="%s" class="wp-video">%s</div>', $width_rule, $html );

	/**
	 * Filters the output of the video shortcode.
	 *
	 * @since 3.6.0
	 *
	 * @param string $output  Video shortcode HTML output.
	 * @param array  $atts    Array of video shortcode attributes.
	 * @param string $video   Video file.
	 * @param int    $post_id Post ID.
	 * @param string $library Media library used for the video shortcode.
	 */
	return apply_filters( 'wp_video_shortcode', $output, $atts, $video, $post_id, $library );
}

add_shortcode( 'video', 'wp_video_shortcode' );

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

/**
 * Retrieves media attached to the passed post.
 *
 * @since 3.6.0
 *
 * @param  string      $type Mime type.
 * @param  int|WP_Post $post Optional.
 *                           Post ID or WP_Post object.
 *                           Default is global $post.
 * @return array       Found attachments.
 */
function get_attached_media( $type, $post = 0 )
{
	if ( ! $post = get_post( $post ) ) {
		return array();
	}

	$args = array(
		'post_parent'    => $post->ID,
		'post_type'      => 'attachment',
		'post_mime_type' => $type,
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC'
	);

	/**
	 * Filters arguments used to retrieve media attached to the given post.
	 *
	 * @since 3.6.0
	 *
	 * @param array  $args Post query arguments.
	 * @param string $type Mime type of the desired media.
	 * @param mixed  $post Post ID or object.
	 */
	$args = apply_filters( 'get_attached_media_args', $args, $type, $post );

	$children = get_children( $args );

	/**
	 * Filters the list of media attached to the given post.
	 *
	 * @since 3.6.0
	 *
	 * @param array  $children Associative array of media attached to the given post.
	 * @param string $type     Mime type of the media desired.
	 * @param mixed  $post     Post ID or object.
	 */
	return ( array ) apply_filters( 'get_attached_media', $children, $type, $post );
}
