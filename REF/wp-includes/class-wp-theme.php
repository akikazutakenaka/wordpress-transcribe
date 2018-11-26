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
			 * Default themes always trump their pretenders.
			 * Properly identify default themes that are inside a directory within wp-content/themes.
			 */
			if ( $default_theme_slug = array_search( $this->headers['Name'], self::$default_themes ) ) {
				if ( basename( $this->stylesheet ) != $default_theme_slug ) {
					$this->headers['Name'] .= '/' . $this->stylesheet;
				}
			}
		}

		if ( ! $this->template && $this->stylesheet === $this->headers['Template'] ) {
			$this->errors = new WP_Error( 'theme_child_invalid', sprintf( __( 'The theme defines itself as its parent theme. Please check the %s header.' ), '<code>Template</code>' ) );
			$this->cache_add( 'theme', array(
					'headers'    => $this->headers,
					'errors'     => $this->errors,
					'stylesheet' => $this->stylesheet
				) );
			return;
		}

		// If template is set from cache [and there are no errors], we know it's good.
		if ( ! $this->template && ! ( $this->template = $this->headers['Template'] ) ) {
			$this->template = $this->stylesheet;

			if ( ! file_exists( $this->theme_root . '/' . $this->stylesheet . '/index.php' ) ) {
				$error_message = sprintf( __( 'Template is missing. Standalone themes need to have a %1$s template file. <a href="%2$s">Child themes</a> need to have a Template header in the %3$s stylesheet.' ), '<code>index.php</code>', __( 'https://codex.wordpress.org/Child_Themes' ), '<code>style.css</code>' );
				$this->errors = new WP_Error( 'theme_no_index', $error_message );
				$this->cache_add( 'theme', array(
						'headers'    => $this->headers,
						'errors'     => $this->errors,
						'stylesheet' => $this->stylesheet,
						'template'   => $this->template
					) );
				return;
			}
		}

		// If we got our data from cache, we can assume that 'template' is pointing to the right place.
		if ( ! is_array( $cache ) && $this->template != $this->stylesheet && ! file_exists( $this->theme_root . '/' . $this->template . '/index.php' ) ) {
			/**
			 * If we're in a directory of themes inside /themes, look for the parent nearby.
			 * wp-content/themes/directory-of-themes/*
			 */
			$parent_dir = dirname( $this->stylesheet );

			if ( '.' != $parent_dir && file_exists( $this->theme_root . '/' . $parent_dir . '/' . $this->template . '/index.php' ) ) {
				$this->template = $parent_dir . '/' . $this->template;
			} elseif ( ( $directories = search_theme_directories() ) && isset( $directories[ $this->template ] ) ) {
				/**
				 * Look for the template in the search_theme_directories() results, in case it is in another theme root.
				 * We don't look into directories of themes, just the theme root.
				 */
				$theme_root_template = $directories[ $this->template ]['theme_root'];
			} else {
				// Parent theme is missing.
				$this->errors = new WP_Error( 'theme_no_parent', sprintf( __( 'The parent theme is missing. Please install the "%s" parent theme.' ), esc_html( $this->template ) ) );
				$this->cache_add( 'theme', array(
						'headers'    => $this->headers,
						'errors'     => $this->errors,
						'stylesheet' => $this->stylesheet,
						'template'   => $this->template
					) );
				$this->parent = new WP_Theme( $this->template, $this->theme_root, $this );
				return;
			}
		}

		// Set the parent, if we're a child theme.
		if ( $this->template != $this->stylesheet ) {
			/**
			 * If we are a parent, then there is a problem.
			 * Only two generations allowed!
			 * Cancel things out.
			 */
			if ( $_child instanceof WP_Theme && $_child->template == $this->stylesheet ) {
				$_child->parent = NULL;
				$_child->errors = new WP_Error( 'theme_parent_invalid', sprintf( __( 'The "%s" theme is not a valid parent theme.' ), esc_html( $_child->template ) ) );
				$_child->cache_add( 'theme', array(
						'headers'    => $_child->headers,
						'errors'     => $_child->errors,
						'stylesheet' => $_child->stylesheet,
						'template'   => $_child->template
					) );

				// The two themes actually reference each other with the Template header.
				if ( $_child->stylesheet == $this->template ) {
					$this->errors = new WP_Error( 'theme_parent_invalid', sprintf( __( 'The "%s" theme is not a valid parent theme.' ), esc_html( $this->template ) ) );
					$this->cache_add( 'theme', array(
							'headers'    => $this->headers,
							'errors'     => $this->errors,
							'stylesheet' => $this->stylesheet,
							'template'   => $this->template
						) );
				}

				return;
			}

			/**
			 * Set the parent.
			 * Pass the current instance so we can do the crazy checks above and assess errors.
			 */
			$this->parent = new WP_Theme( $this->template, isset( $theme_root_templates )
					? $theme_root_template
					: $this->theme_root,
				$this );
		}

		/**
		 * We're good.
		 * If we didn't retrieve from cache, set it.
		 */
		if ( ! is_array( $cache ) ) {
			$cache = array(
				'headers'    => $this->headers,
				'errors'     => $this->errors,
				'stylesheet' => $this->stylesheet,
				'template'   => $this->template
			);

			/**
			 * If the parent theme is in another root, we'll want to cache this.
			 * Avoids an entire branch of filesystem calls above.
			 */
			if ( isset( $theme_root_template ) ) {
				$cache['theme_root_template'] = $theme_root_template;
			}

			$this->cache_add( 'theme', $cache );
		}
	}

	/**
	 * Returns errors property.
	 *
	 * @since 3.4.0
	 *
	 * @return WP_Error|false WP_Error if there are errors, or false.
	 */
	public function errors()
	{
		return is_wp_error( $this->errors )
			? $this->errors
			: FALSE;
	}

	/**
	 * Returns reference to the parent theme.
	 *
	 * @since 3.4.0
	 *
	 * @return WP_Theme|false Parent theme, or false if the current theme is not a child theme.
	 */
	public function parent()
	{
		return isset( $this->parent )
			? $this->parent
			: FALSE;
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

	/**
	 * Get a raw, unformatted theme header.
	 *
	 * The header is sanitized, but is not translated, and is not marked up for display.
	 * To get a theme header for display, use the display() method.
	 *
	 * Use the get_template() method, not the 'Template' header, for finding the template.
	 * The 'Template' header is only good for what was written in the style.css, while get_template() takes into account where WordPress actually located the theme and whether it is actually valid.
	 *
	 * @since 3.4.0
	 *
	 * @param  string       $header Theme header.
	 *                              Name, Description, Author, Version, ThemeURI, AuthorURI, Status, Tags.
	 * @return string|false String on success, false on failure.
	 */
	public function get( $header )
	{
		if ( ! isset( $this->headers[ $header ] ) ) {
			return FALSE;
		}

		if ( ! isset( $this->headers_sanitized ) ) {
			$this->headers_sanitized = $this->cache_get( 'headers' );

			if ( ! is_array( $this->headers_sanitized ) ) {
				$this->headers_sanitized = array();
			}
		}

		if ( isset( $this->headers_sanitized[ $header ] ) ) {
			return $this->headers_sanitized[ $header ];
		}

		/**
		 * If themes are a persistent group, sanitize everything and cache it.
		 * One cache add is better than many cache sets.
		 */
		if ( self::$persistently_cache ) {
			foreach ( array_keys( $this->headers ) as $_header ) {
				$this->headers_sanitized[ $_header ] = $this->sanitize_header( $_header, $this->headers[ $_header ] );
			}

			$this->cache_add( 'headers', $this->headers_sanitized );
		} else {
			$this->headers_sanitized[ $header ] = $this->sanitize_header( $header, $this->headers[ $header ] );
		}

		return $this->headers_sanitized[ $header ];
	}

	/**
	 * Sanitize a theme header.
	 *
	 * @since     3.4.0
	 * @staticvar array $header_tags
	 * @staticvar array $header_tags_with_a
	 *
	 * @param  string $header Theme header.
	 *                        Name, Description, Author, Version, ThemeURI, AuthorURI, Status, Tags.
	 * @param  string $value  Value to sanitize.
	 * @return mixed
	 */
	private function sanitize_header( $header, $value )
	{
		switch ( $header ) {
			case 'Status':
				if ( ! $value ) {
					$value = 'publish';
					break;
				}

				// Fall through otherwise.

			case 'Name':
				static $header_tags = array(
					'abbr'    => array( 'title' => TRUE ),
					'acronym' => array( 'title' => TRUE ),
					'code'    => TRUE,
					'em'      => TRUE,
					'strong'  => TRUE
				);
				$value = wp_kses( $value, $header_tags );
				break;

			case 'Author':
				// There shouldn't be anchor tags in Author, but some themes like to be challenging.

			case 'Description':
				static $header_tags_with_a = array(
					'a'       => array(
							'href'  => TRUE,
							'title' => TRUE
						),
					'abbr'    => array( 'title' => TRUE ),
					'acronym' => array( 'title' => TRUE ),
					'code'    => TRUE,
					'em'      => TRUE,
					'strong'  => TRUE
				);
				$value = wp_kses( $value, $header_tags_with_a );
				break;

			case 'ThemeURI':
			case 'AuthorURI':
				$value = esc_url_raw( $value );
				break;

			case 'Tags':
				$value = array_filter( array_map( 'trim', explode( ',', strip_tags( $value ) ) ) );
				break;

			case 'Version':
				$value = strip_tags( $value );
				break;
		}

		return $value;
	}

	/**
	 * Returns the absolute path to the directory of a theme's "stylesheet" files.
	 *
	 * In the case of a child theme, this is the absolute path to the directory of the child theme's files.
	 *
	 * @since 3.4.0
	 *
	 * @return string Absolute path of the stylesheet directory.
	 */
	public function get_stylesheet_directory()
	{
		return $this->errors() && in_array( 'theme_root_missing', $this->errors()->get_error_codes() )
			? ''
			: $this->theme_root . '/' . $this->stylesheet;
	}

	/**
	 * Returns the absolute path to the directory of a theme's "template" files.
	 *
	 * In the case of a child theme, this is the absolute path to the directory of the parent theme's files.
	 *
	 * @since 3.4.0
	 *
	 * @return string Absolute path of the template directory.
	 */
	public function get_template_directory()
	{
		$theme_root = $this->parent()
			? $this->parent()->theme_root
			: $this->theme_root;

		return $theme_root . '/' . $this->template;
	}

	/**
	 * Return files in the theme's directory.
	 *
	 * @since 3.4.0
	 *
	 * @param  mixed $type          Optional.
	 *                              Array of extensions to return.
	 *                              Defaults to all files (null).
	 * @param  int   $depth         Optional.
	 *                              How deep to search for files.
	 *                              Defaults to a flat scan (0 depth).
	 *                              -1 depth is infinite.
	 * @param  bool  $search_parent Optional.
	 *                              Whether to return parent files.
	 *                              Defaults to false.
	 * @return array Array of files, keyed by the path to the file relative to the theme's directory, with the values being absolute paths.
	 */
	public function get_files( $type = NULL, $depth = 0, $search_parent = FALSE )
	{
		$files = ( array ) self::scandir( $this->get_stylesheet_directory(), $type, $depth );

		if ( $search_parent && $this->parent() ) {
			$files += ( array ) self::scandir( $this->get_template_directory(), $type, $depth );
		}

		return $files;
	}

	/**
	 * Returns the theme's post templates.
	 *
	 * @since 4.7.0
	 *
	 * @return array Array of page templates, keyed by filename and post type, with the value of the translated header name.
	 */
	public function get_post_templates()
	{
		/**
		 * If you screw up your current theme and we invalidate your parent, most things still work.
		 * Let it slide.
		 */
		if ( $this->errors() && $this->erros()->get_error_codes() !== array( 'theme_parent_invalid' ) ) {
			return array();
		}

		$post_templates = $this->cache_get( 'post_templates' );

		if ( ! is_array( $post_templates ) ) {
			$post_templates = array();
			$files = ( array ) $this->get_files( 'php', 1, TRUE );

			foreach ( $files as $file => $full_path ) {
				if ( ! preg_match( '|Template Name:(.*)$|mi', file_get_contents( $full_path ), $header ) ) {
					continue;
				}

				$types = array( 'page' );

				if ( preg_match( '|Template Post Type:(.*)$|mi', file_get_contents( $full_path ), $type ) ) {
					$types = explode( ',', _cleanup_header_comment( $type[1] ) );
				}

				foreach ( $types as $type ) {
					$type = sanitize_key( $type );

					if ( ! isset( $post_templates[ $type ] ) ) {
						$post_templates[ $type ] = array();
					}

					$post_templates[ $type ][ $file ] = _cleanup_header_comment( $header[1] );
				}
			}

			$this->cache_add( 'post_templates', $post_templates );
		}

		if ( $this->load_textdomain() ) {
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/class-wp-theme.php
 * @NOW 008: wp-includes/class-wp-theme.php
 * -> wp-includes/class-wp-theme.php
 */
		}
	}

	/**
	 * Returns the theme's post templates for a given post type.
	 *
	 * @since 3.4.0
	 * @since 4.7.0 Added the `$post_type` parameter.
	 *
	 * @param  WP_Post|null $post      Optional.
	 *                                 The post being edited, provided for context.
	 * @param  string       $post_type Optional.
	 *                                 Post type to get the templates for.
	 *                                 Default 'page'.
	 *                                 If a post is provided, its post type is used.
	 * @return array        Array of page templates, keyed by filename, with the value of the translated header name.
	 */
	public function get_page_templates( $post = NULL, $post_type = 'page' )
	{
		if ( $post ) {
			$post_type = get_post_type( $post );
		}

		$post_templates = $this->get_post_templates();
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
	 * Scans a directory for files of a certain extension.
	 *
	 * @since  3.4.0
	 * @static
	 *
	 * @param  string            $path          Absolute path to search.
	 * @param  array|string|null $extensions    Optional.
	 *                                          Array of extensions to find, string of a single extension, or null for all extensions.
	 *                                          Default null.
	 * @param  int               $depth         Optional.
	 *                                          How many levels deep to search for files.
	 *                                          Accepts 0, 1+, or -1 (infinite depth).
	 *                                          Default 0.
	 * @param  string            $relative_path Optional.
	 *                                          The basename of the absolute path.
	 *                                          Used to control the returned path for the found files, particularly when this function recurses to lower depths.
	 *                                          Default empty.
	 * @return array|false       Array of files, keyed by the path to the file relative to the `$path` directory prepended with `$relative_path`, with the values being absolute paths.
	 *                           False otherwise.
	 */
	private static function scandir( $path, $extensions = NULL, $depth = 0, $relative_path = '' )
	{
		if ( ! is_dir( $path ) ) {
			return FALSE;
		}

		if ( $extensions ) {
			$extensions = ( array ) $extensions;
			$_extensions = array( '|', $extensions );
		}

		$relative_path = trailingslashit( $relative_path );

		if ( '/' == $relative_path ) {
			$relative_path = '';
		}

		$results = scandir( $path );
		$files = array();

		/**
		 * Filters the array of excluded directories and files while scanning theme folder.
		 *
		 * @since 4.7.4
		 *
		 * @param array $exclusions Array of excluded directories and files.
		 */
		$exclusions = ( array ) apply_filters( 'theme_scandir_exclusions', array( 'CVS', 'node_modules', 'vendor', 'bower_components' ) );

		foreach ( $results as $result ) {
			if ( '.' == $result[0] || in_array( $result, $exclusions, TRUE ) ) {
				continue;
			}

			if ( is_dir( $path . '/' . $result ) ) {
				if ( ! $depth ) {
					continue;
				}

				$found = self::scandir( $path . '/' . $result, $extensions, $depth - 1, $relative_path . $result );
				$files = array_merge_recursive( $files, $found );
			} elseif ( ! $extensions || preg_match( '~\.(' . $_extensions . ')$~', $result ) ) {
				$files[ $relative_path . $result ] = $path . '/' . $result;
			}
		}

		return $files;
	}

	/**
	 * Loads the theme's textdomain.
	 *
	 * Translation files are not inherited from the parent theme.
	 * Todo: if this fails for the child theme, it should probably try to load the parent theme's translations.
	 *
	 * @since 3.4.0
	 *
	 * @return bool True if the textdomain was successfully loaded or has already been loaded.
	 *              False if no texetdomain was specified in the file headers, or if the domain could not be loaded.
	 */
	public function load_textdomain()
	{
		if ( isset( $this->textdomain_loaded ) ) {
			return $this->textdomain_loaded;
		}

		$textdomain = $this->get( 'TextDomain' );

		if ( ! $textdomain ) {
			$this->textdomain_loaded = FALSE;
			return FALSE;
		}

		if ( is_textdomain_loaded( $textdomain ) ) {
			$this->textdomain_loaded = TRUE;
			return TRUE;
		}

		$path = $this->get_stylesheet_directory();

		$path .= ( $domainpath = $this->get( 'DomainPath' ) )
			? $domainpath
			: '/languages';

		$this->textdomain_loaded = load_theme_textdomain( $textdomain, $path );
/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/class-wp-theme.php
 * <- wp-includes/class-wp-theme.php
 * @NOW 009: wp-includes/class-wp-theme.php
 */
	}
}
