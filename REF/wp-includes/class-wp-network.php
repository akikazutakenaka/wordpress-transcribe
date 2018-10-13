<?php
/**
 * Network API: WP_Network class
 *
 * @package    WordPress
 * @subpackage Multisite
 * @since      4.4.0
 */

/**
 * Core class used for interacting with a multisite network.
 *
 * This class is used during load to populate the `$current_site` global and setup the current network.
 *
 * This class is most useful in WordPress multi-network installations where the ability to interact with any network of sites is required.
 *
 * @since 4.4.0
 */
class WP_Network
{
	/**
	 * Network ID.
	 *
	 * @since 4.4.0
	 * @since 4.6.0 Converted from public to private to explicitly enable more intuitive access via magic methods.
	 *              As part of the access change, the type was also changed from `string` to `int`.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Domain of the network.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $domain = '';

	/**
	 * Path of the network.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $path = '';

	/**
	 * The ID of the network's main site.
	 *
	 * Named "blog" vs. "site" for legacy reasons.
	 * A main site is mapped to the network when the network is created.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	private $blog_id = '0';

	/**
	 * Domain used to set cookies for this network.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $cookie_domain = '';

	/**
	 * Name of this network.
	 *
	 * Named "site" vs. "network" for legacy reasons.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $site_name = '';

	/**
	 * Create a new WP_Network object.
	 *
	 * Will populate object properties from the object provided and assign other default properties based on that information.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_Network|object $network A network object.
	 */
	public function __construct( $network )
	{
		foreach ( get_object_vars( $network ) as $key => $value ) {
			$this->$key = $value;
		}

		$this->_set_site_name();
// @NOW 023
	}

	/**
	 * Set the site name assigned to the network if one has not been populated.
	 *
	 * @since 4.4.0
	 */
	private function _set_site_name()
	{
		if ( ! empty( $this->site_name ) ) {
			return;
		}

		$default = ucfirst( $this->site_name );
		$this->site_name = get_network_option( $this->id, 'site_name', $default );
	}
}
