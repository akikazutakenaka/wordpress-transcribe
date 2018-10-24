<?php
/**
 * WordPress DB Class
 *
 * Original code from {@link http://php.justinvincent.com Justin Vincent (justin@visunet.ie)}
 *
 * @package WordPress
 * @subpackage Database
 * @since 0.71
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
 * It is possible to replace this class with your own
 * by setting the $wpdb global variable in wp-content/db.php
 * file to your class. The wpdb class will still be included,
 * so you can extend it or simply use your own.
 *
 * @link https://codex.wordpress.org/Function_Reference/wpdb_Class
 *
 * @since 0.71
 */
class wpdb {
	// refactored. var $show_errors = false;
	// :
	// refactored. public function __destruct() {}

	/**
	 * Makes private properties readable for backward compatibility.
	 *
	 * @since 3.5.0
	 *
	 * @param string $name The private member to get, and optionally process
	 * @return mixed The private member
	 */
	public function __get( $name ) {
		if ( 'col_info' === $name )
			$this->load_col_info();

		return $this->$name;
	}

	/**
	 * Makes private properties settable for backward compatibility.
	 *
	 * @since 3.5.0
	 *
	 * @param string $name  The private member to set
	 * @param mixed  $value The value to set
	 */
	public function __set( $name, $value ) {
		$protected_members = array(
			'col_meta',
			'table_charset',
			'check_current_query',
		);
		if (  in_array( $name, $protected_members, true ) ) {
			return;
		}
		$this->$name = $value;
	}

	/**
	 * Makes private properties check-able for backward compatibility.
	 *
	 * @since 3.5.0
	 *
	 * @param string $name  The private member to check
	 *
	 * @return bool If the member is set or not
	 */
	public function __isset( $name ) {
		return isset( $this->$name );
	}

	/**
	 * Makes private properties un-settable for backward compatibility.
	 *
	 * @since 3.5.0
	 *
	 * @param string $name  The private member to unset
	 */
	public function __unset( $name ) {
		unset( $this->$name );
	}

	// refactored. public function init_charset() {}
	// :
	// refactored. public function select( $db, $dbh = null ) {}

	/**
	 * Do not use, deprecated.
	 *
	 * Use esc_sql() or wpdb::prepare() instead.
	 *
	 * @since 2.8.0
	 * @deprecated 3.6.0 Use wpdb::prepare()
	 * @see wpdb::prepare
	 * @see esc_sql()
	 *
	 * @param string $string
	 * @return string
	 */
	function _weak_escape( $string ) {
		if ( func_num_args() === 1 && function_exists( '_deprecated_function' ) )
			_deprecated_function( __METHOD__, '3.6.0', 'wpdb::prepare() or esc_sql()' );
		return addslashes( $string );
	}

	// refactored. function _real_escape( $string ) {}
	// refactored. public function _escape( $data ) {}

	/**
	 * Do not use, deprecated.
	 *
	 * Use esc_sql() or wpdb::prepare() instead.
	 *
	 * @since 0.71
	 * @deprecated 3.6.0 Use wpdb::prepare()
	 * @see wpdb::prepare()
	 * @see esc_sql()
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public function escape( $data ) {
		if ( func_num_args() === 1 && function_exists( '_deprecated_function' ) )
			_deprecated_function( __METHOD__, '3.6.0', 'wpdb::prepare() or esc_sql()' );
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				if ( is_array( $v ) )
					$data[$k] = $this->escape( $v, 'recursive' );
				else
					$data[$k] = $this->_weak_escape( $v, 'internal' );
			}
		} else {
			$data = $this->_weak_escape( $data, 'internal' );
		}

		return $data;
	}

	// refactored. public function escape_by_ref( &$string ) {}
	// :
	// refactored. public function show_errors( $show = true ) {}

	/**
	 * Disables showing of database errors.
	 *
	 * By default database errors are not shown.
	 *
	 * @since 0.71
	 * @see wpdb::show_errors()
	 *
	 * @return bool Whether showing of errors was active
	 */
	public function hide_errors() {
		$show = $this->show_errors;
		$this->show_errors = false;
		return $show;
	}

	// refactored. public function suppress_errors( $suppress = true ) {}
	// :
	// refactored. public function insert( $table, $data, $format = null ) {}

	/**
	 * Replace a row into a table.
	 *
	 *     wpdb::replace( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
	 *     wpdb::replace( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
	 *
	 * @since 3.0.0
	 * @see wpdb::prepare()
	 * @see wpdb::$field_types
	 * @see wp_set_wpdb_vars()
	 *
	 * @param string       $table  Table name
	 * @param array        $data   Data to insert (in column => value pairs).
	 *                             Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 *                             Sending a null value will cause the column to be set to NULL - the corresponding format is ignored in this case.
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data.
	 *                             If string, that format will be used for all of the values in $data.
	 *                             A format is one of '%d', '%f', '%s' (integer, float, string).
	 *                             If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @return int|false The number of rows affected, or false on error.
	 */
	public function replace( $table, $data, $format = null ) {
		return $this->_insert_replace_helper( $table, $data, $format, 'REPLACE' );
	}

