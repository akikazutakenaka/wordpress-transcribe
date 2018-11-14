<?php
/**
 * Class for generating SQL clauses that filter a primary query according to date.
 *
 * WP_Date_Query is a helper that allows primary query classes, such as WP_Query, to filter
 * their results by date columns, by generating `WHERE` subclauses to be attached to the
 * primary SQL query string.
 *
 * Attempting to filter by an invalid date value (eg month=13) will generate SQL that will
 * return no results. In these cases, a _doing_it_wrong() error notice is also thrown.
 * See WP_Date_Query::validate_date_values().
 *
 * @link https://codex.wordpress.org/Function_Reference/WP_Query Codex page.
 *
 * @since 3.7.0
 */
class WP_Date_Query {
	// refactored. public $queries = array();
	// :
	// refactored. public function validate_column( $column ) {}

	/**
	 * Generate WHERE clause to be appended to a main query.
	 *
	 * @since 3.7.0
	 *
	 * @return string MySQL WHERE clause.
	 */
	public function get_sql() {
		$sql = $this->get_sql_clauses();

		$where = $sql['where'];

		/**
		 * Filters the date query WHERE clause.
		 *
		 * @since 3.7.0
		 *
		 * @param string        $where WHERE clause of the date query.
		 * @param WP_Date_Query $this  The WP_Date_Query instance.
		 */
		return apply_filters( 'get_date_sql', $where, $this );
	}

