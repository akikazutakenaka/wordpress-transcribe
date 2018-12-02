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
	// :
	// refactored. protected function set_fragment($ifragment) {}

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

	// refactored. protected function get_iri() {}

	/**
	 * Get the complete URI
	 *
	 * @return string
	 */
	protected function get_uri() {
		return $this->to_uri($this->get_iri());
	}

	// refactored. protected function get_iauthority() {}

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
