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

		if ( WP_DEBUG && WP_DEBUG_DISPLAY )
			$this->show_errors();

		// Use ext/mysqli if it exists unless WP_USE_EXT_MYSQL is defined as true
		if ( function_exists( 'mysqli_connect' ) ) {
			$this->use_mysqli = TRUE;

			if ( defined( 'WP_USE_EXT_MYSQL' ) )
				$this->use_mysqli = ! WP_USE_EXT_MYSQL;
		}

		$this->dbuser = $dbuser;
		$this->dbpassword = $dbpassword;
		$this->dbname = $dbname;
		$this->dbhost = $dbhost;

		// wp-config.php creation will manually connect when ready.
		if ( defined( 'WP_SETUP_CONFIG' ) )
			return;

		$this->db_connect();
		// @NOW 018 -> wp-includes/wp-db.php
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
		} elseif ( defined( 'DB_COLLATE' ) )
			$collate = DB_COLLATE;

		if ( defined( 'DB_CHARSET' ) )
			$charset = DB_CHARSET;

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
		  || empty( $this->dbh ) )
			return compact( 'charset', 'collate' );

		if ( 'utf8' === $charset && $this->has_cap( 'utf8mb4' ) )
			$charset = 'utf8mb4';

		if ( 'utf8mb4' === $charset && ! $this->has_cap( 'utf8mb4' ) ) {
			$charset = 'utf8';
			$collate = str_replace( 'utf8mb4_', 'utf8_', $collate );
		}

		if ( 'utf8mb4' === $charset )
			// _general_ is outdated, so we can upgrade it to _unicode_, instead.
			$collate = ( ! $collate || 'utf8_general_ci' === $collate )
				? 'utf8mb4_unicode_ci'
				: str_replace( 'utf8_', 'utf8mb4_', $collate );

		// _unicode_520_ is a better collation, we should use that when it's available.
		if ( $this->has_cap( 'utf8mb4_520' ) && 'utf8mb4_unicode_ci' === $collate )
			$collate = 'utf8mb4_unicode_520_ci';

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
		if ( ! isset( $charset ) )
			$charset = $this->charset;

		if ( ! isset( $collate ) )
			$collate = $this->collate;

		if ( $this->has_cap( 'collation' ) && ! empty( $charset ) ) {
			$set_charset_succeeded = TRUE;

			if ( $this->use_mysqli ) {
				if ( function_exists( 'mysqli_set_charset' ) && $this->has_cap( 'set_charset' ) )
					$set_charset_succeeded = mysqli_set_charset( $dbh, $charset );

				if ( $set_charset_succeeded ) {
					$query = $this->prepare( 'SET NAMES %s', $charset );
					// @NOW 020 -> wp-includes/wp-db.php
				}
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
		if ( $this->dbh )
			$escaped = $this->use_mysqli ? mysqli_real_escape_string( $this->dbh, $string ) : mysql_real_escape_string( $string, $this->dbh );
		else {
			$class = get_class( $this );

			if ( function_exists( '__' ) )
				_doing_it_wrong( $class, sprintf( __( '%s must set a database connection for use with escaping.' ), $class ), '3.6.0' );
			else
				_doing_it_wrong( $class, sprintf( '%s must set a database connection for use with escaping.', $class ), '3.6.0' );

			$escaped = addslashes( $string );
		}

		return $this->add_placeholder_escape( $escaped );
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
		if ( ! is_float( $string ) )
			$string = $this->_real_escape( $string );
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
		if ( is_null( $query ) )
			return;

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

		foreach ( $args as $arg )
			if ( ! is_scalar( $arg ) && ! is_null( $arg ) ) {
				wp_load_translations_early();
				_doing_it_wrong( 'wpdb::prepare', sprintf( __( 'Unsupported value type (%s).' ), gettype( $arg ) ), '4.8.2' );
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
		// @NOW 021 -> wp-includes/wp-db.php
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

			if ( $host_data = $this->parse_db_host( $this->dbhost ) )
				list( $host, $port, $socket, $is_ipv6 ) = $host_data;

			/**
			 * If using the `mysqlnd` library, the IPv6 address needs to be enclosed in square brackets, whereas it doesn't while using the `libmysqlclient` library.
			 *
			 * @see https://bugs.php.net/bug.php?id=67563
			 */
			if ( $is_ipv6 && extension_loaded( 'mysqlnd' ) )
				$host = "[$host]";

			if ( WP_DEBUG )
				mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, NULL, $port, $socket, $client_flags );
			else
				@mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, NULL, $port, $socket, $client_flags );

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

				if ( $this->has_connected )
					$attempt_fallback = FALSE;
				elseif ( defined( 'WP_USE_EXT_MYSQL' ) && ! WP_USE_EXT_MYSQL )
					$attempt_fallback = FALSE;
				elseif ( ! function_exists( 'mysql_connect' ) )
					$attempt_fallback = FALSE;

				if ( $attempt_fallback ) {
					$this->use_mysqli = FALSE;
					return $this->db_connect( $allow_bail );
				}
			}
		} else {
			if ( WP_DEBUG )
				$this->dbh = mysql_connect( $this->dbhost, $this->dbuser, $this->dbpassword, $new_link, $client_flags );
			else
				$this->dbh = @mysql_connect( $this->dbhost, $this->dbuser, $this->dbpassword, $new_link, $client_flags );
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
			if ( ! $this->has_connected )
				$this->init_charset();

			$this->has_connected = TRUE;
			$this->set_charset( $this->dbh );
			// @NOW 019 -> wp-includes/wp-db.php
		}
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
		} else
			// We seem to be dealing with an IPv4 address.
			$pattern = '#^(?P<host>[^:/]*)(?::(?P<port>[\d]+))?#';

		$matches = [];
		$result = preg_match( $pattern, $host, $matches );

		if ( 1 !== $result )
			// Couldn't parse the address, bail.
			return FALSE;

		$host = '';

		foreach ( ['host', 'port'] as $component )
			if ( ! empty( $matches[$component] ) )
				$$component = $matches[$component];

		return [$host, $port, $socket, $is_ipv6];
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
		if ( ! has_filter( 'query', [$this, 'remove_placeholder_escape'] ) )
			add_filter( 'query', [$this, 'remove_placeholder_escape'] );
			// @NOW 022
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
				} else
					return version_compare( $client_version, '5.5.3', '>=' );

			case 'utf8mb4_520': // @since 4.6.0
				return version_compare( $version, '5.6', '>=' );
		}

		return FALSE;
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
