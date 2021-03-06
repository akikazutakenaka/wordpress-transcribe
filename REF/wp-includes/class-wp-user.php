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
	public $caps = array();

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
	public $roles = array();

	/**
	 * All capabilities the user has, including individual and role based.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $allcaps = array();

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
			self::$back_compat_keys = array(
				'user_firstname'             => 'first_name',
				'user_lastname'              => 'last_name',
				'user_description'           => 'description',
				'user_level'                 => $prefix . 'user_level',
				$prefix . 'usersettings'     => $prefix . 'user-settings',
				$prefix . 'usersettingstime' => $prefix . 'user-settings-time'
			);
		}

		if ( $id instanceof WP_User ) {
			$this->init( $id->data, $site_id );
			return;
		} elseif ( is_object( $id ) ) {
			$this->init( $id, $site_id );
			return;
		}

		if ( ! empty( $id ) && ! is_numeric( $id ) ) {
			$name = $id;
			$id = 0;
		}

		$data = $id
			? self::get_data_by( 'id', $id )
			: self::get_data_by( 'login', $name );

		if ( $data ) {
			$this->init( $data, $site_id );
		} else {
			$this->data = new stdClass;
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
	}

	/**
	 * Return only the main user fields.
	 *
	 * @since  3.3.0
	 * @since  4.4.0 Added 'ID' as an alias of 'id' for the `$field` parameter.
	 * @static
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param  string       $field The field to query against: 'id', 'ID', 'slug', 'email' or 'login'.
	 * @param  string|int   $value The field value.
	 * @return object|false Raw user object.
	 */
	public static function get_data_by( $field, $value )
	{
		global $wpdb;

		// 'ID' is an alias of 'id'.
		if ( 'ID' === $field ) {
			$field = 'id';
		}

		if ( 'id' == $field ) {
			// Make sure the value is numeric to avoid casting objects, for example, to int 1.
			if ( ! is_numeric( $value ) ) {
				return FALSE;
			}

			$value = intval( $value );

			if ( $value < 1 ) {
				return FALSE;
			}
		} else {
			$value = trim( $value );
		}

		if ( ! $value ) {
			return FALSE;
		}

		switch ( $field ) {
			case 'id':
				$user_id = $value;
				$db_field = 'ID';
				break;

			case 'slug':
				$user_id = wp_cache_get( $value, 'userslugs' );
				$db_field = 'user_nicename';
				break;

			case 'email':
				$user_id = wp_cache_get( $value, 'useremail' );
				$db_field = 'user_email';
				break;

			case 'login':
				$value = sanitize_user( $value );
				$user_id = wp_cache_get( $value, 'userlogins' );
				$db_field = 'user_login';
				break;

			default:
				return FALSE;
		}

		if ( FALSE !== $user_id ) {
			if ( $user = wp_cache_get( $user_id, 'users' ) ) {
				return $user;
			}
		}

		if ( ! $user = $wpdb->get_row( $wpdb->prepare( <<<EOQ
SELECT *
FROM $wpdb->users
WHERE $db_field = %s
EOQ
					, $value ) ) ) {
			return FALSE;
		}

		update_user_caches( $user );
		return $user;
	}

	/**
	 * Magic method for checking the existence of a certain custom field.
	 *
	 * @since 3.3.0
	 *
	 * @param  string $key User meta key to check if set.
	 * @return bool   Whether the given user meta key is set.
	 */
	public function __isset( $key )
	{
		if ( 'id' == $key ) {
			_deprecated_argument( 'WP_User->id', '2.1.0', sprintf( __( 'Use %s instead.' ), '<code>WP_User->ID</code>' ) );
			$key = 'ID';
		}

		if ( isset( $this->data->$key ) ) {
			return TRUE;
		}

		if ( isset( self::$back_compat_keys[ $key ] ) ) {
			$key = self::$back_compat_keys[ $key ];
		}

		return metadata_exists( 'user', $this->ID, $key );
	}

	/**
	 * Magic method for accessing custom fields.
	 *
	 * @since 3.3.0
	 *
	 * @param  string $key User meta key to retrieve.
	 * @return mixed  Value of the given user meta key (if set).
	 *                If `$key` is 'id', the user ID.
	 */
	public function __get( $key )
	{
		if ( 'id' == $key ) {
			_deprecated_argument( 'WP_User->id', '2.1.0', sprintf( __( 'Use %s instead.' ), '<code>WP_User->ID</code>' ) );
			return $this->ID;
		}

		if ( isset( $this->data->$key ) ) {
			$value = $this->data->$key;
		} else {
			if ( isset( self::$back_compat_keys[ $key ] ) ) {
				$key = self::$back_compat_keys[ $key ];
			}

			$value = get_user_meta( $this->ID, $key, TRUE );
		}

		if ( $this->filter ) {
			$value = sanitize_user_field( $key, $value, $this->ID, $this->filter );
		}

		return $value;
	}

	/**
	 * Determine whether the user exists in the database.
	 *
	 * @since 3.4.0
	 *
	 * @return bool True if user exists in the database, false if not.
	 */
	public function exists()
	{
		return ! empty( $this->ID );
	}

	/**
	 * Retrieve the value of a property or meta key.
	 *
	 * Retrieves from the users and usermeta table.
	 *
	 * @since 3.3.0
	 *
	 * @param  string $key Property
	 * @return mixed
	 */
	public function get( $key )
	{
		return $this->__get( $key );
	}

	/**
	 * Determine whether a property or meta key is set.
	 *
	 * Consults the users and usermeta tables.
	 *
	 * @since 3.3.0
	 *
	 * @param  string $key Property
	 * @return bool
	 */
	public function has_prop( $key )
	{
		return $this->__isset( $key );
	}

	/**
	 * Retrieve all of the role capabilities and merge with individual capabilities.
	 *
	 * All of the capabilities of the roles the user belongs to are merged with the users individual roles.
	 * This also means that the user can be denied specific roles that their role might have, but the specific user isn't granted permission to.
	 *
	 * @since 2.0.0
	 *
	 * @return array List of all capabilities for the user.
	 */
	public function get_role_caps()
	{
		$switch_site = FALSE;

		if ( is_multisite() && $this->site_id != get_current_blog_id() ) {
			$switch_site = TRUE;
			switch_to_blog( $this->site_id );
		}

		$wp_roles = wp_roles();

		// Filter out caps that are not role names and assign to $this->roles.
		if ( is_array( $this->caps ) ) {
			$this->roles = array_filter( array_keys( $this->caps ), array( $wp_roles, 'is_role' ) );
		}

		// Build $allcaps from role caps, overlay user's $caps.
		$this->allcaps = array();

		foreach ( ( array ) $this->roles as $role ) {
			$the_role = $wp_roles->get_role( $role );
			$this->allcaps = array_merge( ( array ) $this->allcaps, ( array ) $the_role->capabilities );
		}

		$this->allcaps = array_merge( ( array ) $this->allcaps, ( array ) $this->caps );

		if ( $switch_site ) {
			restore_current_blog();
		}

		return $this->allcaps;
	}

	/**
	 * Whether the user has a specific capability.
	 *
	 * While checking against a role in place of a capability is supported in part, this practice is discouraged as it may produce unreliable results.
	 *
	 * @since 2.0.0
	 * @see   map_meta_cap()
	 *
	 * @param  string $cap            Capability name.
	 * @param  int    $object_id, ... Optional.
	 *                                ID of a specific object to check against if `$cap` is a "meta" capability.
	 *                                Meta capabilities such as `edit_post` and `edit_user` are capabilities used by the `map_meta_cap()` function to map to primitive capabilities that a user or role has, such as `edit_posts` and `edit_others_posts`.
	 * @return bool   Whether the user has the given capability, or, if `$object_id` is passed, whether the user has the given capability for that object.
	 */
	public function has_cap( $cap )
	{
		if ( is_numeric( $cap ) ) {
			_deprecated_argument( __FUNCTION__, '2.0.0', __( 'Usage of user levels is deprecated. Use capabilities instead.' ) );
			$cap = $this->translate_level_to_cap( $cap );
		}

		$args = array_slice( func_get_args(), 1 );
		$args = array_merge( array( $cap, $this->ID ), $args );
		$caps = call_user_func_array( 'map_meta_cap', $args );

		// Multisite super admin has all caps by definition, unless specifically denied.
		if ( is_multisite() && is_super_admin( $this->ID ) ) {
			return in_array( 'do_not_allow', $caps )
				? FALSE
				: TRUE;
		}

		/**
		 * Dynamically filter a user's capabilities.
		 *
		 * @since 2.0.0
		 * @since 3.7.0 Added the user object.
		 *
		 * @param array   $allcaps An array of all the user's capabilities.
		 * @param array   $caps    Actual capabilities for meta capability.
		 * @param array   $args    Optional parameters passed to has_cap(), typically object ID.
		 * @param WP_User $user    The user object.
		 */
		$capabilities = apply_filters( 'user_has_cap', $this->allcaps, $caps, $args, $this );

		// Everyone is allowed to exist.
		$capabilities['exist'] = TRUE;

		// Nobody is allowed to do things they are not allowed to do.
		unset( $capabilities['do_not_allow'] );

		// Must have ALL requested caps.
		foreach ( ( array ) $caps as $cap ) {
			if ( empty( $capabilities[ $cap ] ) ) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Convert numeric level to level capability name.
	 *
	 * Prepends 'level_' to level number.
	 *
	 * @since 2.0.0
	 *
	 * @param  int    $level Level number, 1 to 10.
	 * @return string
	 */
	public function translate_level_to_cap( $level )
	{
		return 'level_' . $level;
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
		$this->caps = $this->get_caps_data();
		$this->get_role_caps();
	}

	/**
	 * Gets the available user capabilities data.
	 *
	 * @since 4.9.0
	 *
	 * @return array User capabilities array.
	 */
	private function get_caps_data()
	{
		$caps = get_user_meta( $this->ID, $this->cap_key, TRUE );

		if ( ! is_array( $caps ) ) {
			return array();
		}

		return $caps;
	}
}
