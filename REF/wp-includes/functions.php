<?php
/**
 * Main WordPress API
 *
 * @package WordPress
 */

require( ABSPATH . WPINC . '/option.php' );

/**
 * Convert given date string into a different format.
 *
 * $format should be either a PHP date format string, e.g. 'U' for a Unix timestamp, or 'G' for a Unix timestamp assuming that $date is GMT.
 *
 * If $translate is true then the given date and format string will be passed to date_i18n() for translation.
 *
 * @since 0.71
 *
 * @param  string          $format    Format of the date to return.
 * @param  string          $date      Date string to convert.
 * @param  bool            $translate Whether the return date should be translated.
 *                                    Default true.
 * @return string|int|bool Formatted date string or Unix timestamp.
 *                         False if $date is empty.
 */
function mysql2date( $format, $date, $translate = TRUE )
{
	if ( empty( $date ) ) {
		return FALSE;
	}

	if ( 'G' == $format ) {
		return strtotime( $date . ' +0000' );
	}

	$i = strtotime( $date );

	if ( 'U' == $format ) {
		return $i;
	}

	return $translate
		? date_i18n( $format, $i )
		: date( $format, $i );
}

/**
 * Retrieve the current time based on specified type.
 *
 * The 'mysql' type will return the time in the format for MySQL DATETIME field.
 * The 'timestamp' type will return the current timestamp.
 * Other strings will be interpreted as PHP date formats (e.g. 'Y-m-d').
 *
 * If $gmt is set to either '1' or 'true', then both types will use GMT time.
 * If $gmt is false, the output is adjusted with the GMT offset in the WordPress option.
 *
 * @since 1.0.0
 *
 * @param  string     $type Type of time to retrieve.
 *                          Accepts 'mysql', 'timestamp', or PHP date format string (e.g. 'Y-m-d').
 * @param  int|bool   $gmt  Optional.
 *                          Whether to use GMT timezone.
 *                          Default false.
 * @return int|string Integer if $type is 'timestamp', string otherwise.
 */
