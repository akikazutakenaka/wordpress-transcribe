<?php
/**
 * Meta API: WP_Metadata_Lazyloader class
 *
 * @package WordPress
 * @subpackage Meta
 * @since 4.5.0
 */

/**
 * Core class used for lazy-loading object metadata.
 *
 * When loading many objects of a given type, such as posts in a WP_Query loop, it often makes
 * sense to prime various metadata caches at the beginning of the loop. This means fetching all
 * relevant metadata with a single database query, a technique that has the potential to improve
 * performance dramatically in some cases.
 *
 * In cases where the given metadata may not even be used in the loop, we can improve performance
 * even more by only priming the metadata cache for affected items the first time a piece of metadata
 * is requested - ie, by lazy-loading it. So, for example, comment meta may not be loaded into the
 * cache in the comments section of a post until the first time get_comment_meta() is called in the
 * context of the comment loop.
 *
 * WP uses the WP_Metadata_Lazyloader class to queue objects for metadata cache priming. The class
 * then detects the relevant get_*_meta() function call, and queries the metadata of all queued objects.
 *
 * Do not access this class directly. Use the wp_metadata_lazyloader() function.
 *
 * @since 4.5.0
 */
class WP_Metadata_Lazyloader {
	// refactored. protected $pending_objects;
	// :
	// refactored. public function __construct() {}

	/**
	 * Adds objects to the metadata lazy-load queue.
	 *
	 * @since 4.5.0
	 *
	 * @param string $object_type Type of object whose meta is to be lazy-loaded. Accepts 'term' or 'comment'.
	 * @param array  $object_ids  Array of object IDs.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function queue_objects( $object_type, $object_ids ) {
		if ( ! isset( $this->settings[ $object_type ] ) ) {
			return new WP_Error( 'invalid_object_type', __( 'Invalid object type' ) );
		}

		$type_settings = $this->settings[ $object_type ];

		if ( ! isset( $this->pending_objects[ $object_type ] ) ) {
			$this->pending_objects[ $object_type ] = array();
		}

		foreach ( $object_ids as $object_id ) {
			// Keyed by ID for faster lookup.
			if ( ! isset( $this->pending_objects[ $object_type ][ $object_id ] ) ) {
				$this->pending_objects[ $object_type ][ $object_id ] = 1;
			}
		}

		add_filter( $type_settings['filter'], $type_settings['callback'] );

		/**
		 * Fires after objects are added to the metadata lazy-load queue.
		 *
		 * @since 4.5.0
		 *
		 * @param array                  $object_ids  Object IDs.
		 * @param string                 $object_type Type of object being queued.
		 * @param WP_Metadata_Lazyloader $lazyloader  The lazy-loader object.
		 */
		do_action( 'metadata_lazyloader_queued_objects', $object_ids, $object_type, $this );
	}

	// refactored. public function reset_queue( $object_type ) {}
	// :
	// refactored. public function lazyload_comment_meta( $check ) {}
}
