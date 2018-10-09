<?php
/**
 * User API: WP_User class
 *
 * @package    WordPress
 * @subpackage Users
 * @since      4.4.0
 */

/**
 * Core class used to implement the WP_User object.
 *
 * @since 2.0.0
 */
class WP_User
{
	/**
	 * User data container.
	 *
	 * @since 2.0.0
	 *
	 * @var object
	 */
	public $data;

	/**
	 * The user's ID.
	 *
	 * @since 2.1.0
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * The individual capabilities the user has been given.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $caps = [];

	/**
	 * User metadata option name.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	public $cap_key;

	/**
	 * The roles the user is part of.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $roles = [];

	/**
	 * All capabilities the user has, including individual and role based.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $allcaps = [];

	/**
	 * The filter context applied to user data fields.
	 *
	 * @since 2.9.0
	 *
	 * @var string
	 */
	public $filter = NULL;

	/**
	 * The site ID the capabilities of this user are initialized for.
	 *
	 * @since 4.9.0
	 *
	 * @var int
	 */
	private $site_id = 0;

	/**
	 * @static
	 * @since  3.3.0
	 *
	 * @var array
	 */
	private static $back_compat_keys;

	/**
	 * Constructor.
	 *
	 * Retrieves the userdata and passes it to WP_User::init().
	 *
	 * @since  2.0.0
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int|string|stdClass|WP_User $id      User's ID, a WP_User object, or a user object from the DB.
	 * @param string                      $name    Optional.
	 *                                             User's username.
	 * @param int                         $site_id Optional Site ID, defaults to current site.
	 */
	public function __construct( $id = 0, $name = '', $site_id = '' )
	{
		if ( ! isset( self::$back_compat_keys ) ) {
			$prefix = $GLOBALS['wpdb']->prefix;
			self::$back_compat_keys = [
				'user_firstname'             => 'first_name',
				'user_lastname'              => 'last_name',
				'user_description'           => 'description',
				'user_level'                 => $prefix . 'user_level',
				$prefix . 'usersettings'     => $prefix . 'user-settings',
				$prefix . 'usersettingstime' => $prefix . 'user-settings-time'
			];
		}

		if ( $id instanceof WP_User ) {
			$this->init( $id->data, $site_id );
			// @NOW 015 -> wp-includes/class-wp-user.php
		}
	}

	/**
	 * Sets up object properties, including capabilities.
	 *
	 * @since 3.3.0
	 *
	 * @param object $data    User DB row object.
	 * @param int    $site_id Optional.
	 *                        The site ID to initialize for.
	 */
	public function init( $data, $site_id = '' )
	{
		$this->data = $data;
		$this->ID = ( int ) $data->ID;
		$this->for_site( $site_id );
		// @NOW 016 -> wp-includes/class-wp-user.php
	}

	/**
	 * Sets the site to operate on.
	 * Defaults to the current site.
	 *
	 * @since  4.9.0
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $site_id Site ID to initialize user capabilities for.
	 *                     Default is the current site.
	 */
	public function for_site( $site_id = '' )
	{
		global $wpdb;
		$this->site_id = ! empty( $site_id )
			? absint( $site_id )
			: get_current_blog_id();
		$this->cap_key = $wpdb->get_blog_prefix( $this->site_id ) . 'capabilities';
		// @NOW 017 -> wp-includes/wp-db.php
	}
}