function current_time( $type, $gmt = 0 )
{
	switch ( $type ) {
		case 'mysql':
			return $gmt
				? gmdate( 'Y-m-d H:i:s' )
				: gmdate( 'Y-m-d H:i:s', time() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

		case 'timestamp':
			return $gmt
				? time()
				: time() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;

		default:
			return $gmt
				? date( $type )
				: date( $type, time() + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
	}
}

/**
 * Retrieve the date in localized format, based on timestamp.
 *
 * If the locale specifies the locale month and weekday, then the locale will take over the format for the date.
 * If it isn't, then the date format string will be used instead.
 *
 * @since  0.71
 * @global WP_Locale
 *
 * @param  string   $dateformatstring Format to display the date.
 * @param  bool|int $unixtimestamp    Optional.
 *                                    Unix timestamp.
 *                                    Default false.
 * @param  bool     $gmt              Optional.
 *                                    Whether to use GMT timezone.
 *                                    Default false.
 * @return string   The date, translated if locale specifies it.
 */
function date_i18n( $dateformatstring, $unixtimestamp = FALSE, $gmt = FALSE )
{
	global $wp_locale;
	$i = $unixtimestamp;

	if ( FALSE === $i ) {
		$i = current_time( 'timestamp', $gmt );
	}

	/**
	 * Store original value for language with untypical grammars.
	 * See https://core.trac.wordpress.org/ticket/9396
	 */
	$req_format = $dateformatstring;

	if ( ! empty( $wp_locale->month ) && ! empty( $wp_locale->weekday ) ) {
		$datemonth = $wp_locale->get_month( date( 'm', $i ) );
		$datemonth_abbrev = $wp_locale->get_month_abbrev( $datemonth );
		$dateweekday = $wp_locale->get_weekday( date( 'w', $i ) );
		$dateweekday_abbrev = $wp_locale->get_weekday_abbrev( $dateweekday );
		$datemeridiem = $wp_locale->get_meridiem( date( 'a', $i ) );
		$datemeridiem_captial = $wp_locale->get_meridiem( date( 'A', $i ) );
		$dateformatstring = ' ' . $dateformatstring;
		$dateformatstring = preg_replace( "/([^\\\])D/", "\\1" . backslashit( $dateweekday_abbrev ), $dateformatstring );
		$dateformatstring = preg_replace( "/([^\\\])F/", "\\1" . backslashit( $datemonth ), $dateformatstring );
		$dateformatstring = preg_replace( "/([^\\\])l/", "\\1" . backslashit( $dateweekday ), $dateformatstring );
		$dateformatstring = preg_replace( "/([^\\\])M/", "\\1" . backslashit( $datemonth_abbrev ), $dateformatstring );
		$dateformatstring = preg_replace( "/([^\\\])a/", "\\1" . backslashit( $datemeridiem ), $dateformatstring );
		$dateformatstring = preg_replace( "/([^\\\])A/", "\\1" . backslashit( $datemeridiem_capital ), $dateformatstring );
		$dateformatstring = substr( $dateformatstring, 1, strlen( $dateformatstring ) - 1 );
	}

	$timezone_formats = array( 'P', 'I', 'O', 'T', 'Z', 'e' );
	$timezone_formats_re = implode( '|', $timezone_formats );

	if ( preg_match( "/$timezone_formats_re/", $dateformatstring ) ) {
		$timezone_string = get_option( 'timezone_string' );

		if ( $timezone_string ) {
			$timezone_object = timezone_open( $timezone_string );
			$date_object = date_create( NULL, $timezone_object );

			foreach ( $timezone_formats as $timezone_format ) {
				if ( FALSE !== strpos( $dateformatstring, $timezone_format ) ) {
					$formatted = date_format( $date_object, $timezone_format );
					$dateformatstring = ' ' . $dateformatstring;
					$dateformatstring = preg_replace( "/([^\\\])$timezone_format/", "\\1" . backslashit( $formatted ), $dateformatstring );
					$dateformatstring = substr( $dateformatstring, 1, strlen( $dateformatstring ) - 1 );
				}
			}
		}
	}

	$j = @ date( $dateformatstring, $i );

	/**
	 * Filters the date formatted based on the locale.
	 *
	 * @since 2.8.0
	 *
	 * @param string $j          Formatted date string.
	 * @param string $req_format Format to display the date.
	 * @param int    $i          Unix timestamp.
	 * @param bool   $gmt        Whether to convert to GMT for time.
	 *                           Default false.
	 */
	$j = apply_filters( 'date_i18n', $j, $req_format, $i, $gmt );

	return $j;
}

/**
 * Unserialize value only if it was serialized.
 *
 * @since 2.0.0
 *
 * @param  string $original Maybe unserialized original, if is needed.
 * @return mixed  Unserialized data can be any type.
 */
function maybe_unserialize( $original )
{
	if ( is_serialized( $original ) ) {
		// Don't attempt to unserialize data that wasn't serialized going in.
		return @ unserialize( $original );
	}

	return $original;
}

/**
 * Check value to find if it was serialized.
 *
 * If $data is not an string, then returned value will always be false.
 * Serialized data is always a string.
 *
 * @since 2.0.5
 *
 * @param  string $data   Value to check to see if was serialized.
 * @param  bool   $strict Optional.
 *                        Whether to be strict about the end of the string.
 *                        Default true.
 * @return bool   False if not serialized and true if it was.
 */
function is_serialized( $data, $strict = TRUE )
{
	// If it isn't a string, it isn't serialized.
	if ( ! is_string( $data ) ) {
		return FALSE;
	}

	$data = trim( $data );

	if ( 'N;' == $data ) {
		return TRUE;
	}

	if ( strlen( $data ) < 4 ) {
		return FALSE;
	}

	if ( ':' !== $data[1] ) {
		return FALSE;
	}

	if ( $strict ) {
		$lastc = substr( $data, -1 );

		if ( ';' !== $lastc && '}' !== $lastc ) {
			return FALSE;
		}
	} else {
		$semicolon = strpos( $data, ';' );
		$brace     = strpos( $data, '}' );

		// Either ; or } must exist.
		if ( FALSE === $semicolon && FALSE === $brace ) {
			return FALSE;
		}

		// But neither must be in the first X characters.
		if ( FALSE !== $semicolon && $semicolon < 3 ) {
			return FALSE;
		}

		if ( FALSE !== $brace && $brace < 4 ) {
			return FALSE;
		}
	}

	$token = $data[0];

	switch ( $token ) {
		case 's':
			if ( $strict ) {
				if ( '"' !== substr( $data, -2, 1 ) ) {
					return FALSE;
				}
			} elseif ( FALSE === strpos( $data, '"' ) ) {
				return FALSE;
			}

			// Or else fall through.

		case 'a':
		case 'O':
			return ( bool ) preg_match( "/^{$token}:[0-9]+:/s", $data );

		case 'b':
		case 'i':
		case 'd':
			$end = $strict
				? '$'
				: '';

			return ( bool ) preg_match( "/^{$token}:[0-9.E-]+;$end/", $data );
	}

	return FALSE;
}

/**
 * Serialize data, if needed.
 *
 * @since 2.0.5
 *
 * @param  string|array|object $data Data that might be serialized.
 * @return mixed               A scalar data.
 */
function maybe_serialize( $data )
{
	if ( is_array( $data ) || is_object( $data ) ) {
		return serialize( $data );
	}

	/**
	 * Double serialization is required for backward compatibility.
	 * See https://core.trac.wordpress.org/ticket/12930
	 * Also the world will end.
	 * See WP 3.6.1.
	 */
	if ( is_serialize( $data, FALSE ) ) {
		return serialize( $data );
	}

	return $data;
}

/**
 * Build URL query based on an associative and, or indexed array.
 *
 * This is a convenient function for easily building url queries.
 * It sets the separator to '&' and uses _http_build_query() function.
 *
 * @since 2.3.0
 * @see   _http_build_query() Used to build the query.
 * @link  https://secure.php.net/manual/en/function.http-build-query.php for more on what http_build_query() does.
 *
 * @param  array  $data URL-encode key/value pairs.
 * @return string URL-encoded string.
 */
function build_query( $data )
{
	return _http_build_query( $data, NULL, '&', '', FALSE );
}

/**
 * From php.net (modified by Mark Jaquith to behave like the native PHP5 function).
 *
 * @since  3.2.0
 * @access private
 * @see    https://secure.php.net/manual/en/function.http-build-query.php
 *
 * @param  array|object $data      An array or object of data.
 *                                 Converted to array.
 * @param  string       $prefix    Optional.
 *                                 Numeric index.
 *                                 If set, start parameter numbering with it.
 *                                 Default null.
 * @param  string       $sep       Optional.
 *                                 Argument separator; defaults to 'arg_separator.output'.
 *                                 Default null.
 * @param  string       $key       Optional.
 *                                 Used to prefix key name.
 *                                 Default empty.
 * @param  bool         $urlencode Optional.
 *                                 Whether to use urlencode() in the result.
 *                                 Default true.
 * @return string       The query string.
 */
function _http_build_query( $data, $prefix = NULL, $sep = NULL, $key = '', $urlencode = TRUE )
{
	$ret = array();

	foreach ( ( array ) $data as $k => $v ) {
		if ( $urlencode ) {
			$k = urlencode( $k );
		}

		if ( is_int( $k ) && $prefix != NULL ) {
			$k = $prefix . $k;
		}

		if ( ! empty( $key ) ) {
			$k = $key . '%5B' . $k . '%5D';
		}

		if ( $v === NULL ) {
			continue;
		} elseif ( $v === FALSE ) {
			$v = '0';
		}

		if ( is_array( $v ) || is_object( $v ) ) {
			array_push( $ret, _http_build_query( $v, '', $sep, $k, $urlencode ) );
		} elseif ( $urlencode ) {
			array_push( $ret, $k . '=' . urlencode( $v ) );
		} else {
			array_push( $ret, $k . '=' . $v );
		}
	}

	if ( NULL === $sep ) {
		$sep = ini_get( 'arg_separator.output' );
	}

	return implode( $sep, $ret );
}

/**
 * Retrieves a modified URL query string.
 *
 * You can rebuild the URL and append query variables to the URL query by using this function.
 * There are two ways to use this function; either a single key and value, or an associative array.
 *
 * Using a single key and value:
 *
 *     add_query_arg( 'key', 'value', 'http://example.com' );
 *
 * Using an associative array:
 *
 *     add_query_arg( array(
 *             'key1' => 'value1',
 *             'key2' => 'value2'
 *         ), 'http://example.com' );
 *
 * Omitting the URL from either use results in the current URL being used (the value of `$_SERVER['REQUEST_URI']`).
 *
 * Values are expected to be encoded appropriately with urlencode() or rawurlencode().
 *
 * Setting any query variable's value to boolean false removes the key (see remove_query_arg()).
 *
 * Important: The return value of add_query_arg() is not escaped by default.
 * Output should be late-escaped with esc_url() or similar to help prevent vulnerability to cross-site scripting (XSS) attacks.
 *
 * @since 1.5.0
 *
 * @param  string|array $key   Either a query variable key, or an associative array of query variables.
 * @param  string       $value Optional.
 *                             Either a query variable value, or a URL to act upon.
 * @param  string       $url   Optional.
 *                             A URL to act upon.
 * @return string       New URL query string (unescaped).
 */
function add_query_arg()
{
	$args = func_get_args();

	$uri = is_array( $args[0] )
		? ( count( $args ) < 2 || FALSE === $args[1]
			? $_SERVER['REQUEST_URI']
			: $args[1] )
		: ( count( $args ) < 3 || FALSE === $args[2]
			? $_SERVER['REQUEST_URI']
			: $args[2] );

	if ( $frag = strstr( $uri, '#' ) ) {
		$uri = substr( $uri, 0, - strlen( $frag ) );
	} else {
		$frag = '';
	}

	if ( 0 === stripos( $uri, 'http://' ) ) {
		$protocol = 'http://';
		$uri = substr( $uri, 7 );
	} elseif ( 0 === stripos( $uri, 'https://' ) ) {
		$protocol = 'https://';
		$uri = substr( $uri, 8 );
	} else {
		$protocol = '';
	}

	if ( strpos( $uri, '?' ) !== FALSE ) {
		list( $base, $query ) = explode( '?', $uri, 2 );
		$base .= '?';
	} elseif ( $protocol || strpos( $uri, '=' ) === FALSE ) {
		$base = $uri . '?';
		$query = '';
	} else {
		$base = '';
		$query = $uri;
	}

	wp_parse_str( $query, $qs );
	$qs = urlencode_deep( $qs ); // This re-URL-encodes things that were already in the query string.

	if ( is_array( $args[0] ) ) {
		foreach ( $args[0] as $k => $v ) {
			$qs[ $k ] = $v;
		}
	} else {
		$qs[ $args[0] ] = $args[1];
	}

	foreach ( $qs as $k => $v ) {
		if ( $v === FALSE ) {
			unset( $qs[ $k ] );
		}
	}

	$ret = build_query( $qs );
	$ret = trim( $ret, '?' );
	$ret = preg_replace( '#=(&|$)#', '$1', $ret );
	$ret = $protocol . $base . $ret . $frag;
	$ret = rtrim( $ret, '?' );
	return $ret;
}

/**
 * Removes an item or items from a query string.
 *
 * @since 1.5.0
 *
 * @param  string|array $key   Query key or keys to remove.
 * @param  bool|string  $query Optional.
 *                             When false uses the current URL.
 *                             Default false.
 * @return string       New URL query string.
 */
function remove_query_arg( $key, $query = FALSE )
{
	if ( is_array( $key ) ) { //Removing multiple keys.
		foreach ( $key as $k ) {
			$query = add_query_arg( $k, FALSE, $query );
		}

		return $query;
	}

	return add_query_arg( $key, FALSE, $query );
}

/**
 * Retrieve the description for the HTTP status.
 *
 * @since  2.3.0
 * @global array $wp_header_to_desc
 *
 * @param  int    $code HTTP status code.
 * @return string Empty string if not found, or description if found.
 */
function get_status_header_desc( $code )
{
	global $wp_header_to_desc;
	$code = absint( $code );

	if ( ! isset( $wp_header_to_desc ) ) {
		$wp_header_to_desc = array(
			100 => 'Continue',
			101 => 'Switching Protocols',
			102 => 'Processing',

			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			207 => 'Multi-Status',
			226 => 'IM Used',

			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => 'Reserved',
			307 => 'Temporary Redirect',
			308 => 'Permanant Redirect',

			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			418 => 'I\'m a teapot',
			421 => 'Misdirected Request',
			422 => 'Unprocessable Entity',
			423 => 'Locked',
			424 => 'Failed Dependency',
			426 => 'Upgrade Required',
			428 => 'Precondition Required',
			429 => 'Too Many Requests',
			431 => 'Request Header Fields Too Large',
			451 => 'Unavailable For Legal Reasons',

			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			506 => 'Variant Also Negotiates',
			507 => 'Insufficient Storage',
			510 => 'Not Extended',
			511 => 'Network Authentication Required'
		);
	}

	return isset( $wp_header_to_desc[ $code ] )
		? $wp_header_to_desc[ $code ]
		: '';
}

/**
 * Set HTTP status header.
 *
 * @since 2.0.0
 * @since 4.4.0 Added the `$description` parameter.
 * @see   get_status_header_desc()
 *
 * @param int    $code        HTTP status code.
 * @param string $description Optional.
 *                            A custom description for the HTTP status.
 */
function status_header( $code, $description = '' )
{
	if ( ! $description ) {
		$description = get_status_header_desc( $code );
	}

	if ( empty( $description ) ) {
		return;
	}

	$protocol = wp_get_server_protocol();
	$status_header = "$protocol $code $description";

	if ( function_exists( 'apply_filters' ) ) {
		/**
		 * Filters an HTTP status header.
		 *
		 * @since 2.2.0
		 *
		 * @param string $status_header HTTP status header.
		 * @param int    $code          HTTP status code.
		 * @param string $description   Description for the status code.
		 * @param string $protocol      Server protocol.
		 */
		$status_header = apply_filters( 'status_header', $status_header, $code, $description, $protocol );
	}

	@ header( $status_header, TRUE, $code );
}

/**
 * Get the header information to prevent caching.
 *
 * The several different headers cover the different ways cache prevention is handled by different browsers.
 *
 * @since 2.8.0
 *
 * @return array The associative array of header names and field values.
 */
function wp_get_nocache_headers()
{
	$headers = array(
		'Expires'       => 'Wed, 11 Jan 1984 05:00:00 GMT',
		'Cache-Control' => 'no-cache, must-revalidate, max-age=0'
	);

	if ( function_exists( 'apply_filters' ) ) {
		/**
		 * Filters the cache-controlling headers.
		 *
		 * @since 2.8.0
		 * @see   wp_get_nocache_headers()
		 *
		 * @param array $headers {
		 *     Header names and field values.
		 *
		 *     @type string $Expires       Expires header.
		 *     @type string $Cache-Control Cache-Control header.
		 * }
		 */
		$headers = ( array ) apply_filters( 'nocache_headers', $headers );
	}

	$headers['Last-Modified'] = FALSE;
	return $headers;
}

/**
 * Set the headers to prevent caching for the different browsers.
 *
 * Different browsers support different nocache headers, so several headers must be sent so that all of them get the point that no caching should occur.
 *
 * @since 2.0.0
 * @see   wp_get_nocache_headers()
 */
function nocache_headers()
{
	$headers = wp_get_nocache_headers();
	unset( $headers['Last-Modified'] );

	// In PHP 5.3+, make sure we are not sending a Last-Modified header.
	if ( function_exists( 'header_remove' ) ) {
		@ header_remove( 'Last-Modified' );
	} else {
		// In PHP 5.2, send an empty Last-Modified header, but only as a last resort to override a header already sent. #WP23021
		foreach ( headers_list() as $header ) {
			if ( 0 === stripos( $header, 'Last-Modified' ) ) {
				$headers['Last-Modified'] = '';
				break;
			}
		}
	}

	foreach ( $headers as $name => $field_value ) {
		@ header( "{$name}: {$field_value}" );
	}
}

/**
 * Recursive directory creation based on full path.
 *
 * Will attempt to set permissions on folders.
 *
 * @since 2.0.1
 *
 * @param  string $target Full path to attempt to create.
 * @return bool   Whether the path was created.
 *                True if path already exists.
 */
function wp_mkdir_p( $target )
{
	$wrapper = NULL;

	// Strip the protocol.
	if ( wp_is_stream( $target ) ) {
		list( $wrapper, $target ) = explode( '://', $target, 2 );
	}

	// From php.net/mkdir user contributed notes.
	$target = str_replace( '//', '/', $target );

	// Put the wrapper back on the target.
	if ( $wrapper !== NULL ) {
		$target = $wrapper . '://' . $target;
	}

	/**
	 * Safe mode fails with a trailing slash under certain PHP versions.
	 * Use rtrim() instead of untrailingslashit to avoid formatting.php dependency.
	 */
	$target = rtrim( $target, '/' );

	if ( empty( $target ) ) {
		$target = '/';
	}

	if ( file_exists( $target ) ) {
		return @ is_dir( $target );
	}

	// We need to find the permissions of the parent folder that exists and inherit that.
	$target_parent = dirname( $target );

	while ( '.' != $target_parent && ! is_dir( $target_parent ) && dirname( $target_parent ) !== $target_parent ) {
		$target_parent = dirname( $target_parent );
	}

	// Get the permission bits.
	$dir_perms = ( $stat = @ stat( $target_parent ) )
		? $stat['mode'] & 0007777
		: 0777;

	if ( @ mkdir( $target, $dir_perms, TRUE ) ) {
		// If a umask is set that modifies $dir_perms, we'll have to re-set the $dir_perms correctly with chmod().
		if ( $dir_perms != $dir_perms & ~ umask() ) {
			$folder_parts = explode( '/', substr( $target, strlen( $target_parent ) + 1 ) );

			for ( $i = 1, $c = count( $folder_parts ); $i <= $c; $i++ ) {
				@ chmod( $target_parent . '/' . implode( '/', array_slice( $folder_parts, 0, $i ) ), $dir_perms );
			}
		}

		return TRUE;
	}

	return FALSE;
}

/**
 * Test if a given filesystem path is absolute.
 *
 * For example, '/foo/bar', or 'c:\windows'.
 *
 * @since 2.5.0
 *
 * @param  string $path File path.
 * @return bool   True if path is absolute, false is not absolute.
 */
function path_is_absolute( $path )
{
	// This is definitive if true but fails if $path does not exist or contains a symbolic link.
	return realpath( $path ) == $path
		? TRUE
		: ( strlen( $path ) == 0 || $path[0] == '.'
			? FALSE
			: ( preg_match( '#^[a-zA-Z]:\\\\#', $path ) // Windows allows absolute paths like this.
				? TRUE
				: $path[0] == '/' || $path[0] == '\\' ) ); // A path starting with / or \ is absolute; anything else is relative.
}

/**
 * Join two filesystem paths together.
 *
 * For example, 'give me $path relative to $base'.
 * If the $path is absolute, then it the full path is returned.
 *
 * @since 2.5.0
 *
 * @param  string $base Base path.
 * @param  string $path Path relative to $base.
 * @return string The path with the base or absolute path.
 */
function path_join( $base, $path )
{
	return path_is_absolute( $path )
		? $path
		: rtrim( $base, '/' ) . '/' . ltrim( $path, '/' );
}

/**
 * Normalize a filesystem path.
 *
 * On windows systems, replaces backslashes with forward slashes and forces upper-case drive letters.
 * Allows for two leading slashes for Windows network shares, but ensures that all other duplicate slashes are reduced to a single.
 *
 * @since 3.9.0
 * @since 4.4.0 Ensures upper-case drive letters on Windows systems.
 * @since 4.5.0 Allows for Windows network shares.
 * @since 4.9.7 Allows for PHP file wrappers.
 *
 * @param  string $path Path to normalize.
 * @return string Normalized path.
 */
function wp_normalize_path( $path )
{
	$wrapper = '';

	if ( wp_is_stream( $path ) ) {
		list( $wrapper, $path ) = explode( '://', $path, 2 );
		$wrapper .= '://';
	}

	// Standardise all paths to use /
	$path = str_replace( '\\', '/', $path );

	// Replace multiple slashes down to a singular, allowing for network shares having two slashes.
	$path = preg_replace( '|(?<=.)/+|', '/', $path );

	// Windows paths should uppercase the drive letter
	if ( ':' === substr( $path, 1, 1 ) ) {
		$path = ucfirst( $path );
	}

	return $wrapper . $path;
}

/**
 * Determine a writable directory for temporary files.
 *
 * Function's preference is the return value of sys_get_temp_dir(), followed by your PHP temporary upload directory, followed by WP_CONTENT_DIR, before finally defaulting to /tmp/.
 *
 * In the event that this function does not find a writable location, it may be overridden by the WP_TEMP_DIR constant in your wp-config.php file.
 *
 * @since     2.5.0
 * @staticvar string $temp
 *
 * @return string Writable temporary directory.
 */
function get_temp_dir()
{
	static $temp = '';

	if ( defined( 'WP_TEMP_DIR' ) ) {
		return trailingslashit( WP_TEMP_DIR );
	}

	if ( $temp ) {
		return trailingslashit( $temp );
	}

	if ( function_exists( 'sys_get_temp_dir' ) ) {
		$temp = sys_get_temp_dir();

		if ( @ is_dir( $temp ) && wp_is_writable( $temp ) ) {
			return trailingslashit( $temp );
		}
	}

	$temp = ini_get( 'upload_tmp_dir' );

	if ( @ is_dir( $temp ) && wp_is_writable( $temp ) ) {
		return trailingslashit( $temp );
	}

	$temp = WP_CONTENT_DIR . '/';

	if ( is_dir( $temp ) && wp_is_writable( $temp ) ) {
		return $temp;
	}

	return '/tmp/';
}

/**
 * Determine if a directory is writable.
 *
 * This function is used to work around certain ACL issues in PHP primarily affecting Windows Servers.
 *
 * @since 3.6.0
 * @see   win_is_writable()
 *
 * @param  string $path Path to check for write-ability.
 * @return bool   Whether the path is writable.
 */
function wp_is_writable( $path )
{
	return 'WIN' === strtoupper( substr( PHP_OS, 0, 3 ) )
		? win_is_writable( $path )
		: @ is_writable( $path );
}

/**
 * Workaround for Windows bug in is_writable() function.
 *
 * PHP has issues with Windows ACL's for determine if a directory is writable or not, this works around them by checking the ability to open files rather than relying upon PHP to interprate the OS ACL.
 *
 * @since 2.8.0
 * @see   https://bugs.php.net/bug.php?id=27609
 * @see   https://bugs.php.net/bug.php?id=30931
 *
 * @param  string $path Windows path to check for write-ability.
 * @return bool   Whether the path is writable.
 */
function win_is_writable( $path )
{
	if ( $path[ strlen( $path ) - 1 ] == '/' ) {
		// If it looks like a directory, check a random file within the directory.
		return win_is_writable( $path . uniqid( mt_rand() ) . '.tmp' );
	} elseif ( is_dir( $path ) ) {
		// If it's a directory (and not a file) check a random file within the directory.
		return win_is_writable( $path . '/' . uniqid( mt_rand() ) . '.tmp' );
	}

	// Check tmpfile for read/write capabilities.
	$should_delete_tmp_file = ! file_exists( $path );
	$f = @fopen( $path, 'a' );

	if ( $f === FALSE ) {
		return FALSE;
	}

	fclose( $f );

	if ( $should_delete_tmp_file ) {
		unlink( $path );
	}

	return TRUE;
}

/**
 * Retrieves uploads directory information.
 *
 * Same as wp_upload_dir() but "light weight" as it doesn't attempt to create the uploads directory.
 * Intended for use in themes, when only 'basedir' and 'baseurl' are needed, generally in all cases when not uploading files.
 *
 * @since 4.5.0
 * @see   wp_upload_dir()
 *
 * @return array See wp_upload_dir() for description.
 */
function wp_get_upload_dir()
{
	return wp_upload_dir( NULL, FALSE );
}

/**
 * Get an array containing the current upload directory's path and url.
 *
 * Checks the 'upload_path' option, which should be from the web root folder, and if it isn't empty it will be used.
 * If it is empty, then the path will be 'WP_CONTENT_DIR/uploads'.
 * If the 'UPLOADS' constant is defined, then it will override the 'upload_path' option and 'WP_CONTENT_DIR/uploads' path.
 *
 * The upload URL path is set either by the 'upload_url_path' option or by using the 'WP_CONTENT_URL' constant and appending '/uploads' to the path.
 *
 * If the 'uploads_use_yearmonth_folders' is set to true (checkbox if checked in the administration settings panel), then the time will be used.
 * The format will be year first and then month.
 *
 * If the path couldn't be created, then an error will be returned with the key 'error' containing the error message.
 * The error suggests that the parent directory is not writable by the server.
 *
 * On success, the returned array will have many indices:
 * 'path' - base directory and sub directory or full path to upload directory.
 * 'url' - base url and sub directory or absolute URL to upload directory.
 * 'subdir' - sub directory if uploads use year/month folders option is on.
 * 'basedir' - path without subdir.
 * 'baseurl' - URL path without subdir.
 * 'error' - false or error message.
 *
 * @since     2.0.0
 * @uses      _wp_upload_dir()
 * @staticvar array $cache
 * @staticvar array $tested_paths
 *
 * @param  string $time          Optional.
 *                               Time formatted in 'yyyy/mm'.
 *                               Default null.
 * @param  bool   $create_dir    Optional.
 *                               Whether to check and create the uploads directory.
 *                               Default true for backward compatibility.
 * @param  bool   $refresh_cache Optional.
 *                               Whether to refresh the cache.
 *                               Default false.
 * @return array  See above for description.
 */
function wp_upload_dir( $time = NULL, $create_dir = TRUE, $refresh_cache = FALSE )
{
	static $cache = array(), $tested_paths = array();
	$key = sprintf( '%d-%s', get_current_blog_id(), ( string ) $time );

	if ( $refresh_cache || empty( $cache[ $key ] ) ) {
		$cache[ $key ] = _wp_upload_dir( $time );
	}

	/**
	 * Filters the uploads directory data.
	 *
	 * @since 2.0.0
	 *
	 * @param array $uploads Array of upload directory data with keys of 'path', 'url', 'subdir', 'basedir', and 'error'.
	 */
	$uploads = apply_filters( 'upload_dir', $cache[ $key ] );

	if ( $create_dir ) {
		$path = $uploads['path'];

		if ( array_key_exists( $path, $tested_paths ) ) {
			$uploads['error'] = $tested_paths[ $path ];
		} else {
			if ( ! wp_mkdir_p( $path ) ) {
				$error_path = 0 === strpos( $uploads['basedir'], ABSPATH )
					? str_replace( ABSPATH, '', $uploads['basedir'] ) . $uploads['subdir']
					: basename( $uploads['basedir'] ) . $uploads['subdir'];

				$uploads['error'] = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), esc_html( $error_path ) );
			}

			$tested_paths[ $path ] = $uploads['error'];
		}
	}

	return $uploads;
}

