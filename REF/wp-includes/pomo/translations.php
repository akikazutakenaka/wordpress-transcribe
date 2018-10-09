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

		// @NOW 008

		/**
		 * @param array $headers
		 */
		function set_headers( $headers )
		{
			foreach ( $headers as $header => $value )
				$this->set_header( $header, $value );
		}
	}
}

class Gettext_Translations extends Translations
{
}
