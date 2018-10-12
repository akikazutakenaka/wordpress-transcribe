<?php
/**
 * Main WordPress Formatting API.
 *
 * Handles many functions for formatting output.
 *
 * @package WordPress
 */

/**
 * Sanitizes a username, stripping out unsafe characters.
 *
 * Removes tags, octets, entities, and if strict is enabled, will only keep alphanumeric, _, space, ., -, @.
 * After sanitizing, it passes the username, raw username (the username in the parameter), and the value of $strict as paramters for the {@see 'sanitize_user'} filter.
 *
 * @since 2.0.0
 *
 * @param  string $username The username to be sanitized.
 * @param  bool   $strict   If set limits $username to specific characters.
 *                          Default false.
 * @return string The sanitized username, after passing through filters.
 */
function sanitize_user( $username, $strict = FALSE )
{
	$raw_username = $username;
	$username = wp_strip_all_tags( $username );
// @NOW 017 -> wp-includes/formatting.php
}

/**
 * Sanitizes a string key.
 *
 * Keys are used as internal identifiers.
 * Lowercase alphanumeric characters, dashes and underscores are allowed.
 *
 * @since 3.0.0
 *
 * @param  string $key String key
 * @return string Sanitized key
 */
function sanitize_key( $key )
{
	$raw_key = $key;
	$key = strtolower( $key );
	$key = preg_replace( '/[^a-z0-9_\-]/', '', $key );

	/**
	 * Filters a sanitized key string.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key     Sanitized key.
	 * @param string $raw_key The key prior to sanitization.
	 */
	return apply_filters( 'sanitize_key', $key, $raw_key );
}

/**
 * Removes trailing forward slashes and backslashes if they exist.
 *
 * The primary use of this is for paths and thus should be used for paths.
 * It is not restricted to paths and offers no specific path support.
 *
 * @since 2.2.0
 *
 * @param  string $string What to remove the trailing slashes from.
 * @return string String without the trailing slashes.
 */
function untrailingslashit( $string )
{
	return rtrim( $string, '/\\' );
}

// @NOW 018