/**
 * A non-filtered, non-cached version of wp_upload_dir() that doesn't check the path.
 *
 * @since  4.5.0
 * @access private
 *
 * @param  string $time Optional.
 *                      Time formatted in 'yyyy/mm'.
 *                      Default null.
 * @return array  See wp_upload_dir()
 */
function _wp_upload_dir( $time = NULL )
{
	$siteurl = get_option( 'siteurl' );
	$upload_path = trim( get_option( 'upload_path' ) );

	$dir = empty( $upload_path ) || 'wp-content/uploads' == $upload_path
		? WP_CONTENT_DIR . '/uploads'
		: ( 0 !== strpos( $upload_path, ABSPATH )
			? path_join( ABSPATH, $upload_path ) // $dir is absolute, $upload_path is (maybe) relative to ABSPATH
			: $upload_path );

	if ( ! $url = get_option( 'upload_url_path' ) ) {
		$url = empty( $upload_path ) || 'wp-content/uploads' == $upload_path || $upload_path == $dir
			? WP_CONTENT_URL . '/uploads'
			: trailingslashit( $siteurl ) . $upload_path;
	}

	/**
	 * Honor the value of UPLOADS.
	 * This happens as long as ms-files rewriting is disabled.
	 * We also sometimes obey UPLOADS when rewriting is enabled -- see the next block.
	 */
	if ( defined( 'UPLOADS' )
	  && ! ( is_multisite() && get_site_option( 'ms_files_rewriting' ) ) ) {
		$dir = ABSPATH . UPLOADS;
		$url = trailingslashit( $siteurl ) . UPLOADS;
	}

	// If multisite (and if not the main site in a post-MU network)
	if ( is_multisite()
	  && ! ( is_main_network() && is_main_site() && defined( 'MULTISITE' ) ) ) {
		if ( ! get_site_option( 'ms_files_rewriting' ) ) {
			/**
			 * If ms-files rewriting is disabled (networks created post-3.5), it is fairly straightforward:
			 * Append sites/%d if we're not on the main site (for post-MU networks).
			 * (The extra directory prevents a four-digit ID from conflicting with a year-based directory for the main site.
			 * But if a MU-era network has disabled ms-files rewriting manually, they don't need the extra directory, as they never had wp-content/uploads for the main site.)
			 */
			$ms_dir = defined( 'MULTISITE' )
				? '/sites/' . get_current_blog_id()
				: '/' . get_current_blog_id();

			$dir .= $ms_dir;
			$url .= $ms_dir;
		} elseif ( defined( 'UPLOADS' ) && ! ms_is_switched() ) {
			/**
			 * Handle the old-form ms-files.php rewriting if the network still has that enabled.
			 * When ms-files rewriting is enabled, then we only listen to UPLOADS when:
			 * 1) We are not on the main site in a post-MU network, as wp-content/uploads is used there, and
			 * 2) We are not switched, as ms_upload_constants() hardcodes these constants to reflect the original blog ID.
			 *
			 * Rather than UPLOADS, we actually use BLOGUPLOADDIR if it is set, as it is absolute.
			 * (And it will be set, see ms_upload_constants().)
			 * Otherwise, UPLOADS can be used, as it is relative to ABSPATH.
			 * For the final piece: when UPLOADS is used with ms-files rewriting in multisite, the resulting URL is /files.
			 * (#WP22702 for background.)
			 */
			$dir = defined( 'BLOGUPLOADDIR' )
				? untrailingslashit( BLOGUPLOADDIR )
				: ABSPATH . UPLOADS;

			$url = trailingslashit( $siteurl ) . 'files';
		}
	}

	$basedir = $dir;
	$baseurl = $url;
	$subdir = '';

	if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
		// Generate the yearly and monthly dirs.
		if ( ! $time ) {
			$time = current_time( 'mysql' );
		}

		$y = substr( $time, 0, 4 );
		$m = substr( $time, 5, 2 );
		$subdir = "/$y/$m";
	}

	$dir .= $subdir;
	$url .= $subdir;
	return array(
		'path'    => $dir,
		'url'     => $url,
		'subdir'  => $subdir,
		'basedir' => $basedir,
		'baseurl' => $baseurl,
		'error'   => FALSE
	);
}