	// refactored. function _insert_replace_helper( $table, $data, $format = null, $type = 'INSERT' ) {}
	// :
	// refactored. protected function get_table_from_query( $query ) {}

	/**
	 * Load the column metadata from the last query.
	 *
	 * @since 3.5.0
	 *
	 */
	protected function load_col_info() {
		if ( $this->col_info )
			return;

		if ( $this->use_mysqli ) {
			$num_fields = mysqli_num_fields( $this->result );
			for ( $i = 0; $i < $num_fields; $i++ ) {
				$this->col_info[ $i ] = mysqli_fetch_field( $this->result );
			}
		} else {
			$num_fields = mysql_num_fields( $this->result );
			for ( $i = 0; $i < $num_fields; $i++ ) {
				$this->col_info[ $i ] = mysql_fetch_field( $this->result, $i );
			}
		}
	}

	/**
	 * Retrieve column metadata from the last query.
	 *
	 * @since 0.71
	 *
	 * @param string $info_type  Optional. Type one of name, table, def, max_length, not_null, primary_key, multiple_key, unique_key, numeric, blob, type, unsigned, zerofill
	 * @param int    $col_offset Optional. 0: col name. 1: which table the col's in. 2: col's max length. 3: if the col is numeric. 4: col's type
	 * @return mixed Column Results
	 */
	public function get_col_info( $info_type = 'name', $col_offset = -1 ) {
		$this->load_col_info();

		if ( $this->col_info ) {
			if ( $col_offset == -1 ) {
				$i = 0;
				$new_array = array();
				foreach ( (array) $this->col_info as $col ) {
					$new_array[$i] = $col->{$info_type};
					$i++;
				}
				return $new_array;
			} else {
				return $this->col_info[$col_offset]->{$info_type};
			}
		}
	}

	// refactored. public function timer_start() {}
	// :
	// refactored. public function bail( $message, $error_code = '500' ) {}

	/**
	 * Closes the current database connection.
	 *
	 * @since 4.5.0
	 *
	 * @return bool True if the connection was successfully closed, false if it wasn't,
	 *              or the connection doesn't exist.
	 */
	public function close() {
		if ( ! $this->dbh ) {
			return false;
		}

		if ( $this->use_mysqli ) {
			$closed = mysqli_close( $this->dbh );
		} else {
			$closed = mysql_close( $this->dbh );
		}

		if ( $closed ) {
			$this->dbh = null;
			$this->ready = false;
			$this->has_connected = false;
		}

		return $closed;
	}

	/**
	 * Whether MySQL database is at least the required minimum version.
	 *
	 * @since 2.5.0
	 *
	 * @global string $wp_version
	 * @global string $required_mysql_version
	 *
	 * @return WP_Error|void
	 */
	public function check_database_version() {
		global $wp_version, $required_mysql_version;
		// Make sure the server has the required MySQL version
		if ( version_compare($this->db_version(), $required_mysql_version, '<') ) {
			/* translators: 1: WordPress version number, 2: Minimum required MySQL version number */
			return new WP_Error('database_version', sprintf( __( '<strong>ERROR</strong>: WordPress %1$s requires MySQL %2$s or higher' ), $wp_version, $required_mysql_version ));
		}
	}

	/**
	 * Whether the database supports collation.
	 *
	 * Called when WordPress is generating the table scheme.
	 *
	 * Use `wpdb::has_cap( 'collation' )`.
	 *
	 * @since 2.5.0
	 * @deprecated 3.5.0 Use wpdb::has_cap()
	 *
	 * @return bool True if collation is supported, false if version does not
	 */
	public function supports_collation() {
		_deprecated_function( __FUNCTION__, '3.5.0', 'wpdb::has_cap( \'collation\' )' );
		return $this->has_cap( 'collation' );
	}

	/**
	 * The database character collate.
	 *
	 * @since 3.5.0
	 *
	 * @return string The database character collate.
	 */
	public function get_charset_collate() {
		$charset_collate = '';

		if ( ! empty( $this->charset ) )
			$charset_collate = "DEFAULT CHARACTER SET $this->charset";
		if ( ! empty( $this->collate ) )
			$charset_collate .= " COLLATE $this->collate";

		return $charset_collate;
	}

	// refactored. public function has_cap( $db_cap ) {}
	// :
	// refactored. public function db_version() {}
}
