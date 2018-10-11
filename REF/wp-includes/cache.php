<?php
/**
 * Object Cache API
 *
 * @link       https://codex.wordpress.org/Class_Reference/WP_Object_Cache
 * @package    WordPress
 * @subpackage Cache
 */

/**
 * Adds data to the cache, if the cache key doesn't already exist.
 *
 * @since  2.0.0
 * @see    WP_Object_Cache::add()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param  int|string $key    The cache key to use for retrieval later.
 * @param  mixed      $data   The data to add to the cache.
 * @param  string     $group  Optional.
 *                            The group to add the cache to.
 *                            Enables the same key to be used across groups.
 *                            Default empty.
 * @param  int        $expire Optional.
 *                            When the cache data should expire, in seconds.
 *                            Default 0 (no expiration).
 * @return bool       False if cache key and group already exist, true on success.
 */
function wp_cache_add( $key, $data, $group = '', $expire = 0 )
{
	global $wp_object_cache;
	return $wp_object_cache->add( $key, $data, $group, ( int ) $expire );
}

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
	 * Adds data to the cache if it doesn't already exist.
	 *
	 * @since 2.0.0
	 * @uses  WP_Object_Cache::_exists() Checks to see if the cache already has data.
	 * @uses  WP_Object_Cache::set()     Sets the data after the checking the cache contents existence.
	 *
	 * @param  int|string $key    What to call the contents in the cache.
	 * @param  mixed      $data   The contents to store in the cache.
	 * @param  string     $group  Optional.
	 *                            Where to group the cache contents.
	 *                            Default 'default'.
	 * @param  int        $expire Optional.
	 *                            When to expire the cache contents.
	 *                            Default 0 (no expiration).
	 * @return bool       False if cache key and group already exist, true on success.
	 */
	public function add( $key, $data, $group = 'default', $expire = 0 )
	{
		if ( wp_suspend_cache_addition() ) {
			return FALSE;
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$id = $key;

		if ( $this->multisite && ! isset( $this->global_groups[$group] ) ) {
			$id = $this->blog_prefix . $key;
		}

		if ( $this->_exists( $id, $group ) ) {
			return FALSE;
		}

		return $this->set( $key, $data, $group, ( int ) $expire );
	}

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
		if ( empty( $group ) ) {
			$group = 'default';
		}

		if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) ) {
			$key = $this->blog_prefix . $key;
		}

		if ( $this->_exists( $key, $group ) ) {
			$found = TRUE;
			$this->cache_hits += 1;
			return is_object( $this->cache[ $group ][ $key ] )
				? clone $this->cache[ $group ][ $key ]
				: $this->cache[ $group ][ $key ];
		}

		$found = FALSE;
		$this->cache_misses += 1;
		return FALSE;
	}

	/**
	 * Sets the data contents into the cache.
	 *
	 * The cache contents is grouped by the $group parameter followed by the $key.
	 * This allows for duplicate ids in unique groups.
	 * Therefore, naming of the group should be used with care and should follow normal function naming guidelines outside of core WordPress usage.
	 *
	 * The $expire parameter is not used, because the cache will automatically expire for each time a page is accessed and PHP finishes.
	 * The method is more for cache plugins which use files.
	 *
	 * @since 2.0.0
	 *
	 * @param  int|string $key    What to call the contents in the cache.
	 * @param  mixed      $data   The contents to store in the cache.
	 * @param  string     $group  Optional.
	 *                            Where to group the cache contents.
	 *                            Default 'default'.
	 * @param  int        $expire Not used.
	 * @return true       Always returns true.
	 */
	public function set( $key, $data, $group = 'default', $expire = 0 )
	{
		if ( empty( $group ) ) {
			$group = 'default';
		}

		if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) ) {
			$key = $this->blog_prefix . $key;
		}

		if ( is_object( $data ) ) {
			$data = clone $data;
		}

		$this->cache[ $group ][ $key ] = $data;
		return TRUE;
	}

	/**
	 * Serves as a utility function to determine whether a key exists in the cache.
	 *
	 * @since 3.4.0
	 *
	 * @param  int|string $key   Cache key to check for existence.
	 * @param  string     $group Cache group for the key existence check.
	 * @return bool       Whether the key exists in the cache for the given group.
	 */
	protected function _exists( $key, $group )
	{
		return isset( $this->cache[ $group ] )
		    && ( isset( $this->cache[ $group ][ $key ] ) || array_key_exists( $key, $this->cache[ $group ] ) );
	}

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
