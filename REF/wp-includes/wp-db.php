<?php
/**
 * WordPress DB Class
 *
 * Original code from {@link http://php.justinvincent.com Justin Vincent (justin@visunet.ie)}
 *
 * @package    WordPress
 * @subpackage Database
 * @since      0.71
 */

/**
 * @since 0.71
 */
define( 'EZSQL_VERSION', 'WP1.25' );

/**
 * @since 0.71
 */
define( 'OBJECT', 'OBJECT' );
define( 'object', 'OBJECT' ); // Back compat.

/**
 * @since 2.5.0
 */
define( 'OBJECT_K', 'OBJECT_K' );

/**
 * @since 0.71
 */
define( 'ARRAY_A', 'ARRAY_A' );

/**
 * @since 0.71
 */
define( 'ARRAY_N', 'ARRAY_N' );

/**
 * WordPress Database Access Abstraction Object
 *
 * It is possible to replace this class with your own by setting the $wpdb global variable in wp-content/db.php file to your class.
 * The wpdb class will still be included, so you can extend it or simply use your own.
 *
 * @link  https://codex.wordpress.org/Function_Reference/wpdb_Class
 * @since 0.71
 */
class wpdb
{
	/**
	 * Whether to show SQL/DB errors.
	 *
	 * Default behavior is to show errors if both WP_DEBUG and WP_DEBUG_DISPLAY evaluated to true.
	 *
	 * @since 0.71
	 *
	 * @var bool
	 */
	var $show_errors = FALSE;

	/**
	 * Whether to suppress errors during the DB bootstrapping.
	 *
	 * @since 2.5.0
	 *
	 * @var bool
	 */
	var $suppress_errors = FALSE;

	/**
	 * The last error during query.
	 *
	 * @since 2.5.0
	 *
	 * @var string
	 */
	public $last_error = '';

	/**
	 * Amount of queries made.
	 *
	 * @since 1.2.0
	 *
	 * @var int
	 */
	public $num_queries = 0;

	/**
	 * Count of rows returned by previous query.
	 *
	 * @since 0.71
	 *
	 * @var int
	 */
	public $num_rows = 0;

	/**
	 * Count of affected rows by previous query.
	 *
	 * @since 0.71
	 *
	 * @var int
	 */
	var $rows_affected = 0;

	/**
	 * The ID generated for an AUTO_INCREMENT column by the previous query (usually INSERT).
	 *
	 * @since 0.71
	 *
	 * @var int
	 */
	public $insert_id = 0;

	/**
	 * Last query made.
	 *
	 * @since 0.71
	 *
	 * @var array
	 */
	var $last_query;

	/**
	 * Results of the last query made.
	 *
	 * @since 0.71
	 *
	 * @var array|null
	 */
	var $last_result;

	/**
	 * MySQL result, which is either a resource or boolean.
	 *
	 * @since 0.71
	 *
	 * @var mixed
	 */
	protected $result;

	/**
	 * Cached column info, for sanity checking data before inserting.
	 *
	 * @since 4.2.0
	 *
	 * @var array
	 */
	protected $col_meta = [];

	/**
	 * Calculated character sets on tables
	 *
	 * @since 4.2.0
	 *
	 * @var array
	 */
	protected $table_charset = [];

	/**
	 * Whether text fields in the current query need to be sanity checked.
	 *
	 * @since 4.2.0
	 *
	 * @var bool
	 */
	protected $check_current_query = TRUE;

	/**
	 * Flag to ensure we don't run into recursion problems when checking the collation.
	 *
	 * @since 4.2.0
	 * @see   wpdb::check_safe_collation()
	 *
	 * @var bool
	 */
	private $checking_collation = FALSE;

	/**
	 * Saved info on the table column.
	 *
	 * @since 0.71
	 *
	 * @var array
	 */
	protected $col_info;

	/**
	 * Saved queries that were executed.
	 *
	 * @since 1.5.0
	 *
	 * @var array
	 */
	var $queries;

	/**
	 * The number of times to retry reconnecting before dying.
	 *
	 * @since 3.9.0
	 * @see   wpdb::check_connection()
	 *
	 * @var int
	 */
	protected $reconnect_retries = 5;

	/**
	 * WordPress table prefix.
	 *
	 * You can set this to have multiple WordPress installations in a single database.
	 * The second reason is for possible security precautions.
	 *
	 * @since 2.5.0
	 *
	 * @var string
	 */
	public $prefix = '';

	/**
	 * WordPress base table prefix.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $base_prefix;

	/**
	 * Whether the database queries are ready to start executing.
	 *
	 * @since 2.3.2
	 *
	 * @var bool
	 */
	var $ready = FALSE;

	/**
	 * Blog ID.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	public $blogid = 0;

	/**
	 * Site ID.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	public $siteid = 0;

	/**
	 * List of WordPress per-blog tables.
	 *
	 * @since 2.5.0
	 * @see   wpdb::tables()
	 *
	 * @var array
	 */
	var $tables = ['posts', 'comments', 'links', 'options', 'postmeta', 'terms', 'term_taxonomy', 'term_relationships', 'termmeta', 'commentmeta'];

	/**
	 * List of deprecated WordPress tables.
	 *
	 * categories, post2cat, and link2cat were deprecated in 2.3.0, db version 5539.
	 *
	 * @since 2.9.0
	 * @see   wpdb::tables()
	 *
	 * @var array
	 */
	var $old_tables = ['categories', 'post2cat', 'link2cat'];

	/**
	 * List of WordPress global tables.
	 *
	 * @since 3.0.0
	 * @see   wpdb::tables()
	 *
	 * @var array
	 */
	var $global_tables = ['users', 'usermeta'];

	/**
	 * List of Multisite global tables.
	 *
	 * @since 3.0.0
	 * @see   wpdb::tables()
	 *
	 * @var array
	 */
	var $ms_global_tables = ['blogs', 'signups', 'site', 'sitemeta', 'sitecategories', 'registration_log', 'blog_versions'];

	/**
	 * WordPress Comments table.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $comments;

	/**
	 * WordPress Comment Metadata table.
	 *
	 * @since 2.9.0
	 *
	 * @var string
	 */
	public $commentmeta;

	/**
	 * WordPress Links table.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $links;

	/**
	 * WordPress Options table.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $options;

	/**
	 * WordPress Post Metadata table.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $postmeta;

	/**
	 * WordPress Posts table.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $posts;

	/**
	 * WordPress Terms table.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	public $terms;

	/**
	 * WordPress Term Relationships table.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	public $term_relationships;

	/**
	 * WordPress Term Taxonomy table.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	public $term_taxonomy;

	/**
	 * WordPress Term Meta table.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $termmeta;

	//
	// Global and Multisite Tables
	//

	/**
	 * WordPress User Metadata table.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	public $usermeta;

	/**
	 * WordPress Users table.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	public $users;

	/**
	 * Multisite Blogs table.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $blogs;

	/**
	 * Multisite Blog Versions table.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $blog_versions;

	/**
	 * Multisite Registration Log table.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $registration_log;

	/**
	 * Multisite Signups table.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $signups;

	/**
	 * Multisite Sites table.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $site;

	/**
	 * Multisite Sitewide Terms table.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $sitecategories;

	/**
	 * Multisite Site Metadata table.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $sitemeta;

	/**
	 * Format specifiers for DB columns.
	 * Columns not listed here default to %s.
	 * Initialized during WP load.
	 *
	 * Keys are column names, values are format types: 'ID' => '%d'
	 *
	 * @since 2.8.0
	 * @see   wpdb::prepare()
	 * @see   wpdb::insert()
	 * @see   wpdb::update()
	 * @see   wpdb::delete()
	 * @see   wp_set_wpdb_vars()
	 *
	 * @var array
	 */
	public $field_types = [];

	/**
	 * Database table columns charset.
	 *
	 * @since 2.2.0
	 *
	 * @var string
	 */
	public $charset;

	/**
	 * Database table columns collate.
	 *
	 * @since 2.2.0
	 *
	 * @var string
	 */
	public $collate;

	/**
	 * Database Username.
	 *
	 * @since 2.9.0
	 *
	 * @var string
	 */
	protected $dbuser;

	/**
	 * Database Password.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	protected $dbpassword;

	/**
	 * Database Name.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	protected $dbname;

	/**
	 * Database Host.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	protected $dbhost;

	/**
	 * Database Handle.
	 *
	 * @since 0.71
	 *
	 * @var string
	 */
	protected $dbh;

	/**
	 * A textual description of the last query/get_row/get_var call.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $func_call;

	/**
	 * Whether MySQL is used as the database engine.
	 *
	 * Set in WPDB::db_connect() to true, by default.
	 * This is used when checking against the required MySQL version for WordPress.
	 * Normally, a replacement database drop-in (db.php) will skip these checks, but setting this to true will force the checks to occur.
	 *
	 * @since 3.3.0
	 *
	 * @var bool
	 */
	public $is_mysql = NULL;

	/**
	 * A list of incompatible SQL modes.
	 *
	 * @since 3.9.0
	 *
	 * @var array
	 */
	protected $incompatible_modes = ['NO_ZERO_DATE', 'ONLY_FULL_GROUP_BY', 'STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES', 'TRADITIONAL'];

	/**
	 * Whether to use mysqli over mysql.
	 *
	 * @since 3.9.0
	 *
	 * @var bool
	 */
	private $use_mysqli = FALSE;

