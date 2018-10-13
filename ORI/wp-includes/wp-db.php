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
	// refactored. public function set_sql_mode( $modes = array() ) {}

	/**
	 * Sets the table prefix for the WordPress tables.
	 *
	 * @since 2.5.0
	 *
	 * @param string $prefix          Alphanumeric name for the new prefix.
	 * @param bool   $set_table_names Optional. Whether the table names, e.g. wpdb::$posts, should be updated or not.
	 * @return string|WP_Error Old prefix or WP_Error on error
	 */
	public function set_prefix( $prefix, $set_table_names = true ) {

		if ( preg_match( '|[^a-z0-9_]|i', $prefix ) )
			return new WP_Error('invalid_db_prefix', 'Invalid database prefix' );

		$old_prefix = is_multisite() ? '' : $prefix;

		if ( isset( $this->base_prefix ) )
			$old_prefix = $this->base_prefix;

		$this->base_prefix = $prefix;

		if ( $set_table_names ) {
			foreach ( $this->tables( 'global' ) as $table => $prefixed_table )
				$this->$table = $prefixed_table;

			if ( is_multisite() && empty( $this->blogid ) )
				return $old_prefix;

			$this->prefix = $this->get_blog_prefix();

			foreach ( $this->tables( 'blog' ) as $table => $prefixed_table )
				$this->$table = $prefixed_table;

			foreach ( $this->tables( 'old' ) as $table => $prefixed_table )
				$this->$table = $prefixed_table;
		}
		return $old_prefix;
	}

	// refactored. public function set_blog_id( $blog_id, $network_id = 0 ) {}
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
	// refactored. public function prepare( $query, $args ) {}

	/**
	 * First half of escaping for LIKE special characters % and _ before preparing for MySQL.
	 *
	 * Use this only before wpdb::prepare() or esc_sql().  Reversing the order is very bad for security.
	 *
	 * Example Prepared Statement:
	 *
	 *     $wild = '%';
	 *     $find = 'only 43% of planets';
	 *     $like = $wild . $wpdb->esc_like( $find ) . $wild;
	 *     $sql  = $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_content LIKE %s", $like );
	 *
	 * Example Escape Chain:
	 *
	 *     $sql  = esc_sql( $wpdb->esc_like( $input ) );
	 *
	 * @since 4.0.0
	 *
	 * @param string $text The raw text to be escaped. The input typed by the user should have no
	 *                     extra or deleted slashes.
	 * @return string Text in the form of a LIKE phrase. The output is not SQL safe. Call $wpdb::prepare()
	 *                or real_escape next.
	 */
	public function esc_like( $text ) {
		return addcslashes( $text, '_%\\' );
	}

	// refactored. public function print_error( $str = '' ) {}
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
	// refactored. public function remove_placeholder_escape( $query ) {}

	/**
	 * Insert a row into a table.
	 *
	 *     wpdb::insert( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
	 *     wpdb::insert( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
	 *
	 * @since 2.5.0
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
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public function insert( $table, $data, $format = null ) {
		return $this->_insert_replace_helper( $table, $data, $format, 'INSERT' );
	}

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

	/**
	 * Helper function for insert and replace.
	 *
	 * Runs an insert or replace query based on $type argument.
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
	 * @param string $type         Optional. What type of operation is this? INSERT or REPLACE. Defaults to INSERT.
	 * @return int|false The number of rows affected, or false on error.
	 */
	function _insert_replace_helper( $table, $data, $format = null, $type = 'INSERT' ) {
		$this->insert_id = 0;

		if ( ! in_array( strtoupper( $type ), array( 'REPLACE', 'INSERT' ) ) ) {
			return false;
		}

		$data = $this->process_fields( $table, $data, $format );
		if ( false === $data ) {
			return false;
		}

		$formats = $values = array();
		foreach ( $data as $value ) {
			if ( is_null( $value['value'] ) ) {
				$formats[] = 'NULL';
				continue;
			}

			$formats[] = $value['format'];
			$values[]  = $value['value'];
		}

		$fields  = '`' . implode( '`, `', array_keys( $data ) ) . '`';
		$formats = implode( ', ', $formats );

		$sql = "$type INTO `$table` ($fields) VALUES ($formats)";

		$this->check_current_query = false;
		return $this->query( $this->prepare( $sql, $values ) );
	}

	/**
	 * Update a row in the table
	 *
	 *     wpdb::update( 'table', array( 'column' => 'foo', 'field' => 'bar' ), array( 'ID' => 1 ) )
	 *     wpdb::update( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( 'ID' => 1 ), array( '%s', '%d' ), array( '%d' ) )
	 *
	 * @since 2.5.0
	 * @see wpdb::prepare()
	 * @see wpdb::$field_types
	 * @see wp_set_wpdb_vars()
	 *
	 * @param string       $table        Table name
	 * @param array        $data         Data to update (in column => value pairs).
	 *                                   Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 *                                   Sending a null value will cause the column to be set to NULL - the corresponding
	 *                                   format is ignored in this case.
	 * @param array        $where        A named array of WHERE clauses (in column => value pairs).
	 *                                   Multiple clauses will be joined with ANDs.
	 *                                   Both $where columns and $where values should be "raw".
	 *                                   Sending a null value will create an IS NULL comparison - the corresponding format will be ignored in this case.
	 * @param array|string $format       Optional. An array of formats to be mapped to each of the values in $data.
	 *                                   If string, that format will be used for all of the values in $data.
	 *                                   A format is one of '%d', '%f', '%s' (integer, float, string).
	 *                                   If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @param array|string $where_format Optional. An array of formats to be mapped to each of the values in $where.
	 *                                   If string, that format will be used for all of the items in $where.
	 *                                   A format is one of '%d', '%f', '%s' (integer, float, string).
	 *                                   If omitted, all values in $where will be treated as strings.
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		if ( ! is_array( $data ) || ! is_array( $where ) ) {
			return false;
		}

		$data = $this->process_fields( $table, $data, $format );
		if ( false === $data ) {
			return false;
		}
		$where = $this->process_fields( $table, $where, $where_format );
		if ( false === $where ) {
			return false;
		}

		$fields = $conditions = $values = array();
		foreach ( $data as $field => $value ) {
			if ( is_null( $value['value'] ) ) {
				$fields[] = "`$field` = NULL";
				continue;
			}

			$fields[] = "`$field` = " . $value['format'];
			$values[] = $value['value'];
		}
		foreach ( $where as $field => $value ) {
			if ( is_null( $value['value'] ) ) {
				$conditions[] = "`$field` IS NULL";
				continue;
			}

			$conditions[] = "`$field` = " . $value['format'];
			$values[] = $value['value'];
		}

		$fields = implode( ', ', $fields );
		$conditions = implode( ' AND ', $conditions );

		$sql = "UPDATE `$table` SET $fields WHERE $conditions";

		$this->check_current_query = false;
		return $this->query( $this->prepare( $sql, $values ) );
	}

	/**
	 * Delete a row in the table
	 *
	 *     wpdb::delete( 'table', array( 'ID' => 1 ) )
	 *     wpdb::delete( 'table', array( 'ID' => 1 ), array( '%d' ) )
	 *
	 * @since 3.4.0
	 * @see wpdb::prepare()
	 * @see wpdb::$field_types
	 * @see wp_set_wpdb_vars()
	 *
	 * @param string       $table        Table name
	 * @param array        $where        A named array of WHERE clauses (in column => value pairs).
	 *                                   Multiple clauses will be joined with ANDs.
	 *                                   Both $where columns and $where values should be "raw".
	 *                                   Sending a null value will create an IS NULL comparison - the corresponding format will be ignored in this case.
	 * @param array|string $where_format Optional. An array of formats to be mapped to each of the values in $where.
	 *                                   If string, that format will be used for all of the items in $where.
	 *                                   A format is one of '%d', '%f', '%s' (integer, float, string).
	 *                                   If omitted, all values in $where will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function delete( $table, $where, $where_format = null ) {
		if ( ! is_array( $where ) ) {
			return false;
		}

		$where = $this->process_fields( $table, $where, $where_format );
		if ( false === $where ) {
			return false;
		}

		$conditions = $values = array();
		foreach ( $where as $field => $value ) {
			if ( is_null( $value['value'] ) ) {
				$conditions[] = "`$field` IS NULL";
				continue;
			}

			$conditions[] = "`$field` = " . $value['format'];
			$values[] = $value['value'];
		}

		$conditions = implode( ' AND ', $conditions );

		$sql = "DELETE FROM `$table` WHERE $conditions";

		$this->check_current_query = false;
		return $this->query( $this->prepare( $sql, $values ) );
	}

	/**
	 * Processes arrays of field/value pairs and field formats.
	 *
	 * This is a helper method for wpdb's CRUD methods, which take field/value
	 * pairs for inserts, updates, and where clauses. This method first pairs
	 * each value with a format. Then it determines the charset of that field,
	 * using that to determine if any invalid text would be stripped. If text is
	 * stripped, then field processing is rejected and the query fails.
	 *
	 * @since 4.2.0
	 *
	 * @param string $table  Table name.
	 * @param array  $data   Field/value pair.
	 * @param mixed  $format Format for each field.
	 * @return array|false Returns an array of fields that contain paired values
	 *                    and formats. Returns false for invalid values.
	 */
	protected function process_fields( $table, $data, $format ) {
		$data = $this->process_field_formats( $data, $format );
		if ( false === $data ) {
			return false;
		}

		$data = $this->process_field_charsets( $data, $table );
		if ( false === $data ) {
			return false;
		}

		$data = $this->process_field_lengths( $data, $table );
		if ( false === $data ) {
			return false;
		}

		$converted_data = $this->strip_invalid_text( $data );

		if ( $data !== $converted_data ) {
			return false;
		}

		return $data;
	}

	/**
	 * Prepares arrays of value/format pairs as passed to wpdb CRUD methods.
	 *
	 * @since 4.2.0
	 *
	 * @param array $data   Array of fields to values.
	 * @param mixed $format Formats to be mapped to the values in $data.
	 * @return array Array, keyed by field names with values being an array
	 *               of 'value' and 'format' keys.
	 */
	protected function process_field_formats( $data, $format ) {
		$formats = $original_formats = (array) $format;

		foreach ( $data as $field => $value ) {
			$value = array(
				'value'  => $value,
				'format' => '%s',
			);

			if ( ! empty( $format ) ) {
				$value['format'] = array_shift( $formats );
				if ( ! $value['format'] ) {
					$value['format'] = reset( $original_formats );
				}
			} elseif ( isset( $this->field_types[ $field ] ) ) {
				$value['format'] = $this->field_types[ $field ];
			}

			$data[ $field ] = $value;
		}

		return $data;
	}

	/**
	 * Adds field charsets to field/value/format arrays generated by
	 * the wpdb::process_field_formats() method.
	 *
	 * @since 4.2.0
	 *
	 * @param array  $data  As it comes from the wpdb::process_field_formats() method.
	 * @param string $table Table name.
	 * @return array|false The same array as $data with additional 'charset' keys.
	 */
	protected function process_field_charsets( $data, $table ) {
		foreach ( $data as $field => $value ) {
			if ( '%d' === $value['format'] || '%f' === $value['format'] ) {
				/*
				 * We can skip this field if we know it isn't a string.
				 * This checks %d/%f versus ! %s because its sprintf() could take more.
				 */
				$value['charset'] = false;
			} else {
				$value['charset'] = $this->get_col_charset( $table, $field );
				if ( is_wp_error( $value['charset'] ) ) {
					return false;
				}
			}

			$data[ $field ] = $value;
		}

		return $data;
	}

	/**
	 * For string fields, record the maximum string length that field can safely save.
	 *
	 * @since 4.2.1
	 *
	 * @param array  $data  As it comes from the wpdb::process_field_charsets() method.
	 * @param string $table Table name.
	 * @return array|false The same array as $data with additional 'length' keys, or false if
	 *                     any of the values were too long for their corresponding field.
	 */
	protected function process_field_lengths( $data, $table ) {
		foreach ( $data as $field => $value ) {
			if ( '%d' === $value['format'] || '%f' === $value['format'] ) {
				/*
				 * We can skip this field if we know it isn't a string.
				 * This checks %d/%f versus ! %s because its sprintf() could take more.
				 */
				$value['length'] = false;
			} else {
				$value['length'] = $this->get_col_length( $table, $field );
				if ( is_wp_error( $value['length'] ) ) {
					return false;
				}
			}

			$data[ $field ] = $value;
		}

		return $data;
	}

	/**
	 * Retrieve one variable from the database.
	 *
	 * Executes a SQL query and returns the value from the SQL result.
	 * If the SQL result contains more than one column and/or more than one row, this function returns the value in the column and row specified.
	 * If $query is null, this function returns the value in the specified column and row from the previous SQL result.
	 *
	 * @since 0.71
	 *
	 * @param string|null $query Optional. SQL query. Defaults to null, use the result from the previous query.
	 * @param int         $x     Optional. Column of value to return. Indexed from 0.
	 * @param int         $y     Optional. Row of value to return. Indexed from 0.
	 * @return string|null Database query result (as string), or null on failure
	 */
	public function get_var( $query = null, $x = 0, $y = 0 ) {
		$this->func_call = "\$db->get_var(\"$query\", $x, $y)";

		if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
			$this->check_current_query = false;
		}

		if ( $query ) {
			$this->query( $query );
		}

		// Extract var out of cached results based x,y vals
		if ( !empty( $this->last_result[$y] ) ) {
			$values = array_values( get_object_vars( $this->last_result[$y] ) );
		}

		// If there is a value return it else return null
		return ( isset( $values[$x] ) && $values[$x] !== '' ) ? $values[$x] : null;
	}

	// refactored. public function get_row( $query = null, $output = OBJECT, $y = 0 ) {}

	/**
	 * Retrieve one column from the database.
	 *
	 * Executes a SQL query and returns the column from the SQL result.
	 * If the SQL result contains more than one column, this function returns the column specified.
	 * If $query is null, this function returns the specified column from the previous SQL result.
	 *
	 * @since 0.71
	 *
	 * @param string|null $query Optional. SQL query. Defaults to previous query.
	 * @param int         $x     Optional. Column to return. Indexed from 0.
	 * @return array Database query result. Array indexed from 0 by SQL result row number.
	 */
	public function get_col( $query = null , $x = 0 ) {
		if ( $this->check_current_query && $this->check_safe_collation( $query ) ) {
			$this->check_current_query = false;
		}

		if ( $query ) {
			$this->query( $query );
		}

		$new_array = array();
		// Extract the column values
		for ( $i = 0, $j = count( $this->last_result ); $i < $j; $i++ ) {
			$new_array[$i] = $this->get_var( null, $x, $i );
		}
		return $new_array;
	}

	// refactored. public function get_results( $query = null, $output = OBJECT ) {}
	// refactored. protected function get_table_charset( $table ) {}

	/**
	 * Retrieves the character set for the given column.
	 *
	 * @since 4.2.0
	 *
	 * @param string $table  Table name.
	 * @param string $column Column name.
	 * @return string|false|WP_Error Column character set as a string. False if the column has no
	 *                               character set. WP_Error object if there was an error.
	 */
	public function get_col_charset( $table, $column ) {
		$tablekey = strtolower( $table );
		$columnkey = strtolower( $column );

		/**
		 * Filters the column charset value before the DB is checked.
		 *
		 * Passing a non-null value to the filter will short-circuit
		 * checking the DB for the charset, returning that value instead.
		 *
		 * @since 4.2.0
		 *
		 * @param string $charset The character set to use. Default null.
		 * @param string $table   The name of the table being checked.
		 * @param string $column  The name of the column being checked.
		 */
		$charset = apply_filters( 'pre_get_col_charset', null, $table, $column );
		if ( null !== $charset ) {
			return $charset;
		}

		// Skip this entirely if this isn't a MySQL database.
		if ( empty( $this->is_mysql ) ) {
			return false;
		}

		if ( empty( $this->table_charset[ $tablekey ] ) ) {
			// This primes column information for us.
			$table_charset = $this->get_table_charset( $table );
			if ( is_wp_error( $table_charset ) ) {
				return $table_charset;
			}
		}

		// If still no column information, return the table charset.
		if ( empty( $this->col_meta[ $tablekey ] ) ) {
			return $this->table_charset[ $tablekey ];
		}

		// If this column doesn't exist, return the table charset.
		if ( empty( $this->col_meta[ $tablekey ][ $columnkey ] ) ) {
			return $this->table_charset[ $tablekey ];
		}

		// Return false when it's not a string column.
		if ( empty( $this->col_meta[ $tablekey ][ $columnkey ]->Collation ) ) {
			return false;
		}

		list( $charset ) = explode( '_', $this->col_meta[ $tablekey ][ $columnkey ]->Collation );
		return $charset;
	}

	/**
	 * Retrieve the maximum string length allowed in a given column.
	 * The length may either be specified as a byte length or a character length.
	 *
	 * @since 4.2.1
	 *
	 * @param string $table  Table name.
	 * @param string $column Column name.
	 * @return array|false|WP_Error array( 'length' => (int), 'type' => 'byte' | 'char' )
	 *                              false if the column has no length (for example, numeric column)
	 *                              WP_Error object if there was an error.
	 */
	public function get_col_length( $table, $column ) {
		$tablekey = strtolower( $table );
		$columnkey = strtolower( $column );

		// Skip this entirely if this isn't a MySQL database.
		if ( empty( $this->is_mysql ) ) {
			return false;
		}

		if ( empty( $this->col_meta[ $tablekey ] ) ) {
			// This primes column information for us.
			$table_charset = $this->get_table_charset( $table );
			if ( is_wp_error( $table_charset ) ) {
				return $table_charset;
			}
		}

		if ( empty( $this->col_meta[ $tablekey ][ $columnkey ] ) ) {
			return false;
		}

		$typeinfo = explode( '(', $this->col_meta[ $tablekey ][ $columnkey ]->Type );

		$type = strtolower( $typeinfo[0] );
		if ( ! empty( $typeinfo[1] ) ) {
			$length = trim( $typeinfo[1], ')' );
		} else {
			$length = false;
		}

		switch( $type ) {
			case 'char':
			case 'varchar':
				return array(
					'type'   => 'char',
					'length' => (int) $length,
				);

			case 'binary':
			case 'varbinary':
				return array(
					'type'   => 'byte',
					'length' => (int) $length,
				);

			case 'tinyblob':
			case 'tinytext':
				return array(
					'type'   => 'byte',
					'length' => 255,        // 2^8 - 1
				);

			case 'blob':
			case 'text':
				return array(
					'type'   => 'byte',
					'length' => 65535,      // 2^16 - 1
				);

			case 'mediumblob':
			case 'mediumtext':
				return array(
					'type'   => 'byte',
					'length' => 16777215,   // 2^24 - 1
				);

			case 'longblob':
			case 'longtext':
				return array(
					'type'   => 'byte',
					'length' => 4294967295, // 2^32 - 1
				);

			default:
				return false;
		}
	}

	// refactored. protected function check_ascii( $string ) {}
	// :
	// refactored. protected function strip_invalid_text_from_query( $query ) {}

	/**
	 * Strips any invalid characters from the string for a given table and column.
	 *
	 * @since 4.2.0
	 *
	 * @param string $table  Table name.
	 * @param string $column Column name.
	 * @param string $value  The text to check.
	 * @return string|WP_Error The converted string, or a WP_Error object if the conversion fails.
	 */
	public function strip_invalid_text_for_column( $table, $column, $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}

		$charset = $this->get_col_charset( $table, $column );
		if ( ! $charset ) {
			// Not a string column.
			return $value;
		} elseif ( is_wp_error( $charset ) ) {
			// Bail on real errors.
			return $charset;
		}

		$data = array(
			$column => array(
				'value'   => $value,
				'charset' => $charset,
				'length'  => $this->get_col_length( $table, $column ),
			)
		);

		$data = $this->strip_invalid_text( $data );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return $data[ $column ]['value'];
	}

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
