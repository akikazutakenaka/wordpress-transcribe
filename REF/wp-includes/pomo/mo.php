<?php
/**
 * Class for working with MO files
 *
 * @version    $Id: mo.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package    pomo
 * @subpackage mo
 */

require_once dirname( __FILE__ ) . '/translations.php';
require_once dirname( __FILE__ ) . '/streams.php';

if ( ! class_exists( 'MO', FALSE ) ) {
	class MO extends Gettext_Translations
	{
		var $_nplurals = 2;

		/**
		 * Loaded MO file.
		 *
		 * @var string
		 */
		private $filename = '';

		/**
		 * Fills up with the entries from MO file $filename
		 *
		 * @param string $filename MO file to load
		 */
		function import_from_file( $filename )
		{
			$reader = new POMO_FileReader( $filename );

			if ( ! $reader->is_resource() )
				return FALSE;

			$this->filename = ( string ) $filename;
			return $this->import_from_reader( $reader );
		}

		/**
		 * @param  int          $magic
		 * @return string|false
		 */
		function get_byteorder( $magic )
		{
			// The magic is 0x950412de

			// Bug in PHP 5.0.2, see https://savannah.nongnu.org/bugs/?func=detailitem&item_id=10565
			$magic_little = ( int ) -1794895138;
			$magic_little_64 = ( int ) 2500072158;

			// 0xde120495
			$magic_big = ( ( int ) -569244523 ) & 0xFFFFFFFF;

			return ( $magic_little == $magic || $magic_little_64 == $magic )
				? 'little'
				: ( ( $magic_big == $magic )
					? 'big'
					: FALSE );
		}

		/**
		 * @param POMO_FileReader $reader
		 */
		function import_from_reader( $reader )
		{
			$endian_string = MO::get_byteorder( $reader->readint32() );

			if ( FALSE === $endian_string )
				return FALSE;

			$reader->setEndian( $endian_string );
			$endian = ( 'big' == $endian_string ) ? 'N' : 'V';
			$header = $reader->read( 24 );

			if ( $reader->strlen( $header ) != 24 )
				return FALSE;

			// Parse header
			$header = unpack( "{$endian}revision/{$endian}total/{$endian}originals_lenghts_addr/{$endian}translations_lenghts_addr/{$endian}hash_length/{$endian}hash_addr", $header );

			if ( ! is_array( $header ) )
				return FALSE;

			// Support revision 0 of MO format specs, only
			if ( $header['revision']] != 0 )
				return FALSE;

			// Seek to data blocks
			$reader->seekto( $header['originals_lenghts_addr'] );
			// @NOW 007
		}
	}
}