	/**
	 * Whether we've managed to successfully connect at some point.
	 *
	 * @since 3.9.0
	 *
	 * @var bool
	 */
	private $has_connected = FALSE;

	/**
	 * Connects to the database server and selects a database.
	 *
	 * PHP5 style constructor for compatibility with PHP5.
	 * Does the actual setting up of the class properties and connection to the database.
	 *
	 * @link   https://core.trac.wordpress.org/ticket/3354
	 * @since  2.0.8
	 * @global string $wp_version
	 *
	 * @param string $dbuser     MySQL database user
	 * @param string $dbpassword MySQL database password
	 * @param string $dbname     MySQL database name
	 * @param string $dbhost     MySQL database host
	 */
	public function __construct( $dbuser, $dbpassword, $dbname, $dbhost )
	{
		register_shutdown_function( [$this, '__destruct'] );

		if ( WP_DEBUG && WP_DEBUG_DISPLAY ) {
			$this->show_errors();
		}

		// Use ext/mysqli if it exists unless WP_USE_EXT_MYSQL is defined as true
		if ( function_exists( 'mysqli_connect' ) ) {
			$this->use_mysqli = TRUE;

			if ( defined( 'WP_USE_EXT_MYSQL' ) ) {
				$this->use_mysqli = ! WP_USE_EXT_MYSQL;
			}
		}

		$this->dbuser = $dbuser;
		$this->dbpassword = $dbpassword;
		$this->dbname = $dbname;
		$this->dbhost = $dbhost;

		// wp-config.php creation will manually connect when ready.
		if ( defined( 'WP_SETUP_CONFIG' ) ) {
			return;
		}

		$this->db_connect();
	}

	/**
	 * PHP5 style destructor and will run when database object is destroyed.
	 *
	 * @see   wpdb::__construct()
	 * @since 2.0.8
	 *
	 * @return TRUE
	 */
	public function __destruct()
	{
		return TRUE;
	}

	/**
	 * Set $this->charset and $this->collate
	 *
	 * @since 3.1.0
	 */
	public function init_charset()
	{
		$charset = '';
		$collate = '';

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$charset = 'utf8';
			$collate = ( defined( 'DB_COLLATE' ) && DB_COLLATE ) ? DB_COLLATE : 'utf8_general_ci';
		} elseif ( defined( 'DB_COLLATE' ) ) {
			$collate = DB_COLLATE;
		}

		if ( defined( 'DB_CHARSET' ) ) {
			$charset = DB_CHARSET;
		}

