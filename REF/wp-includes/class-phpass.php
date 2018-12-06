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
	 * PHP5 constructor.
	 *
	 * @param int  $iteration_count_log2
	 * @param bool $portable_hashes
	 */
	function __construct( $iteration_count_log2, $portable_hashes )
	{
		$this->itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

		if ( $iteration_count_log2 < 4 || $iteration_count_log2 > 31 ) {
			$iteration_count_log2 = 8;
		}

		$this->iteration_count_log2 = $iteration_count_log2;
		$this->portable_hashes = $portable_hashes;
		$this->random_state = microtime() . uniqid( rand(), TRUE ); // Removed getmypid() for compatibility reasons.
	}

	/**
	 * PHP4 constructor.
	 *
	 * @param int  $iteration_count_log2
	 * @param bool $portable_hashes
	 */
	public function PasswordHash( $iteration_count_log2, $portable_hashes )
	{
		self::__construct( $iteration_count_log2, $portable_hashes );
	}

/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/formatting.php: wp_trim_excerpt( [string $text = ''] )
 * <-......: wp-includes/post-template.php: get_the_content( [string $more_link_text = NULL [, bool $strip_teaser = FALSE]] )
 * <-......: wp-includes/post-template.php: post_password_required( [int|WP_Post|null $post = NULL] )
 * <-......: wp-includes/class-phpass.php: PasswordHash::CheckPassword( string $password, string $stored_hash )
 * <-......: wp-includes/class-phpass.php: PasswordHash::crypt_private( string $password, string $setting )
 * @NOW 010: wp-includes/class-phpass.php: PasswordHash::encode64( string $input, int $count )
 */

	/**
	 * @param  string $password
	 * @param  string $setting
	 * @return string
	 */
	function crypt_private( $password, $setting )
	{
		$output = '*0';

		if ( substr( $setting, 0, 2 ) == $output ) {
			$output = '*1';
		}

		$id = substr( $setting, 0, 3 );

		// We use "$P$", phpBB3 uses "$H$" for the same thing.
		if ( $id != '$P$' && $id != '$H$' ) {
			return $output;
		}

		$count_log2 = strpos( $this->itoa64, $setting[3] );

		if ( $count_log2 < 7 || $count_log2 > 30 ) {
			return $output;
		}

		$count = 1 << $count_log2;
		$salt = substr( $setting, 4, 8 );

		if ( strlen( $salt ) != 8 ) {
			return $output;
		}

		/**
		 * We're kind of forced to use MD5 here since it's the only cryptographic primitive available in all versions of PHP currently in use.
		 * To implement our own low-level crypto in PHP would result in much worse performance and consequently in lower iteration counts and hashes that are quicker to crack (by non-PHP code).
		 */
		if ( PHP_VERSION >= '5' ) {
			$hash = md5( $salt . $password, TRUE );

			do {
				$hash = md5( $hash . $password, TRUE );
			} while ( --$count );
		} else {
			$hash = pack( 'H*', md5( $salt . $password ) );

			do {
				$hash = pack( 'H*', md5( $hash . $password ) );
			} while ( --$count );
		}

		$output = substr( $setting, 0, 12 );
		$output .= $this->encode64( $hash, 16 );
/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/formatting.php: wp_trim_excerpt( [string $text = ''] )
 * <-......: wp-includes/post-template.php: get_the_content( [string $more_link_text = NULL [, bool $strip_teaser = FALSE]] )
 * <-......: wp-includes/post-template.php: post_password_required( [int|WP_Post|null $post = NULL] )
 * <-......: wp-includes/class-phpass.php: PasswordHash::CheckPassword( string $password, string $stored_hash )
 * @NOW 009: wp-includes/class-phpass.php: PasswordHash::crypt_private( string $password, string $setting )
 * ......->: wp-includes/class-phpass.php: PasswordHash::encode64( string $input, int $count )
 */
	}

	/**
	 * @param  string $password
	 * @param  string $stored_hash
	 * @return bool
	 */
	function CheckPassword( $password, $stored_hash )
	{
		if ( strlen( $password ) > 4096 ) {
			return FALSE;
		}

		$hash = $this->crypt_private( $password, $stored_hash );
/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/formatting.php: wp_trim_excerpt( [string $text = ''] )
 * <-......: wp-includes/post-template.php: get_the_content( [string $more_link_text = NULL [, bool $strip_teaser = FALSE]] )
 * <-......: wp-includes/post-template.php: post_password_required( [int|WP_Post|null $post = NULL] )
 * @NOW 008: wp-includes/class-phpass.php: PasswordHash::CheckPassword( string $password, string $stored_hash )
 * ......->: wp-includes/class-phpass.php: PasswordHash::crypt_private( string $password, string $setting )
 */
	}
}
