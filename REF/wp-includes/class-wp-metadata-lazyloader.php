<?php
/**
 * Meta API: WP_Metadata_Lazyloader class
 *
 * @package    WordPress
 * @subpackage Meta
 * @since      4.5.0
 */

/**
 * Core class used for lazy-loading object metadata.
 *
 * When loading many objects of a given type, such as posts in a WP_Query loop, it often makes sense to prime various metadata caches at the beginning of the loop.
 * This means fetching all relevant metadata with a single database query, a technique that has the potential to improve performance dramatically in some cases.
 *
 * In cases where the given metadata may not even be used in the loop, we can improve performance even more by only priming the metadata cache for affected items the first time a piece of metadata is required - ie, by lazy-loading it.
 * So, for example, comment meta may not be loaded into the cache in the comments section of a post until the first time get_comment_meta() is called in the context of the comment loop.
 *
 * WP Uses the WP_Metadata_Lazyloader class to queue objects for metadata cache priming.
 * The class then detects the relevant get_*_meta() function call, and queries the metadata of all queued objects.
 *
 * Do not access this class directly.
 * Use the wp_metadata_lazyloader() function.
 *
 * @since 4.5.0
 */
class WP_Metadata_Lazyloader
{
	/**
	 * Pending objects queue.
	 *
	 * @since 4.5.0
	 *
	 * @var array
	 */
	protected $pending_objects;

	/**
	 * Settings for supported object types.
	 *
	 * @since 4.5.0
	 *
	 * @var array
	 */
	protected $settings = array();

/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/class-wp-query.php
 * <- wp-includes/comment.php
 * <- wp-includes/meta.php
 * @NOW 012: wp-includes/class-wp-metadata-lazyloader.php
 */
}
