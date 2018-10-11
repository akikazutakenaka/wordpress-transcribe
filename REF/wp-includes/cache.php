<?php
/**
 * Object Cache API
 *
 * @link       https://codex.wordpress.org/Class_Reference/WP_Object_Cache
 * @package    WordPress
 * @subpackage Cache
 */

/**
 * Retrieves the cache contents from the cache by key and group.
 *
 * @since  2.0.0
 * @see    WP_Object_Cache::get()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param  int|string $key   The key under which the cache contents are stored.
 * @param  string     $group Optional.
 *                           Where the cache contents are grouped.
 *                           Default empty.
 * @param  bool       $force Optional.
 *                           Whether to force an update of the local cache from the persistent cache.
 *                           Default false.
 * @param  bool       $found Optional.
 *                           Whether the key was found in the cache (passed by reference).
 *                           Disambiguates a return of false, a storable value.
 *                           Default null.
 * @return bool|mixed False on failure to retrieve contents or the cache contents on success.
 */
function wp_cache_get( $key, $group = '', $force = FALSE, &$found = NULL )
{
	global $wp_object_cache;
	return $wp_object_cache->get( $key, $group, $force, $found );
}

/**
 * Core class that implements an object cache.
 *
 * The WordPress Object Cache is used to save on trips to the database.
 * The Object Cache stores all of the cache data to memory and makes the cache contents available by using a key, which is used to name and later retrieve the cache contents.
 *
 * The Object Cache can be replaced by other caching mechanisms by placing files in the wp-content folder which is looked at in wp-settings.
 * If that file exists, then this file will not be included.
 *
 * @since 2.0.0
 */
class WP_Object_Cache
{
	/**
	 * Holds the cached objects.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private $cache = [];

	/**
	 * The amount of times the cache data was already stored in the cache.
	 *
	 * @since 2.5.0
	 *
	 * @var int
	 */
	public $cache_hits = 0;

	/**
	 * Amount of times the cache did not have the request in cache.
	 *
	 * @since 2.0.0
	 *
	 * @var int
	 */
	public $cache_misses = 0;

	/**
	 * List of global cache groups.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	protected $global_groups = [];

	/**
	 * The blog prefix to prepend to keys in non-global groups.
	 *
	 * @since 3.5.0
	 *
	 * @var int
	 */
	private $blog_prefix;

	/**
	 * Holds the value of is_multisite().
	 *
	 * @since 3.5.0
	 *
	 * @var bool
	 */
	private $multisite;

	/**
	 * Retrieves the cache contents, if it exists.
	 *
	 * The contents will be first attempted to be retrieved by searching by the key in the cache group.
	 * If the cache is hit (success) then the contents are returned.
	 *
	 * On failure, the number of cache misses will be incremented.
	 *
	 * @since 2.0.0
	 *
	 * @param  int|string  $key   What the contents in the cache are called.
	 * @param  string      $group Optional.
	 *                            Where the cache contents are grouped.
	 *                            Default 'default'.
	 * @param  bool        $force Optional.
	 *                            Unused.
	 *                            Whether to force a refetch rather than relying on the local cache.
	 *                            Default false.
	 * @param  bool        $found Optional.
	 *                            Whether the key was found in the cache (passed by reference).
	 *                            Disambiguates a return of false, a storable value.
	 *                            Default null.
	 * @return false|mixed False on failure to retrieve contents or the cache contents on success.
	 */
	public function get( $key, $group = 'default', $force = FALSE, &$found = NULL )
	{
		if ( empty( $group ) )
			$group = 'default';

		if ( $this->multisite && ! isset( $this->global_groups[$group] ) )
			$key = $this->blog_prefix . $key;

		if ( $this->_exists( $key, $group ) ) {
			// @NOW 020 -> wp-includes/cache.php
		}
	}

	// @NOW 021

	/**
	 * Sets up object properties; PHP5 style constructor.
	 *
	 * @since 2.0.8
	 */
	public function __construct()
	{
		$this->multisite = is_multisite();
		$this->blog_prefix = $this->multisite ? get_current_blog_id() . ':' : '';

		/**
		 * @todo This should be moved to the PHP4 style constructor, PHP5 already calls __destruct()
		 */
		register_shutdown_function( [$this, '__destruct'] );
	}

	/**
	 * Saves the object cache before object is completely destroyed.
	 *
	 * Called upon object destruction, which should be when PHP ends.
	 *
	 * @since 2.0.8
	 *
	 * @return true Always returns true.
	 */
	public function __destruct()
	{
		return TRUE;
	}
}
