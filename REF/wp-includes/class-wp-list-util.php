<?php
/**
 * WordPress List utility class
 *
 * @package WordPress
 * @since   4.7.0
 */

/**
 * List utility.
 *
 * Utility class to handle operations on an array of objects.
 *
 * @since 4.7.0
 */
class WP_List_Util
{
	/**
	 * The input array.
	 *
	 * @since 4.7.0
	 *
	 * @var array
	 */
	private $input = array();

	/**
	 * The output array.
	 *
	 * @since 4.7.0
	 *
	 * @var array
	 */
	private $output = array();

	/**
	 * Temporary arguments for sorting.
	 *
	 * @since 4.7.0
	 *
	 * @var array
	 */
	private $orderby = array();

	/**
	 * Constructor.
	 *
	 * Sets the input array.
	 *
	 * @since 4.7.0
	 *
	 * @param array $input Array to perform operations on.
	 */
	public function __construct( $input )
	{
		$this->output = $this->input = $input;
	}

/**
 * <- wp-blog-header.php
 * <- wp-load.php
 * <- wp-settings.php
 * <- wp-includes/default-filters.php
 * <- wp-includes/post.php
 * <- wp-includes/post.php
 * <- wp-includes/class-wp-post.php
 * <- wp-includes/class-wp-post.php
 * <- wp-includes/category-template.php
 * <- wp-includes/taxonomy.php
 * <- wp-includes/taxonomy.php
 * <- wp-includes/class-wp-term-query.php
 * @NOW 013: wp-includes/class-wp-list-util.php
 */
}
