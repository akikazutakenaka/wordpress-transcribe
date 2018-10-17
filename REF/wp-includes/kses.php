<?php
/**
 * kses 0.2.2 - HTML/XHTML filter that only allows some elements and attributes.
 * Copyright (C) 2002, 2003, 2005 Ulf Harnhammar
 *
 * This program is free software and open source software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301, USA
 * http://www.gnu.org/license/gpl.html
 *
 * Added wp_ prefix to avoid conflicts with existing kses users.
 *
 * @version    0.2.2
 * @copyright  (C) 2002, 2003, 2005
 * @author     Ulf Harnhammar <http://advogato.org/person/metaur/>
 * @package    External
 * @subpackage KSES
 */

/**
 * You can override this in a plugin.
 *
 * The {@see 'wp_kses_allwed_html'} filter is more powerful and supplies context.
 *
 * `CUSTOM_TAGS` is not recommended and should be considered deprecated.
 *
 * @see   wp_kses_allowed_html()
 * @since 1.2.0
 */
if ( ! defined( 'CUSTOM_TAGS' ) ) {
	define( 'CUSTOM_TAGS', FALSE );
}

// Ensure that these variables are added to the global namespace (e.g. if using namespaces / autoload in the current PHP environment).
global $allowedposttags, $allowedtags, $allowedentitynames;

