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
		// @NOW 007
	}
}
