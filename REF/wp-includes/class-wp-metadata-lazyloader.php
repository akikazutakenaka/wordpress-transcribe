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
	 * Constructor.
	 *
	 * @since 4.5.0
	 */
	public function __construct()
	{
		$this->settings = array(
			'term'    => array(
				'filter'   => 'get_term_metadata',
				'callback' => array( $this, 'lazyload_term_meta' )
			),
			'comment' => array(
				'filter'   => 'get_comment_metadata',
				'callback' => array( $this, 'lazyload_comment_meta' )
			) );
	}

	/**
	 * Resets lazy-load queue for a given object type.
	 *
	 * @since 4.5.0
	 *
	 * @param  string
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function reset_queue( $object_type )
	{
		if ( ! isset( $this->settings[ $object_type ] ) ) {
			return new WP_Error( 'invalid_object_type', __( 'Invalid object type' ) );
		}

		$type_settings = $this->settings[ $object_type ];
		$this->pending_objects[ $object_type ] = array();
		remove_filter( $type_settings['filter'], $type_settings['callback'] );
	}

	/**
	 * Lazy-loads term meta for queued terms.
	 *
	 * This method is public so that it can be used as a filter callback.
	 * As a rule, there is no need to invoke it directly.
	 *
	 * @since 4.5.0
	 *
	 * @param  mixed $check The `$check` param passed from the 'get_term_metadata' hook.
	 * @return mixed In order not to short-circuit `get_metadata()`.
	 *               Generally, this is `null`, but it could be another value if filtered by a plugin.
	 */
	public function lazyload_term_meta( $check )
	{
		if ( ! empty( $this->pending_objects['term'] ) ) {
			update_termmeta_cache( array_keys( $this->pending_objects['term'] ) );

			// No need to run again for this set of terms.
			$this->reset_queue( 'term' );
		}

		return $check;
	}

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
