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

// @NOW 020