/**
 * Retrieve the file type based on the extension name.
 *
 * @since 2.5.0
 *
 * @param  string      $ext The extension to search.
 * @return string|void The file type, example: audio, video, document, spreadsheet, etc.
 */
function wp_ext2type( $ext )
{
	$ext = strtolower( $ext );
	$ext2type = wp_get_ext_types();

	foreach ( $ext2type as $type => $exts ) {
		if ( in_array( $ext, $exts ) ) {
			return $type;
		}
	}
}

/**
 * Retrieve the file type from the file name.
 *
 * You can optionally define the mime array, if needed.
 *
 * @since 2.0.4
 *
 * @param  string $filename File name or path.
 * @param  array  $mimes    Optional.
 *                          Key is the file extension with value as the mime type.
 * @return array  Values with extension first and mime type.
 */
function wp_check_filetype( $filename, $mimes = NULL )
{
	if ( empty( $mimes ) ) {
		$mimes = get_allowed_mime_types();
	}

	$type = FALSE;
	$ext = FALSE;

	foreach ( $mimes as $ext_preg => $mime_match ) {
		$ext_preg = '!\.(' . $ext_preg . ')$!i';

		if ( preg_match( $ext_preg, $filename, $ext_matches ) ) {
			$type = $mime_match;
			$ext = $ext_matches[1];
			break;
		}
	}

	return compact( 'ext', 'type' );
}

/**
 * Retrieve list of mime types and file extensions.
 *
 * @since 3.5.0
 * @since 4.2.0 Support was added for GIMP (xcf) files.
 *
 * @return array Array of mime types keyed by the file extension regex corresponding to those types.
 */
