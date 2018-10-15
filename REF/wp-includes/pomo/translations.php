<?php
/**
 * Class for a set of entries for translation and their associated headers
 *
 * @version    $Id: translations.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package    pomo
 * @subpackage translations
 */

require_once dirname( __FILE__ ) . '/plural-forms.php';
require_once dirname( __FILE__ ) . '/entry.php';

if ( ! class_exists( 'Translations', FALSE ) ) {
	class Translations
	{
		var $entries = [];
		var $headers = [];

		/**
		 * Sets $header PO header to $value
		 *
		 * If the header already exists, it will be overwritten
		 *
		 * @todo This should be out of this class, it is gettext specific
		 *
		 * @param string $header Header name, without trailing :
		 * @param string $value  Header value, without trailing \n
		 */
		function set_header( $header, $value )
		{
			$this->headers[$header] = $value;
		}

		/**
		 * @param array $headers
		 */
		function set_headers( $headers )
		{
			foreach ( $headers as $header => $value ) {
				$this->set_header( $header, $value );
			}
		}

		/**
		 * Merge $other in the current object.
		 *
		 * @param  Object $other Another Translation object, whose translations will be merged in this one (passed by reference).
		 * @return void
		 */
		function merge_with( &$other )
		{
			foreach ( $other->entries as $entry ) {
				$this->entries[ $entry->key() ] = $entry;
			}
		}
	}

	class Gettext_Translations extends Translations
	{
		/**
		 * @param  string $translation
		 * @return array
		 */
		function make_headers( $translation )
		{
			$headers = [];

			// Sometimes \ns are used instead of real new lines
			$translation = str_replace( '\n', "\n", $translation );
			$lines = explode( "\n", $translation );

			foreach ( $lines as $line ) {
				$parts = explode( ':', $line, 2 );

				if ( ! isset( $parts[1] ) ) {
					continue;
				}

				$headers[ trim( $parts[0] ) ] = trim( $parts[1] );
			}

			return $headers;
		}
	}
}

if ( ! class_exists( 'NOOP_Translations', FALSE ) ) {
	// Provides the same interface as Translations, but doesn't do anything.
	class NOOP_Translations
	{
		var $entries = [];
		var $headers = [];

		/**
		 * @param string $header
		 * @param string $value
		 */
		function set_header( $header, $value )
		{}

// @NOW 010
	}
}