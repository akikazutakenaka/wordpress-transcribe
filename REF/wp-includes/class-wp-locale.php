<?php
/**
 * Locale API: WP_Locale class
 *
 * @package    WordPress
 * @subpackage i18n
 * @since      4.6.0
 */

/**
 * Core class used to store translated data for a locale.
 *
 * @since 2.1.0
 * @since 4.6.0 Moved to its own file from wp-includes/locale.php.
 */
class WP_Locale
{
	/**
	 * Stores the translated strings for the full weekday names.
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	public $weekday;

	/**
	 * Stores the translated strings for the one character weekday names.
	 *
	 * There is a hack to make sure that Tuesday and Thursday, as well as Sunday and Saturday, don't conflict.
	 * See init() method for more.
	 *
	 * @see   WP_Locale::init() for how to handle the hack.
	 * @since 2.1.0
	 *
	 * @var array
	 */
	public $weekday_initial;

	/**
	 * Stores the translated strings for the abbreviated weekday names.
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	public $weekday_abbrev;

	/**
	 * Stores the default start of the week.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $start_of_week;

	/**
	 * Stores the translated strings for the full month names.
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	public $month;

	/**
	 * Stores the translated strings for the month names in genitive case, if the locale specifies.
	 *
	 * @since 4.4.0
	 *
	 * @var array
	 */
	public $month_genitive;

	/**
	 * Stores the translated strings for the abbreviated month names.
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	public $month_abbrev;

	/**
	 * Stores the translated strings for 'am' and 'pm'.
	 *
	 * Also the capitalized versions.
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	public $meridiem;

	/**
	 * The text direction of the locale language.
	 *
	 * Default is left to right 'ltr'.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	public $text_direction = 'ltr';

	/**
	 * The thousands separator and decimal point values used for localizing numbers.
	 *
	 * @since 2.3.0
	 *
	 * @var array
	 */
	public $number_format;

	/**
	 * Constructor which calls helper methods to set up object variables.
	 *
	 * @since 2.1.0
	 */
	public function __construct()
	{
		$this->init();
// @NOW 006 -> wp-includes/class-wp-locale.php
	}

	/**
	 * Sets up the translated strings and object properties.
	 *
	 * The method creates the translatable strings for various calendar elements.
	 * Which allows for specifying locale specific calendar names and text direction.
	 *
	 * @since  2.1.0
	 * @global string $text_direction
	 */
	public function init()
	{
		// The Weekdays
		$this->weekday[0] = __( 'Sunday' );
// @NOW 007 -> wp-includes/l10n.php
	}

	/**
	 * Checks if current locale is RTL.
	 *
	 * @since 3.0.0
	 *
	 * @return bool Whether locale is RTL.
	 */
	public function is_rtl()
	{
		return 'rtl' == $this->text_direction;
	}
}