		$charset_collate = $this->determine_charset( $charset, $collate );
		$this->charset = $charset_collate['charset'];
		$this->collate = $charset_collate['collate'];
	}

	/**
	 * Determines the best charset and collation to use given a charset and collation.
	 *
	 * For example, when able, utf8mb4 should be used instead of utf8.
	 *
	 * @since 4.6.0
	 *
	 * @param  string $charset The character set to check.
	 * @param  string $collate The collation to check.
	 * @return array  The most appropriate character set and collation to use.
	 */
	public function determine_charset( $charset, $collate )
	{
		if ( ( $this->use_mysqli && ! ( $this->dbh instanceof mysqli ) )
		  || empty( $this->dbh ) ) {
			return compact( 'charset', 'collate' );
		}

		if ( 'utf8' === $charset && $this->has_cap( 'utf8mb4' ) ) {
			$charset = 'utf8mb4';
		}

		if ( 'utf8mb4' === $charset && ! $this->has_cap( 'utf8mb4' ) ) {
			$charset = 'utf8';
			$collate = str_replace( 'utf8mb4_', 'utf8_', $collate );
		}

		if ( 'utf8mb4' === $charset ) {
			// _general_ is outdated, so we can upgrade it to _unicode_, instead.
			$collate = ( ! $collate || 'utf8_general_ci' === $collate )
				? 'utf8mb4_unicode_ci'
				: str_replace( 'utf8_', 'utf8mb4_', $collate );
		}

		// _unicode_520_ is a better collation, we should use that when it's available.
		if ( $this->has_cap( 'utf8mb4_520' ) && 'utf8mb4_unicode_ci' === $collate ) {
			$collate = 'utf8mb4_unicode_520_ci';
		}

		return compact( 'charset', 'collate' );
	}

	/**
	 * Sets the connection's character set.
	 *
	 * @since 3.1.0
	 *
	 * @param resource $dbh     The resource given by mysql_connect.
	 * @param string   $charset Optional.
	 *                          The character set.
	 *                          Default null.
	 * @param string   $collate Optional.
	 *                          The collation.
	 *                          Default null.
	 */
	public function set_charset( $dbh, $charset = NULL, $collate = NULL )
	{
		if ( ! isset( $charset ) ) {
			$charset = $this->charset;
		}

		if ( ! isset( $collate ) ) {
			$collate = $this->collate;
		}

		if ( $this->has_cap( 'collation' ) && ! empty( $charset ) ) {
			$set_charset_succeeded = TRUE;

			if ( $this->use_mysqli ) {
				if ( function_exists( 'mysqli_set_charset' ) && $this->has_cap( 'set_charset' ) ) {
					$set_charset_succeeded = mysqli_set_charset( $dbh, $charset );
				}

				if ( $set_charset_succeeded ) {
					$query = $this->prepare( 'SET NAMES %s', $charset );

					if ( ! empty( $collate ) ) {
						$query .= $this->prepare( ' COLLATE %s', $collate );
					}

					mysql_query( $query, $dbh );
				}
			}
		}
	}

	/**
	 * Change the current SQL mode, and ensure its WordPress compatibility.
	 *
	 * If no modes are passed, it will ensure the current MySQL server modes are compatible.
	 *
	 * @since 3.9.0
	 *
	 * @param array $modes Optional.
	 *                     A list of SQL modes to set.
	 */
	public function set_sql_mode( $modes = [] )
	{
		if ( empty( $modes ) ) {
			$res = $this->use_mysqli
				? mysqli_query( $this->dbh, 'SELECT @@SESSION.sql_mode' )
				: mysql_query( 'SELECT @@SESSION.sql_mode', $this->dbh );

			if ( empty( $res ) ) {
				return;
			}

			if ( $this->use_mysqli ) {
				$modes_array = mysqli_fetch_array( $res );

				if ( empty( $modes_array[0] ) ) {
					return;
				}

				$modes_str = $modes_array[0];
			} else {
				$modes_str = mysql_result( $res, 0 );
			}

			if ( empty( $modes_str ) ) {
				return;
			}

			$modes = explode( ',', $modes_str );
		}

		$modes = array_change_key_case( $modes, CASE_UPPER );

		/**
		 * Filters the list of incompatible SQL modes to exclude.
		 *
		 * @since 3.9.0
		 *
		 * @param array $incompatible_modes An array of incompatible modes.
		 */
		$incompatible_modes = ( array ) apply_filters( 'incompatible_sql_modes', $this->incompatible_modes );

		foreach ( $modes as $i => $mode ) {
			if ( in_array( $mode, $incompatible_modes ) ) {
				unset( $modes[ $i ] );
			}
		}

		$modes_str = implode( ',', $modes );

		if ( $this->use_mysqli ) {
			mysqli_query( $this->dbh, "SET SESSION sql_mode='$modes_str'" );
		} else {
			mysql_query( "SET SESSION sql_mode='$modes_str'", $this->dbh );
		}
	}

	/**
	 * Sets blog id.
	 *
	 * @since 3.0.0
	 *
	 * @param  int $blog_id
	 * @param  int $network_id Optional.
	 * @return int Previous blog id.
	 */
	public function set_blog_id( $blog_id, $network_id = 0 )
	{
		if ( ! empty( $network_id ) ) {
			$this->siteid = $network_id;
		}

		$old_blog_id = $this->blogid;
		$this->blogid = $blog_id;
		$this->prefix = $this->get_blog_prefix();

		foreach ( $this->tables( 'blog' ) as $table => $prefixed_table ) {
			$this->table = $prefixed_table;
		}

		foreach ( $this->tables( 'old' ) as $table => $prefixed_table ) {
			$this->table = $prefixed_table;
		}

		return $old_blog_id;
	}

	/**
	 * Gets blog prefix.
	 *
	 * @since 3.0.0
	 *
	 * @param  int    $blog_id Optional.
	 * @return string Blog prefix.
	 */
	public function get_blog_prefix( $blog_id = NULL )
	{
		if ( is_multisite() ) {
			if ( NULL === $blog_id ) {
				$blog_id = $this->blogid;
			}

			$blog_id = ( int ) $blog_id;
			return ( defined( 'MULTISITE' )
			      && ( 0 == $blog_id || 1 == $blog_id ) )
				? $this->base_prefix
				: $this->base_prefix . $blog_id . '_';
		} else {
			return $this->base_prefix;
		}
	}

	/**
	 * Returns an array of WordPress tables.
	 *
	 * Also allows for the CUSTOM_USER_TABLE and CUSTOM_USER_META_TABLE to override the WordPress users and usermeta tables that would otherwise be determined by the prefix.
	 *
	 * The scope argument can take one of the following:
	 *
	 *     'all'       - Returns 'all' and 'global' tables.
	 *                   No old tables are returned.
	 *     'blog'      - Returns the blog-level tables for the queried blog.
	 *     'global'    - Returns the global tables for the installation, returning multisite tables only if running multisite.
	 *     'ms_global' - Returns the multisite global tables, regardless if current installation is multisite.
	 *     'old'       - Returns tables which are deprecated.
	 *
	 * @since 3.0.0
	 * @uses  wpdb::$tables
	 * @uses  wpdb::$old_tables
	 * @uses  wpdb::$global_tables
	 * @uses  wpdb::$ms_global_tables
	 *
	 * @param  string $scope   Optional.
	 *                         Can be all, global, ms_global, blog, or old tables.
	 *                         Defaults to all.
	 * @param  bool   $prefix  Optional.
	 *                         Whether to include table prefixes.
	 *                         Default true.
	 *                         If blog prefix is requested, then the custom users and usermeta tables will be mapped.
	 * @param  int    $blog_id Optional.
	 *                         The blog_id to prefix.
	 *                         Defaults to wpdb::$blogid.
	 *                         Used only when prefix is requested.
	 * @return array  Table names.
	 *                When a prefix is requested, the key is the unprefixed table name.
	 */
	public function tables( $scope = 'all', $prefix = TRUE, $blog_id = 0 )
	{
		switch ( $scope ) {
			case 'all':
				$tables = array_merge( $this->global_tables, $this->tables );

				if ( is_multisite() ) {
					$tables = array_merge( $tables, $this->ms_global_tables );
				}

				break;

			case 'blog':
				$tables = $this->tables;
				break;

			case 'global':
				$tables = $this->global_tables;

				if ( is_multisite() ) {
					$tables = array_merge( $tables, $this->ms_global_tables );
				}

				break;

			case 'ms_global':
				$tables = $this->ms_global_tables;
				break;

			case 'old':
				$tables = $this->old_tables;
				break;

			default:
				return [];
		}

		if ( $prefix ) {
			if ( ! $blog_id ) {
				$blog_id = $this->blogid;
			}

			$blog_prefix = $this->get_blog_prefix( $blog_id );
			$base_prefix = $this->base_prefix;
			$global_tables = array_merge( $this->global_tables, $this->ms_global_tables );

			foreach ( $tables as $k => $table ) {
				$tables[ $table ] = in_array( $table, $global_tables )
					? $base_prefix . $table
					: $blog_prefix . $table;
				unset( $tables[ $k ] );
			}

			if ( isset( $tables['users'] ) && defined( 'CUSTOM_USER_TABLE' ) ) {
				$tables['users'] = CUSTOM_USER_TABLE;
			}

			if ( isset( $tables['usermeta'] ) && defined( 'CUSTOM_USER_META_TABLE' ) ) {
				$tables['usermeta'] = CUSTOM_USER_META_TABLE;
			}
		}

		return $tables;
	}

	/**
	 * Selects a database using the current database connection.
	 *
	 * The database name will be changed based on the current database connection.
	 * On failure, the execution will bail and display an DB error.
	 *
	 * @since 0.71
	 *
	 * @param string        $db  MySQL database name.
	 * @param resource|null $dbh Optional link identifier.
	 */
	public function select( $db, $dbh = NULL )
	{
		if ( is_null( $dbh ) ) {
			$dbh = $this->dbh;
		}

		$success = $this->use_mysqli ? mysqli_select_db( $dbh, $db ) : mysql_select_db( $db, $dbh );

		if ( ! $success ) {
			$this->ready = FALSE;

			if ( ! did_action( 'template_redirect' ) ) {
				wp_load_translations_early();
				$message = '<h1>' . __( 'Can&#8217;t select database' ) . "</h1>\n";
				$message .= '<p>' . sprintf( __( 'We were able to connect to the database server (which means your username and password is okay) but not able to select the %s database.' ), '<code>' . htmlspecialchars( $db, ENT_QUOTES ) . '</code>' ) . "</p>\n";
				$message .= "<ul>\n";
				$message .= '<li>' . __( 'Are you sure it exists?' ) . "</li>\n";
				$message .= '<li>' . sprintf( __( 'Does the user %1$s have permission to use the %2$s database?' ), '<code>' . htmlspecialchars( $this->dbuser, ENT_QUOTES ) . '</code>', '<code>' . htmlspecialchars( $db, ENT_QUOTES ) . '</code>' ) . "</li>\n";
				$message .= '<li>' . sprintf( __( 'On some systems the name of your database is prefixed with your username, so it would be like <code>username_%1$s</code>. Could that be the problem?' ), htmlspecialchars( $db, ENT_QUOTES ) ) . "</li>\n";
				$message .= "</ul>\n";
				$message .= '<p>' . sprintf( __( 'If you don&#8217;t know how to set up a database you should <strong>contact your host</strong>. If all else fails you may find help at the <a href="%s">WordPress Support Forums</a>.' ), __( 'https://wordpress.org/support/' ) ) . "</p>\n";
				$this->bail( $message, 'db_select_fail' );
			}
		}
	}

	/**
	 * Real escape, using mysqli_real_escape_string() or mysql_real_escape_string()
	 *
	 * @see   mysqli_real_escape_string()
	 * @see   mysql_real_escape_string()
	 * @since 2.8.0
	 *
	 * @param  string $string to escape
	 * @return string escaped
	 */
	function _real_escape( $string )
	{
		if ( $this->dbh ) {
			$escaped = $this->use_mysqli ? mysqli_real_escape_string( $this->dbh, $string ) : mysql_real_escape_string( $string, $this->dbh );
		} else {
			$class = get_class( $this );

			if ( function_exists( '__' ) ) {
				_doing_it_wrong( $class, sprintf( __( '%s must set a database connection for use with escaping.' ), $class ), '3.6.0' );
			} else {
				_doing_it_wrong( $class, sprintf( '%s must set a database connection for use with escaping.', $class ), '3.6.0' );
			}

			$escaped = addslashes( $string );
		}

		return $this->add_placeholder_escape( $escaped );
	}

	/**
	 * Escape data.
	 * Works on arrays.
	 *
	 * @uses  wpdb::_real_escape()
	 * @since 2.8.0
	 *
	 * @param  string|array $data
	 * @param  string|array Escaped
	 */
	public function _escape( $data )
	{
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				$data[ $k ] = is_array( $v ) ? $this->_escape( $v ) : $this->_real_escape( $v );
			}
		} else {
			$data = $this->_real_escape( $data );
		}

		return $data;
	}

	/**
	 * Escapes content by reference for insertion into the database, for security.
	 *
	 * @uses  wpdb::_real_escape()
	 * @since 2.3.0
	 *
	 * @param string $string to escape
	 */
	public function escape_by_ref( &$string )
	{
		if ( ! is_float( $string ) ) {
			$string = $this->_real_escape( $string );
		}
	}

	/**
	 * Prepares a SQL query for safe execution.
	 * Uses sprintf()-like syntax.
	 *
	 * The following placeholders can be used in the query string:
	 *     %d (integer)
	 *     %f (float)
	 *     %s (string)
	 *
	 * All placeholders MUST be left unquoted in the query string.
	 * A corresponding argument MUST be passed for each placeholder.
	 *
	 * For compatibility with old behavior, numbered or formatted string placeholders (eg, %1$s, %5s) will not have quotes added by this function, so should be passed with appropriate quotes around them for your usage.
	 *
	 * Literal percentage signs (%) in the query string must be written as %%.
	 * Percentage wildcards (for example, to use in LIKE syntax) must be passed via a substitution argument containing the complete LIKE string, these cannot be inserted directly in the query string.
	 * Also see {@see esc_like()}.
	 *
	 * Arguments may be passed as individual arguments to the method, or as a single array containing all arguments.
	 * A combination of the two is not supported.
	 *
	 * Examples:
	 *     $wpdb->prepare( "SELECT * FROM `table` WHERE `column` = %s AND `field` = %d OR `other_field` LIKE %s", ['foo', 1337, '%bar'] );
	 *     $wpdb->prepare( "SELECT DATE_FORMAT( `field`, '%%c' ) FROM `table` WHERE `column` = %s", 'foo' );
	 *
	 * @link  https://secure.php.net/sprintf Description of syntax.
	 * @since 2.3.0
	 *
	 * @param  string      $query     Query statement with sprintf()-like placeholders.
	 * @param  array|mixed $args      The array of variables to substitute into the query's placeholders if being called with an array of arguments, or the first variable to substitute into the query's placeholders if being called with individual arguments.
	 * @param  mixed       $args, ... Further variables to substitute into the query's placeholders if being called with individual arguments.
	 * @return string|void Sanitized query string, if there is a query to prepare.
	 */
	public function prepare( $query, $args )
	{
		if ( is_null( $query ) ) {
			return;
		}

		// This is not meant to be foolproof -- but it will catch obviously incorrect usage.
		if ( strpos( $query, '%' ) === FALSE ) {
			wp_load_translations_early();
			_doing_it_wrong( 'wpdb::prepare', sprintf( __( 'The query argument of %s must have a placeholder.' ), 'wpdb::prepare()' ), '3.9.0' );
		}

		$args = func_get_args();
		array_shift( $args );

		// If args were passed as an array (as in vsprintf), move them up.
		$passed_as_array = FALSE;

		if ( is_array( $args[0] ) && count( $args ) == 1 ) {
			$passed_as_array = TRUE;
			$args = $args[0];
		}

		foreach ( $args as $arg ) {
			if ( ! is_scalar( $arg ) && ! is_null( $arg ) ) {
				wp_load_translations_early();
				_doing_it_wrong( 'wpdb::prepare', sprintf( __( 'Unsupported value type (%s).' ), gettype( $arg ) ), '4.8.2' );
			}
		}

		/**
		 * Specify the formatting allowed in a placeholder.
		 * The following are allowed:
		 *
		 *     - Sign specifier. eg, $+d
		 *     - Numbered placeholders. eg, %1$s
		 *     - Padding specifier, including custom padding characters. eg, %05s, %'#5s
		 *     - Alignment specifier. eg, %05-s
		 *     - Precision specifier. eg, %.2f
		 */
		$allowed_format = '(?:[1-9][0-9]*[$])?[-+0-9]*(?: |0|\'.)?[-+0-9]*(?:\.[0-9]+)?';

		/**
		 * If a %s placeholder already has quoted around it, removing the existing quotes and re-inserting them ensures the quotes are consistent.
		 *
		 * For backwards compatibility, this is only applied to %s, and not to placeholders like %1$s, which are frequently used in the middle of longer strings, or as table name placeholders.
		 */
		$query = str_replace( "'%s'", '%s', $query ); // Strip any existing single quotes.
		$query = str_replace( '"%s"', '%s', $query ); // Strip any existing double quotes.
		$query = preg_replace( '/(?<!%)%s/', "'%s'", $query ); // Quote the strings, avoiding escaped strings like %%s.

		$query = preg_replace( "/(?<!%)(%($allowed_format)?f)/", '%\\2F', $query ); // Force floats to be locale unaware.

		$query = preg_replace( "/%(?:%|$|(?!($allowed_format)?[sdF]))/", '%%\\1', $query ); // Escape any unescaped percents.

		// Count the number of valid placeholders in the query.
		$placeholders = preg_match_all( "/(^|[^%]|(%%)+)%($allowed_format)?[sdF]/", $query, $matches );

		if ( count( $args ) !== $placeholders ) {
			if ( 1 === $placeholders && $passed_as_array ) {
				// If the passed query only expected one argument, but the wrong number of arguments were sent as an array, bail.
				wp_load_translations_early();
				_doing_it_wrong( 'wpdb::prepare', __( 'The query only expected one placeholder, but an array of multiple placeholders was sent.' ), '4.9.0' );
				return;
			} else {
				// If we don't have the right number of placeholders, but they were passed as individual arguments, or we were expecting multiple arguments in an array, throw a warning.
				wp_load_translations_early();
				_doing_it_wrong( 'wpdb::prepare', sprintf( __( 'The query does not contain the correct number of placeholders (%1$d) for the number of arguments passed (%2$d).' ), $placeholders, count( $args ) ), '4.8.3' );
			}
		}

		array_walk( $args, [$this, 'escape_by_ref'] );
		$query = @vsprintf( $query, $args );
		return $this->add_placeholder_escape( $query );
	}

	/**
	 * Print SQL/DB error.
	 *
	 * @since  0.71
	 * @global array $EZSQL_ERROR Stores error information of query and error string.
	 *
	 * @param  string     $str The error to display.
	 * @return false|void False if the showing of errors is disabled.
	 */
	public function print_error( $str = '' )
	{
		global $EZSQL_ERROR;

		if ( ! $str ) {
			$str = $this->use_mysqli ? mysqli_error( $this->dbh ) : mysql_error( $this->dbh );
		}

		$EZSQL_ERROR[] = [
			'query'     => $this->last_query,
			'error_str' => $str
		];

		if ( $this->suppress_errors ) {
			return FALSE;
		}

		wp_load_translations_early();

		$error_str = ( $caller = $this->get_caller() )
			? sprintf( __( 'WordPress database error %1$s for query %2$s made by %3$s' ), $str, $this->last_query, $caller )
			: sprintf( __( 'WordPress database error %1$s for query %2$s' ), $str, $this->last_query );

		error_log( $error_str );

		// Are we showing errors?
		if ( ! $this->show_errors )
			return FALSE;

		// If there is an error then take note of it.
		if ( is_multisite() ) {
			$msg = sprintf( "%s [%s]\n%s\n", __( 'WordPress database error:' ), $str, $this->last_query );

			if ( defined( 'ERRORLOGFILE' ) ) {
				error_log( $msg, 3, ERRORLOGFILE );
			}

			if ( defined( 'DIEONDBERROR' ) ) {
				wp_die( $msg );
			}
		} else {
			$str   = htmlspecialchars( $str, ENT_QUOTES );
			$query = htmlspecialchars( $this->last_query, ENT_QUOTES );
			printf( '<div id="error"><p class="wpdberror"><strong>%s</strong> [%s]<br /><code>%s</code></p></div>', __( 'WordPress database error:' ), $str, $query );
		}
	}

	/**
	 * Enables showing of database errors.
	 *
	 * This function should be used only to enable showing of errors.
	 * wpdb::hide_errors() should be used instead for hiding of errors.
	 * However, this function can be used to enable and disable showing of database errors.
	 *
	 * @since 0.71
	 * @see   wpdb::hide_errors()
	 *
	 * @param  bool $show Whether to show or hide errors.
	 * @return bool Old value for showing errors.
	 */
	public function show_errors( $show = TRUE )
	{
		$errors = $this->show_errors;
		$this->show_errors = $show;
		return $errors;
	}

	/**
	 * Whether to suppress database errors.
	 *
	 * By default database errors are suppressed, with a simple call to this function they can be enabled.
	 *
	 * @since 2.5.0
	 * @see   wpdb::hide_errors()
	 *
	 * @param  bool $suppress Optional.
	 *                        New value.
	 *                        Defaults to true.
	 * @return bool Old value.
	 */
	public function suppress_errors( $suppress = TRUE )
	{
		$errors = $this->suppress_errors;
		$this->suppress_errors = ( bool ) $suppress;
		return $errors;
	}

	/**
	 * Kill cached query results.
	 *
	 * @since 0.71
	 */
	public function flush()
	{
		$this->last_result   = [];
		$this->col_info      = NULL;
		$this->last_query    = NULL;
		$this->rows_affected = $this->num_rows = 0;
		$this->last_error    = '';

		if ( $this->use_mysqli && $this->result instanceof mysqli_result ) {
			mysqli_free_result( $this->result );
			$this->result = NULL;

			// Sanity check before using the handle.
			if ( empty( $this->dbh ) || ! ( $this->dbh instanceof mysqli ) ) {
				return;
			}

			// Clear out any results from a multi-query.
			while ( mysqli_more_results( $this->dbh ) ) {
				mysqli_next_result( $this->dbh );
			}
		} elseif ( is_resource( $this->result ) ) {
			mysql_free_result( $this->result );
		}
	}

	/**
	 * Connect to and select database.
	 *
	 * If $allow_bail is false, the lack of database connection will need to be handled manually.
	 *
	 * @since 3.0.0
	 * @since 3.9.0 $allow_bail parameter added.
	 *
	 * @param  bool $allow_bail Optional.
	 *                          Allows the function to bail.
	 *                          Default true.
	 * @return bool True with a successful connection, false on failure.
	 */
	public function db_connect( $allow_bail = TRUE )
	{
		$this->is_mysql = TRUE;

		/**
		 * Deprecated in 3.9+ when using MySQLi.
		 * No equivalent $new_link parameter exists for mysqli_* functions.
		 */
		$new_link = defined( 'MYSQL_NEW_LINK' ) ? MYSQL_NEW_LINK : TRUE;
		$client_flags = defined( 'MYSQL_CLIENT_FLAGS' ) ? MYSQL_CLIENT_FLAGS : 0;

		if ( $this->use_mysqli ) {
			$this->dbh = mysqli_init();
			$host = $this->dbhost;
			$port = NULL;
			$socket = NULL;
			$is_ipv6 = FALSE;

			if ( $host_data = $this->parse_db_host( $this->dbhost ) ) {
				list( $host, $port, $socket, $is_ipv6 ) = $host_data;
			}

			/**
			 * If using the `mysqlnd` library, the IPv6 address needs to be enclosed in square brackets, whereas it doesn't while using the `libmysqlclient` library.
			 *
			 * @see https://bugs.php.net/bug.php?id=67563
			 */
			if ( $is_ipv6 && extension_loaded( 'mysqlnd' ) ) {
				$host = "[$host]";
			}

			if ( WP_DEBUG ) {
				mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, NULL, $port, $socket, $client_flags );
			} else {
				@mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, NULL, $port, $socket, $client_flags );
			}

			if ( $this->dbh->connect_errno ) {
				$this->dbh = NULL;

				/**
				 * It's possible ext/mysqli is misconfigured.
				 * Fall back to ext/mysql if:
				 *
				 * - We haven't previously connected, and
				 * - WP_USE_EXT_MYSQL isn't set to false, and
				 * - ext/mysql is loaded.
				 */
				$attempt_fallback = TRUE;

				if ( $this->has_connected ) {
					$attempt_fallback = FALSE;
				} elseif ( defined( 'WP_USE_EXT_MYSQL' ) && ! WP_USE_EXT_MYSQL ) {
					$attempt_fallback = FALSE;
				} elseif ( ! function_exists( 'mysql_connect' ) ) {
					$attempt_fallback = FALSE;
				}

				if ( $attempt_fallback ) {
					$this->use_mysqli = FALSE;
					return $this->db_connect( $allow_bail );
				}
			}
		} else {
			if ( WP_DEBUG ) {
				$this->dbh = mysql_connect( $this->dbhost, $this->dbuser, $this->dbpassword, $new_link, $client_flags );
			} else {
				$this->dbh = @mysql_connect( $this->dbhost, $this->dbuser, $this->dbpassword, $new_link, $client_flags );
			}
		}

		if ( ! $this->dbh && $allow_bail ) {
			wp_load_translations_early();

			// Load custom DB error template, if present.
			if ( file_exists( WP_CONTENT_DIR . '/db-error.php' ) ) {
				require_once( WP_CONTENT_DIR . '/db-error.php' );
				die();
			}

			$message = '<h1>' . __( 'Error establishing a database connection' ) . "</h1>\n";
			$message .= '<p>' . sprintf( __( 'This either means that the username and password information in your %1$s file is incorrect or we can&#8217;t contact the database server at %2$s. This could mean your host&#8217;s database server is down.' ), '<code>wp-config.php</code>', '<code>' . htmlspecialchars( $this->dbhost, ENT_QUOTES ) . '</code>' ) . "</p>\n";
			$message .= "<ul>\n";
			$message .= '<li>' . __( 'Are you sure you have the correct username and password?' ) . "</li>\n";
			$message .= '<li>' . __( 'Are you sure that you have typed the correct hostname?' ) . "</li>\n";
			$message .= '<li>' . __( 'Are you sure that the database server is running?' ) . "</li>\n";
			$message .= "</ul>\n";
			$message .= '<p>' . sprintf( __( 'If you&#8217;re unsure what these terms mean you should probably contact your host. If you still need help you can always visit the <a href="%s">WordPress Support Forums</a>.' ), __( 'https://wordpress.org/support/' ) ) . "</p>\n";
			$this->bail( $message, 'db_connect_fail' );
			return FALSE;
		} elseif ( $this->dbh ) {
			if ( ! $this->has_connected ) {
				$this->init_charset();
			}

			$this->has_connected = TRUE;
			$this->set_charset( $this->dbh );
			$this->ready = TRUE;
			$this->set_sql_mode();
			$this->select( $this->dbname, $this->dbh );
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Parse the DB_HOST setting to interpret it for mysqli_real_connect.
	 *
	 * mysqli_real_connect doesn't support the host param including a port or socket like mysql_connect does.
	 * This duplicates how mysql_connect detects a port and/or socket file.
	 *
	 * @since 4.9.0
	 *
	 * @param  string     $host The DB_HOST setting to parse.
	 * @return array|bool Array containing the host, the port, the socket and whether it is an IPv6 address, in that order.
	 *                    If $host couldn't be parsed, returns false.
	 */
	public function parse_db_host( $host )
	{
		$port = NULL;
		$socket = NULL;
		$is_ipv6 = FALSE;

		// First peel off the socket parameter from the right, if it exists.
		$socket_pos = strpos( $host, ':/' );

		if ( $socket_pos !== FALSE ) {
			$socket = substr( $host, $socket_pos + 1 );
			$host = substr( $host, 0, $socket_pos );
		}

		/**
		 * We need to check for an IPv6 address first.
		 * An IPv6 address will always contain at least two colons.
		 */
		if ( substr_count( $host, ':' ) > 1 ) {
			$pattern = '#^(?:\[)?(?P<host>[0-9a-fA-F:]+)(?:\]:(?P<port>[\d]+))?#';
			$is_ipv6 = TRUE;
		} else {
			// We seem to be dealing with an IPv4 address.
			$pattern = '#^(?P<host>[^:/]*)(?::(?P<port>[\d]+))?#';
		}

		$matches = [];
		$result = preg_match( $pattern, $host, $matches );

		if ( 1 !== $result ) {
			// Couldn't parse the address, bail.
			return FALSE;
		}

		$host = '';

		foreach ( ['host', 'port'] as $component ) {
			if ( ! empty( $matches[$component] ) ) {
				$$component = $matches[ $component ];
			}
		}

		return [$host, $port, $socket, $is_ipv6];
	}

	/**
	 * Checks that the connection to the database is still up.
	 * If not, try to reconnect.
	 *
	 * If this function is unable to reconnect, it will forcibly die, or if after the {@see 'template_redirect'} hook has been fired, return false instead.
	 *
	 * If $allow_bail is false, the lack of database connection will need to be handled manually.
	 *
	 * @since 3.9.0
	 *
	 * @param  bool      $allow_bail Optional.
	 *                               Allows the function to bail.
	 *                               Default true.
	 * @return bool|void True if the connection is up.
	 */
	public function check_connection( $allow_bail = TRUE )
	{
		if ( $this->use_mysqli ) {
			if ( ! empty( $this->dbh ) && mysqli_ping( $this->dbh ) ) {
				return TRUE;
			}
		} else {
			if ( ! empty( $this->dbh ) && mysql_ping( $this->dbh ) ) {
				return TRUE;
			}
		}

		$error_reporting = FALSE;

		// Disable warnings, as we don't want to see a multitude of "unable to connect" messages.
		if ( WP_DEBUG ) {
			$error_reporting = error_reporting();
			error_reporting( $error_reporting & ~ E_WARNING );
		}

		for ( $tries = 1; $tries <= $this->reconnect_retries; $tries++ ) {
			/**
			 * On the last try, re-enable warnings.
			 * We want to see a single instance of the "unable to connect" message on the bail() screen, if it appears.
			 */
			if ( $this->reconnect_retries === $tries && WP_DEBUG ) {
				error_reporting( $error_reporting );
			}

			if ( $this->db_connect( FALSE ) ) {
				if ( $error_reporting ) {
					error_reporting( $error_reporting );
				}

				return TRUE;
			}

			sleep( 1 );
		}

		/**
		 * If template_redirect has already happened, it's too late for wp_die()/dead_db().
		 * Let's just return and hope for the best.
		 */
		if ( did_action( 'template_redirect' ) ) {
			return FALSE;
		}

		if ( ! $allow_bail ) {
			return FALSE;
		}

		wp_load_translations_early();
		$message = '<h1>' . __( 'Error reconnecting to the database' ) . "</h1>\n";
		$message .= '<p>' . sprintf( __( 'This means that we lost contact with the database server at %s. This could mean your host&#8217;s database server is down.' ), '<code>' . htmlspecialchars( $this->dbhost, ENT_QUOTES ) . '</code>' ) . "</p>\n";
		$message .= "<ul>\n";
		$message .= '<li>' . __( 'Are you sure that the database server is running?' ) . "</li>\n";
		$message .= '<li>' . __( 'Are you sure that the database server is not under particularly heavy load?' ) . "</li>\n";
		$message .= "</ul>\n";
		$message .= '<p>' . sprintf( __( 'If you&#8217;re unsure what these terms mean you should probably contact your host. If you still need help you can always visit the <a href="%s">WordPress Support Forums</a>.' ), __( 'https://wordpress.org/support/' ) ) . "</p>\n";

		// We weren't able to reconnect, so we better bail.
		$this->bail( $message, 'db_connect_fail' );

		/**
		 * Call dead_db() if bail didn't die, because this database is no more.
		 * It has ceased to be (at least temporarily).
		 */
		dead_db();
	}

	/**
	 * Perform a MySQL database query, using current database connection.
	 *
	 * More information can be found on the codex page.
	 *
	 * @since 0.71
	 *
	 * @param  string    $query Database query.
	 * @return int|false Number of rows affected/selected or false on error.
	 */
	public function query( $query )
	{
		if ( ! $this->ready ) {
			$this->check_current_query = TRUE;
			return FALSE;
		}

		/**
		 * Filters the database query.
		 *
		 * Some queries are made before the plugins have been loaded, and thus cannot be filtered with this method.
		 *
		 * @since 2.1.0
		 *
		 * @param string $query Database query.
		 */
		$query = apply_filters( 'query', $query );

		$this->flush();

		// Log how the function was called.
		$this->func_call = "\$db->query(\"$query\")";

		// If we're writing to the database, make sure the query will write safely.
		if ( $this->check_current_query && ! $this->check_ascii( $query ) ) {
			$stripped_query = $this->strip_invalid_text_from_query( $query );

			// strip_invalid_text_from_query() can perform queries, so we need to flush again, just to make sure everything is clear.
			$this->flush();

			if ( $stripped_query !== $query ) {
				$this->insert_id = 0;
				return FALSE;
			}
		}

		$this->check_current_query = TRUE;

		// Keep track of the last query for debug.
		$this->last_query = $query;

		$this->_do_query( $query );

		// MySQL server has gone away, try to reconnect.
		$mysql_errno = 0;

		if ( ! empty( $this->dbh ) ) {
			$mysql_errno = $this->use_mysqli
				? ( ( $this->dbh instanceof mysqli ) ? mysqli_errno( $this->dbh ) : 2006 )
				: ( is_resource( $this->dbh ) ? mysql_errno( $this->dbh ) : 2006 );
		}

		if ( empty( $this->dbh ) || 2006 == $mysql_errno ) {
			if ( $this->check_connection() ) {
				$this->_do_query( $query );
			} else {
				$this->insert_id = 0;
				return FALSE;
			}
		}

		// If there is an error then take note of it.
		$this->last_error = $this->use_mysqli
			? ( ( $this->dbh instanceof mysqli )
				? mysqli_error( $this->dbh )
				: __( 'Unable to retrieve the error message from MySQL' ) )
			: ( is_resource( $this->dbh )
				? mysql_error( $this->dbh )
				: __( 'Unable to retrieve the error message from MySQL' ) );

		if ( $this->last_error ) {
			// Clear insert_id on a subsequent failed insert.
			if ( $this->insert_id && preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
				$this->insert_id = 0;
			}

			$this->print_error();
			return FALSE;
		}

		if ( preg_match( '/^\s*(create|alter|truncate|drop)\s/i', $query ) ) {
			$return_val = $this->result;
		} elseif ( preg_match( '/^\s*(insert|delete|update|replace)\s/i', $query ) ) {
			$this->rows_affected = $this->use_mysqli ? mysqli_affected_rows( $this->dbh ) : mysql_affected_rows( $this->dbh );

			// Take note of the insert_id.
			if ( preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
				$this->insert_id = $this->use_mysqli ? mysqli_insert_id( $this->dbh ) : mysql_insert_id( $this->dbh );
			}

			// Return number of rows affected.
			$return_val = $this->rows_affected;
		} else {
			$num_rows = 0;

			if ( $this->use_mysqli && $this->result instanceof mysqli_result ) {
				while ( $row = mysqli_fetch_object( $this->result ) ) {
					$this->last_result[ $num_rows ] = $row;
					$num_rows++;
				}
			} elseif ( is_resource( $this->result ) ) {
				while ( $row = mysql_fetch_object( $this->result ) ) {
					$this->last_result[ $num_rows ] = $row;
					$num_rows++;
				}
			}

			// Log number of rows the query returned and return number of rows selected.
			$this->num_rows = $num_rows;
			$return_val     = $num_rows;
		}

		return $return_val;
	}

	/**
	 * Internal function to perform the mysql_query() call.
	 *
	 * @since 3.9.0
	 * @see   wpdb::query()
	 *
	 * @param string $query The query to run.
	 */
	private function _do_query( $query )
	{
		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$this->timer_start();
		}

		if ( ! empty( $this->dbh ) && $this->use_mysqli ) {
			$this->result = mysqli_query( $this->dbh, $query );
		} elseif ( ! empty( $this->dbh ) ) {
			$this->result = mysql_query( $query, $this->dbh );
		}

		$this->num_queries++;

		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$this->queries[] = [$query, $this->timer_stop(), $this->get_caller()];
		}
	}

	/**
	 * Generates and returns a placeholder escape string for use in queries returned by ::prepare().
	 *
	 * @since 4.8.3
	 *
	 * @return string String to escape placeholders.
	 */
	public function placeholder_escape()
	{
		static $placeholder;

		if ( ! $placeholder ) {
			// If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
			$algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';

			// Old WP installs may not have AUTH_SALT defined.
			$salt = defined( 'AUTH_SALT' ) && AUTH_SALT ? AUTH_SALT : ( string ) rand();

			$placeholder = '{' . hash_hmac( $algo, uniqid( $salt, TRUE ), $salt ) . '}';
		}

		/**
		 * Add the filter to remove the placeholder escaper.
		 * Uses priority 0, so that anything else attached to this filter will receive the query with the placeholder string removed.
		 */
		if ( ! has_filter( 'query', [$this, 'remove_placeholder_escape'] ) ) {
			add_filter( 'query', [$this, 'remove_placeholder_escape'] );
		}

		return $placeholder;
	}

	/**
	 * Adds a placeholder escape string, to escape anything that resembles a printf() placeholder.
	 *
	 * @since 4.8.3
	 *
	 * @param  string $query The query to escape.
	 * @return string The query with the placeholder escape string inserted where necessary.
	 */
	public function add_placeholder_escape( $query )
	{
		// To prevent returning anything that even vaguely resembles a placeholder, we clobber every % we can find.
		return str_replace( '%', $this->placeholder_escape(), $query );
	}

	/**
	 * Removes the placeholder escape strings from a query.
	 *
	 * @since 4.8.3
	 *
	 * @param  string $query The query from which the placeholder will be removed.
	 * @return string The query with the placeholder removed.
	 */
	public function remove_placeholder_escape( $query )
	{
		return str_replace( $this->placeholder_escape(), '%', $query );
	}

	/**
	 * Retrieve one row from the database.
	 *
	 * Executes a SQL query and returns the row from the SQL result.
	 *
	 * @since 0.71
	 *
	 * @param  string|null            $query  SQL query.
	 * @param  string                 $output Optional.
	 *                                        The required return type.
	 *                                        One of OBJECT, ARRAY_A, or ARRAY_N, which correspond to an stdClass object, an associative array, or a numeric array, respectively.
	 *                                        Default OBJECT.
	 * @param  int                    $y      Optional.
	 *                                        Row to return.
	 *                                        Indexed from 0.
	 * @return array|object|null|void Database query result in format specified by $output or null on failure.
	 */
	public function get_row( $query = NULL, $output = OBJECT, $y = 0 )
	{
		$this->func_call = "\$db->get_row(\"$query\", $output, $y)";

		if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
			$this->check_current_query = FALSE;
		}

		if ( $query ) {
			$this->query( $query );
		} else {
			return NULL;
		}

		if ( ! isset( $this->last_query[ $y ] ) ) {
			return NULL;
		}

		if ( $output == OBJECT ) {
			return $this->last_result[ $y ] ? $this->last_result[ $y ] : NULL;
		} elseif ( $output == ARRAY_A ) {
			return $this->last_result[ $y ] ? get_object_vars( $this->last_result[ $y ] ) : NULL;
		} elseif ( $output == ARRAY_N ) {
			return $this->last_result[ $y ] ? array_values( get_object_vars( $this->last_result[ $y ] ) ) : NULL;
		} elseif ( strtoupper( $output ) === OBJECT ) {
			// Back compat for OBJECT being previously case insensitive.
			return $this->last_result[ $y ] ? $this->last_result[ $y ] : NULL;
		} else {
			$this->print_error( " \$db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N" );
		}
	}

	/**
	 * Retrieve an entire SQL result set from the database (i.e., many rows)
	 *
	 * Executes a SQL query and returns the entire SQL result.
	 *
	 * @since 0.71
	 *
	 * @param  string            $query  SQL query.
	 * @param  string            $output Optional.
	 *                                   Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants.
	 *                                   With one of the first three, return an array of rows indexed from 0 by SQL result row number.
	 *                                   Each row is an associative array (column => value, ...), a numerically indexed array (0 => value, ...), or an object ( ->column = value), respectively.
	 *                                   With OBJECT_K, return an asociative array of row objects keyed by the value of each row's first column's value.
	 *                                   Duplicate keys are discarded.
	 * @return array|object|null Database query results.
	 */
	public function get_results( $query = NULL, $output = OBJECT )
	{
		$this->func_call = "\$db->get_results(\"$query\", $output)";

		if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
			$this->check_current_query = FALSE;
		}

		if ( $query ) {
			$this->query( $query );
		} else {
			return NULL;
		}

		$new_array = [];

		if ( $output == OBJECT ) {
			// Return an integer-keyed array of row objects.
			return $this->last_result;
		} elseif ( $output == OBJECT_K ) {
			// Return an array of row objects with keys from column 1 (Duplicates are discarded).
			foreach ( $this->last_result as $row ) {
				$var_by_ref = get_object_vars( $row );
				$key = array_shift( $var_by_ref );

				if ( ! isset( $new_array[ $key ] ) ) {
					$new_array[ $key ] = $row;
				}
			}

			return $new_array;
		} elseif ( $output == ARRAY_A || $output == ARRAY_N ) {
			// Return an integer-keyed array of...
			if ( $this->last_result ) {
				foreach ( ( array ) $this->last_result as $row ) {
					$new_array[] = ( $output == ARRAY_N )
						? array_values( get_object_vars( $row ) ) // ...integer-keyed row arrays
						: get_object_vars( $row ); // ...column name-keyed row arrays
				}
			}

			return $new_array;
		} elseif ( strtoupper( $output ) === OBJECT ) {
			// Back compat for OBJECT being previously case insensitive.
			return $this->last_result;
		}

		return NULL;
	}

	/**
	 * Retrieves the character set for the given table.
	 *
	 * @since 4.2.0
	 *
	 * @param  string          $table Table name.
	 * @return string|WP_Error Table character set, WP_Error object if it couldn't be found.
	 */
	protected function get_table_charset( $table )
	{
		$tablekey = strtolower( $table );

		/**
		 * Filters the table charset value before the DB is checked.
		 *
		 * Passing a non-null value to the filter will effectively short-circuit checking the DB for the charset, returning that value instead.
		 *
		 * @since 4.2.0
		 *
		 * @param string $charset The character set to use.
		 *                        Default null.
		 * @param string $table   The name of the table being checked.
		 */
		$charset = apply_filters( 'pre_get_table_charset', NULL, $table );

		if ( NULL !== $charset ) {
			return $charset;
		}

		if ( isset( $this->table_charset[ $tablekey ] ) ) {
			return $this->table_charset[ $tablekey ];
		}

		$charsets = columns = [];
		$table_parts = explode( '.', $table );
		$table = '`' . implode( '`.`', $table_parts ) . '`';
		$results = $this->get_results( "SHOW FULL COLUMNS FROM $table" );

		if ( ! $results ) {
			return new WP_Error( 'wpdb_get_table_charset_failure' );
		}

		foreach ( $results as $column ) {
			$columns[ strtolower( $column->Field ) ] = $column;
		}

		$this->col_meta[ $tablekey ] = $columns;

		foreach ( $columns as $column ) {
			if ( ! empty( $column->Collation ) ) {
				list( $charset ) = explode( '_', $column->Collation );

				// If the current connection can't support utf8mb4 characters, let's only send 3-byte utf8 characters.
				if ( 'utf8mb4' === $charset && ! $this->has_cap( 'utf8mb4' ) ) {
					$charset = 'utf8';
				}

				$charsets[ strtolower( $charset ) ] = TRUE;
			}

			list( $type ) = explode( '(', $column->Type );

			// A binary/blob means the whole query gets treated like this.
			if ( in_array( strtoupper( $type ), ['BINARY', 'VARBINARY', 'TINYBLOB', 'MEDIUMBLOB', 'BLOB', 'LONGBLOB'] ) ) {
				$this->table_charset[ $tablekey ] = 'binary';
				return 'binary';
			}
		}

		// utf8mb3 is an alias for utf8.
		if ( isset( $charsets['utf8mb3'] ) ) {
			$charsets['utf8'] = TRUE;
			unset( $charsets['utf8mb3'] );
		}

		// Check if we have more than one charset in play.
		$count = count( $charsets );

		if ( 1 === $count ) {
			$charset = key( $charsets );
		} elseif ( 0 === $count ) {
			// No charsets, assume this table can store whatever.
			$charset = FALSE;
		} else {
			/**
			 * More than one charset.
			 * Remove latin1 if present and recalculate.
			 */
			unset( $charsets['latin1'] );
			$count = count( $charsets );

			if ( 1 === $count ) {
				// Only one charset (besides latin1).
				$charset = key( $charsets );
			} elseif ( 2 === $count && isset( $charsets['utf8'], $charsets['utf8mb4'] ) ) {
				// Two charsets, but they're utf8 and utf8mb4, use utf8.
				$charset = 'utf8';
			} else {
				// Two mixed character sets, ascii.
				$charset = 'ascii';
			}
		}

		$this->table_charset[ $tablekey ] = $charset;
		return $charset;
	}

	/**
	 * Check if a string is ASCII.
	 *
	 * The negative regex is faster for non-ASCII strings, as it allows the search to finish as soon as it encounters a non-ASCII character.
	 *
	 * @since 4.2.0
	 *
	 * @param  string $string String to check.
	 * @return bool   True if ASCII, false if not.
	 */
	protected function check_ascii( $string )
	{
		if ( function_exists( 'mb_check_encoding' ) ) {
			if ( mb_check_encoding( $string, 'ASCII' ) ) {
				return TRUE;
			}
		} elseif ( ! preg_match( '/[^\x00-\x7F]/', $string ) ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Check if the query is accessing a collation considered safe on the current version of MySQL.
	 *
	 * @since 4.2.0
	 *
	 * @param  string $query The query to check.
	 * @return bool   True if the collation is safe, false if it isn't.
	 */
	protected function check_safe_collation( $query )
	{
		if ( $this->checking_collation ) {
			return TRUE;
		}

		// We don't need to check the collation for queries that don't read data.
		$query = ltrim( $query, "\r\n\t (" );

		if ( preg_match( '/^(?:SHOW|DESCRIBE|DESC|EXPLAIN|CREATE)\s/i', $query ) ) {
			return TRUE;
		}

		// All-ASCII queries don't need extra checking.
		if ( $this->check_ascii( $query ) ) {
			return TRUE;
		}

		$table = $this->get_table_from_query( $query );

		if ( ! $table ) {
			return FALSE;
		}

		$this->checking_collation = TRUE;
		$collation = $this->get_table_charset( $table );
		$this->checking_collation = FALSE;

		// Tables with no collation, or latin1 only, don't need extra checking.
		if ( FALSE === $collation || 'latin1' === $collation ) {
			return TRUE;
		}

		$table = strtolower( $table );

		if ( empty( $this->col_meta[ $table ] ) ) {
			return FALSE;
		}

		// If any of the columns don't have one of these collations, it needs more sanity checking.
		foreach ( $this->col_meta[ $table ] as $col ) {
			if ( empty( $col->Collation ) ) {
				continue;
			}

			if ( ! in_array( $col->Collation, ['utf8_general_ci', 'utf8_bin', 'utf8mb4_general_ci', 'utf8mb4_bin'], TRUE ) ) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Strips any invalid characters based on value/charset pairs.
	 *
	 * @since 4.2.0
	 *
	 * @param  array          $data Array of value arrays.
	 *                              Each value array has the keys 'value' and 'charset'.
	 *                              An optional 'ascii' key can be set to false to avoid redundant ASCII checks.
	 * @return array|WP_Error The $data parameter, with invalid characters removed from each value.
	 *                        This works as a passthrough: any additional keys such as 'field' are retained in each value array.
	 *                        If we cannot remove invalid characters, a WP_Error object is returned.
	 */
	protected function strip_invalid_text( $data )
	{
		$db_check_string = FALSE;

		foreach ( $data as &$value ) {
			$charset = $value['charset'];

			if ( is_array( $value['length'] ) ) {
				$length = $value['length']['length'];
				$truncate_by_byte_length = 'byte' === $value['length']['type'];
			} else {
				$length = FALSE;

				/**
				 * Since we have no length, we'll never truncate.
				 * Initialize the variable to false.
				 * True would take us through an unnecessary (for this case) codepath below.
				 */
				$truncate_by_byte_length = FALSE;
			}

			// There's no charset to work with.
			if ( FALSE === $charset ) {
				continue;
			}

			// Column isn't a string.
			if ( ! is_string( $value['value'] ) ) {
				continue;
			}

			$needs_validation = TRUE;

			if ( // latin1 can store any byte sequence.
			     'latin1' === $charset
			  || // ASCII is always OK.
			     ( ! isset( $value['ascii'] ) && $this->check_ascii( $value['value'] ) ) ) {
				$truncate_by_byte_length = TRUE;
				$needs_validation = FALSE;
			}

			if ( $truncate_by_byte_length ) {
				mbstring_binary_safe_encoding();

				if ( FALSE !== $length && strlen( $value['value'] ) > $length ) {
					$value['value'] = substr( $value['value'], 0, $length );
				}

				reset_mbstring_encoding();

				if ( ! $needs_validation ) {
					continue;
				}
			}

			// utf8 can be handled by regex, which is a bunch faster than a DB lookup.
			if ( ( 'utf8' === $charset || 'utf8mb3' === $charset || 'utf8mb4' === $charset )
			  && function_exists( 'mb_strlen' ) ) {
				$regex = '/('
					. '(?:[\x00-\x7F]'              // single-byte sequences 0xxxxxxx
					. '|[\xC2-\xDF][\x80-\xBF]'     // double-byte sequences 110xxxxx 10xxxxxx
					. '|\xE0[\xA0-\xBF][\x80-\xBF]' // triple-byte sequences 1110xxxx 10xxxxxx * 2
					. '|[\xE1-\xEC][\x80-\xBF]{2}'
					. '|\xED[\x80-\x9F][\x80-\xBF]'
					. '|[\xEE-\xEF][\x80-\xBF]{2}';

				if ( 'utf8mb4' === $charset ) {
					$regex .= '|\xF0[\x90-\xBF][\x80-\xBF]{2}' // four-byte sequences 11110xxx 10xxxxxx * 3
						. '|[\xF1-\xF3][\x80-\xBF]{3}'
						. '|\xF4[\x80-\x8F][\x80-\xBF]{2}';
				}

				$regex .= '){1,40}' // ...one or more times
					. ')|.'         // anything else
					. '/x';
				$value['value'] = preg_replace( $regex, '$1', $value['value'] );

				if ( FALSE !== $length && mb_strlen( $value['value'], 'UTF-8' ) > $length ) {
					$value['value'] = mb_substr( $value['value'], 0, $length, 'UTF-8' );
				}

				continue;
			}

			// We couldn't use any local conversions, send it to the DB.
			$value['db'] = $db_checking_string = TRUE;
		}

		unset( $value ); // Remove by reference.

		if ( $db_check_string ) {
			$queries = [];

			foreach ( $data as $col => $value ) {
				if ( ! empty( $value['db'] ) ) {
					// We're going to need to truncate by characters or bytes, depending on the length value we have.
					$charset = ( 'byte' === $value['length']['type'] )
						? 'binary' // Using binary causes LEFT() to truncate by bytes.
						: $value['charset'];

					$connection_charset = $this->charset
						? $this->charset
						: ( $this->use_mysqli
							? mysqli_character_set_name( $this->dbh )
							: mysql_client_encoding() );

					if ( is_array( $value['length'] ) ) {
						$length = sprintf( '%.0f', $value['length']['length'] );
						$queries[ $col ] = $this->prepare( "CONVERT( LEFT( CONVERT( %s USING $charset ), $length ) USING $connection_charset )", $value['value'] );
					} else if ( 'binary' !== $charset ) {
						// If we don't have a length, there's no need to convert binary - it will always return the same result.
						$queries[ $col ] = $this->prepare( "CONVERT( CONVERT( %s USING $charset ) USING $connection_charset )", $value['value'] );
					}

					unset( $data[ $col ]['db'] );
				}
			}

			$sql = [];

			foreach ( $queries as $column => $query ) {
				if ( ! $query ) {
					continue;
				}

				$sql[] = $query . " AS x_$column";
			}

			$this->check_current_query = FALSE;
			$row = $this->get_row( "SELECT " . implode( ', ', $sql ), ARRAY_A );

			if ( ! $row ) {
				return new WP_Error( 'wpdb_strip_invalid_text_failure' );
			}

			foreach ( array_keys( $data ) as $column ) {
				if ( isset( $row["x_$column"] ) ) {
					$data[ $column ]['value'] = $row["x_$column"];
				}
			}
		}

		return $data;
	}

	/**
	 * Strips any invalid characters from the query.
	 *
	 * @since 4.2.0
	 *
	 * @param  string          $query Query to convert.
	 * @return string|WP_Error The converted query, or a WP_Error object if the conversion fails.
	 */
	protected function strip_invalid_text_from_query( $query )
	{
		// We don't need to check the collation for queries that don't read data.
		$trimmed_query = ltrim( $query, "\r\n\t (" );

		if ( preg_match( '/^(?:SHOW|DESCRIBE|DESC|EXPLAIN|CREATE)\s/i', $trimmed_query ) ) {
			return $query;
		}

		$table = $this->get_table_from_query( $query );

		if ( $table ) {
			$charset = $this->get_table_charset( $table );

			if ( is_wp_error( $charset ) ) {
				return $charset;
			}

			// We can't reliably strip text from tables containing binary/blob columns.
			if ( 'binary' === $charset ) {
				return $query;
			}
		} else {
			$charset = $this->charset;
		}

		$data = [
			'value'   => $query,
			'charset' => $charset,
			'ascii'   => FALSE,
			'length'  => FALSE
		];
		$data = $this->strip_invalid_text( [ $data ] );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return $data[0]['value'];
	}

	/**
	 * Find the first table name referenced in a query.
	 *
	 * @since 4.2.0
	 *
	 * @param  string       $query The query to search.
	 * @return string|false $table The table name found, or false if a table couldn't be found.
	 */
	protected function get_table_from_query( $query )
	{
		// Remove characters that can legally trail the table name.
		$query = rtrim( $query, ';/-#' );

		/**
		 * Allow (select...) union [...] style queries.
		 * Use the first query's table name.
		 */
		$query = ltrim( $query, "\r\n\t (" );

		// Strip everything between parentheses except nested selects.
		$query = preg_replace( '/\((?!\s*select)[^(]*?\)/is', '()', $query );

		// Quickly match most common queries.
		if ( preg_match( '/^\s*(?:'
				. 'SELECT.*?\s+FROM'
				. '|INSERT(?:\s+LOW_PRIORITY|\s+DELAYED|\s+HIGH_PRIORITY)?(?:\s+IGNORE)?(?:\s+INTO)?'
				. '|REPLACE(?:\s+LOW_PRIORITY|\s+DELAYED)?(?:\s+INTO)?'
				. '|UPDATE(?:\s+LOW_PRIORITY)?(?:\s+IGNORE)?'
				. '|DELETE(?:\s+LOW_PRIORITY|\s+QUICK|\s+IGNORE)*(?:.+?FROM)?'
				. ')\s+((?:[0-9a-zA-Z$_.`-]|[\xC2-\xDF][\x80-\xBF])+)/is', $query, $maybe ) ) {
			return str_replace( '`', '', $maybe[1] );
		}

		// SHOW TABLE STATUS and SHOW TABLES WHERE Name = 'wp_posts'
		if ( preg_match( '/^\s*SHOW\s+(?:TABLE\s+STATUS|(?:FULL\s+)?TABLES).+WHERE\s+Name\s*=\s*("|\')((?:[0-9a-zA-Z$_.-]|[\xC2-\xDF][\x80-\xBF])+)\\1/is', $query, $maybe ) ) {
			return $maybe[2];
		}

		/**
		 * SHOW TABLE STATUS LIKE and SHOW TABLES LIKE 'wp\_123\_%'
		 * This quoted LIKE operand seldom holds a full table name.
		 * It is usually a pattern for matching a prefix so we just strip the trailing % and unescape the _ to get 'wp_123_' which drop-ins can use for routing these SQL statements.
		 */
		if ( preg_match( '/^\s*SHOW\s+(?:TABLE\s+STATUS|(?:FULL\s+)?TABLES)\s+(?:WHERE\s+Name\s+)?LIKE\s*("|\')((?:[\\\\0-9a-zA-Z$_.-]|[\xC2-\xDF][\x80-\xBF])+)%?\\1/is', $query, $maybe ) ) {
			return str_replace( '\\_', '_', $maybe[2] );
		}

		// Big pattern for the rest of the table-related queries.
		if ( preg_match( '/^\s*(?:'
				. '(?:EXPLAIN\s+(?:EXTENDED\s+)?)?SELECT.*?\s+FROM'
				. '|DESCRIBE|DESC|EXPLAIN|HANDLER'
				. '|(?:LOCK|UNLOCK)\s+TABLE(?:S)?'
				. '|(?:RENAME|OPTIMIZE|BACKUP|RESTORE|CHECK|CHECKSUM|ANALYZE|REPAIR).*\s+TABLE'
				. '|TRUNCATE(?:\s+TABLE)?'
				. '|CREATE(?:\s+TEMPORARY)?\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?'
				. '|ALTER(?:\s+IGNORE)?\s+TABLE'
				. '|DROP\s+TABLE(?:\s+IF\s+EXISTS)?'
				. '|CREATE(?:\s+\w+)?\s+INDEX.*\s+ON'
				. '|DROP\s+INDEX.*\s+ON'
				. '|LOAD\s+DATA.*INFILE.*INTO\s+TABLE'
				. '|(?:GRANT|REVOKE).*ON\s+TABLE'
				. '|SHOW\s+(?:.*FROM|.*TABLE)'
				. ')\s+\(*\s*((?:[0-9a-zA-Z$_.`-]|[\xC2-\xDF][\x80-\xBF])+)\s*\)*/is', $query, $maybe ) ) {
			return str_replace( '`', '', $maybe[1] );
		}

		return FALSE;
	}

	/**
	 * Starts the timer, for debugging purposes.
	 *
	 * @since 1.5.0
	 *
	 * @return true
	 */
	public function timer_start()
	{
		$this->timer_start = microtime( TRUE );
		return TRUE;
	}

	/**
	 * Stops the debugging timer.
	 *
	 * @since 1.5.0
	 *
	 * @return float Total time spent on the query, in seconds.
	 */
	public function timer_stop()
	{
		return ( microtime( TRUE ) - $this->timer_start );
	}

	/**
	 * Wraps errors in a nice header and footer and dies.
	 *
	 * Will not die if wpdb::$show_errors is false.
	 *
	 * @since 1.5.0
	 *
	 * @param  string     $message    The Error message.
	 * @param  string     $error_code Optional.
	 *                                A Computer readable string to identify the error.
	 * @return false|void
	 */
	public function bail( $message, $error_code = '500' )
	{
		if ( ! $this->show_errors ) {
			$this->error = class_exists( 'WP_Error', FALSE )
				? new WP_Error( $error_code, $message )
				: $message;

			return FALSE;
		}

		wp_die( $message );
	}

	/**
	 * Determine if a database supports a particular feature.
	 *
	 * @since 2.7.0
	 * @since 4.1.0 Added support for the 'utf8mb4' feature.
	 * @since 4.6.0 Added support for the 'utf8mb4_520' feature.
	 * @see   wpdb::db_version()
	 *
	 * @param  string    $db_cap The feature to check for.
	 *                           Accepts 'collation', 'group_concat', 'subqueries', 'set_charset', 'utf8mb4', or 'utf8mb4_520'.
	 * @return int|false Whether the database feature is supported, false otherwise.
	 */
	public function has_cap( $db_cap )
	{
		$version = $this->db_version();

		switch ( strtolower( $db_cap ) ) {
			case 'collation': // @since 2.5.0
			case 'group_concat': // @since 2.7.0
			case 'subqueries': // @since 2.7.0
				return version_compare( $version, '4.1', '>=' );

			case 'set_charset':
				return version_compare( $version, '5.0.7', '>=' );

			case 'utf8mb4': // @since 4.1.0
				if ( version_compare( $version, '5.5.3', '<' ) )
					return FALSE;

				$client_version = $this->use_mysqli ? mysqli_get_client_info() : mysql_get_client_info();

				/**
				 * libmysql has supported utf8mb4 since 5.5.3, same as the MySQL server.
				 * mysqlnd has supported utf8mb4 since 5.0.9.
				 */
				if ( FALSE !== strpos( $client_version, 'mysqlnd' ) ) {
					$client_version = preg_replace( '/^\D+([\d.]+).*/', '$1', $client_version );
					return version_compare( $client_version, '5.0.9', '>=' );
				} else {
					return version_compare( $client_version, '5.5.3', '>=' );
				}

			case 'utf8mb4_520': // @since 4.6.0
				return version_compare( $version, '5.6', '>=' );
		}

		return FALSE;
	}

	/**
	 * Retrieve the name of the function that called wpdb.
	 *
	 * Searches up the list of functions until it reaches the one that would most logically had called this method.
	 *
	 * @since 2.5.0
	 *
	 * @return string|array The name of the calling function.
	 */
	public function get_caller()
	{
		return wp_debug_backtrace_summary( __CLASS__ );
	}

	/**
	 * Retrieves the MySQL server version.
	 *
	 * @since 2.7.0
	 *
	 * @return null|string Null on failure, version number on success.
	 */
	public function db_version()
	{
		$server_info = $this->use_mysqli
			? mysqli_get_server_info( $this->dbh )
			: mysql_get_server_info( $this->dbh );
		return preg_replace( '/[^0-9.].*/', '', $server_info );
	}
}
