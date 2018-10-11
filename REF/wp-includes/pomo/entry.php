<?php
/**
 * Contains Translation_Entry class
 *
 * @version    $Id: entry.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package    pomo
 * @subpackage entry
 */

if ( ! class_exists( 'Translation_Entry', FALSE ) ) {
	/**
	 * Translation_Entry class encapsulates a translatable string
	 */
	class Translation_Entry
	{
		/**
		 * Whether the entry contains a string and its plural form, default is false
		 *
		 * @var bool
		 */
		var $is_plural = FALSE;

		var $context = NULL;
		var $singular = NULL;
		var $plural = NULL;
		var $translations = [];
		var $translator_comments = '';
		var $extracted_comments = '';
		var $references = [];
		var $flags = [];

		/**
		 * @param array $args Associative array, support following keys:
		 *     - singular (string)            The string to translate, if omitted and empty entry will be created.
		 *     - plural (string)              The plural form of the string, setting this will set {@link $is_plural} to true.
		 *     - translations (array)         Translations of the string and possibly its plural forms.
		 *     - context (string)             A string differentiating two equal strings used in different contexts.
		 *     - translator_comments (string) Comments left by translators.
		 *     - extracted_comments (string)  Comments left by developers.
		 *     - references (array)           Places in the code this strings is used, in relative_to_root_path/file.php:linum form.
		 *     - flags (array)                Flags like php-format.
		 */
		function __construct( $args = [] )
		{
			// if no singular, empty object
			if ( ! isset( $args['singular'] ) ) {
				return;
			}

			// Get member variable values from args hash
			foreach ( $args as $varname => $value ) {
				$this->$varname = $value;
			}

			if ( isset( $args['plural'] ) && $args['plural'] ) {
				$this->is_plural = TRUE;
			}

			if ( ! is_array( $this->translations ) ) {
				$this->translations = [];
			}

			if ( ! is_array( $this->references ) ) {
				$this->references = [];
			}

			if ( ! is_array( $this->flags ) ) {
				$this->flags = [];
			}
		}

		/**
		 * PHP4 constructor.
		 */
		public function Translation_Entry( $args = [] )
		{
			self::__construct( $args );
		}

		/**
		 * Generates a unique key for this entry
		 *
		 * @return string|bool the key or false if the entry is empty
		 */
		function key()
		{
			if ( NULL === $this->singular || '' === $this->singular ) {
				return FALSE;
			}

			// Prepend context and EOT, like in MO files
			$key = ! $this->context ? $this->singular : $this->context . chr( 4 ) . $this->singular;

			// Standardize on \n line endings
			$key = str_replace( ["\r\n", "\r"], "\n", $key );

			return $key;
		}
	}
}
