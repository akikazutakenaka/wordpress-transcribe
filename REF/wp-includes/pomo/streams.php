<?php
/**
 * Classes, which help reading streams of data from files.
 * Based on the classes from Danilo Segan <danilo@kvota.net>
 *
 * @version    $Id: streams.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package    pomo
 * @subpackage streams
 */

if ( ! class_exists( 'POMO_Reader', FALSE ) ) {
	class POMO_Reader
	{
		var $endian = 'little';
		var $_post = '';
	}

	/**
	 * PHP5 constructor.
	 */
	function __construct()
	{
		$this->is_overloaded = ( ( ini_get( "mbstring.func_overload" ) & 2 ) != 0 ) && function_exists( 'mb_substr' );
		$this->_pos = 0;
	}

	/**
	 * PHP4 constructor.
	 */
	public function POMO_Reader()
	{
		self::__construct();
	}
}

if ( ! class_exists( 'POMO_FileReader', FALSE ) ) {
	class POMO_FileReader extends POMO_Reader
	{
		/**
		 * @param string $filename
		 */
		function __construct( $filename )
		{
			parent::POMO_Reader();
			$this->_f = fopen( $filename, 'rb' );
		}

		// @NOW 008
	}
}
