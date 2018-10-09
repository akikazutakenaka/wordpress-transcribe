<?php
/**
 * Contains Translation_Entry class
 *
 * @version $Id: entry.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package pomo
 * @subpackage entry
 */

if ( ! class_exists( 'Translation_Entry', false ) ):
/**
 * Translation_Entry class encapsulates a translatable string
 */
class Translation_Entry {
	// refactored. var $is_plural = false;
	// refactored. var $context = null;
	// refactored. var $singular = null;
	// refactored. var $plural = null;
	// refactored. var $translations = array();
	// refactored. var $translator_comments = '';
	// refactored. var $extracted_comments = '';
	// refactored. var $references = array();
	// refactored. var $flags = array();
	// refactored. function __construct( $args = array() ) {}

	/**
	 * PHP4 constructor.
	 */
	public function Translation_Entry( $args = array() ) {
		self::__construct( $args );
	}

	/**
	 * Generates a unique key for this entry
	 *
	 * @return string|bool the key or false if the entry is empty
	 */
	function key() {
		if ( null === $this->singular || '' === $this->singular ) return false;

		// Prepend context and EOT, like in MO files
		$key = !$this->context? $this->singular : $this->context.chr(4).$this->singular;
		// Standardize on \n line endings
		$key = str_replace( array( "\r\n", "\r" ), "\n", $key );

		return $key;
	}

	/**
	 * @param object $other
	 */
	function merge_with(&$other) {
		$this->flags = array_unique( array_merge( $this->flags, $other->flags ) );
		$this->references = array_unique( array_merge( $this->references, $other->references ) );
		if ( $this->extracted_comments != $other->extracted_comments ) {
			$this->extracted_comments .= $other->extracted_comments;
		}

	}
}
endif;