function wp_get_mime_types()
{
	/**
	 * Filters the list of mime types and file extensions.
	 *
	 * This filter should be used to add, not remove, mime types.
	 * To remove mime types, use the {@see 'upload_mimes'} filter.
	 *
	 * @since 3.5.0
	 *
	 * @param array $wp_get_mime_types Mime types keyed by the file extension regex corresponding to those types.
	 */
	return apply_filters( 'mime_types', array(
			// Image formats.
			'jpg|jpeg|jpe'                 => 'image/jpeg',
			'gif'                          => 'image/gif',
			'png'                          => 'image/png',
			'bmp'                          => 'image/bmp',
			'tiff|tif'                     => 'image/tiff',
			'ico'                          => 'image/x-icon',

			// Video formats.
			'asf|asx'                      => 'video/x-ms-asf',
			'wmv'                          => 'video/x-ms-wmv',
			'wmx'                          => 'video/x-ms-wmx',
			'wm'                           => 'video/x-ms-wm',
			'avi'                          => 'video/avi',
			'divx'                         => 'video/divx',
			'flv'                          => 'video/x-flv',
			'mov|qt'                       => 'video/quicktime',
			'mpeg|mpg|mpe'                 => 'video/mpeg',
			'mp4|m4v'                      => 'video/mp4',
			'ogv'                          => 'video/ogg',
			'webm'                         => 'video/webm',
			'mkv'                          => 'video/x-matroska',
			'3gp|3gpp'                     => 'video/3gpp', // Can also be audio
			'3g2|3gp2'                     => 'video/3gpp2', // Can also be audio

			// Text formats.
			'txt|asc|c|cc|h|srt'           => 'text/plain',
			'csv'                          => 'text/csv',
			'tsv'                          => 'text/tab-separated-values',
			'ics'                          => 'text/calendar',
			'rtx'                          => 'text/richtext',
			'css'                          => 'text/css',
			'htm|html'                     => 'text/html',
			'vtt'                          => 'text/vtt',
			'dfxp'                         => 'application/ttaf+xml',
			// Audio formats.
			'mp3|m4a|m4b'                  => 'audio/mpeg',
			'aac'                          => 'audio/aac',
			'ra|ram'                       => 'audio/x-realaudio',
			'wav'                          => 'audio/wav',
			'ogg|oga'                      => 'audio/ogg',
			'flac'                         => 'audio/flac',
			'mid|midi'                     => 'audio/midi',
			'wma'                          => 'audio/x-ms-wma',
			'wax'                          => 'audio/x-ms-wax',
			'mka'                          => 'audio/x-matroska',

			// Misc application formats.
			'rtf'                          => 'application/rtf',
			'js'                           => 'application/javascript',
			'pdf'                          => 'application/pdf',
			'swf'                          => 'application/x-shockwave-flash',
			'class'                        => 'application/java',
			'tar'                          => 'application/x-tar',
			'zip'                          => 'application/zip',
			'gz|gzip'                      => 'application/x-gzip',
			'rar'                          => 'application/rar',
			'7z'                           => 'application/x-7z-compressed',
			'exe'                          => 'application/x-msdownload',
			'psd'                          => 'application/octet-stream',
			'xcf'                          => 'application/octet-stream',

			// MS Office formats.
			'doc'                          => 'application/msword',
			'pot|pps|ppt'                  => 'application/vnd.ms-powerpoint',
			'wri'                          => 'application/vnd.ms-write',
			'xla|xls|xlt|xlw'              => 'application/vnd.ms-excel',
			'mdb'                          => 'application/vnd.ms-access',
			'mpp'                          => 'application/vnd.ms-project',
			'docx'                         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'docm'                         => 'application/vnd.ms-word.document.macroEnabled.12',
			'dotx'                         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
			'dotm'                         => 'application/vnd.ms-word.template.macroEnabled.12',
			'xlsx'                         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xlsm'                         => 'application/vnd.ms-excel.sheet.macroEnabled.12',
			'xlsb'                         => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
			'xltx'                         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'xltm'                         => 'application/vnd.ms-excel.template.macroEnabled.12',
			'xlam'                         => 'application/vnd.ms-excel.addin.macroEnabled.12',
			'pptx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'pptm'                         => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
			'ppsx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'ppsm'                         => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
			'potx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.template',
			'potm'                         => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
			'ppam'                         => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
			'sldx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
			'sldm'                         => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
			'onetoc|onetoc2|onetmp|onepkg' => 'application/onenote',
			'oxps'                         => 'application/oxps',
			'xps'                          => 'application/vnd.ms-xpsdocument',

			// OpenOffice formats.
			'odt'                          => 'application/vnd.oasis.opendocument.text',
			'odp'                          => 'application/vnd.oasis.opendocument.presentation',
			'ods'                          => 'application/vnd.oasis.opendocument.spreadsheet',
			'odg'                          => 'application/vnd.oasis.opendocument.graphics',
			'odc'                          => 'application/vnd.oasis.opendocument.chart',
			'odb'                          => 'application/vnd.oasis.opendocument.database',
			'odf'                          => 'application/vnd.oasis.opendocument.formula',

			// WordPerfect formats.
			'wp|wpd'                       => 'application/wordperfect',

			// iWork formats.
			'key'                          => 'application/vnd.apple.keynote',
			'numbers'                      => 'application/vnd.apple.numbers',
			'pages'                        => 'application/vnd.apple.pages'
		) );
}

/**
 * Retrieves the list of common file extensions and their types.
 *
 * @since 4.6.0
 *
 * @return array Array of file extensions types keyed by the type of file.
 */
function wp_get_ext_types()
{
	/**
	 * Filters file type based on the extension name.
	 *
	 * @since 2.5.0
	 * @see   wp_ext2type()
	 *
	 * @param array $ext2type Multi-dimensional array with extensions for a default set of file types.
	 */
	return apply_filters( 'ext2type', array(
			'image'       => array( 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tif', 'tiff', 'ico' ),
			'audio'       => array( 'aac', 'ac3', 'aif', 'aiff', 'flac', 'm3a', 'm4a', 'm4b', 'mka', 'mp1', 'mp2', 'mp3', 'ogg', 'oga', 'ram', 'wav', 'wma' ),
			'video'       => array( '3g2', '3gp', '3gpp', 'asf', 'avi', 'divx', 'dv', 'flv', 'm4v', 'mkv', 'mov', 'mp4', 'mpeg', 'mpg', 'mpv', 'ogm', 'ogv', 'qt', 'rm', 'vob', 'wmv' ),
			'document'    => array( 'doc', 'docx', 'docm', 'dotm', 'odt', 'pages', 'pdf', 'xps', 'oxps', 'rtf', 'wp', 'wpd', 'psd', 'xcf' ),
			'spreadsheet' => array( 'numbers', 'ods', 'xls', 'xlsx', 'xlsm', 'xlsb' ),
			'interactive' => array( 'swf', 'key', 'ppt', 'pptx', 'pptm', 'pps', 'ppsx', 'ppsm', 'sldx', 'sldm', 'odp' ),
			'text'        => array( 'asc', 'csv', 'tsv', 'txt' ),
			'archive'     => array( 'bz2', 'cab', 'dmg', 'gz', 'rar', 'sea', 'sit', 'sqx', 'tar', 'tgz', 'zip', '7z' ),
			'code'        => array( 'css', 'htm', 'html', 'php', 'js' )
		) );
}

/**
 * Retrieve list of allowed mime types and file extensions.
 *
 * @since 2.8.6
 *
 * @param  int|WP_User $user Optional.
 *                           User to check.
 *                           Defaults to the current user.
 * @return array       Array of mime types keyed by the file extension regex corresponding to those types.
 */
function get_allowed_mime_types( $user = NULL )
{
	$t = wp_get_mime_types();
	unset( $t['swf'], $t['exe'] );

	if ( function_exists( 'current_user_can' ) ) {
		$unfiltered = $user
			? user_can( $user, 'unfiltered_html' )
			: current_user_can( 'unfiltered_html' );
	}

	if ( empty( $unfiltered ) ) {
		unset( $t['htm|html'], $t['js'] );
	}

	/**
	 * Filters list of allowed mime types and file extensions.
	 *
	 * @since 2.0.0
	 * @param array            $t    Mime types keyed by the file extension regex corresponding to those types.
	 *                               'swf' and 'exe' removed from full list.
	 *                               'htm|html' also removed depending on '$user' capabilities.
	 * @param int|WP_User|null $user User ID, User object or null if not provided (indicates current user).
	 */
	return apply_filters( 'upload_mimes', $t, $user );
}

/**
 * Kill WordPress execution and display HTML message with error message.
 *
 * This function complements the `die()` PHP function.
 * The difference is that HTML will be displayed to the user.
 * It is recommended to use this function only when the execution should not continue any further.
 * It is not recommended to call this function very often, and try to handle as many errors as possible silently or more gracefully.
 *
 * As a shorthand, the desired HTTP response code may be passed as an integer to the `$title` parameter (the default title would apply) or the `$args` parameter.
 *
 * @since 2.0.4
 * @since 4.1.0 The `$title` and `$args` parameters were changed to optionally accept an integer to be used as the response code.
 *
 * @param string|WP_Error  $message Optional.
 *                                  Error message.
 *                                  If this is a WP_Error object, and not an Ajax or XML-RPC request, the error's messages are used.
 *                                  Default empty.
 * @param string|int       $title   Optional.
 *                                  Error title.
 *                                  If `$message` is a `WP_Error` object, error data with the key 'title' may be used to specify the title.
 *                                  If `$title` is an integer, then it is treated as the response code.
 *                                  Default empty.
 * @param string|array|int $args {
 *     Optional.
 *     Arguments to control behavior.
 *     If `$args` is an integer, then it is treated as the response code.
 *     Default empty array.
 *
 *     @type int    $response       The HTTP response code.
 *                                  Default 200 for Ajax requests, 500 otherwise.
 *     @type bool   $back_link      Whether to include a link to go back.
 *                                  Default false.
 *     @type string $text_direction The text direction.
 *                                  This is only useful internally, when WordPress is still loading and the site's locale is not set up yet.
 *                                  Accepts 'rtl'.
 *                                  Default is the value of is_rtl().
 * }
 */
function wp_die( $message = '', $title = '', $args = array() )
{
	if ( is_int( $args ) ) {
		$args = array( 'response' => $args );
	} elseif ( is_int( $title ) ) {
		$args = array( 'response' => $title );
		$title = '';
	}

	if ( wp_doing_ajax() ) {
		/**
		 * Filters the callback for killing WordPress execution for Ajax requests.
		 *
		 * @since 3.4.0
		 *
		 * @param callable $function Callback function name.
		 */
		$function = apply_filters( 'wp_die_ajax_handler', '_ajax_wp_die_handler' );
	} elseif ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		/**
		 * Filters the callback for killing WordPress execution for XML-RPC requests.
		 *
		 * @since 3.4.0
		 *
		 * @param callable $function Callback function name.
		 */
		$function = apply_filters( 'wp_die_xmlrpc_handler', '_xmlrpc_wp_die_handler' );
	} else {
		/**
		 * Filters the callback for killing WordPress execution for all non-Ajax, non-XML-RPC requests.
		 *
		 * @since 3.0.0
		 *
		 * @param callable $function Callback function name.
		 */
		$function = apply_filters( 'wp_die_handler', '_default_wp_die_handler' );
	}

	call_user_func( $function, $message, $title, $args );
}

/**
 * Merge user defined arguments into defaults array.
 *
 * This function is used throughout WordPress to allow for both string or array to be merged into another array.
 *
 * @since 2.2.0
 * @since 2.3.0 `$args` can now also be an object.
 *
 * @param  string|array|object $args     Value to merge with $defaults.
 * @param  array               $defaults Optional.
 *                             Array that serves as the defaults.
 *                             Default empty.
 * @return array               Merged user defined values with defaults.
 */
function wp_parse_args( $args, $defaults = '' )
{
	if ( is_object( $args ) ) {
		$r = get_object_vars( $args );
	} elseif ( is_array( $args ) ) {
		$r = &$args;
	} else {
		wp_parse_str( $args, $r );
	}

	return is_array( $defaults )
		? array_merge( $defaults, $r )
		: $r;
}

