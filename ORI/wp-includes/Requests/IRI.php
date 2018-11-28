<?php
/**
 * IRI parser/serialiser/normaliser
 *
 * @package Requests
 * @subpackage Utilities
 */

/**
 * IRI parser/serialiser/normaliser
 *
 * Copyright (c) 2007-2010, Geoffrey Sneddon and Steve Minutillo.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  * Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *  * Redistributions in binary form must reproduce the above copyright notice,
 *       this list of conditions and the following disclaimer in the documentation
 *       and/or other materials provided with the distribution.
 *
 *  * Neither the name of the SimplePie Team nor the names of its contributors
 *       may be used to endorse or promote products derived from this software
 *       without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package Requests
 * @subpackage Utilities
 * @author Geoffrey Sneddon
 * @author Steve Minutillo
 * @copyright 2007-2009 Geoffrey Sneddon and Steve Minutillo
 * @license http://www.opensource.org/licenses/bsd-license.php
 * @link http://hg.gsnedders.com/iri/
 *
 * @property string $iri IRI we're working with
 * @property-read string $uri IRI in URI form, {@see to_uri}
 * @property string $authority Authority part, formatted for a URI (userinfo + host + port)
 * @property string $iauthority Authority part of the IRI (userinfo + host + port)
 * @property string $userinfo Userinfo part, formatted for a URI (after '://' and before '@')
 * @property string $host Host part, formatted for a URI
 * @property string $path Path part, formatted for a URI (after first '/')
 * @property string $query Query part, formatted for a URI (after '?')
 * @property string $fragment Fragment, formatted for a URI (after '#')
 */
class Requests_IRI {
	// refactored. protected $scheme = null;
	// :
	// refactored. protected $normalization = array();

	/**
	 * Return the entire IRI when you try and read the object as a string
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->get_iri();
	}

	/**
	 * Overload __set() to provide access via properties
	 *
	 * @param string $name Property name
	 * @param mixed $value Property value
	 */
	public function __set($name, $value) {
		if (method_exists($this, 'set_' . $name)) {
			call_user_func(array($this, 'set_' . $name), $value);
		}
		elseif (
			   $name === 'iauthority'
			|| $name === 'iuserinfo'
			|| $name === 'ihost'
			|| $name === 'ipath'
			|| $name === 'iquery'
			|| $name === 'ifragment'
		) {
			call_user_func(array($this, 'set_' . substr($name, 1)), $value);
		}
	}

	/**
	 * Overload __get() to provide access via properties
	 *
	 * @param string $name Property name
	 * @return mixed
	 */
	public function __get($name) {
		// isset() returns false for null, we don't want to do that
		// Also why we use array_key_exists below instead of isset()
		$props = get_object_vars($this);

		if (
			$name === 'iri' ||
			$name === 'uri' ||
			$name === 'iauthority' ||
			$name === 'authority'
		) {
			$method = 'get_' . $name;
			$return = $this->$method();
		}
		elseif (array_key_exists($name, $props)) {
			$return = $this->$name;
		}
		// host -> ihost
		elseif (($prop = 'i' . $name) && array_key_exists($prop, $props)) {
			$name = $prop;
			$return = $this->$prop;
		}
		// ischeme -> scheme
		elseif (($prop = substr($name, 1)) && array_key_exists($prop, $props)) {
			$name = $prop;
			$return = $this->$prop;
		}
		else {
			trigger_error('Undefined property: ' . get_class($this) . '::' . $name, E_USER_NOTICE);
			$return = null;
		}

		if ($return === null && isset($this->normalization[$this->scheme][$name])) {
			return $this->normalization[$this->scheme][$name];
		}
		else {
			return $return;
		}
	}

	/**
	 * Overload __isset() to provide access via properties
	 *
	 * @param string $name Property name
	 * @return bool
	 */
	public function __isset($name) {
		return (method_exists($this, 'get_' . $name) || isset($this->$name));
	}

	/**
	 * Overload __unset() to provide access via properties
	 *
	 * @param string $name Property name
	 */
	public function __unset($name) {
		if (method_exists($this, 'set_' . $name)) {
			call_user_func(array($this, 'set_' . $name), '');
		}
	}

	// refactored. public function __construct($iri = null) {}

