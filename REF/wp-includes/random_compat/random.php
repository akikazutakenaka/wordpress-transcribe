<?php
/**
 * Random_* Compatibility Library
 * for using the new PHP 7 random_* API in PHP 5 projects
 *
 * @version  1.2.1
 * @released 2016-02-29
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Paragon Initiative Enterprises
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

if ( ! defined( 'PHP_VERSION_ID' ) ) {
	// This constant was introduced in PHP 5.2.7
	$RandomCompatversion = explode( '.', PHP_VERSION );
	define( 'PHP_VERSION_ID', $RandomCompatversion[0] * 10000 + $RandomCompatversion[1] * 100 + $RandomCompatversion[2] );
	$RandomCompatversion = NULL;
}

if ( PHP_VERSION_ID < 70000 ) {
	if ( ! defined( 'RANDOM_COMPAT_READ_BUFFER' ) ) {
		define( 'RANDOM_COMPAT_READ_BUFFER', 8 );
	}

	$RandomCompatDIR = dirname( __FILE__ );
	require_once $RandomCompatDIR . '/byte_safe_strings.php';
	require_once $RandomCompatDIR . '/cast_to_int.php';
	require_once $RandomCompatDIR . '/error_polyfill.php';
// @NOW 005 -> wp-includes/random_compat/error_polyfill.php
}
