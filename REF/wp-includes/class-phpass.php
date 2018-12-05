<?php
/**
 * Portable PHP password hashing framework.
 *
 * @package phpass
 * @since   2.5.0
 * @version 0.3 / WordPress
 * @link    http://www.openwall.com/phpass/
 */

/**
 * Written by Solar Designer <solar@openwall.com> in 2004-2006 and placed in the public domain.
 * Revised in subsequent years, still public domain.
 *
 * There's absolutely no warranty.
 *
 * Please be sure to update the Version line if you edit this file in any way.
 * It is suggested that you leave the main version number intact, but indicate your project name (after the slash) and add your own revision information.
 *
 * Please do not change the "private" password hashing method implemented in here, thereby making your hashes incompatible.
 * However, if you must, please change the hash type identifier (the "$P$") to something different.
 *
 * Obviously, since this code is in the public domain, the above are not requirements (there can be none), but merely suggestions.
 */

/**
 * Portable PHP password hashing framework.
 *
 * @package phpass
 * @version 0.3 / WordPress
 * @link    http://www.openwall.com/phpass/
 * @since   2.5.0
 */
class PasswordHash
{
	var $itoa64;
	var $iteration_count_log2;
	var $portable_hashes;
	var $random_state;

/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/formatting.php: wp_trim_excerpt( [string $text = ''] )
 * <-......: wp-includes/post-template.php: get_the_content( [string $more_link_text = NULL [, bool $strip_teaser = FALSE]] )
 * <-......: wp-includes/post-template.php: post_password_required( [int|WP_Post|null $post = NULL] )
 * @NOW 008: wp-includes/class-phpass.php::__construct( int $iteration_count_log2, bool $portable_hashes )
 */
}