	/**
	 * Create a new IRI object by resolving a relative IRI
	 *
	 * Returns false if $base is not absolute, otherwise an IRI.
	 *
	 * @param IRI|string $base (Absolute) Base IRI
	 * @param IRI|string $relative Relative IRI
	 * @return IRI|false
	 */
	public static function absolutize($base, $relative) {
		if (!($relative instanceof Requests_IRI)) {
			$relative = new Requests_IRI($relative);
		}
		if (!$relative->is_valid()) {
			return false;
		}
		elseif ($relative->scheme !== null) {
			return clone $relative;
		}

		if (!($base instanceof Requests_IRI)) {
			$base = new Requests_IRI($base);
		}
		if ($base->scheme === null || !$base->is_valid()) {
			return false;
		}

		if ($relative->get_iri() !== '') {
			if ($relative->iuserinfo !== null || $relative->ihost !== null || $relative->port !== null) {
				$target = clone $relative;
				$target->scheme = $base->scheme;
			}
			else {
				$target = new Requests_IRI;
				$target->scheme = $base->scheme;
				$target->iuserinfo = $base->iuserinfo;
				$target->ihost = $base->ihost;
				$target->port = $base->port;
				if ($relative->ipath !== '') {
					if ($relative->ipath[0] === '/') {
						$target->ipath = $relative->ipath;
					}
					elseif (($base->iuserinfo !== null || $base->ihost !== null || $base->port !== null) && $base->ipath === '') {
						$target->ipath = '/' . $relative->ipath;
					}
					elseif (($last_segment = strrpos($base->ipath, '/')) !== false) {
						$target->ipath = substr($base->ipath, 0, $last_segment + 1) . $relative->ipath;
					}
					else {
						$target->ipath = $relative->ipath;
					}
					$target->ipath = $target->remove_dot_segments($target->ipath);
					$target->iquery = $relative->iquery;
				}
				else {
					$target->ipath = $base->ipath;
					if ($relative->iquery !== null) {
						$target->iquery = $relative->iquery;
					}
					elseif ($base->iquery !== null) {
						$target->iquery = $base->iquery;
					}
				}
				$target->ifragment = $relative->ifragment;
			}
		}
		else {
			$target = clone $base;
			$target->ifragment = null;
		}
		$target->scheme_normalization();
		return $target;
	}

	// refactored. protected function parse_iri($iri) {}

	/**
	 * Remove dot segments from a path
	 *
	 * @param string $input
	 * @return string
	 */
	protected function remove_dot_segments($input) {
		$output = '';
		while (strpos($input, './') !== false || strpos($input, '/.') !== false || $input === '.' || $input === '..') {
			// A: If the input buffer begins with a prefix of "../" or "./",
			// then remove that prefix from the input buffer; otherwise,
			if (strpos($input, '../') === 0) {
				$input = substr($input, 3);
			}
			elseif (strpos($input, './') === 0) {
				$input = substr($input, 2);
			}
			// B: if the input buffer begins with a prefix of "/./" or "/.",
			// where "." is a complete path segment, then replace that prefix
			// with "/" in the input buffer; otherwise,
			elseif (strpos($input, '/./') === 0) {
				$input = substr($input, 2);
			}
			elseif ($input === '/.') {
				$input = '/';
			}
			// C: if the input buffer begins with a prefix of "/../" or "/..",
			// where ".." is a complete path segment, then replace that prefix
			// with "/" in the input buffer and remove the last segment and its
			// preceding "/" (if any) from the output buffer; otherwise,
			elseif (strpos($input, '/../') === 0) {
				$input = substr($input, 3);
				$output = substr_replace($output, '', strrpos($output, '/'));
			}
			elseif ($input === '/..') {
				$input = '/';
				$output = substr_replace($output, '', strrpos($output, '/'));
			}
			// D: if the input buffer consists only of "." or "..", then remove
			// that from the input buffer; otherwise,
			elseif ($input === '.' || $input === '..') {
				$input = '';
			}
			// E: move the first path segment in the input buffer to the end of
			// the output buffer, including the initial "/" character (if any)
			// and any subsequent characters up to, but not including, the next
			// "/" character or the end of the input buffer
			elseif (($pos = strpos($input, '/', 1)) !== false) {
				$output .= substr($input, 0, $pos);
				$input = substr_replace($input, '', 0, $pos);
			}
			else {
				$output .= $input;
				$input = '';
			}
		}
		return $output . $input;
	}

	// refactored. protected function replace_invalid_with_pct_encoding($string, $extra_chars, $iprivate = false) {}
	// :
	// refactored. protected function scheme_normalization() {}

