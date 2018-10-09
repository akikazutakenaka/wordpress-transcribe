<?php
/**
 * These functions are needed to load WordPress.
 *
 * @package WordPress
 */

/**
 * Converts a shorthand byte value to an integer byte value.
 *
 * @since 2.3.0
 * @since 4.6.0 Moved from media.php to load.php.
 * @link  https://secure.php.net/manual/en/function.ini-get.php
 * @link  https://secure.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
 *
 * @param  string $value A (PHP ini) byte value, either shorthand or ordinary.
 * @return int    An integer byte value.
 */
function wp_convert_hr_to_bytes( $value )
{
	$value = strtolower( trim( $value ) );
	$bytes = ( int ) $value;

	if ( FALSE !== strpos( $value, 'g' ) )
		$bytes *= GB_IN_BYTES;
	elseif ( FALSE !== strpos( $value, 'm' ) )
		$bytes *= MB_IN_BYTES;
	elseif ( FALSE !== strpos( $value, 'k' ) )
		$bytes *= KB_IN_BYTES;

	// Deal with large (float) values which run into the maximum integer size.
	return min( $bytes, PHP_INT_MAX );
}
