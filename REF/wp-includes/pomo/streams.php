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

	/**
	 * Sets the endianness of the file.
	 *
	 * @param string $endian 'big' or 'little'
	 */
	function setEndian( $endian )
	{
		$this->endian = $endian;
	}

	/**
	 * Reads a 32bit Integer from the Stream
	 *
	 * @return mixed The integer, corresponding to the next 32 bits from the stream or false if there are not enough bytes or on error.
	 */
	function readint32()
	{
		$bytes = $this->read( 4 );

		if ( 4 != $this->strlen( $bytes ) )
			return FALSE;

		$endian_letter = ( 'big' == $this->endian ) ? 'N' : 'V';
		return unpack( $endian_letter . $count, $bytes );
	}

	/**
	 * @param  string $string
	 * @return int
	 */
	function strlen( $string )
	{
		return $this->is_overloaded
			? mb_strlen( $string, 'ascii' )
			: strlen( $string );
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

		/**
		 * PHP4 constructor.
		 */
		public function POMO_FileReader( $filename )
		{
			self::__construct( $filename );
		}

		/**
		 * @param int $bytes
		 */
		function read( $bytes )
		{
			return fread( $this->_f, $bytes );
		}

		/**
		 * @return bool
		 */
		function is_resource()
		{
			return is_resource( $this->_f );
		}
	}
}