if ( ! CUSTOM_TAGS ) {
	/**
	 * Kses global for default allowable HTML tags.
	 *
	 * Can be override by using CUSTOM_TAGS constant.
	 *
	 * @global array $allowedposttags
	 * @since  2.0.0
	 */
	$allowedposttags = array(
		'address'    => array(),
		'a'          => array(
			'href'   => TRUE,
			'rel'    => TRUE,
			'rev'    => TRUE,
			'name'   => TRUE,
			'target' => TRUE
		),
		'abbr'       => array(),
		'acronym'    => array(),
		'area'       => array(
			'alt'    => TRUE,
			'coords' => TRUE,
			'href'   => TRUE,
			'nohref' => TRUE,
			'shape'  => TRUE,
			'target' => TRUE
		),
		'article'    => array(
			'align'    => TRUE,
			'dir'      => TRUE,
			'lang'     => TRUE,
			'xml:lang' => TRUE
		),
		'aside'      => array(
			'align'    => TRUE,
			'dir'      => TRUE,
			'lang'     => TRUE,
			'xml:lang' => TRUE
		),
		'audio'      => array(
			'autoplay' => TRUE,
			'controls' => TRUE,
			'loop'     => TRUE,
			'muted'    => TRUE,
			'preload'  => TRUE,
			'src'      => TRUE
		),
		'b'          => array(),
		'bdo'        => array( 'dir' => TRUE ),
		'big'        => array(),
		'blockquote' => array(
			'cite'     => TRUE,
			'lang'     => TRUE,
			'xml:lang' => TRUE,
		),
		'br'         => array(),
		'button'     => array(
			'disabled' => TRUE,
			'name'     => TRUE,
			'type'     => TRUE,
			'value'    => TRUE
		),
		'caption'    => array( 'align' => TRUE ),
		'cite'       => array(
			'dir'  => TRUE,
			'lang' => TRUE
		),
		'code'       => array(),
		'col'        => array(
			'align'   => TRUE,
			'char'    => TRUE,
			'charoff' => TRUE,
			'span'    => TRUE,
			'dir'     => TRUE,
			'valign'  => TRUE,
			'width'   => TRUE
		),
		'colgroup'   => array(
			'align'   => TRUE,
			'char'    => TRUE,
			'charoff' => TRUE,
			'span'    => TRUE,
			'valign'  => TRUE,
			'width'   => TRUE
		),
		'del'        => array( 'datetime' => TRUE ),
		'dd'         => array(),
		'dfn'        => array(),
		'details'    => array(
			'align'    => TRUE,
			'dir'      => TRUE,
			'lang'     => TRUE,
			'open'     => TRUE,
			'xml:lang' => TRUE
		),
		'div'        => array(
			'align'    => TRUE,
			'dir'      => TRUE,
			'lang'     => TRUE,
			'xml:lang' => TRUE
		),
		'dl'         => array(),
		'dt'         => array(),
		'em'         => array(),
		'fieldset'   => array(),
		'figure'     => array(
			'align'    => TRUE,
			'dir'      => TRUE,
			'lang'     => TRUE,
			'xml:lang' => TRUE
		),
		'figcaption' => array(
			'align'    => TRUE,
			'dir'      => TRUE,
			'lang'     => TRUE,
			'xml:lang' => TRUE
		),
		'font'       => array(
			'color' => TRUE,
			'face'  => TRUE,
			'size'  => TRUE
		),
		'footer'     => array(
			'align'    => TRUE,
			'dir'      => TRUE,
			'lang'     => TRUE,
			'xml:lang' => TRUE
		),
		'form'       => array(
			'action'         => TRUE,
			'accept'         => TRUE,
			'accept-charset' => TRUE,
			'enctype'        => TRUE,
			'method'         => TRUE,
			'name'           => TRUE,
			'target'         => TRUE
		),
		'h1'         => array( 'align' => TRUE ),
		'h2'         => array( 'align' => TRUE ),
		'h3'         => array( 'align' => TRUE ),
		'h4'         => array( 'align' => TRUE ),
		'h5'         => array( 'align' => TRUE ),
		'h6'         => array( 'align' => TRUE ),
		'header'     => array(
			'align'    => TRUE,
			'dir'      => TRUE,
			'lang'     => TRUE,
			'xml:lang' => TRUE
		),
		'hgroup'     => array(
			'align'    => TRUE,
			'dir'      => TRUE,
			'lang'     => TRUE,
			'xml:lang' => TRUE
		),
		'hr'         => array(
			'align'   => TRUE,
			'noshade' => TRUE,
			'size'    => TRUE,
			'width'   => TRUE
		),
		'i'          => array(),
		'img'        => array(
			'alt'      => TRUE,
			'align'    => TRUE,
			'border'   => TRUE,
			'height'   => TRUE,
			'hspace'   => TRUE,
			'longdesc' => TRUE,
			'vpsace'   => TRUE,
			'src'      => TRUE,
			'usemap'   => TRUE,
			'width'    => TRUE
		),
		'ins'        => array(
			'datetime' => TRUE,
			'cite'     => TRUE
		),
		'kbd'        => array(),
		'label'      => array( 'for' => TRUE ),
		'legend'     => array( 'align' => TRUE ),
		'li'         => array(
			'align' => TRUE,
			'value' => TRUE
		),
		'map'        => array( 'name' => TRUE ),
		'mark'       => array(),
		'menu'       => array( 'type' => TRUE ),
		'nav'        => array(
			'align'    => TRUE,
			'dir'      => TRUE,
			'lang'     => TRUE,
			'xml:lang' => TRUE
		),
		'p'          => array(
			'align'    => TRUE,
			'dir'      => TRUE,
			'lang'     => TRUE,
			'xml:lang' => TRUE
		),
		'pre'        => array( 'width' => TRUE ),
		'q'          => array( 'cite' => TRUE ),
		's'          => array(),
		'samp'       => array(),
		'span'       => array(
			'dir'      => TRUE,
			'align'    => TRUE,
			'lang'     => TRUE,
			'xml:lang' => TRUE
		),
		'section'    => array(
			'align'    => TRUE,
			'dir'      => TRUE,
			'lang'     => TRUE,
			'xml:lang' => TRUE
		),
		'small'      => array(),
		'strike'     => array(),
		'strong'     => array(),
		'sub'        => array(),
		'summary'    => array(
			'align'    => TRUE,
			'dir'      => TRUE,
			'lang'     => TRUE,
			'xml:lang' => TRUE
		),
		'sup'        => array(),
		'table'      => array(
			'align'       => TRUE,
			'bgcolor'     => TRUE,
			'border'      => TRUE,
			'cellpadding' => TRUE,
			'cellspacing' => TRUE,
			'dir'         => TRUE,
			'rules'       => TRUE,
			'summary'     => TRUE,
			'width'       => TRUE
		),
		'tbody'      => array(
			'align'   => TRUE,
			'char'    => TRUE,
			'charoff' => TRUE,
			'valign'  => TRUE
		),
		'td'         => array(
			'abbr'    => TRUE,
			'align'   => TRUE,
			'axis'    => TRUE,
			'bgcolor' => TRUE,
			'char'    => TRUE,
			'charoff' => TRUE,
			'colspan' => TRUE,
			'dir'     => TRUE,
			'headers' => TRUE,
			'height'  => TRUE,
			'nowrap'  => TRUE,
			'rowspan' => TRUE,
			'scope'   => TRUE,
			'valign'  => TRUE,
			'width'   => TRUE
		),
		'textarea'   => array(
			'cols'     => TRUE,
			'rows'     => TRUE,
			'disabled' => TRUE,
			'name'     => TRUE,
			'readonly' => TRUE
		),
		'tfoot'      => array(
			'align'   => TRUE,
			'char'    => TRUE,
			'charoff' => TRUE,
			'valign'  => TRUE
		),
		'th'         => array(
			'abbr'    => TRUE,
			'align'   => TRUE,
			'axis'    => TRUE,
			'bgcolor' => TRUE,
			'char'    => TRUE,
			'charoff' => TRUE,
			'colspan' => TRUE,
			'headers' => TRUE,
			'height'  => TRUE,
			'nowrap'  => TRUE,
			'rowspan' => TRUE,
			'scope'   => TRUE,
			'valign'  => TRUE,
			'width'   => TRUE
		),
		'thead'      => array(
			'align'   => TRUE,
			'char'    => TRUE,
			'charoff' => TRUE,
			'valign'  => TRUE
		),
		'title'      => array(),
		'tr'         => array(
			'align'   => TRUE,
			'bgcolor' => TRUE,
			'char'    => TRUE,
			'charoff' => TRUE,
			'valign'  => TRUE
		),
		'track'      => array(
			'default' => TRUE,
			'kind'    => TRUE,
			'label'   => TRUE,
			'src'     => TRUE,
			'srclang' => TRUE
		),
		'tt'         => array(),
		'u'          => array(),
		'ul'         => array( 'type' => TRUE ),
		'ol'         => array(
			'start'    => TRUE,
			'type'     => TRUE,
			'reversed' => TRUE
		),
		'var'        => array(),
		'video'      => array(
			'autoplay' => TRUE,
			'controls' => TRUE,
			'height'   => TRUE,
			'loop'     => TRUE,
			'muted'    => TRUE,
			'poster'   => TRUE,
			'preload'  => TRUE,
			'src'      => TRUE,
			'width'    => TRUE
		)
	);

	/**
	 * Kses allowed HTML elements.
	 *
	 * @global array $allowedtags
	 * @since  1.0.0
	 */
	$allowedtags = array(
		'a'          => array(
			'href'  => TRUE,
			'title' => TRUE
		),
		'abbr'       => array( 'title' => TRUE ),
		'acronym'    => array( 'title' => TRUE ),
		'b'          => array(),
		'blockquote' => array( 'cite' => TRUE ),
		'cite'       => array(),
		'code'       => array(),
		'del'        => array( 'datetime' => TRUE ),
		'em'         => array(),
		'i'          => array(),
		'q'          => array( 'cite' => TRUE ),
		's'          => array(),
		'strike'     => array(),
		'strong'     => array()
	);
	$allowedentitynames = array( 'nbsp', 'iexcl', 'cent', 'pound', 'curren', 'yen', 'brvbar', 'sect', 'uml', 'copy', 'ordf', 'laquo', 'not', 'shy', 'reg', 'macr', 'deg', 'plusmn', 'acute', 'micro', 'para', 'middot', 'cedil', 'ordm', 'raquo', 'iquest', 'Agrave', 'Aacute', 'Acirc', 'Atilde', 'Auml', 'Aring', 'AElig', 'Ccedil', 'Egrave', 'Eacute', 'Ecirc', 'Euml', 'Igrave', 'Iacute', 'Icirc', 'Iuml', 'ETH', 'Ntilde', 'Ograve', 'Oacute', 'Ocirc', 'Otilde', 'Ouml', 'times', 'Oslash', 'Ugrave', 'Uacute', 'Ucirc', 'Uuml', 'Yacute', 'THORN', 'szlig', 'agrave', 'aacute', 'acirc', 'atilde', 'auml', 'aring', 'aelig', 'ccedil', 'egrave', 'eacute', 'ecirc', 'euml', 'igrave', 'iacute', 'icirc', 'iuml', 'eth', 'ntilde', 'ograve', 'oacute', 'ocirc', 'otilde', 'ouml', 'divide', 'oslash', 'ugrave', 'uacute', 'ucirc', 'uuml', 'yacute', 'thorn', 'yuml', 'quot', 'amp', 'lt', 'gt', 'apos', 'OElig', 'oelig', 'Scaron', 'scaron', 'Yuml', 'circ', 'tilde', 'ensp', 'emsp', 'thinsp', 'zwnj', 'zwj', 'lrm', 'rlm', 'ndash', 'mdash', 'lsquo', 'rsquo', 'sbquo', 'ldquo', 'rdquo', 'bdquo', 'dagger', 'Dagger', 'permil', 'lsaquo', 'rsaquo', 'euro', 'fnof', 'Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon', 'Zeta', 'Eta', 'Theta', 'Iota', 'Kappa', 'Lambda', 'Mu', 'Nu', 'Xi', 'Omicron', 'Pi', 'Rho', 'Sigma', 'Tau', 'Upsilon', 'Phi', 'Chi', 'Psi', 'Omega', 'alpha', 'beta', 'gamma', 'delta', 'epsilon', 'zeta', 'eta', 'theta', 'iota', 'kappa', 'lambda', 'mu', 'nu', 'xi', 'omicron', 'pi', 'rho', 'sigmaf', 'sigma', 'tau', 'upsilon', 'phi', 'chi', 'psi', 'omega', 'thetasym', 'upsih', 'piv', 'bull', 'hellip', 'prime', 'Prime', 'oline', 'frasl', 'weierp', 'image', 'real', 'trade', 'alefsym', 'larr', 'uarr', 'rarr', 'darr', 'harr', 'crarr', 'lArr', 'uArr', 'rArr', 'dArr', 'hArr', 'forall', 'part', 'exist', 'empty', 'nabla', 'isin', 'notin', 'ni', 'prod', 'sum', 'minus', 'lowast', 'radic', 'prop', 'infin', 'ang', 'and', 'or', 'cap', 'cup', 'int', 'sim', 'cong', 'asymp', 'ne', 'equiv', 'le', 'ge', 'sub', 'sup', 'nsub', 'sube', 'supe', 'oplus', 'otimes', 'perp', 'sdot', 'lceil', 'rceil', 'lfloor', 'rfloor', 'lang', 'rang', 'loz', 'spades', 'clubs', 'hearts', 'diams', 'sup1', 'sup2', 'sup3', 'frac14', 'frac12', 'frac34', 'there4' );
	$allowedposttags = array_map( '_wp_add_global_attributes', $allowedposttags );
} else {
	$allowedtags = wp_kses_array_lc( $allowedtags );
// @NOW 020
}

