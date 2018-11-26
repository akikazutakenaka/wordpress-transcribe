<?php
/**
 * WP_Theme Class
 *
 * @package    WordPress
 * @subpackage Theme
 * @since      3.4.0
 */
final class WP_Theme implements ArrayAccess
{
	/**
	 * Whether the theme has been marked as updateable.
	 *
	 * @since 4.4.0
	 * @see   WP_MS_Themes_List_Table
	 *
	 * @var bool
	 */
	public $update = FALSE;

	/**
	 * Headers for style.css files.
	 *
	 * @static
	 *
	 * @var array
	 */
	private static $file_headers = array(
		'Name'        => 'Theme Name',
		'ThemeURI'    => 'Theme URI',
		'Description' => 'Description',
		'Author'      => 'Author',
		'AuthorURI'   => 'Author URI',
		'Version'     => 'Version',
		'Template'    => 'Template',
		'Status'      => 'Status',
		'Tags'        => 'Tags',
		'TextDomain'  => 'Text Domain',
		'DomainPath'  => 'Domain Path'
	);

	/**
	 * Default themes.
	 *
	 * @static
	 *
	 * @var array
	 */
	private static $default_themes = array(
		'classic'         => 'WordPress Classic',
		'default'         => 'WordPress Default',
		'twentyten'       => 'Twenty Ten',
		'twentyeleven'    => 'Twenty Eleven',
		'twentytwelve'    => 'Twenty Twelve',
		'twentythirteen'  => 'Twenty Thirteen',
		'twentyfourteen'  => 'Twenty Fourteen',
		'twentyfifteen'   => 'Twenty Fifteen',
		'twentysixteen'   => 'Twenty Sixteen',
		'twentyseventeen' => 'Twenty Seventeen'
	);

	/**
	 * Renamed theme tags.
	 *
	 * @static
	 *
	 * @var array
	 */
	private static $tag_map = array(
		'fixed-width'    => 'fixed-layout',
		'flexible-width' => 'fluid-layout'
	);

	/**
	 * Absolute path to the theme root, usually wp-content/themes.
	 *
	 * @var string
	 */
	private $theme_root;

	/**
	 * Header data from the theme's style.css file.
	 *
	 * @var array
	 */
	private $headers = array();

	/**
	 * Header data from the theme's style.css file after being sanitized.
	 *
	 * @var array
	 */
	private $headers_sanitized;

	/**
	 * Header name from the theme's style.css after being translated.
	 *
	 * Cached due to sorting functions running over the translated name.
	 *
	 * @var string
	 */
	private $name_translated;

	/**
	 * Errors encountered when initializing the theme.
	 *
	 * @var WP_Error
	 */
	private $errors;

	/**
	 * The directory name of the theme's files, inside the theme root.
	 *
	 * In the case of a child theme, this is directory name of the child theme.
	 * Otherwise, 'stylesheet' is the same as 'template'.
	 *
	 * @var string
	 */
	private $stylesheet;

	/**
	 * The directory name of the theme's files, inside the theme root.
	 *
	 * In the case of a child theme, this is the directory name of the parent theme.
	 * Otherwise, 'template' is the same as 'stylesheet'.
	 *
	 * @var string
	 */
	private $template;

	/**
	 * A reference to the parent theme, in the case of a child theme.
	 *
	 * @var WP_Theme
	 */
	private $parent;

	/**
	 * URL to the theme root, usually an absolute URL to wp-content/themes.
	 *
	 * @var string
	 */
	private $theme_root_uri;

	/**
	 * Flag for whether the theme's textdomain is loaded.
	 *
	 * @var bool
	 */
	private $textdomain_loaded;

	/**
	 * Stores an md5 hash of the theme root, to function as the cache key.
	 *
	 * @var string
	 */
	private $cache_hash;

	/**
	 * Flag for whether the themes cache bucket shoudl be persistently cached.
	 *
	 * Default is false.
	 * Can be set with the {@see 'wp_cache_themes_persistently'} filter.
	 *
	 * @static
	 *
	 * @var bool
	 */
	private static $persistently_cache;

	/**
	 * Expiration time for the themes cache bucket.
	 *
	 * By default the bucket is not cached, so this value is useless.
	 *
	 * @static
	 *
	 * @var bool
	 */
	private static $cache_expiration = 1800;

	/**
	 * Constructor for WP_Theme.
	 *
	 * @since  3.4.0
	 * @global array $wp_theme_directories
	 *
	 * @param string        $theme_dir  Directory of the theme within the theme_root.
	 * @param string        $theme_root Theme root.
	 * @param WP_Error|void $_child     If this theme is a parent theme, the child may be passed for validation purposes.
	 */
	public function __construct( $theme_dir, $theme_root, $_child = NULL )
	{
		global $wp_theme_directories;

		// Initialize caching on first run.
		if ( ! isset( self::$persistently_cache ) ) {
			// This action is documented in wp-includes/theme.php
			self::$persistently_cache = apply_filters( 'wp_cache_themes_persistently', FALSE, 'WP_Theme' );

			if ( self::$persistently_cache ) {
				wp_cache_add_global_groups( 'themes' );

				if ( is_int( self::$persistently_cache ) ) {
					self::$cache_expiration = self::$persistently_cache;
				}
			} else {
				wp_cache_add_non_persistent_groups( 'themes' );
			}
		}

		$this->theme_root = $theme_root;
		$this->stylesheet = $theme_dir;

		// Correct a situation where the theme is 'some-directory/some-theme' but 'some-directory' was passed in as part of the theme root instead.
		if ( ! in_array( $theme_root, ( array ) $wp_theme_directories ) && in_array( dirname( $theme_root ), ( array ) $wp_theme_directories ) ) {
			$this->stylesheet = basename( $this->theme_root ) . '/' . $this->stylesheet;
			$this->theme_root = dirname( $theme_root );
		}

		$this->cache_hash = md5( $this->theme_root . '/' . $this->stylesheet );
		$theme_file = $this->stylesheet . '/style.css';
		$cache = $this->cache_get( 'theme' );
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * @NOW 007: wp-includes/class-wp-theme.php
 * -> wp-includes/class-wp-theme.php
 */
	}

/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/class-wp-theme.php
 * @NOW 008: wp-includes/class-wp-theme.php
 */
}
