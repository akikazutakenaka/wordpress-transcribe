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

		// @NOW 008

		/**
		 * @param POMO_FileReader $reader
		 */
		function import_from_reader( $reader )
		{
			$endian_string = MO::get_byteorder( $reader->readint32() );
			// @NOW 007 -> wp-includes/pomo/mo.php
		}
	}
}
