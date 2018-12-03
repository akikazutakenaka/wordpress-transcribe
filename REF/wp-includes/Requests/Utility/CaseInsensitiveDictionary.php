<?php
/**
 * Case-insensitive dictionary, suitable for HTTP headers
 *
 * @package    Requests
 * @subpackage Utilities
 */

/**
 * Case-insensitive dictionary, suitable for HTTP headers.
 *
 * @package    Requests
 * @subpackage Utilities
 */
class Requests_Utility_CaseInsensitiveDictionary implements ArrayAccess, IteratorAggregate
{
	/**
	 * Actual item data.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Creates a case insensitive dictionary.
	 *
	 * @param array $data Dictionary/map to convert to case-insensitive.
	 */
	public function __construct( array $data = array() )
	{
		foreach ( $data as $key => $value ) {
			$this->offsetSet( $key, $value );
		}
	}

	/**
	 * Set the given item.
	 *
	 * @throws Requests_Exception On attempting to use dictionary as list (`invalidset`)
	 *
	 * @param string $key   Item name.
	 * @param string $value Item value.
	 */
	public function offsetSet( $key, $value )
	{
		if ( $key === NULL ) {
			throw new Requests_Exception( 'Object is a dictionary, not a list', 'invalidset' );
		}

		$key = strtolower( $key );
		$this->data[ $key ] = $value;
	}

	/**
	 * Get the headers as an array.
	 *
	 * @return array Header data.
	 */
	public function getAll()
	{
		return $this->data;
	}
}
