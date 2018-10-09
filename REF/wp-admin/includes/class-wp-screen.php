<?php
/**
 * Screen API: WP_Screen class
 *
 * @package    WordPress
 * @subpackage Administration
 * @since      4.4.0
 */

/**
 * Core class used to implement an admin screen API.
 *
 * @since 3.3.0
 */
final class WP_Screen
{
	/**
	 * Any action associated with the screen.
	 * 'add' for *-add.php and *-new.php screens.
	 * Empty otherwise.
	 *
	 * @since 3.3.0
	 *
	 * @var string
	 */
	public $action;

	/**
	 * The base type of the screen.
	 * This is typically the same as $id but with any post types and taxonomies stripped.
	 * For example, for an $id of 'edit-post' the base is 'edit'.
	 *
	 * @since 3.3.0
	 *
	 * @var string
	 */
	public $base;

	/**
	 * The number of columns to display.
	 * Access with get_columns().
	 *
	 * @since 3.4.0
	 *
	 * @var int
	 */
	private $column = 0;

	/**
	 * The unique ID of the screen.
	 *
	 * @since 3.3.0
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Which admin the screen is in.
	 * network | user | site | false
	 *
	 * @since 3.5.0
	 *
	 * @var string
	 */
	protected $in_admin;

	/**
	 * Whether the screen is in the network admin.
	 *
	 * Deprecated.
	 * Use in_admin() instead.
	 *
	 * @since      3.3.0
	 * @deprecated 3.5.0
	 *
	 * @var bool
	 */
	public $is_network;

	/**
	 * Whether the screen is in the user admin.
	 *
	 * Deprecated.
	 * Use in_admin() instead.
	 *
	 * @since      3.3.0
	 * @deprecated 3.5.0
	 *
	 * @var bool
	 */
	public $is_user;

	/**
	 * The base menu parent.
	 * This is derived from $parent_file by removing the query string and any .php extension.
	 * $parent_file values of 'edit.php?post_type=page' and 'edit.php?post_type=post' have a $parent_base of 'edit'.
	 *
	 * @since 3.3.0
	 *
	 * @var string
	 */
	public $parent_base;

	/**
	 * The parent_file for the screen per the admin menu system.
	 * Some $parent_file values are 'edit.php?post_type=page', 'edit.php', and 'options-general.php'.
	 *
	 * @since 3.3.0
	 *
	 * @var string
	 */
	public $parent_file;

	/**
	 * The post type associated with the screen, if any.
	 * The 'edit.php?post_type=page' screen has a post type of 'page'.
	 * The 'edit-tags.php?taxonomy=$taxonomy&post_type=page' screen has a post type of 'page'.
	 *
	 * @since 3.3.0
	 *
	 * @var string
	 */
	public $post_type;

	/**
	 * The taxonomy associated with the screen, if any.
	 * The 'edit-tags.php?taxonomy=category' screen has a taxonomy of 'category'.
	 *
	 * @since 3.3.0
	 *
	 * @var string
	 */
	public $taxonomy;

	/**
	 * The help tab data associated with the screen, if any.
	 *
	 * @since 3.3.0
	 *
	 * @var array
	 */
	private $_help_tabs = [];

	/**
	 * The help sidebar data associated with screen, if any.
	 *
	 * @since 3.3.0
	 *
	 * @var string
	 */
	private $_help_sidebar = '';

	/**
	 * The accessible hidden headings and text associated with the screen, if any.
	 *
	 * @since 4.4.0
	 *
	 * @var array
	 */
	private $_screen_reader_content = [];

	/**
	 * Stores old string-based help.
	 *
	 * @static
	 *
	 * @var array
	 */
	private static $_old_compat_help = [];

	/**
	 * The screen options associated with screen, if any.
	 *
	 * @since 3.3.0
	 *
	 * @var array
	 */
	private $_options = [];

	/**
	* The screen object registry.
	*
	* @since  3.3.0
	* @static
	*
	* @var array
	*/
	private static $_registry = [];

	/**
	 * Stores the result of the public show_screen_options function.
	 *
	 * @since 3.3.0
	 *
	 * @var bool
	 */
	private $_show_screen_options;

	/**
	 * Stores the 'screen_settings' section of screen options.
	 *
	 * @since 3.3.0
	 *
	 * @var string
	 */
	private $_screen_settings;

	/**
	 * Constructor
	 *
	 * @since 3.3.0
	 */
	private function __construct()
	{}

	/**
	 * Indicates whether the screen is in a particular admin
	 *
	 * @since 3.5.0
	 *
	 * @param  string $admin The admin to check against (network | user | site).
	 *                       If empty any of the three admins will result in true.
	 * @return bool   True if the screen is in the indicated admin, false otherwise.
	 */
	public function in_admin( $admin = NULL )
	{
		return empty( $admin )
			? ( bool ) $this->in_admin
			: ( $admin == $this->in_admin );
	}
}