	/**
	 * Check if the object represents a valid IRI. This needs to be done on each
	 * call as some things change depending on another part of the IRI.
	 *
	 * @return bool
	 */
	public function is_valid() {
		$isauthority = $this->iuserinfo !== null || $this->ihost !== null || $this->port !== null;
		if ($this->ipath !== '' &&
			(
				$isauthority && $this->ipath[0] !== '/' ||
				(
					$this->scheme === null &&
					!$isauthority &&
					strpos($this->ipath, ':') !== false &&
					(strpos($this->ipath, '/') === false ? true : strpos($this->ipath, ':') < strpos($this->ipath, '/'))
				)
			)
		) {
			return false;
		}

		return true;
	}

	/**
	 * Set the entire IRI. Returns true on success, false on failure (if there
	 * are any invalid characters).
	 *
	 * @param string $iri
	 * @return bool
	 */
	protected function set_iri($iri) {
		static $cache;
		if (!$cache) {
			$cache = array();
		}

		if ($iri === null) {
			return true;
		}
		if (isset($cache[$iri])) {
			list($this->scheme,
				 $this->iuserinfo,
				 $this->ihost,
				 $this->port,
				 $this->ipath,
				 $this->iquery,
				 $this->ifragment,
				 $return) = $cache[$iri];
			return $return;
		}

		$parsed = $this->parse_iri((string) $iri);

		$return = $this->set_scheme($parsed['scheme'])
			&& $this->set_authority($parsed['authority'])
			&& $this->set_path($parsed['path'])
			&& $this->set_query($parsed['query'])
			&& $this->set_fragment($parsed['fragment']);

		$cache[$iri] = array($this->scheme,
							 $this->iuserinfo,
							 $this->ihost,
							 $this->port,
							 $this->ipath,
							 $this->iquery,
							 $this->ifragment,
							 $return);
		return $return;
	}

	// refactored. protected function set_scheme($scheme) {}

	/**
	 * Set the authority. Returns true on success, false on failure (if there are
	 * any invalid characters).
	 *
	 * @param string $authority
	 * @return bool
	 */
	protected function set_authority($authority) {
		static $cache;
		if (!$cache) {
			$cache = array();
		}

		if ($authority === null) {
			$this->iuserinfo = null;
			$this->ihost = null;
			$this->port = null;
			return true;
		}
		if (isset($cache[$authority])) {
			list($this->iuserinfo,
				 $this->ihost,
				 $this->port,
				 $return) = $cache[$authority];

			return $return;
		}

		$remaining = $authority;
		if (($iuserinfo_end = strrpos($remaining, '@')) !== false) {
			$iuserinfo = substr($remaining, 0, $iuserinfo_end);
			$remaining = substr($remaining, $iuserinfo_end + 1);
		}
		else {
			$iuserinfo = null;
		}
		if (($port_start = strpos($remaining, ':', strpos($remaining, ']'))) !== false) {
			$port = substr($remaining, $port_start + 1);
			if ($port === false || $port === '') {
				$port = null;
			}
			$remaining = substr($remaining, 0, $port_start);
		}
		else {
			$port = null;
		}

		$return = $this->set_userinfo($iuserinfo) &&
				  $this->set_host($remaining) &&
				  $this->set_port($port);

		$cache[$authority] = array($this->iuserinfo,
								   $this->ihost,
								   $this->port,
								   $return);

		return $return;
	}

	// refactored. protected function set_userinfo($iuserinfo) {}