/**
 * Clean up an array, comma- or space-separated list of IDs.
 *
 * @since 3.0.0
 *
 * @param  array|string $list List of ids.
 * @return array        Sanitized array of IDs.
 */
function wp_parse_id_list( $list )
{
	if ( ! is_array( $list ) ) {
		$list = preg_split( '/[\s,]+/', $list );
	}

	return array_unique( array_map( 'absint', $list ) );
}

/**
 * Extract a slice of an array, given a list of keys.
 *
 * @since 3.1.0
 *
 * @param  array $array The original array.
 * @param  array $keys  The list of keys.
 * @return array The array slice.
 */
function wp_array_slice_assoc( $array, $keys )
{
	$slice = array();

	foreach ( $keys as $key ) {
		if ( isset( $array[ $key ] ) ) {
			$slice[ $key ] = $array[ $key ];
		}
	}

	return $slice;
}

/**
 * Filters a list of objects, based on a set of key => value arguments.
 *
 * @since 3.0.0
 * @since 4.7.0 Uses WP_List_Util class.
 *
 * @param  array       $list     An array of objects to filter.
 * @param  array       $args     Optional.
 *                               An array of key => value arguments to match against each object.
 *                               Default empty array.
 * @param  string      $operator Optional.
 *                               The logical operation to perform.
 *                               'or' means only one element from the array needs to match; 'and' means all elements must match; 'not' means no elements may match.
 *                               Default 'and'.
 * @param  bool|string $field    A field from the object to place instead of the entire object.
 *                               Default false.
 * @return array       A list of objects or object fields.
 */
function wp_filter_object_list( $list, $args = array(), $operator = 'and', $field = FALSE )
{
	if ( ! is_array( $list ) ) {
		return array();
	}

	$util = new WP_List_Util( $list );
	$util->filter( $args, $operator );

	if ( $field ) {
		$util->pluck( $field );
	}

	return $util->get_output();
}

/**
 * Pluck a certain field out of each object in a list.
 *
 * This has the same functionality and prototype of array_column() (PHP 5.5) but also supports objects.
 *
 * @since 3.1.0
 * @since 4.0.0 $index_key parameter added.
 * @since 4.7.0 Uses WP_List_Util class.
 *
 * @param  array      $list      List of objects or arrays.
 * @param  int|string $field     Field from the object to place instead of the entire object.
 * @param  int|string $index_key Optional.
 *                               Field from the object to use as keys for the new array.
 *                               Default null.
 * @return array      Array of found values.
 *                    If `$index_key` is set, an array of found values with keys corresponding to `$index_key`.
 *                    If `$index_key` is null, array keys from the original `$list` will be preserved in the results.
 */
function wp_list_pluck( $list, $field, $index_key = NULL )
{
	$util = new WP_List_Util( $list );
	return $util->pluck( $field, $index_key );
}

/**
 * Sorts a list of objects, based on one or more orderby arguments.
 *
 * @since 4.7.0
 *
 * @param  array        $list          An array of objects to filter.
 * @param  string|array $orderby       Optional.
 *                                     Either the field name to order by or an array of multiple orderby fields as $orderby => $order.
 * @param  string       $order         Optional.
 *                                     Either 'ASC' or 'DESC'.
 *                                     Only used if $orderby is a string.
 * @param  bool         $preserve_keys Optional.
 *                                     Whether to preserve keys.
 *                                     Default false.
 * @return array        The sorted array.
 */
function wp_list_sort( $list, $orderby = array(), $order = 'ASC', $preserve_keys = FALSE )
{
	if ( ! is_array( $list ) ) {
		return array();
	}

	$util = new WP_List_Util( $list );
	return $util->sort( $orderby, $order, $preserve_keys );
}

/**
 * Load custom DB error or display WordPress DB error.
 *
 * If a file exists in the wp-content directory named db-error.php, then it will be loaded instead of displaying the WordPress DB error.
 * If it is not found, then the WordPress DB error will be displayed instead.
 *
 * The WordPress DB error sets the HTTP status header to 500 to try to prevent search engines from caching the message.
 * Custom DB messages should do the same.
 *
 * This function was backported to WordPress 2.3.2, but originally was added in WordPress 2.5.0.
 *
 * @since  2.3.2
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function dead_db()
{
	global $wpdb;
	wp_load_translations_early();

	// Load custom DB error template, if present.
	if ( file_exists( WP_CONTENT_DIR . '/db-error.php' ) ) {
		require_once( WP_CONTENT_DIR . '/db-error.php' );
		die();
	}

	// If installing or in the admin, provide the verbose message.
	if ( wp_installing() || defined( 'WP_ADMIN' ) ) {
		wp_die( $wpdb->error );
	}

	// Otherwise, be terse.
	status_header( 500 );
	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml"<?php if ( is_rtl() ) echo ' dir="rtl"'; ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php _e( 'Database Error' ); ?></title>
</head>
<body>
	<h1><?php _e( 'Error establishing a database connection' ); ?></h1>
</body>
</html>
<?php
	die();
}

/**
 * Convert a value to non-negative integer.
 *
 * @since 2.5.0
 *
 * @param  mixed $maybeint Data you wish to have converted to a non-negative integer.
 * @return int   A non-negative integer.
 */
function absint( $maybeint )
{
	return abs( intval( $maybeint ) );
}

/**
 * Mark a function argument as deprecated and inform when it has been used.
 *
 * This function is to be used whenever a deprecated function argument is used.
 * Before this function is called, the argument must be checked for whether it was used by comparing it to its default value or evaluating whether it is empty.
 * For example:
 *
 *     if ( ! empty( $deprecated ) ) {
 *         _deprecated_argument( __FUNCTION__, '3.0.0' );
 *     }
 *
 * There is a hook deprecated_argument_run that will be called that can be used to get the backtrace up to what file and function used the deprecated argument.
 *
 * The current behavior is to trigger a user error if WP_DEBUG is true.
 *
 * @since  3.0.0
 * @access private
 *
 * @param string $function The function that was called.
 * @param string $version  The version of WordPress that deprecated the argument used.
 * @param string $message  Optional.
 *                         A message regarding the change.
 *                         Default null.
 */
function _deprecated_argument( $function, $version, $message = NULL )
{
	/**
	 * Fires when a deprecated argument is called.
	 *
	 * @since 3.0.0
	 *
	 * @param string $function The function that was called.
	 * @param string $message  A message regarding the change.
	 * @param string $version  The version of WordPress that deprecated the argument used.
	 */
	do_action( 'deprecated_argument_run', $function, $message, $version );

	/**
	 * Filters whether to trigger an error for deprecated arguments.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $trigger Whether to trigger the error for deprecated arguments.
	 *                      Default true.
	 */
	if ( WP_DEBUG && apply_filters( 'deprecated_argument_trigger_error', TRUE ) ) {
		if ( function_exists( '__' ) ) {
			if ( ! is_null( $message ) ) {
				trigger_error( sprintf( __( '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s! %3$s' ), $function, $version, $message ) );
			} else {
				trigger_error( sprintf( __( '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s with no alternative available.' ), $function, $version ) );
			}
		} else {
			if ( ! is_null( $message ) ) {
				trigger_error( sprintf( '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s! %3$s', $function, $version, $message ) );
			} else {
				trigger_error( sprintf( '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s with no alternative available.', $function, $version ) );
			}
		}
	}
}

/**
 * Marks a deprecated action or filter hook as deprecated and throws a notice.
 *
 * Use the {@see 'deprecated_hook_run'} action to get the backtrace describing where the deprecated hook was called.
 *
 * Default behavior is to trigger a user error if `WP_DEBUG` is true.
 *
 * This function is called by the do_action_deprecated() and apply_filters_deprecated() functions, and so generally does not need to be called directly.
 *
 * @since  4.6.0
 * @access private
 *
 * @param string $hook        The hook that was used.
 * @param string $version     The version of WordPress that deprecated the hook.
 * @param string $replacement Optional.
 *                            The hook that should have been used.
 * @param string $message     Optional.
 *                            A message regarding the change.
 */
function _deprecated_hook( $hook, $version, $replacement = NULL, $message = NULL )
{
	/**
	 * Filters when a deprecated hook is called.
	 *
	 * @since 4.6.0
	 *
	 * @param string $hook        The hook that was called.
	 * @param string $replacement The hook that should be used as a replacement.
	 * @param string $version     The version of WordPress that deprecated the argument used.
	 * @param string $message     A message regarding the change.
	 */
	do_action( 'deprecated_hook_run', $hook, $replacement, $version, $message );

	/**
	 * Filters whether to trigger deprecated hook errors.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $trigger Whether to trigger deprecated hook errors.
	 *                      Requires `WP_DEBUG` to be defined true.
	 */
	if ( WP_DEBUG && apply_filters( 'deprecated_hook_trigger_error', TRUE ) ) {
		$message = empty( $message )
			? ''
			: ' ' . $message;

		if ( ! is_null( $replacement ) ) {
			trigger_error( sprintf( __( '%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.' ), $hook, $version, $replacement ) . $message );
		} else {
			trigger_error( sprintf( __( '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.' ), $hook, $version ) . $message );
		}
	}
}

/**
 * Mark something as being incorrectly called.
 *
 * There is ahook {@see 'doing_it_wrong_run'} that will be called that can be used to get the backtrace up to what file and function called the deprecated function.
 *
 * The current behavior is to trigger a user error if `WP_DEBUG` is true.
 *
 * @since  3.1.0
 * @access private
 *
 * @param string $function The function that was called.
 * @param string $message  A message explaining what has been done incorrectly.
 * @param string $version  The version of WordPress where the message was added.
 */
