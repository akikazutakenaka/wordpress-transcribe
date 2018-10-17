<?php
/**
 * WordPress Feed API
 *
 * Many of the functions used in here belong in The Loop, or The Loop for the Feeds.
 *
 * @package    WordPress
 * @subpackage Feed
 * @since      2.1.0
 */

/**
 * Retrieve the default feed.
 *
 * The default feed is 'rss2', unless a plugin changes it through the {@see 'default_feed'} filter.
 *
 * @since 2.5.0
 *
 * @return string Default feed, or for example 'rss2', 'atom', etc.
 */
function get_default_feed()
{
	/**
	 * Filters the default feed type.
	 *
	 * @since 2.5.0
	 *
	 * @param string $feed_type Type of default feed.
	 *                          Possible values include 'rss2', 'atom'.
	 *                          Default 'rss2'.
	 */
	$default_feed = apply_filters( 'default_feed', 'rss2' );

	return 'rss' == $default_feed
		? 'rss2'
		: $default_feed;
}