	/**
	 * Set the ihost. Returns true on success, false on failure (if there are
	 * any invalid characters).
	 *
	 * @param string $ihost
	 * @return bool
	 */
	protected function set_host($ihost) {
		if ($ihost === null) {
			$this->ihost = null;
			return true;
		}
		if (substr($ihost, 0, 1) === '[' && substr($ihost, -1) === ']') {
			if (Requests_IPv6::check_ipv6(substr($ihost, 1, -1))) {
				$this->ihost = '[' . Requests_IPv6::compress(substr($ihost, 1, -1)) . ']';
			}
			else {
				$this->ihost = null;
				return false;
			}
		}
		else {
			$ihost = $this->replace_invalid_with_pct_encoding($ihost, '!$&\'()*+,;=');

			// Lowercase, but ignore pct-encoded sections (as they should
			// remain uppercase). This must be done after the previous step
			// as that can add unescaped characters.
			$position = 0;
			$strlen = strlen($ihost);
			while (($position += strcspn($ihost, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ%', $position)) < $strlen) {
				if ($ihost[$position] === '%') {
					$position += 3;
				}
				else {
					$ihost[$position] = strtolower($ihost[$position]);
					$position++;
				}
			}

			$this->ihost = $ihost;
		}

		$this->scheme_normalization();

		return true;
	}

	/**
	 * Set the port. Returns true on success, false on failure (if there are
	 * any invalid characters).
	 *
	 * @param string $port
	 * @return bool
	 */
	protected function set_port($port) {
		if ($port === null) {
			$this->port = null;
			return true;
		}

		if (strspn($port, '0123456789') === strlen($port)) {
			$this->port = (int) $port;
			$this->scheme_normalization();
			return true;
		}

		$this->port = null;
		return false;
	}

	/**
	 * Set the ipath.
	 *
	 * @param string $ipath
	 * @return bool
	 */
	protected function set_path($ipath) {
		static $cache;
		if (!$cache) {
			$cache = array();
		}

		$ipath = (string) $ipath;

		if (isset($cache[$ipath])) {
			$this->ipath = $cache[$ipath][(int) ($this->scheme !== null)];
		}
		else {
			$valid = $this->replace_invalid_with_pct_encoding($ipath, '!$&\'()*+,;=@:/');
			$removed = $this->remove_dot_segments($valid);

			$cache[$ipath] = array($valid, $removed);
			$this->ipath = ($this->scheme !== null) ? $removed : $valid;
		}
		$this->scheme_normalization();
		return true;
	}

	/**
	 * Set the iquery.
	 *
	 * @param string $iquery
	 * @return bool
	 */
	protected function set_query($iquery) {
		if ($iquery === null) {
			$this->iquery = null;
		}
		else {
			$this->iquery = $this->replace_invalid_with_pct_encoding($iquery, '!$&\'()*+,;=:@/?', true);
			$this->scheme_normalization();
		}
		return true;
	}

	/**
	 * Set the ifragment.
	 *
	 * @param string $ifragment
	 * @return bool
	 */
	protected function set_fragment($ifragment) {
		if ($ifragment === null) {
			$this->ifragment = null;
		}
		else {
			$this->ifragment = $this->replace_invalid_with_pct_encoding($ifragment, '!$&\'()*+,;=:@/?');
			$this->scheme_normalization();
		}
		return true;
	}

	/**
	 * Convert an IRI to a URI (or parts thereof)
	 *
	 * @param string|bool IRI to convert (or false from {@see get_iri})
	 * @return string|false URI if IRI is valid, false otherwise.
	 */
	protected function to_uri($string) {
		if (!is_string($string)) {
			return false;
		}

		static $non_ascii;
		if (!$non_ascii) {
			$non_ascii = implode('', range("\x80", "\xFF"));
		}

		$position = 0;
		$strlen = strlen($string);
		while (($position += strcspn($string, $non_ascii, $position)) < $strlen) {
			$string = substr_replace($string, sprintf('%%%02X', ord($string[$position])), $position, 1);
			$position += 3;
			$strlen += 2;
		}

		return $string;
	}

	/**
	 * Get the complete IRI
	 *
	 * @return string
	 */
	protected function get_iri() {
		if (!$this->is_valid()) {
			return false;
		}

		$iri = '';
		if ($this->scheme !== null) {
			$iri .= $this->scheme . ':';
		}
		if (($iauthority = $this->get_iauthority()) !== null) {
			$iri .= '//' . $iauthority;
		}
		$iri .= $this->ipath;
		if ($this->iquery !== null) {
			$iri .= '?' . $this->iquery;
		}
		if ($this->ifragment !== null) {
			$iri .= '#' . $this->ifragment;
		}

		return $iri;
	}

	/**
	 * Get the complete URI
	 *
	 * @return string
	 */
	protected function get_uri() {
		return $this->to_uri($this->get_iri());
	}

	/**
	 * Get the complete iauthority
	 *
	 * @return string
	 */
	protected function get_iauthority() {
		if ($this->iuserinfo === null && $this->ihost === null && $this->port === null) {
			return null;
		}

		$iauthority = '';
		if ($this->iuserinfo !== null) {
			$iauthority .= $this->iuserinfo . '@';
		}
		if ($this->ihost !== null) {
			$iauthority .= $this->ihost;
		}
		if ($this->port !== null) {
			$iauthority .= ':' . $this->port;
		}
		return $iauthority;
	}

	/**
	 * Get the complete authority
	 *
	 * @return string
	 */
	protected function get_authority() {
		$iauthority = $this->get_iauthority();
		if (is_string($iauthority)) {
			return $this->to_uri($iauthority);
		}
		else {
			return $iauthority;
		}
	}
}
