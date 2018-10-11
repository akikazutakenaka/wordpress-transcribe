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

	// @NOW 020
}