function _doing_it_wrong( $function, $message, $version )
{
	/**
	 * Fires when the given function is being used incorrectly.
	 *
	 * @since 3.1.0
	 *
	 * @param string $function The function that was called.
	 * @param string $message  A message explaining what has been done incorrectly.
	 * @param string $version  The version of WordPress where the message was added.
	 */
	do_action( 'doing_it_wrong_run', $function, $message, $version );

	/**
	 * Filters whether to trigger an error for _doing_it_wrong() calls.
	 *
	 * @since 3.1.0
	 *
	 * @param bool $trigger Whether to trigger the error for _doing_it_wrong() calls.
	 *                      Default true.
	 */
	if ( WP_DEBUG && apply_filters( 'doing_it_wrong_trigger_error', TRUE ) ) {
		if ( function_exists( '__' ) ) {
			$version = is_null( $version )
				? ''
				: sprintf( __( '(This message was added in version %s.)' ), $version );

			$message .= ' ' . sprintf( __( 'Please see <a href="%s">Debugging in WordPress</a> for more information.' ), __( 'https://codex.wordpress.org/Debugging_in_WordPress' ) );
			trigger_error( sprintf( __( '%1$s was called <strong>incorrectly</strong>. %2$s %3$s' ), $function, $message, $version ) );
		} else {
			$version = is_null( $version )
				? ''
				: sprintf( '(This message was added in version %s.)', $version );

			$message .= sprintf( ' Please see <a href="%s">Debugging in WordPress</a> for more information.', 'https://codex.wordpress.org/Debugging_in_WordPress' );
			trigger_error( sprintf( '%1$s was called <strong>incorrectly</strong>. %2$s %3$s', $function, $message, $version ) );
		}
	}
}

/**
 * Whether to force SSL used for the Administration Screens.
 *
 * @since     2.6.0
 * @staticvar bool $forced
 *
 * @param  string|bool $force Optional.
 *                            Whether to force SSL in admin screens.
 *                            Default null.
 * @return bool        True if forced, false if not forced.
 */
function force_ssl_admin( $force = NULL )
{
	static $forced = FALSE;

	if ( ! is_null( $force ) ) {
		$old_forced = $forced;
		$forced = $force;
		return $old_forced;
	}

	return $forced;
}

/**
 * Temporarily suspend cache additions.
 *
 * Stops more data being added to the cache, but still allows cache retrieval.
 * This is useful for actions, such as imports, when a lot of data would otherwise be almost uselessly added to the cache.
 *
 * Suspension lasts for a single page load at most.
 * Remember to call this function again if you wish to re-enable cache adds earlier.
 *
 * @since     3.3.0
 * @staticvar bool $_suspend
 *
 * @param  bool $suspend Optional.
 *                       Suspends additions if true, re-enables them if false.
 * @return bool The current suspend setting.
 */
function wp_suspend_cache_addition( $suspend = NULL )
{
	static $_suspend = FALSE;

	if ( is_bool( $suspend ) ) {
		$_suspend = $suspend;
	}

	return $_suspend;
}

/**
 * Determine whether a site is the main site of the current network.
 *
 * @since 3.0.0
 * @since 4.9.0 The $network_id parameter has been added.
 *
 * @param  int  $site_id    Optional.
 *                          Site ID to test.
 *                          Defaults to current site.
 * @param  int  $network_id Optional.
 *                          Network ID of the network to check for.
 *                          Defaults to current network.
 * @return bool True if $site_id is the main site of the network, or if not running Multisite.
 */
function is_main_site( $site_id = NULL, $network_id = NULL )
{
	if ( ! is_multisite() ) {
		return TRUE;
	}

	if ( ! $site_id ) {
		$site_id = get_current_blog_id();
	}

	$site_id = ( int ) $site_id;
	return $site_id === get_main_site_id( $network_id );
}

/**
 * Get the main site ID.
 *
 * @since 4.9.0
 *
 * @param  int $network_id Optional.
 *                         The ID of the network for which to get the main site.
 *                         Defaults to the current network.
 * @return int The ID of the main site.
 */
function get_main_site_id( $network_id = NULL )
{
	if ( ! is_multisite() ) {
		return get_current_blog_id();
	}

	$network = get_network( $network_id );

	if ( ! $network ) {
		return 0;
	}

	return $network->site_id;
}

/**
 * Determine whether a network is the main network of the Multisite installation.
 *
 * @since 3.7.0
 *
 * @param  int  $network_id Optional.
 *                          Network ID to test.
 *                          Defaults to current network.
 * @return bool True if $network_id is the main network, or if not running Multisite.
 */
function is_main_network( $network_id = NULL )
{
	if ( ! is_multisite() ) {
		return TRUE;
	}

	if ( NULL === $network_id ) {
		$network_id = get_current_network_id();
	}

	$network_id = ( int ) $network_id;
	return $network_id === get_main_network_id();
}

/**
 * Get the main network ID.
 *
 * @since 4.3.0
 *
 * @return int The ID of the main network.
 */
function get_main_network_id()
{
	if ( ! is_multisite() ) {
		return 1;
	}

	$current_network = get_network();

	if ( defined( 'PRIMARY_NETWORK_ID' ) ) {
		$main_network_id = PRIMARY_NETWORK_ID;
	} elseif ( isset( $current_network->id ) && 1 === ( int ) $current_network->id ) {
		// If the current network has an ID of 1, assume it is the main network.
		$main_network_id = 1;
	} else {
		$_networks = get_networks( array(
				'fields' => 'ids',
				'number' => 1
			) );
		$main_network_id = array_shift( $_networks );
	}

	/**
	 * Filters the main network ID.
	 *
	 * @since 4.3.0
	 *
	 * @param int $main_network_id The ID of the main network.
	 */
	return ( int ) apply_filters( 'get_main_network_id', $main_network_id );
}

/**
 * Strip close comment and close php tags from file headers used by WP.
 *
 * @since  2.8.0
 * @access private
 * @see    https://core.trac.wordpress.org/ticket/8497
 *
 * @param  string $str Header comment to clean up.
 * @return string
 */
function _cleanup_header_comment( $str )
{
	return trim( preg_replace( "/\s*(?:\*\/|\?>).*/", '', $str ) );
}

/**
 * Retrieve metadata from a file.
 *
 * Searches for metadata in the first 8kiB of a file, such as a plugin or theme.
 * Each piece of metadata must be on its own line.
 * Fields can not span multiple lines, the value will get cut at the end of the first line.
 *
 * If the file data is not within that first 8kiB, then the author should correct their plugin file and move the data headers to the top.
 *
 * @link  https://codex.wordpress.org/File_Header
 * @since 2.9.0
 *
 * @param  string $file            Path to the file.
 * @param  array  $default_headers List of headers, in the format array( 'HeaderKey' => 'Header Name' ).
 * @param  string $context         Optional.
 *                                 If specified adds filter hook {@see 'extra_$context_headers'}.
 *                                 Default empty.
 * @return array  Array of file headers in `HeaderKey => Header Value` format.
 */
function get_file_data( $file, $default_headers, $context = '' )
{
	// We don't need to write to the file, so just open for reading.
	$fp = fopen( $file, 'r' );

	// Pull only the first 8kiB of the file in.
	$file_data = fread( $fp, 8192 );

	// PHP will close file handle, but we are good citizens.
	fclose( $fp );

	// Make sure we catch CR-only line endings.
	$file_data = str_replace( "\r", "\n", $file_data );

	/**
	 * Filters extra file headers by context.
	 *
	 * The dynamic portion of the hook name, `$context`, refers to the context where extra headers might be loaded.
	 *
	 * @since 2.9.0
	 *
	 * @param array $extra_context_headers Empty array by default.
	 */
	if ( $context && $extra_headers = apply_filters( "extra_{$context}_headers", array() ) ) {
		$extra_headers = array_combine( $extra_headers, $extra_headers ); // Keys equal values
		$all_headers = array_merge( $extra_headers, ( array ) $default_headers );
	} else {
		$all_headers = $default_headers;
	}

	foreach ( $all_headers as $field => $regex ) {
		$all_headers[ $field ] = preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1]
			? _cleanup_header_comment( $match[1] )
			: '';
	}

	return $all_headers;
}

/**
 * Return a MySQL expression for selecting the week number based on the start_of_week option.
 *
 * @ignore
 * @since  3.0.0
 *
 * @param  string $column Database column.
 * @return string SQL clause.
 */
function _wp_mysql_week( $column )
{
	switch ( $start_of_week = ( int ) get_option( 'start_of_week' ) ) {
		case 1:
			return "WEEK( $column, 1 )";

		case 2:
		case 3:
		case 4:
		case 5:
		case 6:
			return "WEEK( DATE_SUB( $column, INTERVAL $start_of_week DAY ), 0 )";

		case 0:
		default:
			return "WEEK( $column, 0 )";
	}
}

/**
 * Find hierarchy loops using a callback function that maps object IDs to parent IDs.
 *
 * @since  3.1.0
 * @access private
 *
 * @param  callable $callback      Function that accepts (ID, $callback_args) and outputs parent_ID.
 * @param  int      $start         The ID to start the loop check at.
 * @param  int      $start_parent  The parent_ID of $start to use instead of calling $callback( $start ).
 *                                 Use null to always use $callback.
 * @param  array    $callback_args Optional.
 *                                 Additional arguments to send to $callback.
 * @return array    IDs of all members of loop.
 */
function wp_find_hierarchy_loop( $callback, $start, $start_parent, $callback_args = array() )
{
	$override = is_null( $start_parent )
		? array()
		: array( $start => $start_parent );

	if ( ! $arbitrary_loop_member = wp_find_hierarchy_loop_tortoise_hare( $callback, $start, $override, $callback_args ) ) {
		return array();
	}

	return wp_find_hierarchy_loop_tortoise_hare( $callback, $arbitrary_loop_member, $override, $callback_args, TRUE );
}

