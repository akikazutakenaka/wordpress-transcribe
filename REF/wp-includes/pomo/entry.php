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

		// @NOW 009
	}
}
