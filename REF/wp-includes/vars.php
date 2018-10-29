<?php
/**
 * Creates common globals for the rest of WordPress.
 *
 * Sets $pagenow global which is the current page.
 * Checks for the browser to set which one is currently being used.
 *
 * Detects which user environment WordPress is being used on.
 * Only attempts to check for Apache, Nginx and IIS -- three web server with known pretty permalink capability.
 *
 * Note: Though Nginx is detected, WordPress does not currently generate rewrite rules for it.
 * See https://codex.wordpress.org/Nginx
 *
 * @package WordPress
 */

global $pagenow, $is_lynx, $is_gecko, $is_winIE, $is_macIE, $is_opera, $is_NS4, $is_safari, $is_chrome, $is_iphone, $is_IE, $is_edge, $is_apache, $is_IIS, $is_iis7, $is_nginx;

// On which pages are we?
if ( is_admin() ) {
	// wp-admin pages are checked more carefully.
	if ( is_network_admin() ) {
// @NOW 011 -> wp-includes/load.php
	}
}
