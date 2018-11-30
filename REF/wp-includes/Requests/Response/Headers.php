<?php
/**
 * Case-insensitive dictionary, suitable for HTTP headers
 *
 * @package Requests
 */

/**
 * Case-insensitive dictionary, suitable for HTTP headers.
 *
 * @package Requests
 */
class Requests_Response_Headers extends Requests_Utility_CaseInsensitiveDictionary
{
	/**
	 * Get all values for a given header.
	 *
	 * @param  string $key
	 * @return array  Header values.
	 */
	public function getValues( $key )
	{
		$key = strtolower( $key );

		return ! isset( $this->data[ $key ] )
			? NULL
			: $this->data[ $key ];
	}
}
