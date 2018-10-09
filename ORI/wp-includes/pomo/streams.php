<?php
/**
 * Classes, which help reading streams of data from files.
 * Based on the classes from Danilo Segan <danilo@kvota.net>
 *
 * @version $Id: streams.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package pomo
 * @subpackage streams
 */

if ( ! class_exists( 'POMO_Reader', false ) ):
class POMO_Reader {
	// refactored. var $endian = 'little';
	// refactored. var $_post = '';
	// refactored. function __construct() {}
	// refactored. public function POMO_Reader() {}
	// refactored. function setEndian($endian) {}
	// refactored. function readint32() {}

	/**
	 * Reads an array of 32-bit Integers from the Stream
	 *
	 * @param integer count How many elements should be read
	 * @return mixed Array of integers or false if there isn't
	 * 	enough data or on error
	 */
	function readint32array($count) {
		$bytes = $this->read(4 * $count);
		if (4*$count != $this->strlen($bytes))
			return false;
		$endian_letter = ('big' == $this->endian)? 'N' : 'V';
		return unpack($endian_letter.$count, $bytes);
	}

	// refactored. function substr($string, $start, $length) {}
	// refactored. function strlen($string) {}

	/**
	 * @param string $string
	 * @param int    $chunk_size
	 * @return array
	 */
	function str_split($string, $chunk_size) {
		if (!function_exists('str_split')) {
			$length = $this->strlen($string);
			$out = array();
			for ($i = 0; $i < $length; $i += $chunk_size)
				$out[] = $this->substr($string, $i, $chunk_size);
			return $out;
		} else {
			return str_split( $string, $chunk_size );
		}
	}

	/**
	 * @return int
	 */
	function pos() {
		return $this->_pos;
	}

	/**
	 * @return true
	 */
	function is_resource() {
		return true;
	}

	/**
	 * @return true
	 */
	function close() {
		return true;
	}
}
endif;

if ( ! class_exists( 'POMO_FileReader', false ) ):
class POMO_FileReader extends POMO_Reader {
	// refactored. function __construct( $filename ) {}
	// refactored. public function POMO_FileReader( $filename ) {}
	// refactored. function read($bytes) {}
	// refactored. function seekto($pos) {}
	// refactored. function is_resource() {}

	/**
	 * @return bool
	 */
	function feof() {
		return feof($this->_f);
	}

	/**
	 * @return bool
	 */
	function close() {
		return fclose($this->_f);
	}

	/**
	 * @return string
	 */
	function read_all() {
		$all = '';
		while ( !$this->feof() )
			$all .= $this->read(4096);
		return $all;
	}
}
endif;

if ( ! class_exists( 'POMO_StringReader', false ) ):
/**
 * Provides file-like methods for manipulating a string instead
 * of a physical file.
 */
class POMO_StringReader extends POMO_Reader {

	var $_str = '';

	/**
	 * PHP5 constructor.
	 */
	function __construct( $str = '' ) {
		parent::POMO_Reader();
		$this->_str = $str;
		$this->_pos = 0;
	}

	/**
	 * PHP4 constructor.
	 */
	public function POMO_StringReader( $str = '' ) {
		self::__construct( $str );
	}

	/**
	 * @param string $bytes
	 * @return string
	 */
	function read($bytes) {
		$data = $this->substr($this->_str, $this->_pos, $bytes);
		$this->_pos += $bytes;
		if ($this->strlen($this->_str) < $this->_pos) $this->_pos = $this->strlen($this->_str);
		return $data;
	}

	/**
	 * @param int $pos
	 * @return int
	 */
	function seekto($pos) {
		$this->_pos = $pos;
		if ($this->strlen($this->_str) < $this->_pos) $this->_pos = $this->strlen($this->_str);
		return $this->_pos;
	}

	/**
	 * @return int
	 */
	function length() {
		return $this->strlen($this->_str);
	}

	/**
	 * @return string
	 */
	function read_all() {
		return $this->substr($this->_str, $this->_pos, $this->strlen($this->_str));
	}

}
endif;

if ( ! class_exists( 'POMO_CachedFileReader', false ) ):
/**
 * Reads the contents of the file in the beginning.
 */
class POMO_CachedFileReader extends POMO_StringReader {
	/**
	 * PHP5 constructor.
	 */
	function __construct( $filename ) {
		parent::POMO_StringReader();
		$this->_str = file_get_contents($filename);
		if (false === $this->_str)
			return false;
		$this->_pos = 0;
	}

	/**
	 * PHP4 constructor.
	 */
	public function POMO_CachedFileReader( $filename ) {
		self::__construct( $filename );
	}
}
endif;

if ( ! class_exists( 'POMO_CachedIntFileReader', false ) ):
/**
 * Reads the contents of the file in the beginning.
 */
class POMO_CachedIntFileReader extends POMO_CachedFileReader {
	/**
	 * PHP5 constructor.
	 */
	public function __construct( $filename ) {
		parent::POMO_CachedFileReader($filename);
	}

	/**
	 * PHP4 constructor.
	 */
	function POMO_CachedIntFileReader( $filename ) {
		self::__construct( $filename );
	}
}
endif;

