<?php
/**
 * Locale API: WP_Locale class
 *
 * @package WordPress
 * @subpackage i18n
 * @since 4.6.0
 */

/**
 * Core class used to store translated data for a locale.
 *
 * @since 2.1.0
 * @since 4.6.0 Moved to its own file from wp-includes/locale.php.
 */
class WP_Locale {
	// refactored. public $weekday;
	// :
	// refactored. public function get_weekday($weekday_number) {}

	/**
	 * Retrieve the translated weekday initial.
	 *
	 * The weekday initial is retrieved by the translated
	 * full weekday word. When translating the weekday initial
	 * pay attention to make sure that the starting letter does
	 * not conflict.
	 *
	 * @since 2.1.0
	 *
	 * @param string $weekday_name
	 * @return string
	 */
	public function get_weekday_initial($weekday_name) {
		return $this->weekday_initial[$weekday_name];
	}

	// refactored. public function get_weekday_abbrev($weekday_name) {}
	// :
	// refactored. public function is_rtl() {}

	/**
	 * Register date/time format strings for general POT.
	 *
	 * Private, unused method to add some date/time formats translated
	 * on wp-admin/options-general.php to the general POT that would
	 * otherwise be added to the admin POT.
	 *
	 * @since 3.6.0
	 */
	public function _strings_for_pot() {
		/* translators: localized date format, see https://secure.php.net/date */
		__( 'F j, Y' );
		/* translators: localized time format, see https://secure.php.net/date */
		__( 'g:i a' );
		/* translators: localized date and time format, see https://secure.php.net/date */
		__( 'F j, Y g:i a' );
	}
}
