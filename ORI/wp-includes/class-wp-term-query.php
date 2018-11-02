<?php

/**
 * Taxonomy API: WP_Term_Query class.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 4.6.0
 */

/**
 * Class used for querying terms.
 *
 * @since 4.6.0
 *
 * @see WP_Term_Query::__construct() for accepted arguments.
 */
class WP_Term_Query {
	// refactored. public $request;
	// :
	// refactored. public function query( $query ) {}

	/**
	 * Get terms, based on query_vars.
	 *
	 * @since 4.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return array List of terms.
	 */
	public function get_terms() {
		global $wpdb;

		$this->parse_query( $this->query_vars );
		$args = &$this->query_vars;

		// Set up meta_query so it's available to 'pre_get_terms'.
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars( $args );

		/**
		 * Fires before terms are retrieved.
		 *
		 * @since 4.6.0
		 *
		 * @param WP_Term_Query $this Current instance of WP_Term_Query.
		 */
		do_action( 'pre_get_terms', $this );

		$taxonomies = (array) $args['taxonomy'];

		// Save queries by not crawling the tree in the case of multiple taxes or a flat tax.
		$has_hierarchical_tax = false;
		if ( $taxonomies ) {
			foreach ( $taxonomies as $_tax ) {
				if ( is_taxonomy_hierarchical( $_tax ) ) {
					$has_hierarchical_tax = true;
				}
			}
		}

		if ( ! $has_hierarchical_tax ) {
			$args['hierarchical'] = false;
			$args['pad_counts'] = false;
		}

		// 'parent' overrides 'child_of'.
		if ( 0 < intval( $args['parent'] ) ) {
			$args['child_of'] = false;
		}

		if ( 'all' == $args['get'] ) {
			$args['childless'] = false;
			$args['child_of'] = 0;
			$args['hide_empty'] = 0;
			$args['hierarchical'] = false;
			$args['pad_counts'] = false;
		}

		/**
		 * Filters the terms query arguments.
		 *
		 * @since 3.1.0
		 *
		 * @param array $args       An array of get_terms() arguments.
		 * @param array $taxonomies An array of taxonomies.
		 */
		$args = apply_filters( 'get_terms_args', $args, $taxonomies );

		// Avoid the query if the queried parent/child_of term has no descendants.
		$child_of = $args['child_of'];
		$parent   = $args['parent'];

		if ( $child_of ) {
			$_parent = $child_of;
		} elseif ( $parent ) {
			$_parent = $parent;
		} else {
			$_parent = false;
		}

		if ( $_parent ) {
			$in_hierarchy = false;
			foreach ( $taxonomies as $_tax ) {
				$hierarchy = _get_term_hierarchy( $_tax );

				if ( isset( $hierarchy[ $_parent ] ) ) {
					$in_hierarchy = true;
				}
			}

			if ( ! $in_hierarchy ) {
				return array();
			}
		}

		// 'term_order' is a legal sort order only when joining the relationship table.
		$_orderby = $this->query_vars['orderby'];
		if ( 'term_order' === $_orderby && empty( $this->query_vars['object_ids'] ) ) {
			$_orderby = 'term_id';
		}
		$orderby = $this->parse_orderby( $_orderby );

		if ( $orderby ) {
			$orderby = "ORDER BY $orderby";
		}

		$order = $this->parse_order( $this->query_vars['order'] );

		if ( $taxonomies ) {
			$this->sql_clauses['where']['taxonomy'] = "tt.taxonomy IN ('" . implode( "', '", array_map( 'esc_sql', $taxonomies ) ) . "')";
		}

		$exclude      = $args['exclude'];
		$exclude_tree = $args['exclude_tree'];
		$include      = $args['include'];

		$inclusions = '';
		if ( ! empty( $include ) ) {
			$exclude = '';
			$exclude_tree = '';
			$inclusions = implode( ',', wp_parse_id_list( $include ) );
		}

		if ( ! empty( $inclusions ) ) {
			$this->sql_clauses['where']['inclusions'] = 't.term_id IN ( ' . $inclusions . ' )';
		}

		$exclusions = array();
		if ( ! empty( $exclude_tree ) ) {
			$exclude_tree = wp_parse_id_list( $exclude_tree );
			$excluded_children = $exclude_tree;
			foreach ( $exclude_tree as $extrunk ) {
				$excluded_children = array_merge(
					$excluded_children,
					(array) get_terms( reset( $taxonomies ), array(
						'child_of' => intval( $extrunk ),
						'fields' => 'ids',
						'hide_empty' => 0
					) )
				);
			}
			$exclusions = array_merge( $excluded_children, $exclusions );
		}

		if ( ! empty( $exclude ) ) {
			$exclusions = array_merge( wp_parse_id_list( $exclude ), $exclusions );
		}

		// 'childless' terms are those without an entry in the flattened term hierarchy.
		$childless = (bool) $args['childless'];
		if ( $childless ) {
			foreach ( $taxonomies as $_tax ) {
				$term_hierarchy = _get_term_hierarchy( $_tax );
				$exclusions = array_merge( array_keys( $term_hierarchy ), $exclusions );
			}
		}

		if ( ! empty( $exclusions ) ) {
			$exclusions = 't.term_id NOT IN (' . implode( ',', array_map( 'intval', $exclusions ) ) . ')';
		} else {
			$exclusions = '';
		}

		/**
		 * Filters the terms to exclude from the terms query.
		 *
		 * @since 2.3.0
		 *
		 * @param string $exclusions `NOT IN` clause of the terms query.
		 * @param array  $args       An array of terms query arguments.
		 * @param array  $taxonomies An array of taxonomies.
		 */
		$exclusions = apply_filters( 'list_terms_exclusions', $exclusions, $args, $taxonomies );

		if ( ! empty( $exclusions ) ) {
			// Must do string manipulation here for backward compatibility with filter.
			$this->sql_clauses['where']['exclusions'] = preg_replace( '/^\s*AND\s*/', '', $exclusions );
		}

		if (
			( ! empty( $args['name'] ) ) ||
			( is_string( $args['name'] ) && 0 !== strlen( $args['name'] ) )
		) {
			$names = (array) $args['name'];
			foreach ( $names as &$_name ) {
				// `sanitize_term_field()` returns slashed data.
				$_name = stripslashes( sanitize_term_field( 'name', $_name, 0, reset( $taxonomies ), 'db' ) );
			}

			$this->sql_clauses['where']['name'] = "t.name IN ('" . implode( "', '", array_map( 'esc_sql', $names ) ) . "')";
		}

		if (
			( ! empty( $args['slug'] ) ) ||
			( is_string( $args['slug'] ) && 0 !== strlen( $args['slug'] ) )
		) {
			if ( is_array( $args['slug'] ) ) {
				$slug = array_map( 'sanitize_title', $args['slug'] );
				$this->sql_clauses['where']['slug'] = "t.slug IN ('" . implode( "', '", $slug ) . "')";
			} else {
				$slug = sanitize_title( $args['slug'] );
				$this->sql_clauses['where']['slug'] = "t.slug = '$slug'";
			}
		}

		if ( ! empty( $args['term_taxonomy_id'] ) ) {
			if ( is_array( $args['term_taxonomy_id'] ) ) {
				$tt_ids = implode( ',', array_map( 'intval', $args['term_taxonomy_id'] ) );
				$this->sql_clauses['where']['term_taxonomy_id'] = "tt.term_taxonomy_id IN ({$tt_ids})";
			} else {
				$this->sql_clauses['where']['term_taxonomy_id'] = $wpdb->prepare( "tt.term_taxonomy_id = %d", $args['term_taxonomy_id'] );
			}
		}

		if ( ! empty( $args['name__like'] ) ) {
			$this->sql_clauses['where']['name__like'] = $wpdb->prepare( "t.name LIKE %s", '%' . $wpdb->esc_like( $args['name__like'] ) . '%' );
		}

		if ( ! empty( $args['description__like'] ) ) {
			$this->sql_clauses['where']['description__like'] = $wpdb->prepare( "tt.description LIKE %s", '%' . $wpdb->esc_like( $args['description__like'] ) . '%' );
		}

		if ( ! empty( $args['object_ids'] ) ) {
			$object_ids = $args['object_ids'];
			if ( ! is_array( $object_ids ) ) {
				$object_ids = array( $object_ids );
			}

			$object_ids = implode( ', ', array_map( 'intval', $object_ids ) );
			$this->sql_clauses['where']['object_ids'] = "tr.object_id IN ($object_ids)";
		}

		/*
		 * When querying for object relationships, the 'count > 0' check
		 * added by 'hide_empty' is superfluous.
		 */
		if ( ! empty( $args['object_ids'] ) ) {
			$args['hide_empty'] = false;
		}

		if ( '' !== $parent ) {
			$parent = (int) $parent;
			$this->sql_clauses['where']['parent'] = "tt.parent = '$parent'";
		}

		$hierarchical = $args['hierarchical'];
		if ( 'count' == $args['fields'] ) {
			$hierarchical = false;
		}
		if ( $args['hide_empty'] && !$hierarchical ) {
			$this->sql_clauses['where']['count'] = 'tt.count > 0';
		}

		$number = $args['number'];
		$offset = $args['offset'];

		// Don't limit the query results when we have to descend the family tree.
		if ( $number && ! $hierarchical && ! $child_of && '' === $parent ) {
			if ( $offset ) {
				$limits = 'LIMIT ' . $offset . ',' . $number;
			} else {
				$limits = 'LIMIT ' . $number;
			}
		} else {
			$limits = '';
		}


		if ( ! empty( $args['search'] ) ) {
			$this->sql_clauses['where']['search'] = $this->get_search_sql( $args['search'] );
		}

		// Meta query support.
		$join = '';
		$distinct = '';

		// Reparse meta_query query_vars, in case they were modified in a 'pre_get_terms' callback.
		$this->meta_query->parse_query_vars( $this->query_vars );
		$mq_sql = $this->meta_query->get_sql( 'term', 't', 'term_id' );
		$meta_clauses = $this->meta_query->get_clauses();

		if ( ! empty( $meta_clauses ) ) {
			$join .= $mq_sql['join'];
			$this->sql_clauses['where']['meta_query'] = preg_replace( '/^\s*AND\s*/', '', $mq_sql['where'] );
			$distinct .= "DISTINCT";

		}

		$selects = array();
		switch ( $args['fields'] ) {
			case 'all':
			case 'all_with_object_id' :
			case 'tt_ids' :
			case 'slugs' :
				$selects = array( 't.*', 'tt.*' );
				if ( 'all_with_object_id' === $args['fields'] && ! empty( $args['object_ids'] ) ) {
					$selects[] = 'tr.object_id';
				}
				break;
			case 'ids':
			case 'id=>parent':
				$selects = array( 't.term_id', 'tt.parent', 'tt.count', 'tt.taxonomy' );
				break;
			case 'names':
				$selects = array( 't.term_id', 'tt.parent', 'tt.count', 't.name', 'tt.taxonomy' );
				break;
			case 'count':
				$orderby = '';
				$order = '';
				$selects = array( 'COUNT(*)' );
				break;
			case 'id=>name':
				$selects = array( 't.term_id', 't.name', 'tt.count', 'tt.taxonomy' );
				break;
			case 'id=>slug':
				$selects = array( 't.term_id', 't.slug', 'tt.count', 'tt.taxonomy' );
				break;
		}

		$_fields = $args['fields'];

		/**
		 * Filters the fields to select in the terms query.
		 *
		 * Field lists modified using this filter will only modify the term fields returned
		 * by the function when the `$fields` parameter set to 'count' or 'all'. In all other
		 * cases, the term fields in the results array will be determined by the `$fields`
		 * parameter alone.
		 *
		 * Use of this filter can result in unpredictable behavior, and is not recommended.
		 *
		 * @since 2.8.0
		 *
		 * @param array $selects    An array of fields to select for the terms query.
		 * @param array $args       An array of term query arguments.
		 * @param array $taxonomies An array of taxonomies.
		 */
		$fields = implode( ', ', apply_filters( 'get_terms_fields', $selects, $args, $taxonomies ) );

		$join .= " INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id";

		if ( ! empty( $this->query_vars['object_ids'] ) ) {
			$join .= " INNER JOIN {$wpdb->term_relationships} AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id";
		}

		$where = implode( ' AND ', $this->sql_clauses['where'] );

		/**
		 * Filters the terms query SQL clauses.
		 *
		 * @since 3.1.0
		 *
		 * @param array $pieces     Terms query SQL clauses.
		 * @param array $taxonomies An array of taxonomies.
		 * @param array $args       An array of terms query arguments.
		 */
		$clauses = apply_filters( 'terms_clauses', compact( 'fields', 'join', 'where', 'distinct', 'orderby', 'order', 'limits' ), $taxonomies, $args );

		$fields = isset( $clauses[ 'fields' ] ) ? $clauses[ 'fields' ] : '';
		$join = isset( $clauses[ 'join' ] ) ? $clauses[ 'join' ] : '';
		$where = isset( $clauses[ 'where' ] ) ? $clauses[ 'where' ] : '';
		$distinct = isset( $clauses[ 'distinct' ] ) ? $clauses[ 'distinct' ] : '';
		$orderby = isset( $clauses[ 'orderby' ] ) ? $clauses[ 'orderby' ] : '';
		$order = isset( $clauses[ 'order' ] ) ? $clauses[ 'order' ] : '';
		$limits = isset( $clauses[ 'limits' ] ) ? $clauses[ 'limits' ] : '';

		if ( $where ) {
			$where = "WHERE $where";
		}

		$this->sql_clauses['select']  = "SELECT $distinct $fields";
		$this->sql_clauses['from']    = "FROM $wpdb->terms AS t $join";
		$this->sql_clauses['orderby'] = $orderby ? "$orderby $order" : '';
		$this->sql_clauses['limits']  = $limits;

		$this->request = "{$this->sql_clauses['select']} {$this->sql_clauses['from']} {$where} {$this->sql_clauses['orderby']} {$this->sql_clauses['limits']}";

		// $args can be anything. Only use the args defined in defaults to compute the key.
		$key = md5( serialize( wp_array_slice_assoc( $args, array_keys( $this->query_var_defaults ) ) ) . serialize( $taxonomies ) . $this->request );
		$last_changed = wp_cache_get_last_changed( 'terms' );
		$cache_key = "get_terms:$key:$last_changed";
		$cache = wp_cache_get( $cache_key, 'terms' );
		if ( false !== $cache ) {
			if ( 'all' === $_fields || 'all_with_object_id' === $_fields ) {
				$cache = $this->populate_terms( $cache );
			}

			$this->terms = $cache;
			return $this->terms;
		}

		if ( 'count' == $_fields ) {
			$count = $wpdb->get_var( $this->request );
			wp_cache_set( $cache_key, $count, 'terms' );
			return $count;
		}

		$terms = $wpdb->get_results( $this->request );
		if ( 'all' == $_fields || 'all_with_object_id' === $_fields ) {
			update_term_cache( $terms );
		}

		// Prime termmeta cache.
		if ( $args['update_term_meta_cache'] ) {
			$term_ids = wp_list_pluck( $terms, 'term_id' );
			update_termmeta_cache( $term_ids );
		}

		if ( empty( $terms ) ) {
			wp_cache_add( $cache_key, array(), 'terms', DAY_IN_SECONDS );
			return array();
		}

		if ( $child_of ) {
			foreach ( $taxonomies as $_tax ) {
				$children = _get_term_hierarchy( $_tax );
				if ( ! empty( $children ) ) {
					$terms = _get_term_children( $child_of, $terms, $_tax );
				}
			}
		}

		// Update term counts to include children.
		if ( $args['pad_counts'] && 'all' == $_fields ) {
			foreach ( $taxonomies as $_tax ) {
				_pad_term_counts( $terms, $_tax );
			}
		}

		// Make sure we show empty categories that have children.
		if ( $hierarchical && $args['hide_empty'] && is_array( $terms ) ) {
			foreach ( $terms as $k => $term ) {
				if ( ! $term->count ) {
					$children = get_term_children( $term->term_id, $term->taxonomy );
					if ( is_array( $children ) ) {
						foreach ( $children as $child_id ) {
							$child = get_term( $child_id, $term->taxonomy );
							if ( $child->count ) {
								continue 2;
							}
						}
					}

					// It really is empty.
					unset( $terms[ $k ] );
				}
			}
		}

		/*
		 * When querying for terms connected to objects, we may get
		 * duplicate results. The duplicates should be preserved if
		 * `$fields` is 'all_with_object_id', but should otherwise be
		 * removed.
		 */
		if ( ! empty( $args['object_ids'] ) && 'all_with_object_id' != $_fields ) {
			$_tt_ids = $_terms = array();
			foreach ( $terms as $term ) {
				if ( isset( $_tt_ids[ $term->term_id ] ) ) {
					continue;
				}

				$_tt_ids[ $term->term_id ] = 1;
				$_terms[] = $term;
			}

			$terms = $_terms;
		}

		$_terms = array();
		if ( 'id=>parent' == $_fields ) {
			foreach ( $terms as $term ) {
				$_terms[ $term->term_id ] = $term->parent;
			}
		} elseif ( 'ids' == $_fields ) {
			foreach ( $terms as $term ) {
				$_terms[] = (int) $term->term_id;
			}
		} elseif ( 'tt_ids' == $_fields ) {
			foreach ( $terms as $term ) {
				$_terms[] = (int) $term->term_taxonomy_id;
			}
		} elseif ( 'names' == $_fields ) {
			foreach ( $terms as $term ) {
				$_terms[] = $term->name;
			}
		} elseif ( 'slugs' == $_fields ) {
			foreach ( $terms as $term ) {
				$_terms[] = $term->slug;
			}
		} elseif ( 'id=>name' == $_fields ) {
			foreach ( $terms as $term ) {
				$_terms[ $term->term_id ] = $term->name;
			}
		} elseif ( 'id=>slug' == $_fields ) {
			foreach ( $terms as $term ) {
				$_terms[ $term->term_id ] = $term->slug;
			}
		}

		if ( ! empty( $_terms ) ) {
			$terms = $_terms;
		}

		// Hierarchical queries are not limited, so 'offset' and 'number' must be handled now.
		if ( $hierarchical && $number && is_array( $terms ) ) {
			if ( $offset >= count( $terms ) ) {
				$terms = array();
			} else {
				$terms = array_slice( $terms, $offset, $number, true );
			}
		}

		wp_cache_add( $cache_key, $terms, 'terms', DAY_IN_SECONDS );

		if ( 'all' === $_fields || 'all_with_object_id' === $_fields ) {
			$terms = $this->populate_terms( $terms );
		}

		$this->terms = $terms;
		return $this->terms;
	}

	// refactored. protected function parse_orderby( $orderby_raw ) {}
	// :
	// refactored. protected function parse_order( $order ) {}

	/**
	 * Used internally to generate a SQL string related to the 'search' parameter.
	 *
	 * @since 4.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $string
	 * @return string
	 */
	protected function get_search_sql( $string ) {
		global $wpdb;

		$like = '%' . $wpdb->esc_like( $string ) . '%';

		return $wpdb->prepare( '((t.name LIKE %s) OR (t.slug LIKE %s))', $like, $like );
	}

	/**
	 * Creates an array of term objects from an array of term IDs.
	 *
	 * Also discards invalid term objects.
	 *
	 * @since 4.9.8
	 *
	 * @param array $term_ids Term IDs.
	 * @return array
	 */
	protected function populate_terms( $term_ids ) {
		$terms = array();

		if ( ! is_array( $term_ids ) ) {
			return $terms;
		}

		foreach ( $term_ids as $key => $term_id ) {
			$term = get_term( $term_id );
			if ( $term instanceof WP_Term ) {
				$terms[ $key ] = $term;
			}
		}

		return $terms;
	}
}