	/**
	 * Generate SQL clauses to be appended to a main query.
	 *
	 * Called by the public WP_Date_Query::get_sql(), this method is abstracted
	 * out to maintain parity with the other Query classes.
	 *
	 * @since 4.1.0
	 *
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_clauses() {
		$sql = $this->get_sql_for_query( $this->queries );

		if ( ! empty( $sql['where'] ) ) {
			$sql['where'] = ' AND ' . $sql['where'];
		}

		return $sql;
	}

	/**
	 * Generate SQL clauses for a single query array.
	 *
	 * If nested subqueries are found, this method recurses the tree to
	 * produce the properly nested SQL.
	 *
	 * @since 4.1.0
	 *
	 * @param array $query Query to parse.
	 * @param int   $depth Optional. Number of tree levels deep we currently are.
	 *                     Used to calculate indentation. Default 0.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to a single query array.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_for_query( $query, $depth = 0 ) {
		$sql_chunks = array(
			'join'  => array(),
			'where' => array(),
		);

		$sql = array(
			'join'  => '',
			'where' => '',
		);

		$indent = '';
		for ( $i = 0; $i < $depth; $i++ ) {
			$indent .= "  ";
		}

		foreach ( $query as $key => $clause ) {
			if ( 'relation' === $key ) {
				$relation = $query['relation'];
			} elseif ( is_array( $clause ) ) {

				// This is a first-order clause.
				if ( $this->is_first_order_clause( $clause ) ) {
					$clause_sql = $this->get_sql_for_clause( $clause, $query );

					$where_count = count( $clause_sql['where'] );
					if ( ! $where_count ) {
						$sql_chunks['where'][] = '';
					} elseif ( 1 === $where_count ) {
						$sql_chunks['where'][] = $clause_sql['where'][0];
					} else {
						$sql_chunks['where'][] = '( ' . implode( ' AND ', $clause_sql['where'] ) . ' )';
					}

					$sql_chunks['join'] = array_merge( $sql_chunks['join'], $clause_sql['join'] );
				// This is a subquery, so we recurse.
				} else {
					$clause_sql = $this->get_sql_for_query( $clause, $depth + 1 );

					$sql_chunks['where'][] = $clause_sql['where'];
					$sql_chunks['join'][]  = $clause_sql['join'];
				}
			}
		}

		// Filter to remove empties.
		$sql_chunks['join']  = array_filter( $sql_chunks['join'] );
		$sql_chunks['where'] = array_filter( $sql_chunks['where'] );

		if ( empty( $relation ) ) {
			$relation = 'AND';
		}

		// Filter duplicate JOIN clauses and combine into a single string.
		if ( ! empty( $sql_chunks['join'] ) ) {
			$sql['join'] = implode( ' ', array_unique( $sql_chunks['join'] ) );
		}

		// Generate a single WHERE clause with proper brackets and indentation.
		if ( ! empty( $sql_chunks['where'] ) ) {
			$sql['where'] = '( ' . "\n  " . $indent . implode( ' ' . "\n  " . $indent . $relation . ' ' . "\n  " . $indent, $sql_chunks['where'] ) . "\n" . $indent . ')';
		}

		return $sql;
	}

	/**
	 * Turns a single date clause into pieces for a WHERE clause.
	 *
	 * A wrapper for get_sql_for_clause(), included here for backward
	 * compatibility while retaining the naming convention across Query classes.
	 *
	 * @since  3.7.0
	 *
	 * @param  array $query Date query arguments.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_for_subquery( $query ) {
		return $this->get_sql_for_clause( $query, '' );
	}

	/**
	 * Turns a first-order date query into SQL for a WHERE clause.
	 *
	 * @since  4.1.0
	 *
	 * @param  array $query        Date query clause.
	 * @param  array $parent_query Parent query of the current date query.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_for_clause( $query, $parent_query ) {
		global $wpdb;

		// The sub-parts of a $where part.
		$where_parts = array();

		$column = ( ! empty( $query['column'] ) ) ? esc_sql( $query['column'] ) : $this->column;

		$column = $this->validate_column( $column );

		$compare = $this->get_compare( $query );

		$inclusive = ! empty( $query['inclusive'] );

		// Assign greater- and less-than values.
		$lt = '<';
		$gt = '>';

		if ( $inclusive ) {
			$lt .= '=';
			$gt .= '=';
		}

		// Range queries.
		if ( ! empty( $query['after'] ) ) {
			$where_parts[] = $wpdb->prepare( "$column $gt %s", $this->build_mysql_datetime( $query['after'], ! $inclusive ) );
		}
		if ( ! empty( $query['before'] ) ) {
			$where_parts[] = $wpdb->prepare( "$column $lt %s", $this->build_mysql_datetime( $query['before'], $inclusive ) );
		}
		// Specific value queries.

		if ( isset( $query['year'] ) && $value = $this->build_value( $compare, $query['year'] ) )
			$where_parts[] = "YEAR( $column ) $compare $value";

		if ( isset( $query['month'] ) && $value = $this->build_value( $compare, $query['month'] ) ) {
			$where_parts[] = "MONTH( $column ) $compare $value";
		} elseif ( isset( $query['monthnum'] ) && $value = $this->build_value( $compare, $query['monthnum'] ) ) {
			$where_parts[] = "MONTH( $column ) $compare $value";
		}
		if ( isset( $query['week'] ) && false !== ( $value = $this->build_value( $compare, $query['week'] ) ) ) {
			$where_parts[] = _wp_mysql_week( $column ) . " $compare $value";
		} elseif ( isset( $query['w'] ) && false !== ( $value = $this->build_value( $compare, $query['w'] ) ) ) {
			$where_parts[] = _wp_mysql_week( $column ) . " $compare $value";
		}
		if ( isset( $query['dayofyear'] ) && $value = $this->build_value( $compare, $query['dayofyear'] ) )
			$where_parts[] = "DAYOFYEAR( $column ) $compare $value";

		if ( isset( $query['day'] ) && $value = $this->build_value( $compare, $query['day'] ) )
			$where_parts[] = "DAYOFMONTH( $column ) $compare $value";

		if ( isset( $query['dayofweek'] ) && $value = $this->build_value( $compare, $query['dayofweek'] ) )
			$where_parts[] = "DAYOFWEEK( $column ) $compare $value";

		if ( isset( $query['dayofweek_iso'] ) && $value = $this->build_value( $compare, $query['dayofweek_iso'] ) )
			$where_parts[] = "WEEKDAY( $column ) + 1 $compare $value";

		if ( isset( $query['hour'] ) || isset( $query['minute'] ) || isset( $query['second'] ) ) {
			// Avoid notices.
			foreach ( array( 'hour', 'minute', 'second' ) as $unit ) {
				if ( ! isset( $query[ $unit ] ) ) {
					$query[ $unit ] = null;
				}
			}

			if ( $time_query = $this->build_time_query( $column, $compare, $query['hour'], $query['minute'], $query['second'] ) ) {
				$where_parts[] = $time_query;
			}
		}

		/*
		 * Return an array of 'join' and 'where' for compatibility
		 * with other query classes.
		 */
		return array(
			'where' => $where_parts,
			'join'  => array(),
		);
	}

	/**
	 * Builds and validates a value string based on the comparison operator.
	 *
	 * @since 3.7.0
	 *
	 * @param string $compare The compare operator to use
	 * @param string|array $value The value
	 * @return string|false|int The value to be used in SQL or false on error.
	 */
	public function build_value( $compare, $value ) {
		if ( ! isset( $value ) )
			return false;

		switch ( $compare ) {
			case 'IN':
			case 'NOT IN':
				$value = (array) $value;

				// Remove non-numeric values.
				$value = array_filter( $value, 'is_numeric' );

				if ( empty( $value ) ) {
					return false;
				}

				return '(' . implode( ',', array_map( 'intval', $value ) ) . ')';

			case 'BETWEEN':
			case 'NOT BETWEEN':
				if ( ! is_array( $value ) || 2 != count( $value ) ) {
					$value = array( $value, $value );
				} else {
					$value = array_values( $value );
				}

				// If either value is non-numeric, bail.
				foreach ( $value as $v ) {
					if ( ! is_numeric( $v ) ) {
						return false;
					}
				}

				$value = array_map( 'intval', $value );

				return $value[0] . ' AND ' . $value[1];

			default:
				if ( ! is_numeric( $value ) ) {
					return false;
				}

				return (int) $value;
		}
	}

	// refactored. public function build_mysql_datetime( $datetime, $default_to_max = false ) {}

	/**
	 * Builds a query string for comparing time values (hour, minute, second).
	 *
	 * If just hour, minute, or second is set than a normal comparison will be done.
	 * However if multiple values are passed, a pseudo-decimal time will be created
	 * in order to be able to accurately compare against.
	 *
	 * @since 3.7.0
	 *
	 * @param string $column The column to query against. Needs to be pre-validated!
	 * @param string $compare The comparison operator. Needs to be pre-validated!
	 * @param int|null $hour Optional. An hour value (0-23).
	 * @param int|null $minute Optional. A minute value (0-59).
	 * @param int|null $second Optional. A second value (0-59).
	 * @return string|false A query part or false on failure.
	 */
	public function build_time_query( $column, $compare, $hour = null, $minute = null, $second = null ) {
		global $wpdb;

		// Have to have at least one
		if ( ! isset( $hour ) && ! isset( $minute ) && ! isset( $second ) )
			return false;

		// Complex combined queries aren't supported for multi-value queries
		if ( in_array( $compare, array( 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ) ) ) {
			$return = array();

			if ( isset( $hour ) && false !== ( $value = $this->build_value( $compare, $hour ) ) )
				$return[] = "HOUR( $column ) $compare $value";

			if ( isset( $minute ) && false !== ( $value = $this->build_value( $compare, $minute ) ) )
				$return[] = "MINUTE( $column ) $compare $value";

			if ( isset( $second ) && false !== ( $value = $this->build_value( $compare, $second ) ) )
				$return[] = "SECOND( $column ) $compare $value";

			return implode( ' AND ', $return );
		}

		// Cases where just one unit is set
		if ( isset( $hour ) && ! isset( $minute ) && ! isset( $second ) && false !== ( $value = $this->build_value( $compare, $hour ) ) ) {
			return "HOUR( $column ) $compare $value";
		} elseif ( ! isset( $hour ) && isset( $minute ) && ! isset( $second ) && false !== ( $value = $this->build_value( $compare, $minute ) ) ) {
			return "MINUTE( $column ) $compare $value";
		} elseif ( ! isset( $hour ) && ! isset( $minute ) && isset( $second ) && false !== ( $value = $this->build_value( $compare, $second ) ) ) {
			return "SECOND( $column ) $compare $value";
		}

		// Single units were already handled. Since hour & second isn't allowed, minute must to be set.
		if ( ! isset( $minute ) )
			return false;

		$format = $time = '';

		// Hour
		if ( null !== $hour ) {
			$format .= '%H.';
			$time   .= sprintf( '%02d', $hour ) . '.';
		} else {
			$format .= '0.';
			$time   .= '0.';
		}

		// Minute
		$format .= '%i';
		$time   .= sprintf( '%02d', $minute );

		if ( isset( $second ) ) {
			$format .= '%s';
			$time   .= sprintf( '%02d', $second );
		}

		return $wpdb->prepare( "DATE_FORMAT( $column, %s ) $compare %f", $format, $time );
	}
}
