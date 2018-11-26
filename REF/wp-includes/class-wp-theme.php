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

		if ( is_array( $cache ) ) {
			foreach ( array( 'errors', 'headers', 'template' ) as $key ) {
				if ( isset( $cache[ $key ] ) ) {
					$this->$key = $cache[ $key ];
				}
			}

			if ( $this->errors ) {
				return;
			}

			if ( isset( $cache['theme_root_template'] ) ) {
				$theme_root_template = $cache['theme_root_template'];
			}
		} elseif ( ! file_exists( $this->theme_root . '/' . $theme_file ) ) {
			$this->headers['Name'] = $this->stylesheet;

			$this->errors = ! file_exists( $this->theme_root . '/' . $this->stylesheet )
				? new WP_Error( 'theme_not_found', sprintf( __( 'The theme directory "%s" does not exist.' ), esc_html( $this->stylesheet ) ) )
				: new WP_Error( 'theme_no_stylesheet', __( 'Stylesheet is missing.' ) );

			$this->template = $this->stylesheet;
			$this->cache_add( 'theme', array(
					'headers'    => $this->headers,
					'errors'     => $this->errors,
					'stylesheet' => $this->stylesheet,
					'template'   => $this->template
				) );

			if ( ! file_exists( $this->theme_root ) ) { // Don't cache this one.
				$this->errors->add( 'theme_root_missing', __( 'ERROR: The themes directory is either empty or doesn&#8217;t exist. Please check your installation.' ) );
			}

			return;
		} elseif ( ! is_readable( $this->theme_root . '/' . $theme_file ) ) {
			$this->headers['Name'] = $this->stylesheet;
			$this->errors = new WP_Error( 'theme_stylesheet_not_readable', __( 'Stylesheet is not readable.' ) );
			$this->template = $this->stylesheet;
			$this->cache_add( 'theme', array(
					'headers'    => $this->headers,
					'errors'     => $this->errors,
					'stylesheet' => $this->stylesheet,
					'template'   => $this->template
				) );
			return;
		} else {
			$this->headers = get_file_data( $this->theme_root . '/' . $theme_file, self::$file_headers, 'theme' );
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * @NOW 007: wp-includes/class-wp-theme.php
 * -> wp-includes/functions.php
 */
		}
	}

	/**
	 * Adds theme data to cache.
	 *
	 * Cache entries keyed by the theme and the type of data.
	 *
	 * @since 3.4.0
	 *
	 * @param  string $key  Type of data to store (theme, screenshot, headers, post_templates).
	 * @param  string $data Data to store.
	 * @return bool   Return value from wp_cache_add().
	 */
	private function cache_add( $key, $data )
	{
		return wp_cache_add( $key . '-' . $this->cache_hash, $data, 'themes', self::$cache_expiration );
	}

	/**
	 * Gets theme data from cache.
	 *
	 * Cache entries are keyed by the theme and the type of data.
	 *
	 * @since 3.4.0
	 *
	 * @param  string $key Type of data to retrieve (theme, screenshot, headers, post_templates).
	 * @return mixed  Retrieved data.
	 */
	private function cache_get( $key )
	{
		return wp_cache_get( $key . '-' . $this->cache_hash, 'themes' );
	}
}
