<?php
/**
 * Taxonomy API: WP_Term class
 *
 * @package    WordPress
 * @subpackage Taxonomy
 * @since      4.4.0
 */

/**
 * Core class used to implement the WP_Term object.
 *
 * @since 4.4.0
 */
final class WP_Term
{
	/**
	 * Term ID.
	 *
	 * @since 4.4.0
	 *
	 * @var int
	 */
	public $term_id;

	/**
	 * The term's name.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * The term's slug.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * The term's term_group.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $term_group = '';

	/**
	 * Term Taxonomy ID.
	 *
	 * @since 4.4.0
	 *
	 * @var int
	 */
	public $term_taxonomy_id = 0;

	/**
	 * The term's taxonomy name.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $taxonomy = '';

	/**
	 * The term's description.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * ID of a term's parent term.
	 *
	 * @since 4.4.0
	 *
	 * @var int
	 */
	public $parent = 0;

	/**
	 * Cached object count for this term.
	 *
	 * @since 4.4.0
	 *
	 * @var int
	 */
	public $count = 0;

	/**
	 * Stores the term object's sanitization level.
	 *
	 * Does not corespond to a database field.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	public $filter = 'raw';

	/**
	 * Constructor.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_Term|object $term Term object.
	 */
	public function __construct( $term )
	{
		foreach ( get_object_vars( $term ) as $key => $value ) {
			$this->$key = $value;
		}
	}
}
