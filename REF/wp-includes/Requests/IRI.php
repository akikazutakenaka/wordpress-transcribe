<?php
/**
 * IRI parser/serialiser/normaliser
 *
 * @package    Requests
 * @subpackage Utilities
 */

/**
 * IRI parser/serialiser/normaliser.
 *
 * Copyright (C) 2007-2010, Geoffrey Sneddon and Steve Minutillo.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * * Neither the name of the SimplePie Team nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Requests
 * @subpackage Utilities
 * @author     Geoffrey Sneddon
 * @author     Steve Minutillo
 * @copyright  2007-2009 Geoffrey Sneddon and Steve Minutillo
 * @license    http://www.opensource.org/licenses/bsd-license.php
 * @link       http://hg.gsnedders.com/iri/
 */
class Requests_IRI
{
	/**
	 * Scheme part of the IRI.
	 *
	 * @var string
	 */
	protected $scheme = NULL;

	/**
	 * Userinfo part of the IRI (after '://' and before '@').
	 *
	 * @var string
	 */
	protected $iuserinfo = NULL;

	/**
	 * Host part of the IRI.
	 *
	 * @var string
	 */
	protected $ihost = NULL;

	/**
	 * Port part of the IRI (after ':').
	 *
	 * @var string
	 */
	protected $port = NULL;

	/**
	 * Path part of the IRI (after first '/').
	 *
	 * @var string
	 */
	protected $ipath = '';

	/**
	 * Query part of the IRI (after '?').
	 *
	 * @var string
	 */
	protected $iquery = NULL;

	/**
	 * Fragment part of the IRI (after '#').
	 *
	 * @var string
	 */
	protected $ifragment = NULL;

	/**
	 * Normalization database.
	 *
	 * Each key is the scheme, each value is an array with each key as the IRI part and value as the default value for that part.
	 *
	 * @var array
	 */
	protected $normalization = array(
		'acap'  => array( 'port' => 674 ),
		'dict'  => array( 'port' => 2628 ),
		'file'  => array( 'ihost' => 'localhost' ),
		'http'  => array( 'port' => 80 ),
		'https' => array( 'port' => 443 )
	);

	/**
	 * Create a new IRI object, from a specified string.
	 *
	 * @param string|null $iri
	 */
	public function __construct( $iri = NULL )
	{
		$this->set_iri( $iri );
	}

/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/post.php: wp_check_post_hierarchy_for_loops( int $post_parent, int $post_ID )
 * <-......: wp-includes/post.php: wp_insert_post( array $postarr [, bool $wp_error = FALSE] )
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::get_page_templates( [WP_Post|null $post = NULL [, string $post_type = 'page']] )
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::get_post_templates()
 * <-......: wp-includes/class-wp-theme.php: WP_Theme::translate_header( string $header, string $value )
 * <-......: wp-admin/includes/theme.php: get_theme_feature_list( [bool $api = TRUE] )
 * <-......: wp-admin/includes/theme.php: themes_api( string $action [, array|object $args = array()] )
 * <-......: wp-includes/class-http.php: WP_Http::request( string $url [, string|array $args = array()] )
 * <-......: wp-includes/class-requests.php: Requests::request( string $url [, array $headers = array() [, array|null $data = array() [, string $type = self::GET [, array $options = array()]]]] )
 * <-......: wp-includes/class-requests.php: Requests::set_defaults( &string $url, &array $headers, &array|null $data, &string $type, &array $options )
 * <-......: wp-includes/Requests/Cookie/Jar.php: Requests_Cookie_Jar::register( Requests_Hooker $hooks )
 * <-......: wp-includes/Requests/Cookie/Jar.php: Requests_Cookie_Jar::before_request( string $url, &array $headers, &array $data, &string $type, &array $options )
 * @NOW 017: wp-includes/Requests/IRI.php: Requests_IRI::set_iri( string $iri )
 */
}