/**
 * Goes through an array and changes the keys to all lower case.
 *
 * @since  1.0.0
 *
 * @param  array $inarray Unfiltered array.
 * @return array Fixed array with all lowercase keys.
 */
function wp_kses_array_lc( $inarray )
{
	$outarray = array();

	foreach ( ( array ) $inarray as $inkey => $inval ) {
		$outkey = strtolower( $inkey );
		$outarray[ $outkey ] = array();

		foreach ( ( array ) $inval as $inkey2 => $inval2 ) {
			$outkey2 = strtolower( $inkey2 );
			$outarray[ $outkey ][ $outkey2 ] = $inval2;
		}
	}

	return $outarray;
}

/**
 * Helper function to add global attributes to a tag in the allowed html list.
 *
 * @since  3.5.0
 * @access private
 *
 * @param  array $value An array of attributes.
 * @return array The array of attributes with global attributes added.
 */
function _wp_add_global_attributes( $value )
{
	$global_attributes = array(
		'class' => TRUE,
		'id'    => TRUE,
		'style' => TRUE,
		'title' => TRUE,
		'role'  => TRUE
	);

	if ( TRUE === $value ) {
		$value = array();
	}

	if ( is_array( $value ) ) {
		return array_merge( $value, $global_attributes );
	}

	return $value;
}
