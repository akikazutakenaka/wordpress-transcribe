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
	 */
	public function PasswordHash( $iteration_count_log2, $portable_hashes )
	{
		self::__construct( $iteration_count_log2, $portable_hashes );
	}
}