/**
 * Use the "The Tortoise and the Hare" algorithm to detect loops.
 *
 * For every step of the algorithm, the hare takes two steps and the tortoise one.
 * If the hare ever laps the tortoise, there must be a loop.
 *
 * @since  3.1.0
 * @access private
 *
 * @param  callable $callback      Function that accepts (ID, callback_arg, ...) and outputs parent_ID.
 * @param  int      $start         The ID to start the loop check at.
 * @param  array    $override      Optional.
 *                                 An array of (ID => parent_ID, ...) to use instead of $callback.
 *                                 Default empty array.
 * @param  array    $callback_args Optional.
 *                                 Additional arguments to send to $callback.
 *                                 Default empty array.
 * @param  bool     $_return_loop  Optional.
 *                                 Return loop members or just detect presence of loop?
 *                                 Only set to true if you already know the given $start is part of a loop (otherwise the returned array might include branches).
 *                                 Default false.
 * @return mixed    Scalar ID of some arbitrary member of the loop, or array of IDs of all members of loop if $_return_loop.
 */
function wp_find_hierarchy_loop_tortoise_hare( $callback, $start, $override = array(), $callback_args = array(), $_return_loop = FALSE )
{
	$tortoise = $hare = $evanescent_hare = $start;
	$return = array();

	/**
	 * Set evanescent_hare to one past hare.
	 * Increment hare two steps.
	 */
	while ( $tortoise
	     && ( $evanescent_hare = isset( $override[ $hare ] )
	     	? $override[ $hare ]
	     	: call_user_func_array( $callback, array_merge( array( $hare ), $callback_args ) ) )
	     && ( $hare = isset( $override[ $evanescent_hare ] )
	     	? $override[ $evanescent_hare ]
	     	: call_user_func_array( $callback, array_merge( array( $evanescent_hare ), $callback_args ) ) ) ) {
		if ( $_return_loop ) {
			$return[ $tortoise ] = $return[ $evanescent_hare ] = $return[ $hare ] = TRUE;
		}

		// Tortoise got lapped - must be a loop.
		if ( $tortoise == $evanescent_hare || $tortoise == $hare ) {
			return $_return_loop
				? $return
				: $tortoise;
		}

		// Increment tortoise by one step.
		$tortoise = isset( $override[ $tortoise ] )
			? $override[ $tortoise ]
			: call_user_func_array( $callback, array_merge( array( $tortoise ), $callback_args ) );
	}

	return FALSE;
}

/**
 * Retrieve a list of protocols to allow in HTML attributes.
 *
 * @since     3.3.0
 * @since     4.3.0 Added 'webcal' to the protocols array.
 * @since     4.7.0 Added 'urn' to the protocols array.
 * @see       wp_kses()
 * @see       esc_url()
 * @staticvar array $protocols
 *
 * @return array Array of allowed protocols.
 *               Defaults to an array containing 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'svn', 'tel', 'fax', 'xmpp', 'webcal', and 'urn'.
 */
function wp_allowed_protocols()
{
	static $protocols = array();

	if ( empty( $protocols ) ) {
		$protocols = array( 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'svn', 'tel', 'fax', 'xmpp', 'webcal', 'urn' );
	}

	if ( ! did_action( 'wp_loaded' ) ) {
		/**
		 * Filters the list of protocols allowed in HTML attributes.
		 *
		 * @since 3.0.0
		 *
		 * @param array $protocols Array of allowed protocols e.g. 'http', 'ftp', 'tel', and more.
		 */
		$protocols = array_unique( ( array ) apply_filters( 'kses_allowed_protocols', $protocols ) );
	}

	return $protocols;
}

/**
 * Return a comma-separated string of functions that have been called to get to the current point in code.
 *
 * @since 3.4.0
 * @see   https://core.trac.wordpress.org/ticket/19589
 *
 * @param  string       $ignore_class Optional.
 *                                    A class to ignore all function calls within - useful when you want to just give info about the callee.
 *                                    Default null.
 * @param  int          $skip_frames  Optional.
 *                                    A number of stack frames to skip - useful for unwinding back to the source of the issue.
 *                                    Default 0.
 * @param  bool         $pretty       Optional.
 *                                    Whether or not you want a comma separated string or raw array returned.
 *                                    Default true.
 * @return string|array Either a string containing a reversed comma separated trace or an array of individual calls.
 */
function wp_debug_backtrace_summary( $ignore_class = NULL, $skip_frames = 0, $pretty = TRUE )
{
	$trace = version_compare( PHP_VERSION, '5.2.5', '>=' )
		? debug_backtrace( FALSE )
		: debug_backtrace();

	$caller = array();
	$check_class = ! is_null( $ignore_class );
	$skip_frames++; // Skip this function.

	foreach ( $trace as $call ) {
		if ( $skip_frames > 0 ) {
			$skip_frames--;
		} elseif ( isset( $call['class'] ) ) {
			if ( $check_class && $ignore_class == $call['class'] ) {
				continue; // Filter out calls.
			}

			$caller[] = "{$call['class']}{$call['type']}{$call['function']}";
		} else {
			$caller[] = in_array( $call['function'], ['do_action', 'apply_filters'] )
				? "{$call['function']}('{$call['args'][0]}')"
				: ( in_array( $call['function'], ['include', 'include_once', 'require', 'require_once'] )
					? $call['function'] . "('" . str_replace( [WP_CONTENT_DIR, ABSPATH], '', $call['args'][0] ) . "')"
					: $call['function'] );
		}
	}

	return $pretty
		? join( ', ', array_reverse( $caller ) )
		: $caller;
}

/**
 * Retrieve ids that are not already present in the cache.
 *
 * @since  3.4.0
 * @access private
 *
 * @param  array  $object_ids ID list.
 * @param  string $cache_key  The cache bucket to check against.
 * @return array  List of ids not present in the cache.
 */
function _get_non_cached_ids( $object_ids, $cache_key )
{
	$clean = array();

	foreach ( $object_ids as $id ) {
		$id = ( int ) $id;

		if ( ! wp_cache_get( $id, $cache_key ) ) {
			$clean[] = $id;
		}
	}

	return $clean;
}

/**
 * Test if a given path is a stream URL.
 *
 * @since 3.5.0
 *
 * @param  string $path The resource path or URL.
 * @return bool   True if the path is a stream URL.
 */
function wp_is_stream( $path )
{
	if ( FALSE === strpos( $path, '://' ) ) {
		// $path isn't a stream.
		return FALSE;
	}

	$wrappers    = stream_get_wrappers();
	$wrappers    = array_map( 'preg_quote', $wrappers );
	$wrappers_re = '(' . join( '|', $wrappers ) . ')';
	return preg_match( "!^$wrappers_re://!", $path ) === 1;
}

/**
 * Test if the supplied date is valid for the Gregorian calendar.
 *
 * @since 3.5.0
 * @see   checkdate()
 *
 * @param  int    $month       Month number.
 * @param  int    $day         Day number.
 * @param  int    $year        Year number.
 * @param  string $source_date The date to filter.
 * @return bool   True if valid date, false if not valid date.
 */
function wp_checkdate( $month, $day, $year, $source_date )
{
	/**
	 * Filters whether the given date is valid for the Gregorian calendar.
	 *
	 * @since 3.5.0
	 *
	 * @param bool   $checkdate   Whether the given date is valid.
	 * @param string $source_date Date to check.
	 */
	return apply_filters( 'wp_checkdate', checkdate( $month, $day, $year ), $source_date );
}

/**
 * Set the mbstring internal encoding to a binary safe encoding when func_overload is enabled.
 *
 * When mbstring.func_overload is in use for multi-byte encodings, the results from strlen() and similar functions respect the utf8 characters, causing binary data to return incorrect lengths.
 *
 * This function overrides the mbstring encoding to a binary-safe encoding, and resets it to the users expected encoding afterwards through the `reset_mbstring_encoding` function.
 *
 * It is safe to recursively call this function, however each `mbstring_binary_safe_encoding()` call must be followed up with an equal number of `reset_mbstring_encoding()` calls.
 *
 * @since     3.7.0
 * @see       reset_mbstring_encoding()
 * @staticvar array $encodings
 * @staticvar bool  $overloaded
 *
 * @param bool Optional.
 *             Whether to reset the encoding back to a previously-set encoding.
 *             Default false.
 */
function mbstring_binary_safe_encoding( $reset = FALSE )
{
	static $encodings = array();
	static $overloaded = NULL;

	if ( is_null( $overloaded ) ) {
		$overloaded = function_exists( 'mb_internal_encoding' ) && ( ini_get( 'mbstring.func_overload' ) & 2 );
	}

	if ( FALSE === $overloaded ) {
		return;
	}

	if ( ! $reset ) {
		$encoding = mb_internal_encoding();
		array_push( $encodings, $encoding );
		mb_internal_encoding( 'ISO-8859-1' );
	}

	if ( $reset && $encodings ) {
		$encoding = array_pop( $encodings );
		mb_internal_encoding( $encoding );
	}
}

/**
 * Reset the mbstring internal encoding to a users previously set encoding.
 *
 * @see   mbstring_binary_safe_encoding()
 * @since 3.7.0
 */
function reset_mbstring_encoding()
{
	mbstring_binary_safe_encoding( TRUE );
}

/**
 * Filter/validate a variable as a boolean.
 *
 * Alternative to `filter_var( $var, FILTER_VALIDATE_BOOLEAN )`.
 *
 * @since 4.0.0
 *
 * @param  mixed $var Boolean value to validate.
 * @return bool  Whether the value is validated.
 */
function wp_validate_boolean( $var )
{
	return is_bool( $var )
		? $var
		: ( is_string( $var ) && 'false' === strtolower( $var )
			? FALSE
			: ( bool ) $var );
}

/**
 * Get last changed date for the specified cache group.
 *
 * @since 4.7.0
 *
 * @param  string $group        Where the cache contents are grouped.
 * @return string $last_changed UNIX timestamp with microseconds representing when the group was last changed.
 */
function wp_cache_get_last_changed( $group )
{
	$last_changed = wp_cache_get( 'last_changed', $group );

	if ( ! $last_changed ) {
		$last_changed = microtime();
		wp_cache_set( 'last_changed', $last_changed, $group );
	}

	return $last_changed;
}
