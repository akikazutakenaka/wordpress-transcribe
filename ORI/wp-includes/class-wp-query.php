<?php
/**
 * Query API: WP_Query class
 *
 * @package WordPress
 * @subpackage Query
 * @since 4.7.0
 */

/**
 * The WordPress Query class.
 *
 * @link https://codex.wordpress.org/Function_Reference/WP_Query Codex page.
 *
 * @since 1.5.0
 * @since 4.5.0 Removed the `$comments_popup` property.
 */
class WP_Query {
	// refactored. public $query;
	// :
	// refactored. public function init() {}

	/**
	 * Reparse the query vars.
	 *
	 * @since 1.5.0
	 */
	public function parse_query_vars() {
		$this->parse_query();
	}

	// refactored. public function fill_query_vars($array) {}
	// :
	// refactored. public function parse_tax_query( &$q ) {}

	/**
	 * Generates SQL for the WHERE clause based on passed search terms.
	 *
	 * @since 3.7.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param array $q Query variables.
	 * @return string WHERE clause.
	 */
	protected function parse_search( &$q ) {
		global $wpdb;

		$search = '';

		// added slashes screw with quote grouping when done early, so done later
		$q['s'] = stripslashes( $q['s'] );
		if ( empty( $_GET['s'] ) && $this->is_main_query() )
			$q['s'] = urldecode( $q['s'] );
		// there are no line breaks in <input /> fields
		$q['s'] = str_replace( array( "\r", "\n" ), '', $q['s'] );
		$q['search_terms_count'] = 1;
		if ( ! empty( $q['sentence'] ) ) {
			$q['search_terms'] = array( $q['s'] );
		} else {
			if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $q['s'], $matches ) ) {
				$q['search_terms_count'] = count( $matches[0] );
				$q['search_terms'] = $this->parse_search_terms( $matches[0] );
				// if the search string has only short terms or stopwords, or is 10+ terms long, match it as sentence
				if ( empty( $q['search_terms'] ) || count( $q['search_terms'] ) > 9 )
					$q['search_terms'] = array( $q['s'] );
			} else {
				$q['search_terms'] = array( $q['s'] );
			}
		}

		$n = ! empty( $q['exact'] ) ? '' : '%';
		$searchand = '';
		$q['search_orderby_title'] = array();

		/**
		 * Filters the prefix that indicates that a search term should be excluded from results.
		 *
		 * @since 4.7.0
		 *
		 * @param string $exclusion_prefix The prefix. Default '-'. Returning
		 *                                 an empty value disables exclusions.
		 */
		$exclusion_prefix = apply_filters( 'wp_query_search_exclusion_prefix', '-' );

		foreach ( $q['search_terms'] as $term ) {
			// If there is an $exclusion_prefix, terms prefixed with it should be excluded.
			$exclude = $exclusion_prefix && ( $exclusion_prefix === substr( $term, 0, 1 ) );
			if ( $exclude ) {
				$like_op  = 'NOT LIKE';
				$andor_op = 'AND';
				$term     = substr( $term, 1 );
			} else {
				$like_op  = 'LIKE';
				$andor_op = 'OR';
			}

			if ( $n && ! $exclude ) {
				$like = '%' . $wpdb->esc_like( $term ) . '%';
				$q['search_orderby_title'][] = $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", $like );
			}

			$like = $n . $wpdb->esc_like( $term ) . $n;
			$search .= $wpdb->prepare( "{$searchand}(({$wpdb->posts}.post_title $like_op %s) $andor_op ({$wpdb->posts}.post_excerpt $like_op %s) $andor_op ({$wpdb->posts}.post_content $like_op %s))", $like, $like, $like );
			$searchand = ' AND ';
		}

		if ( ! empty( $search ) ) {
			$search = " AND ({$search}) ";
			if ( ! is_user_logged_in() ) {
				$search .= " AND ({$wpdb->posts}.post_password = '') ";
			}
		}

		return $search;
	}

	/**
	 * Check if the terms are suitable for searching.
	 *
	 * Uses an array of stopwords (terms) that are excluded from the separate
	 * term matching when searching for posts. The list of English stopwords is
	 * the approximate search engines list, and is translatable.
	 *
	 * @since 3.7.0
	 *
	 * @param array $terms Terms to check.
	 * @return array Terms that are not stopwords.
	 */
	protected function parse_search_terms( $terms ) {
		$strtolower = function_exists( 'mb_strtolower' ) ? 'mb_strtolower' : 'strtolower';
		$checked = array();

		$stopwords = $this->get_search_stopwords();

		foreach ( $terms as $term ) {
			// keep before/after spaces when term is for exact match
			if ( preg_match( '/^".+"$/', $term ) )
				$term = trim( $term, "\"'" );
			else
				$term = trim( $term, "\"' " );

			// Avoid single A-Z and single dashes.
			if ( ! $term || ( 1 === strlen( $term ) && preg_match( '/^[a-z\-]$/i', $term ) ) )
				continue;

			if ( in_array( call_user_func( $strtolower, $term ), $stopwords, true ) )
				continue;

			$checked[] = $term;
		}

		return $checked;
	}

	/**
	 * Retrieve stopwords used when parsing search terms.
	 *
	 * @since 3.7.0
	 *
	 * @return array Stopwords.
	 */
	protected function get_search_stopwords() {
		if ( isset( $this->stopwords ) )
			return $this->stopwords;

		/* translators: This is a comma-separated list of very common words that should be excluded from a search,
		 * like a, an, and the. These are usually called "stopwords". You should not simply translate these individual
		 * words into your language. Instead, look for and provide commonly accepted stopwords in your language.
		 */
		$words = explode( ',', _x( 'about,an,are,as,at,be,by,com,for,from,how,in,is,it,of,on,or,that,the,this,to,was,what,when,where,who,will,with,www',
			'Comma-separated list of search stopwords in your language' ) );

		$stopwords = array();
		foreach ( $words as $word ) {
			$word = trim( $word, "\r\n\t " );
			if ( $word )
				$stopwords[] = $word;
		}

		/**
		 * Filters stopwords used when parsing search terms.
		 *
		 * @since 3.7.0
		 *
		 * @param array $stopwords Stopwords.
		 */
		$this->stopwords = apply_filters( 'wp_search_stopwords', $stopwords );
		return $this->stopwords;
	}

	/**
	 * Generates SQL for the ORDER BY condition based on passed search terms.
	 *
	 * @since 3.7.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param array $q Query variables.
	 * @return string ORDER BY clause.
	 */
	protected function parse_search_order( &$q ) {
		global $wpdb;

		if ( $q['search_terms_count'] > 1 ) {
			$num_terms = count( $q['search_orderby_title'] );

			// If the search terms contain negative queries, don't bother ordering by sentence matches.
			$like = '';
			if ( ! preg_match( '/(?:\s|^)\-/', $q['s'] ) ) {
				$like = '%' . $wpdb->esc_like( $q['s'] ) . '%';
			}

			$search_orderby = '';

			// sentence match in 'post_title'
			if ( $like ) {
				$search_orderby .= $wpdb->prepare( "WHEN {$wpdb->posts}.post_title LIKE %s THEN 1 ", $like );
			}

			// sanity limit, sort as sentence when more than 6 terms
			// (few searches are longer than 6 terms and most titles are not)
			if ( $num_terms < 7 ) {
				// all words in title
				$search_orderby .= 'WHEN ' . implode( ' AND ', $q['search_orderby_title'] ) . ' THEN 2 ';
				// any word in title, not needed when $num_terms == 1
				if ( $num_terms > 1 )
					$search_orderby .= 'WHEN ' . implode( ' OR ', $q['search_orderby_title'] ) . ' THEN 3 ';
			}

			// Sentence match in 'post_content' and 'post_excerpt'.
			if ( $like ) {
				$search_orderby .= $wpdb->prepare( "WHEN {$wpdb->posts}.post_excerpt LIKE %s THEN 4 ", $like );
				$search_orderby .= $wpdb->prepare( "WHEN {$wpdb->posts}.post_content LIKE %s THEN 5 ", $like );
			}

			if ( $search_orderby ) {
				$search_orderby = '(CASE ' . $search_orderby . 'ELSE 6 END)';
			}
		} else {
			// single word or sentence search
			$search_orderby = reset( $q['search_orderby_title'] ) . ' DESC';
		}

		return $search_orderby;
	}

	/**
	 * Converts the given orderby alias (if allowed) to a properly-prefixed value.
	 *
	 * @since 4.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $orderby Alias for the field to order by.
	 * @return string|false Table-prefixed value to used in the ORDER clause. False otherwise.
	 */
	protected function parse_orderby( $orderby ) {
		global $wpdb;

		// Used to filter values.
		$allowed_keys = array(
			'post_name', 'post_author', 'post_date', 'post_title', 'post_modified',
			'post_parent', 'post_type', 'name', 'author', 'date', 'title', 'modified',
			'parent', 'type', 'ID', 'menu_order', 'comment_count', 'rand',
		);

		$primary_meta_key = '';
		$primary_meta_query = false;
		$meta_clauses = $this->meta_query->get_clauses();
		if ( ! empty( $meta_clauses ) ) {
			$primary_meta_query = reset( $meta_clauses );

			if ( ! empty( $primary_meta_query['key'] ) ) {
				$primary_meta_key = $primary_meta_query['key'];
				$allowed_keys[] = $primary_meta_key;
			}

			$allowed_keys[] = 'meta_value';
			$allowed_keys[] = 'meta_value_num';
			$allowed_keys   = array_merge( $allowed_keys, array_keys( $meta_clauses ) );
		}

		// If RAND() contains a seed value, sanitize and add to allowed keys.
		$rand_with_seed = false;
		if ( preg_match( '/RAND\(([0-9]+)\)/i', $orderby, $matches ) ) {
			$orderby = sprintf( 'RAND(%s)', intval( $matches[1] ) );
			$allowed_keys[] = $orderby;
			$rand_with_seed = true;
		}

		if ( ! in_array( $orderby, $allowed_keys, true ) ) {
			return false;
		}

		switch ( $orderby ) {
			case 'post_name':
			case 'post_author':
			case 'post_date':
			case 'post_title':
			case 'post_modified':
			case 'post_parent':
			case 'post_type':
			case 'ID':
			case 'menu_order':
			case 'comment_count':
				$orderby_clause = "{$wpdb->posts}.{$orderby}";
				break;
			case 'rand':
				$orderby_clause = 'RAND()';
				break;
			case $primary_meta_key:
			case 'meta_value':
				if ( ! empty( $primary_meta_query['type'] ) ) {
					$orderby_clause = "CAST({$primary_meta_query['alias']}.meta_value AS {$primary_meta_query['cast']})";
				} else {
					$orderby_clause = "{$primary_meta_query['alias']}.meta_value";
				}
				break;
			case 'meta_value_num':
				$orderby_clause = "{$primary_meta_query['alias']}.meta_value+0";
				break;
			default:
				if ( array_key_exists( $orderby, $meta_clauses ) ) {
					// $orderby corresponds to a meta_query clause.
					$meta_clause = $meta_clauses[ $orderby ];
					$orderby_clause = "CAST({$meta_clause['alias']}.meta_value AS {$meta_clause['cast']})";
				} elseif ( $rand_with_seed ) {
					$orderby_clause = $orderby;
				} else {
					// Default: order by post field.
					$orderby_clause = "{$wpdb->posts}.post_" . sanitize_key( $orderby );
				}

				break;
		}

		return $orderby_clause;
	}

	/**
	 * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
	 *
	 * @since 4.0.0
	 *
	 * @param string $order The 'order' query variable.
	 * @return string The sanitized 'order' query variable.
	 */
	protected function parse_order( $order ) {
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}

	// refactored. public function set_404() {}

	/**
	 * Retrieve query variable.
	 *
	 * @since 1.5.0
	 * @since 3.9.0 The `$default` argument was introduced.
	 *
	 *
	 * @param string $query_var Query variable key.
	 * @param mixed  $default   Optional. Value to return if the query variable is not set. Default empty.
	 * @return mixed Contents of the query variable.
	 */
	public function get( $query_var, $default = '' ) {
		if ( isset( $this->query_vars[ $query_var ] ) ) {
			return $this->query_vars[ $query_var ];
		}

		return $default;
	}

	/**
	 * Set query variable.
	 *
	 * @since 1.5.0
	 *
	 * @param string $query_var Query variable key.
	 * @param mixed  $value     Query variable value.
	 */
	public function set($query_var, $value) {
		$this->query_vars[$query_var] = $value;
	}

	/**
	 * Retrieve the posts based on query variables.
	 *
	 * There are a few filters and actions that can be used to modify the post
	 * database query.
	 *
	 * @since 1.5.0
	 *
	 * @return array List of posts.
	 */
	public function get_posts() {
		global $wpdb;

		$this->parse_query();

		/**
		 * Fires after the query variable object is created, but before the actual query is run.
		 *
		 * Note: If using conditional tags, use the method versions within the passed instance
		 * (e.g. $this->is_main_query() instead of is_main_query()). This is because the functions
		 * like is_main_query() test against the global $wp_query instance, not the passed one.
		 *
		 * @since 2.0.0
		 *
		 * @param WP_Query $this The WP_Query instance (passed by reference).
		 */
		do_action_ref_array( 'pre_get_posts', array( &$this ) );

		// Shorthand.
		$q = &$this->query_vars;

		// Fill again in case pre_get_posts unset some vars.
		$q = $this->fill_query_vars($q);

		// Parse meta query
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars( $q );

		// Set a flag if a pre_get_posts hook changed the query vars.
		$hash = md5( serialize( $this->query_vars ) );
		if ( $hash != $this->query_vars_hash ) {
			$this->query_vars_changed = true;
			$this->query_vars_hash = $hash;
		}
		unset($hash);

		// First let's clear some variables
		$distinct = '';
		$whichauthor = '';
		$whichmimetype = '';
		$where = '';
		$limits = '';
		$join = '';
		$search = '';
		$groupby = '';
		$post_status_join = false;
		$page = 1;

		if ( isset( $q['caller_get_posts'] ) ) {
			_deprecated_argument( 'WP_Query', '3.1.0',
				/* translators: 1: caller_get_posts, 2: ignore_sticky_posts */
				sprintf( __( '%1$s is deprecated. Use %2$s instead.' ),
					'<code>caller_get_posts</code>',
					'<code>ignore_sticky_posts</code>'
				)
			);

			if ( ! isset( $q['ignore_sticky_posts'] ) ) {
				$q['ignore_sticky_posts'] = $q['caller_get_posts'];
			}
		}

		if ( !isset( $q['ignore_sticky_posts'] ) )
			$q['ignore_sticky_posts'] = false;

		if ( !isset($q['suppress_filters']) )
			$q['suppress_filters'] = false;

		if ( !isset($q['cache_results']) ) {
			if ( wp_using_ext_object_cache() )
				$q['cache_results'] = false;
			else
				$q['cache_results'] = true;
		}

		if ( !isset($q['update_post_term_cache']) )
			$q['update_post_term_cache'] = true;

		if ( ! isset( $q['lazy_load_term_meta'] ) ) {
			$q['lazy_load_term_meta'] = $q['update_post_term_cache'];
		}

		if ( !isset($q['update_post_meta_cache']) )
			$q['update_post_meta_cache'] = true;

		if ( !isset($q['post_type']) ) {
			if ( $this->is_search )
				$q['post_type'] = 'any';
			else
				$q['post_type'] = '';
		}
		$post_type = $q['post_type'];
		if ( empty( $q['posts_per_page'] ) ) {
			$q['posts_per_page'] = get_option( 'posts_per_page' );
		}
		if ( isset($q['showposts']) && $q['showposts'] ) {
			$q['showposts'] = (int) $q['showposts'];
			$q['posts_per_page'] = $q['showposts'];
		}
		if ( (isset($q['posts_per_archive_page']) && $q['posts_per_archive_page'] != 0) && ($this->is_archive || $this->is_search) )
			$q['posts_per_page'] = $q['posts_per_archive_page'];
		if ( !isset($q['nopaging']) ) {
			if ( $q['posts_per_page'] == -1 ) {
				$q['nopaging'] = true;
			} else {
				$q['nopaging'] = false;
			}
		}

		if ( $this->is_feed ) {
			// This overrides posts_per_page.
			if ( ! empty( $q['posts_per_rss'] ) ) {
				$q['posts_per_page'] = $q['posts_per_rss'];
			} else {
				$q['posts_per_page'] = get_option( 'posts_per_rss' );
			}
			$q['nopaging'] = false;
		}
		$q['posts_per_page'] = (int) $q['posts_per_page'];
		if ( $q['posts_per_page'] < -1 )
			$q['posts_per_page'] = abs($q['posts_per_page']);
		elseif ( $q['posts_per_page'] == 0 )
			$q['posts_per_page'] = 1;

		if ( !isset($q['comments_per_page']) || $q['comments_per_page'] == 0 )
			$q['comments_per_page'] = get_option('comments_per_page');

		if ( $this->is_home && (empty($this->query) || $q['preview'] == 'true') && ( 'page' == get_option('show_on_front') ) && get_option('page_on_front') ) {
			$this->is_page = true;
			$this->is_home = false;
			$q['page_id'] = get_option('page_on_front');
		}

		if ( isset($q['page']) ) {
			$q['page'] = trim($q['page'], '/');
			$q['page'] = absint($q['page']);
		}

		// If true, forcibly turns off SQL_CALC_FOUND_ROWS even when limits are present.
		if ( isset($q['no_found_rows']) )
			$q['no_found_rows'] = (bool) $q['no_found_rows'];
		else
			$q['no_found_rows'] = false;

		switch ( $q['fields'] ) {
			case 'ids':
				$fields = "{$wpdb->posts}.ID";
				break;
			case 'id=>parent':
				$fields = "{$wpdb->posts}.ID, {$wpdb->posts}.post_parent";
				break;
			default:
				$fields = "{$wpdb->posts}.*";
		}

		if ( '' !== $q['menu_order'] ) {
			$where .= " AND {$wpdb->posts}.menu_order = " . $q['menu_order'];
		}
		// The "m" parameter is meant for months but accepts datetimes of varying specificity
		if ( $q['m'] ) {
			$where .= " AND YEAR({$wpdb->posts}.post_date)=" . substr($q['m'], 0, 4);
			if ( strlen($q['m']) > 5 ) {
				$where .= " AND MONTH({$wpdb->posts}.post_date)=" . substr($q['m'], 4, 2);
			}
			if ( strlen($q['m']) > 7 ) {
				$where .= " AND DAYOFMONTH({$wpdb->posts}.post_date)=" . substr($q['m'], 6, 2);
			}
			if ( strlen($q['m']) > 9 ) {
				$where .= " AND HOUR({$wpdb->posts}.post_date)=" . substr($q['m'], 8, 2);
			}
			if ( strlen($q['m']) > 11 ) {
				$where .= " AND MINUTE({$wpdb->posts}.post_date)=" . substr($q['m'], 10, 2);
			}
			if ( strlen($q['m']) > 13 ) {
				$where .= " AND SECOND({$wpdb->posts}.post_date)=" . substr($q['m'], 12, 2);
			}
		}

		// Handle the other individual date parameters
		$date_parameters = array();

		if ( '' !== $q['hour'] )
			$date_parameters['hour'] = $q['hour'];

		if ( '' !== $q['minute'] )
			$date_parameters['minute'] = $q['minute'];

		if ( '' !== $q['second'] )
			$date_parameters['second'] = $q['second'];

		if ( $q['year'] )
			$date_parameters['year'] = $q['year'];

		if ( $q['monthnum'] )
			$date_parameters['monthnum'] = $q['monthnum'];

		if ( $q['w'] )
			$date_parameters['week'] = $q['w'];

		if ( $q['day'] )
			$date_parameters['day'] = $q['day'];

		if ( $date_parameters ) {
			$date_query = new WP_Date_Query( array( $date_parameters ) );
			$where .= $date_query->get_sql();
		}
		unset( $date_parameters, $date_query );

		// Handle complex date queries
		if ( ! empty( $q['date_query'] ) ) {
			$this->date_query = new WP_Date_Query( $q['date_query'] );
			$where .= $this->date_query->get_sql();
		}


		// If we've got a post_type AND it's not "any" post_type.
		if ( !empty($q['post_type']) && 'any' != $q['post_type'] ) {
			foreach ( (array)$q['post_type'] as $_post_type ) {
				$ptype_obj = get_post_type_object($_post_type);
				if ( !$ptype_obj || !$ptype_obj->query_var || empty($q[ $ptype_obj->query_var ]) )
					continue;

				if ( ! $ptype_obj->hierarchical ) {
					// Non-hierarchical post types can directly use 'name'.
					$q['name'] = $q[ $ptype_obj->query_var ];
				} else {
					// Hierarchical post types will operate through 'pagename'.
					$q['pagename'] = $q[ $ptype_obj->query_var ];
					$q['name'] = '';
				}

				// Only one request for a slug is possible, this is why name & pagename are overwritten above.
				break;
			} //end foreach
			unset($ptype_obj);
		}

		if ( '' !== $q['title'] ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title = %s", stripslashes( $q['title'] ) );
		}

		// Parameters related to 'post_name'.
		if ( '' != $q['name'] ) {
			$q['name'] = sanitize_title_for_query( $q['name'] );
			$where .= " AND {$wpdb->posts}.post_name = '" . $q['name'] . "'";
		} elseif ( '' != $q['pagename'] ) {
			if ( isset($this->queried_object_id) ) {
				$reqpage = $this->queried_object_id;
			} else {
				if ( 'page' != $q['post_type'] ) {
					foreach ( (array)$q['post_type'] as $_post_type ) {
						$ptype_obj = get_post_type_object($_post_type);
						if ( !$ptype_obj || !$ptype_obj->hierarchical )
							continue;

						$reqpage = get_page_by_path($q['pagename'], OBJECT, $_post_type);
						if ( $reqpage )
							break;
					}
					unset($ptype_obj);
				} else {
					$reqpage = get_page_by_path($q['pagename']);
				}
				if ( !empty($reqpage) )
					$reqpage = $reqpage->ID;
				else
					$reqpage = 0;
			}

			$page_for_posts = get_option('page_for_posts');
			if  ( ('page' != get_option('show_on_front') ) || empty($page_for_posts) || ( $reqpage != $page_for_posts ) ) {
				$q['pagename'] = sanitize_title_for_query( wp_basename( $q['pagename'] ) );
				$q['name'] = $q['pagename'];
				$where .= " AND ({$wpdb->posts}.ID = '$reqpage')";
				$reqpage_obj = get_post( $reqpage );
				if ( is_object($reqpage_obj) && 'attachment' == $reqpage_obj->post_type ) {
					$this->is_attachment = true;
					$post_type = $q['post_type'] = 'attachment';
					$this->is_page = true;
					$q['attachment_id'] = $reqpage;
				}
			}
		} elseif ( '' != $q['attachment'] ) {
			$q['attachment'] = sanitize_title_for_query( wp_basename( $q['attachment'] ) );
			$q['name'] = $q['attachment'];
			$where .= " AND {$wpdb->posts}.post_name = '" . $q['attachment'] . "'";
		} elseif ( is_array( $q['post_name__in'] ) && ! empty( $q['post_name__in'] ) ) {
			$q['post_name__in'] = array_map( 'sanitize_title_for_query', $q['post_name__in'] );
			$post_name__in = "'" . implode( "','", $q['post_name__in'] ) . "'";
			$where .= " AND {$wpdb->posts}.post_name IN ($post_name__in)";
		}

		// If an attachment is requested by number, let it supersede any post number.
		if ( $q['attachment_id'] )
			$q['p'] = absint($q['attachment_id']);

		// If a post number is specified, load that post
		if ( $q['p'] ) {
			$where .= " AND {$wpdb->posts}.ID = " . $q['p'];
		} elseif ( $q['post__in'] ) {
			$post__in = implode(',', array_map( 'absint', $q['post__in'] ));
			$where .= " AND {$wpdb->posts}.ID IN ($post__in)";
		} elseif ( $q['post__not_in'] ) {
			$post__not_in = implode(',',  array_map( 'absint', $q['post__not_in'] ));
			$where .= " AND {$wpdb->posts}.ID NOT IN ($post__not_in)";
		}

		if ( is_numeric( $q['post_parent'] ) ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_parent = %d ", $q['post_parent'] );
		} elseif ( $q['post_parent__in'] ) {
			$post_parent__in = implode( ',', array_map( 'absint', $q['post_parent__in'] ) );
			$where .= " AND {$wpdb->posts}.post_parent IN ($post_parent__in)";
		} elseif ( $q['post_parent__not_in'] ) {
			$post_parent__not_in = implode( ',',  array_map( 'absint', $q['post_parent__not_in'] ) );
			$where .= " AND {$wpdb->posts}.post_parent NOT IN ($post_parent__not_in)";
		}

		if ( $q['page_id'] ) {
			if  ( ('page' != get_option('show_on_front') ) || ( $q['page_id'] != get_option('page_for_posts') ) ) {
				$q['p'] = $q['page_id'];
				$where = " AND {$wpdb->posts}.ID = " . $q['page_id'];
			}
		}

		// If a search pattern is specified, load the posts that match.
		if ( strlen( $q['s'] ) ) {
			$search = $this->parse_search( $q );
		}

		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the search SQL that is used in the WHERE clause of WP_Query.
			 *
			 * @since 3.0.0
			 *
			 * @param string   $search Search SQL for WHERE clause.
			 * @param WP_Query $this   The current WP_Query object.
			 */
			$search = apply_filters_ref_array( 'posts_search', array( $search, &$this ) );
		}

		// Taxonomies
		if ( !$this->is_singular ) {
			$this->parse_tax_query( $q );

			$clauses = $this->tax_query->get_sql( $wpdb->posts, 'ID' );

			$join .= $clauses['join'];
			$where .= $clauses['where'];
		}

		if ( $this->is_tax ) {
			if ( empty($post_type) ) {
				// Do a fully inclusive search for currently registered post types of queried taxonomies
				$post_type = array();
				$taxonomies = array_keys( $this->tax_query->queried_terms );
				foreach ( get_post_types( array( 'exclude_from_search' => false ) ) as $pt ) {
					$object_taxonomies = $pt === 'attachment' ? get_taxonomies_for_attachments() : get_object_taxonomies( $pt );
					if ( array_intersect( $taxonomies, $object_taxonomies ) )
						$post_type[] = $pt;
				}
				if ( ! $post_type )
					$post_type = 'any';
				elseif ( count( $post_type ) == 1 )
					$post_type = $post_type[0];

				$post_status_join = true;
			} elseif ( in_array('attachment', (array) $post_type) ) {
				$post_status_join = true;
			}
		}

		/*
		 * Ensure that 'taxonomy', 'term', 'term_id', 'cat', and
		 * 'category_name' vars are set for backward compatibility.
		 */
		if ( ! empty( $this->tax_query->queried_terms ) ) {

			/*
			 * Set 'taxonomy', 'term', and 'term_id' to the
			 * first taxonomy other than 'post_tag' or 'category'.
			 */
			if ( ! isset( $q['taxonomy'] ) ) {
				foreach ( $this->tax_query->queried_terms as $queried_taxonomy => $queried_items ) {
					if ( empty( $queried_items['terms'][0] ) ) {
						continue;
					}

					if ( ! in_array( $queried_taxonomy, array( 'category', 'post_tag' ) ) ) {
						$q['taxonomy'] = $queried_taxonomy;

						if ( 'slug' === $queried_items['field'] ) {
							$q['term'] = $queried_items['terms'][0];
						} else {
							$q['term_id'] = $queried_items['terms'][0];
						}

						// Take the first one we find.
						break;
					}
				}
			}

			// 'cat', 'category_name', 'tag_id'
			foreach ( $this->tax_query->queried_terms as $queried_taxonomy => $queried_items ) {
				if ( empty( $queried_items['terms'][0] ) ) {
					continue;
				}

				if ( 'category' === $queried_taxonomy ) {
					$the_cat = get_term_by( $queried_items['field'], $queried_items['terms'][0], 'category' );
					if ( $the_cat ) {
						$this->set( 'cat', $the_cat->term_id );
						$this->set( 'category_name', $the_cat->slug );
					}
					unset( $the_cat );
				}

				if ( 'post_tag' === $queried_taxonomy ) {
					$the_tag = get_term_by( $queried_items['field'], $queried_items['terms'][0], 'post_tag' );
					if ( $the_tag ) {
						$this->set( 'tag_id', $the_tag->term_id );
					}
					unset( $the_tag );
				}
			}
		}

		if ( !empty( $this->tax_query->queries ) || !empty( $this->meta_query->queries ) ) {
			$groupby = "{$wpdb->posts}.ID";
		}

		// Author/user stuff

		if ( ! empty( $q['author'] ) && $q['author'] != '0' ) {
			$q['author'] = addslashes_gpc( '' . urldecode( $q['author'] ) );
			$authors = array_unique( array_map( 'intval', preg_split( '/[,\s]+/', $q['author'] ) ) );
			foreach ( $authors as $author ) {
				$key = $author > 0 ? 'author__in' : 'author__not_in';
				$q[$key][] = abs( $author );
			}
			$q['author'] = implode( ',', $authors );
		}

		if ( ! empty( $q['author__not_in'] ) ) {
			$author__not_in = implode( ',', array_map( 'absint', array_unique( (array) $q['author__not_in'] ) ) );
			$where .= " AND {$wpdb->posts}.post_author NOT IN ($author__not_in) ";
		} elseif ( ! empty( $q['author__in'] ) ) {
			$author__in = implode( ',', array_map( 'absint', array_unique( (array) $q['author__in'] ) ) );
			$where .= " AND {$wpdb->posts}.post_author IN ($author__in) ";
		}

		// Author stuff for nice URLs

		if ( '' != $q['author_name'] ) {
			if ( strpos($q['author_name'], '/') !== false ) {
				$q['author_name'] = explode('/', $q['author_name']);
				if ( $q['author_name'][ count($q['author_name'])-1 ] ) {
					$q['author_name'] = $q['author_name'][count($q['author_name'])-1]; // no trailing slash
				} else {
					$q['author_name'] = $q['author_name'][count($q['author_name'])-2]; // there was a trailing slash
				}
			}
			$q['author_name'] = sanitize_title_for_query( $q['author_name'] );
			$q['author'] = get_user_by('slug', $q['author_name']);
			if ( $q['author'] )
				$q['author'] = $q['author']->ID;
			$whichauthor .= " AND ({$wpdb->posts}.post_author = " . absint($q['author']) . ')';
		}

		// Matching by comment count.
		if ( isset( $q['comment_count'] ) ) {
			// Numeric comment count is converted to array format.
			if ( is_numeric( $q['comment_count'] ) ) {
				$q['comment_count'] = array(
					'value' => intval( $q['comment_count'] ),
				);
			}

			if ( isset( $q['comment_count']['value'] ) ) {
				$q['comment_count'] = array_merge( array(
					'compare' => '=',
				), $q['comment_count'] );

				// Fallback for invalid compare operators is '='.
				$compare_operators = array( '=', '!=', '>', '>=', '<', '<=' );
				if ( ! in_array( $q['comment_count']['compare'], $compare_operators, true ) ) {
					$q['comment_count']['compare'] = '=';
				}

				$where .= $wpdb->prepare( " AND {$wpdb->posts}.comment_count {$q['comment_count']['compare']} %d", $q['comment_count']['value'] );
			}
		}

		// MIME-Type stuff for attachment browsing

		if ( isset( $q['post_mime_type'] ) && '' != $q['post_mime_type'] ) {
			$whichmimetype = wp_post_mime_type_where( $q['post_mime_type'], $wpdb->posts );
		}
		$where .= $search . $whichauthor . $whichmimetype;

		if ( ! empty( $this->meta_query->queries ) ) {
			$clauses = $this->meta_query->get_sql( 'post', $wpdb->posts, 'ID', $this );
			$join   .= $clauses['join'];
			$where  .= $clauses['where'];
		}

		$rand = ( isset( $q['orderby'] ) && 'rand' === $q['orderby'] );
		if ( ! isset( $q['order'] ) ) {
			$q['order'] = $rand ? '' : 'DESC';
		} else {
			$q['order'] = $rand ? '' : $this->parse_order( $q['order'] );
		}

		// Order by.
		if ( empty( $q['orderby'] ) ) {
			/*
			 * Boolean false or empty array blanks out ORDER BY,
			 * while leaving the value unset or otherwise empty sets the default.
			 */
			if ( isset( $q['orderby'] ) && ( is_array( $q['orderby'] ) || false === $q['orderby'] ) ) {
				$orderby = '';
			} else {
				$orderby = "{$wpdb->posts}.post_date " . $q['order'];
			}
		} elseif ( 'none' == $q['orderby'] ) {
			$orderby = '';
		} elseif ( $q['orderby'] == 'post__in' && ! empty( $post__in ) ) {
			$orderby = "FIELD( {$wpdb->posts}.ID, $post__in )";
		} elseif ( $q['orderby'] == 'post_parent__in' && ! empty( $post_parent__in ) ) {
			$orderby = "FIELD( {$wpdb->posts}.post_parent, $post_parent__in )";
		} elseif ( $q['orderby'] == 'post_name__in' && ! empty( $post_name__in ) ) {
			$orderby = "FIELD( {$wpdb->posts}.post_name, $post_name__in )";
		} else {
			$orderby_array = array();
			if ( is_array( $q['orderby'] ) ) {
				foreach ( $q['orderby'] as $_orderby => $order ) {
					$orderby = addslashes_gpc( urldecode( $_orderby ) );
					$parsed  = $this->parse_orderby( $orderby );

					if ( ! $parsed ) {
						continue;
					}

					$orderby_array[] = $parsed . ' ' . $this->parse_order( $order );
				}
				$orderby = implode( ', ', $orderby_array );

			} else {
				$q['orderby'] = urldecode( $q['orderby'] );
				$q['orderby'] = addslashes_gpc( $q['orderby'] );

				foreach ( explode( ' ', $q['orderby'] ) as $i => $orderby ) {
					$parsed = $this->parse_orderby( $orderby );
					// Only allow certain values for safety.
					if ( ! $parsed ) {
						continue;
					}

					$orderby_array[] = $parsed;
				}
				$orderby = implode( ' ' . $q['order'] . ', ', $orderby_array );

				if ( empty( $orderby ) ) {
					$orderby = "{$wpdb->posts}.post_date " . $q['order'];
				} elseif ( ! empty( $q['order'] ) ) {
					$orderby .= " {$q['order']}";
				}
			}
		}

		// Order search results by relevance only when another "orderby" is not specified in the query.
		if ( ! empty( $q['s'] ) ) {
			$search_orderby = '';
			if ( ! empty( $q['search_orderby_title'] ) && ( empty( $q['orderby'] ) && ! $this->is_feed ) || ( isset( $q['orderby'] ) && 'relevance' === $q['orderby'] ) )
				$search_orderby = $this->parse_search_order( $q );

			if ( ! $q['suppress_filters'] ) {
				/**
				 * Filters the ORDER BY used when ordering search results.
				 *
				 * @since 3.7.0
				 *
				 * @param string   $search_orderby The ORDER BY clause.
				 * @param WP_Query $this           The current WP_Query instance.
				 */
				$search_orderby = apply_filters( 'posts_search_orderby', $search_orderby, $this );
			}

			if ( $search_orderby )
				$orderby = $orderby ? $search_orderby . ', ' . $orderby : $search_orderby;
		}

		if ( is_array( $post_type ) && count( $post_type ) > 1 ) {
			$post_type_cap = 'multiple_post_type';
		} else {
			if ( is_array( $post_type ) )
				$post_type = reset( $post_type );
			$post_type_object = get_post_type_object( $post_type );
			if ( empty( $post_type_object ) )
				$post_type_cap = $post_type;
		}

		if ( isset( $q['post_password'] ) ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_password = %s", $q['post_password'] );
			if ( empty( $q['perm'] ) ) {
				$q['perm'] = 'readable';
			}
		} elseif ( isset( $q['has_password'] ) ) {
			$where .= sprintf( " AND {$wpdb->posts}.post_password %s ''", $q['has_password'] ? '!=' : '=' );
		}

		if ( ! empty( $q['comment_status'] ) ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.comment_status = %s ", $q['comment_status'] );
		}

		if ( ! empty( $q['ping_status'] ) )  {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.ping_status = %s ", $q['ping_status'] );
		}

		if ( 'any' == $post_type ) {
			$in_search_post_types = get_post_types( array('exclude_from_search' => false) );
			if ( empty( $in_search_post_types ) ) {
				$where .= ' AND 1=0 ';
			} else {
				$where .= " AND {$wpdb->posts}.post_type IN ('" . join( "', '", array_map( 'esc_sql', $in_search_post_types ) ) . "')";
			}
		} elseif ( !empty( $post_type ) && is_array( $post_type ) ) {
			$where .= " AND {$wpdb->posts}.post_type IN ('" . join("', '", esc_sql( $post_type ) ) . "')";
		} elseif ( ! empty( $post_type ) ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_type = %s", $post_type );
			$post_type_object = get_post_type_object ( $post_type );
		} elseif ( $this->is_attachment ) {
			$where .= " AND {$wpdb->posts}.post_type = 'attachment'";
			$post_type_object = get_post_type_object ( 'attachment' );
		} elseif ( $this->is_page ) {
			$where .= " AND {$wpdb->posts}.post_type = 'page'";
			$post_type_object = get_post_type_object ( 'page' );
		} else {
			$where .= " AND {$wpdb->posts}.post_type = 'post'";
			$post_type_object = get_post_type_object ( 'post' );
		}

		$edit_cap = 'edit_post';
		$read_cap = 'read_post';

		if ( ! empty( $post_type_object ) ) {
			$edit_others_cap = $post_type_object->cap->edit_others_posts;
			$read_private_cap = $post_type_object->cap->read_private_posts;
		} else {
			$edit_others_cap = 'edit_others_' . $post_type_cap . 's';
			$read_private_cap = 'read_private_' . $post_type_cap . 's';
		}

		$user_id = get_current_user_id();

		$q_status = array();
		if ( ! empty( $q['post_status'] ) ) {
			$statuswheres = array();
			$q_status = $q['post_status'];
			if ( ! is_array( $q_status ) )
				$q_status = explode(',', $q_status);
			$r_status = array();
			$p_status = array();
			$e_status = array();
			if ( in_array( 'any', $q_status ) ) {
				foreach ( get_post_stati( array( 'exclude_from_search' => true ) ) as $status ) {
					if ( ! in_array( $status, $q_status ) ) {
						$e_status[] = "{$wpdb->posts}.post_status <> '$status'";
					}
				}
			} else {
				foreach ( get_post_stati() as $status ) {
					if ( in_array( $status, $q_status ) ) {
						if ( 'private' == $status ) {
							$p_status[] = "{$wpdb->posts}.post_status = '$status'";
						} else {
							$r_status[] = "{$wpdb->posts}.post_status = '$status'";
						}
					}
				}
			}

			if ( empty($q['perm'] ) || 'readable' != $q['perm'] ) {
				$r_status = array_merge($r_status, $p_status);
				unset($p_status);
			}

			if ( !empty($e_status) ) {
				$statuswheres[] = "(" . join( ' AND ', $e_status ) . ")";
			}
			if ( !empty($r_status) ) {
				if ( !empty($q['perm'] ) && 'editable' == $q['perm'] && !current_user_can($edit_others_cap) ) {
					$statuswheres[] = "({$wpdb->posts}.post_author = $user_id " . "AND (" . join( ' OR ', $r_status ) . "))";
				} else {
					$statuswheres[] = "(" . join( ' OR ', $r_status ) . ")";
				}
			}
			if ( !empty($p_status) ) {
				if ( !empty($q['perm'] ) && 'readable' == $q['perm'] && !current_user_can($read_private_cap) ) {
					$statuswheres[] = "({$wpdb->posts}.post_author = $user_id " . "AND (" . join( ' OR ', $p_status ) . "))";
				} else {
					$statuswheres[] = "(" . join( ' OR ', $p_status ) . ")";
				}
			}
			if ( $post_status_join ) {
				$join .= " LEFT JOIN {$wpdb->posts} AS p2 ON ({$wpdb->posts}.post_parent = p2.ID) ";
				foreach ( $statuswheres as $index => $statuswhere ) {
					$statuswheres[$index] = "($statuswhere OR ({$wpdb->posts}.post_status = 'inherit' AND " . str_replace( $wpdb->posts, 'p2', $statuswhere ) . "))";
				}
			}
			$where_status = implode( ' OR ', $statuswheres );
			if ( ! empty( $where_status ) ) {
				$where .= " AND ($where_status)";
			}
		} elseif ( !$this->is_singular ) {
			$where .= " AND ({$wpdb->posts}.post_status = 'publish'";

			// Add public states.
			$public_states = get_post_stati( array('public' => true) );
			foreach ( (array) $public_states as $state ) {
				if ( 'publish' == $state ) // Publish is hard-coded above.
					continue;
				$where .= " OR {$wpdb->posts}.post_status = '$state'";
			}

			if ( $this->is_admin ) {
				// Add protected states that should show in the admin all list.
				$admin_all_states = get_post_stati( array('protected' => true, 'show_in_admin_all_list' => true) );
				foreach ( (array) $admin_all_states as $state ) {
					$where .= " OR {$wpdb->posts}.post_status = '$state'";
				}
			}

			if ( is_user_logged_in() ) {
				// Add private states that are limited to viewing by the author of a post or someone who has caps to read private states.
				$private_states = get_post_stati( array('private' => true) );
				foreach ( (array) $private_states as $state ) {
					$where .= current_user_can( $read_private_cap ) ? " OR {$wpdb->posts}.post_status = '$state'" : " OR {$wpdb->posts}.post_author = $user_id AND {$wpdb->posts}.post_status = '$state'";
				}
			}

			$where .= ')';
		}

		/*
		 * Apply filters on where and join prior to paging so that any
		 * manipulations to them are reflected in the paging by day queries.
		 */
		if ( !$q['suppress_filters'] ) {
			/**
			 * Filters the WHERE clause of the query.
			 *
			 * @since 1.5.0
			 *
			 * @param string   $where The WHERE clause of the query.
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			$where = apply_filters_ref_array( 'posts_where', array( $where, &$this ) );

			/**
			 * Filters the JOIN clause of the query.
			 *
			 * @since 1.5.0
			 *
			 * @param string   $join  The JOIN clause of the query.
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			$join = apply_filters_ref_array( 'posts_join', array( $join, &$this ) );
		}

		// Paging
		if ( empty($q['nopaging']) && !$this->is_singular ) {
			$page = absint($q['paged']);
			if ( !$page )
				$page = 1;

			// If 'offset' is provided, it takes precedence over 'paged'.
			if ( isset( $q['offset'] ) && is_numeric( $q['offset'] ) ) {
				$q['offset'] = absint( $q['offset'] );
				$pgstrt = $q['offset'] . ', ';
			} else {
				$pgstrt = absint( ( $page - 1 ) * $q['posts_per_page'] ) . ', ';
			}
			$limits = 'LIMIT ' . $pgstrt . $q['posts_per_page'];
		}

		// Comments feeds
		if ( $this->is_comment_feed && ! $this->is_singular ) {
			if ( $this->is_archive || $this->is_search ) {
				$cjoin = "JOIN {$wpdb->posts} ON ({$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID) $join ";
				$cwhere = "WHERE comment_approved = '1' $where";
				$cgroupby = "{$wpdb->comments}.comment_id";
			} else { // Other non singular e.g. front
				$cjoin = "JOIN {$wpdb->posts} ON ( {$wpdb->comments}.comment_post_ID = {$wpdb->posts}.ID )";
				$cwhere = "WHERE ( post_status = 'publish' OR ( post_status = 'inherit' AND post_type = 'attachment' ) ) AND comment_approved = '1'";
				$cgroupby = '';
			}

			if ( !$q['suppress_filters'] ) {
				/**
				 * Filters the JOIN clause of the comments feed query before sending.
				 *
				 * @since 2.2.0
				 *
				 * @param string   $cjoin The JOIN clause of the query.
				 * @param WP_Query $this The WP_Query instance (passed by reference).
				 */
				$cjoin = apply_filters_ref_array( 'comment_feed_join', array( $cjoin, &$this ) );

				/**
				 * Filters the WHERE clause of the comments feed query before sending.
				 *
				 * @since 2.2.0
				 *
				 * @param string   $cwhere The WHERE clause of the query.
				 * @param WP_Query $this   The WP_Query instance (passed by reference).
				 */
				$cwhere = apply_filters_ref_array( 'comment_feed_where', array( $cwhere, &$this ) );

				/**
				 * Filters the GROUP BY clause of the comments feed query before sending.
				 *
				 * @since 2.2.0
				 *
				 * @param string   $cgroupby The GROUP BY clause of the query.
				 * @param WP_Query $this     The WP_Query instance (passed by reference).
				 */
				$cgroupby = apply_filters_ref_array( 'comment_feed_groupby', array( $cgroupby, &$this ) );

				/**
				 * Filters the ORDER BY clause of the comments feed query before sending.
				 *
				 * @since 2.8.0
				 *
				 * @param string   $corderby The ORDER BY clause of the query.
				 * @param WP_Query $this     The WP_Query instance (passed by reference).
				 */
				$corderby = apply_filters_ref_array( 'comment_feed_orderby', array( 'comment_date_gmt DESC', &$this ) );

				/**
				 * Filters the LIMIT clause of the comments feed query before sending.
				 *
				 * @since 2.8.0
				 *
				 * @param string   $climits The JOIN clause of the query.
				 * @param WP_Query $this    The WP_Query instance (passed by reference).
				 */
				$climits = apply_filters_ref_array( 'comment_feed_limits', array( 'LIMIT ' . get_option('posts_per_rss'), &$this ) );
			}
			$cgroupby = ( ! empty( $cgroupby ) ) ? 'GROUP BY ' . $cgroupby : '';
			$corderby = ( ! empty( $corderby ) ) ? 'ORDER BY ' . $corderby : '';

			$comments = (array) $wpdb->get_results("SELECT $distinct {$wpdb->comments}.* FROM {$wpdb->comments} $cjoin $cwhere $cgroupby $corderby $climits");
			// Convert to WP_Comment
			$this->comments = array_map( 'get_comment', $comments );
			$this->comment_count = count($this->comments);

			$post_ids = array();

			foreach ( $this->comments as $comment )
				$post_ids[] = (int) $comment->comment_post_ID;

			$post_ids = join(',', $post_ids);
			$join = '';
			if ( $post_ids ) {
				$where = "AND {$wpdb->posts}.ID IN ($post_ids) ";
			} else {
				$where = "AND 0";
			}
		}

		$pieces = array( 'where', 'groupby', 'join', 'orderby', 'distinct', 'fields', 'limits' );

		/*
		 * Apply post-paging filters on where and join. Only plugins that
		 * manipulate paging queries should use these hooks.
		 */
		if ( !$q['suppress_filters'] ) {
			/**
			 * Filters the WHERE clause of the query.
			 *
			 * Specifically for manipulating paging queries.
			 *
			 * @since 1.5.0
			 *
			 * @param string   $where The WHERE clause of the query.
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			$where = apply_filters_ref_array( 'posts_where_paged', array( $where, &$this ) );

			/**
			 * Filters the GROUP BY clause of the query.
			 *
			 * @since 2.0.0
			 *
			 * @param string   $groupby The GROUP BY clause of the query.
			 * @param WP_Query $this    The WP_Query instance (passed by reference).
			 */
			$groupby = apply_filters_ref_array( 'posts_groupby', array( $groupby, &$this ) );

			/**
			 * Filters the JOIN clause of the query.
			 *
			 * Specifically for manipulating paging queries.
			 *
			 * @since 1.5.0
			 *
			 * @param string   $join  The JOIN clause of the query.
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			$join = apply_filters_ref_array( 'posts_join_paged', array( $join, &$this ) );

			/**
			 * Filters the ORDER BY clause of the query.
			 *
			 * @since 1.5.1
			 *
			 * @param string   $orderby The ORDER BY clause of the query.
			 * @param WP_Query $this    The WP_Query instance (passed by reference).
			 */
			$orderby = apply_filters_ref_array( 'posts_orderby', array( $orderby, &$this ) );

			/**
			 * Filters the DISTINCT clause of the query.
			 *
			 * @since 2.1.0
			 *
			 * @param string   $distinct The DISTINCT clause of the query.
			 * @param WP_Query $this     The WP_Query instance (passed by reference).
			 */
			$distinct = apply_filters_ref_array( 'posts_distinct', array( $distinct, &$this ) );

			/**
			 * Filters the LIMIT clause of the query.
			 *
			 * @since 2.1.0
			 *
			 * @param string   $limits The LIMIT clause of the query.
			 * @param WP_Query $this   The WP_Query instance (passed by reference).
			 */
			$limits = apply_filters_ref_array( 'post_limits', array( $limits, &$this ) );

			/**
			 * Filters the SELECT clause of the query.
			 *
			 * @since 2.1.0
			 *
			 * @param string   $fields The SELECT clause of the query.
			 * @param WP_Query $this   The WP_Query instance (passed by reference).
			 */
			$fields = apply_filters_ref_array( 'posts_fields', array( $fields, &$this ) );

			/**
			 * Filters all query clauses at once, for convenience.
			 *
			 * Covers the WHERE, GROUP BY, JOIN, ORDER BY, DISTINCT,
			 * fields (SELECT), and LIMITS clauses.
			 *
			 * @since 3.1.0
			 *
			 * @param array    $clauses The list of clauses for the query.
			 * @param WP_Query $this    The WP_Query instance (passed by reference).
			 */
			$clauses = (array) apply_filters_ref_array( 'posts_clauses', array( compact( $pieces ), &$this ) );

			$where = isset( $clauses[ 'where' ] ) ? $clauses[ 'where' ] : '';
			$groupby = isset( $clauses[ 'groupby' ] ) ? $clauses[ 'groupby' ] : '';
			$join = isset( $clauses[ 'join' ] ) ? $clauses[ 'join' ] : '';
			$orderby = isset( $clauses[ 'orderby' ] ) ? $clauses[ 'orderby' ] : '';
			$distinct = isset( $clauses[ 'distinct' ] ) ? $clauses[ 'distinct' ] : '';
			$fields = isset( $clauses[ 'fields' ] ) ? $clauses[ 'fields' ] : '';
			$limits = isset( $clauses[ 'limits' ] ) ? $clauses[ 'limits' ] : '';
		}

		/**
		 * Fires to announce the query's current selection parameters.
		 *
		 * For use by caching plugins.
		 *
		 * @since 2.3.0
		 *
		 * @param string $selection The assembled selection query.
		 */
		do_action( 'posts_selection', $where . $groupby . $orderby . $limits . $join );

		/*
		 * Filters again for the benefit of caching plugins.
		 * Regular plugins should use the hooks above.
		 */
		if ( !$q['suppress_filters'] ) {
			/**
			 * Filters the WHERE clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $where The WHERE clause of the query.
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			$where = apply_filters_ref_array( 'posts_where_request', array( $where, &$this ) );

			/**
			 * Filters the GROUP BY clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $groupby The GROUP BY clause of the query.
			 * @param WP_Query $this    The WP_Query instance (passed by reference).
			 */
			$groupby = apply_filters_ref_array( 'posts_groupby_request', array( $groupby, &$this ) );

			/**
			 * Filters the JOIN clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $join  The JOIN clause of the query.
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			$join = apply_filters_ref_array( 'posts_join_request', array( $join, &$this ) );

			/**
			 * Filters the ORDER BY clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $orderby The ORDER BY clause of the query.
			 * @param WP_Query $this    The WP_Query instance (passed by reference).
			 */
			$orderby = apply_filters_ref_array( 'posts_orderby_request', array( $orderby, &$this ) );

			/**
			 * Filters the DISTINCT clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $distinct The DISTINCT clause of the query.
			 * @param WP_Query $this     The WP_Query instance (passed by reference).
			 */
			$distinct = apply_filters_ref_array( 'posts_distinct_request', array( $distinct, &$this ) );

			/**
			 * Filters the SELECT clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $fields The SELECT clause of the query.
			 * @param WP_Query $this   The WP_Query instance (passed by reference).
			 */
			$fields = apply_filters_ref_array( 'posts_fields_request', array( $fields, &$this ) );

			/**
			 * Filters the LIMIT clause of the query.
			 *
			 * For use by caching plugins.
			 *
			 * @since 2.5.0
			 *
			 * @param string   $limits The LIMIT clause of the query.
			 * @param WP_Query $this   The WP_Query instance (passed by reference).
			 */
			$limits = apply_filters_ref_array( 'post_limits_request', array( $limits, &$this ) );

			/**
			 * Filters all query clauses at once, for convenience.
			 *
			 * For use by caching plugins.
			 *
			 * Covers the WHERE, GROUP BY, JOIN, ORDER BY, DISTINCT,
			 * fields (SELECT), and LIMITS clauses.
			 *
			 * @since 3.1.0
			 *
			 * @param array    $pieces The pieces of the query.
			 * @param WP_Query $this   The WP_Query instance (passed by reference).
			 */
			$clauses = (array) apply_filters_ref_array( 'posts_clauses_request', array( compact( $pieces ), &$this ) );

			$where = isset( $clauses[ 'where' ] ) ? $clauses[ 'where' ] : '';
			$groupby = isset( $clauses[ 'groupby' ] ) ? $clauses[ 'groupby' ] : '';
			$join = isset( $clauses[ 'join' ] ) ? $clauses[ 'join' ] : '';
			$orderby = isset( $clauses[ 'orderby' ] ) ? $clauses[ 'orderby' ] : '';
			$distinct = isset( $clauses[ 'distinct' ] ) ? $clauses[ 'distinct' ] : '';
			$fields = isset( $clauses[ 'fields' ] ) ? $clauses[ 'fields' ] : '';
			$limits = isset( $clauses[ 'limits' ] ) ? $clauses[ 'limits' ] : '';
		}

		if ( ! empty($groupby) )
			$groupby = 'GROUP BY ' . $groupby;
		if ( !empty( $orderby ) )
			$orderby = 'ORDER BY ' . $orderby;

		$found_rows = '';
		if ( !$q['no_found_rows'] && !empty($limits) )
			$found_rows = 'SQL_CALC_FOUND_ROWS';

		$this->request = $old_request = "SELECT $found_rows $distinct $fields FROM {$wpdb->posts} $join WHERE 1=1 $where $groupby $orderby $limits";

		if ( !$q['suppress_filters'] ) {
			/**
			 * Filters the completed SQL query before sending.
			 *
			 * @since 2.0.0
			 *
			 * @param string   $request The complete SQL query.
			 * @param WP_Query $this    The WP_Query instance (passed by reference).
			 */
			$this->request = apply_filters_ref_array( 'posts_request', array( $this->request, &$this ) );
		}

		/**
		 * Filters the posts array before the query takes place.
		 *
		 * Return a non-null value to bypass WordPress's default post queries.
		 *
		 * Filtering functions that require pagination information are encouraged to set
		 * the `found_posts` and `max_num_pages` properties of the WP_Query object,
		 * passed to the filter by reference. If WP_Query does not perform a database
		 * query, it will not have enough information to generate these values itself.
		 *
		 * @since 4.6.0
		 *
		 * @param array|null $posts Return an array of post data to short-circuit WP's query,
		 *                          or null to allow WP to run its normal queries.
		 * @param WP_Query   $this  The WP_Query instance (passed by reference).
		 */
		$this->posts = apply_filters_ref_array( 'posts_pre_query', array( null, &$this ) );

		if ( 'ids' == $q['fields'] ) {
			if ( null === $this->posts ) {
				$this->posts = $wpdb->get_col( $this->request );
			}

			$this->posts = array_map( 'intval', $this->posts );
			$this->post_count = count( $this->posts );
			$this->set_found_posts( $q, $limits );

			return $this->posts;
		}

		if ( 'id=>parent' == $q['fields'] ) {
			if ( null === $this->posts ) {
				$this->posts = $wpdb->get_results( $this->request );
			}

			$this->post_count = count( $this->posts );
			$this->set_found_posts( $q, $limits );

			$r = array();
			foreach ( $this->posts as $key => $post ) {
				$this->posts[ $key ]->ID = (int) $post->ID;
				$this->posts[ $key ]->post_parent = (int) $post->post_parent;

				$r[ (int) $post->ID ] = (int) $post->post_parent;
			}

			return $r;
		}

		if ( null === $this->posts ) {
			$split_the_query = ( $old_request == $this->request && "{$wpdb->posts}.*" == $fields && !empty( $limits ) && $q['posts_per_page'] < 500 );

			/**
			 * Filters whether to split the query.
			 *
			 * Splitting the query will cause it to fetch just the IDs of the found posts
			 * (and then individually fetch each post by ID), rather than fetching every
			 * complete row at once. One massive result vs. many small results.
			 *
			 * @since 3.4.0
			 *
			 * @param bool     $split_the_query Whether or not to split the query.
			 * @param WP_Query $this            The WP_Query instance.
			 */
			$split_the_query = apply_filters( 'split_the_query', $split_the_query, $this );

			if ( $split_the_query ) {
				// First get the IDs and then fill in the objects

				$this->request = "SELECT $found_rows $distinct {$wpdb->posts}.ID FROM {$wpdb->posts} $join WHERE 1=1 $where $groupby $orderby $limits";

				/**
				 * Filters the Post IDs SQL request before sending.
				 *
				 * @since 3.4.0
				 *
				 * @param string   $request The post ID request.
				 * @param WP_Query $this    The WP_Query instance.
				 */
				$this->request = apply_filters( 'posts_request_ids', $this->request, $this );

				$ids = $wpdb->get_col( $this->request );

				if ( $ids ) {
					$this->posts = $ids;
					$this->set_found_posts( $q, $limits );
					_prime_post_caches( $ids, $q['update_post_term_cache'], $q['update_post_meta_cache'] );
				} else {
					$this->posts = array();
				}
			} else {
				$this->posts = $wpdb->get_results( $this->request );
				$this->set_found_posts( $q, $limits );
			}
		}

		// Convert to WP_Post objects.
		if ( $this->posts ) {
			$this->posts = array_map( 'get_post', $this->posts );
		}

		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the raw post results array, prior to status checks.
			 *
			 * @since 2.3.0
			 *
			 * @param array    $posts The post results array.
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			$this->posts = apply_filters_ref_array( 'posts_results', array( $this->posts, &$this ) );
		}

		if ( !empty($this->posts) && $this->is_comment_feed && $this->is_singular ) {
			/** This filter is documented in wp-includes/query.php */
			$cjoin = apply_filters_ref_array( 'comment_feed_join', array( '', &$this ) );

			/** This filter is documented in wp-includes/query.php */
			$cwhere = apply_filters_ref_array( 'comment_feed_where', array( "WHERE comment_post_ID = '{$this->posts[0]->ID}' AND comment_approved = '1'", &$this ) );

			/** This filter is documented in wp-includes/query.php */
			$cgroupby = apply_filters_ref_array( 'comment_feed_groupby', array( '', &$this ) );
			$cgroupby = ( ! empty( $cgroupby ) ) ? 'GROUP BY ' . $cgroupby : '';

			/** This filter is documented in wp-includes/query.php */
			$corderby = apply_filters_ref_array( 'comment_feed_orderby', array( 'comment_date_gmt DESC', &$this ) );
			$corderby = ( ! empty( $corderby ) ) ? 'ORDER BY ' . $corderby : '';

			/** This filter is documented in wp-includes/query.php */
			$climits = apply_filters_ref_array( 'comment_feed_limits', array( 'LIMIT ' . get_option('posts_per_rss'), &$this ) );

			$comments_request = "SELECT {$wpdb->comments}.* FROM {$wpdb->comments} $cjoin $cwhere $cgroupby $corderby $climits";
			$comments = $wpdb->get_results($comments_request);
			// Convert to WP_Comment
			$this->comments = array_map( 'get_comment', $comments );
			$this->comment_count = count($this->comments);
		}

		// Check post status to determine if post should be displayed.
		if ( !empty($this->posts) && ($this->is_single || $this->is_page) ) {
			$status = get_post_status($this->posts[0]);
			if ( 'attachment' === $this->posts[0]->post_type && 0 === (int) $this->posts[0]->post_parent ) {
				$this->is_page = false;
				$this->is_single = true;
				$this->is_attachment = true;
			}
			$post_status_obj = get_post_status_object($status);

			// If the post_status was specifically requested, let it pass through.
			if ( !$post_status_obj->public && ! in_array( $status, $q_status ) ) {

				if ( ! is_user_logged_in() ) {
					// User must be logged in to view unpublished posts.
					$this->posts = array();
				} else {
					if  ( $post_status_obj->protected ) {
						// User must have edit permissions on the draft to preview.
						if ( ! current_user_can($edit_cap, $this->posts[0]->ID) ) {
							$this->posts = array();
						} else {
							$this->is_preview = true;
							if ( 'future' != $status )
								$this->posts[0]->post_date = current_time('mysql');
						}
					} elseif ( $post_status_obj->private ) {
						if ( ! current_user_can($read_cap, $this->posts[0]->ID) )
							$this->posts = array();
					} else {
						$this->posts = array();
					}
				}
			}

			if ( $this->is_preview && $this->posts && current_user_can( $edit_cap, $this->posts[0]->ID ) ) {
				/**
				 * Filters the single post for preview mode.
				 *
				 * @since 2.7.0
				 *
				 * @param WP_Post  $post_preview  The Post object.
				 * @param WP_Query $this          The WP_Query instance (passed by reference).
				 */
				$this->posts[0] = get_post( apply_filters_ref_array( 'the_preview', array( $this->posts[0], &$this ) ) );
			}
		}

		// Put sticky posts at the top of the posts array
		$sticky_posts = get_option('sticky_posts');
		if ( $this->is_home && $page <= 1 && is_array($sticky_posts) && !empty($sticky_posts) && !$q['ignore_sticky_posts'] ) {
			$num_posts = count($this->posts);
			$sticky_offset = 0;
			// Loop over posts and relocate stickies to the front.
			for ( $i = 0; $i < $num_posts; $i++ ) {
				if ( in_array($this->posts[$i]->ID, $sticky_posts) ) {
					$sticky_post = $this->posts[$i];
					// Remove sticky from current position
					array_splice($this->posts, $i, 1);
					// Move to front, after other stickies
					array_splice($this->posts, $sticky_offset, 0, array($sticky_post));
					// Increment the sticky offset. The next sticky will be placed at this offset.
					$sticky_offset++;
					// Remove post from sticky posts array
					$offset = array_search($sticky_post->ID, $sticky_posts);
					unset( $sticky_posts[$offset] );
				}
			}

			// If any posts have been excluded specifically, Ignore those that are sticky.
			if ( !empty($sticky_posts) && !empty($q['post__not_in']) )
				$sticky_posts = array_diff($sticky_posts, $q['post__not_in']);

			// Fetch sticky posts that weren't in the query results
			if ( !empty($sticky_posts) ) {
				$stickies = get_posts( array(
					'post__in' => $sticky_posts,
					'post_type' => $post_type,
					'post_status' => 'publish',
					'nopaging' => true
				) );

				foreach ( $stickies as $sticky_post ) {
					array_splice( $this->posts, $sticky_offset, 0, array( $sticky_post ) );
					$sticky_offset++;
				}
			}
		}

		// If comments have been fetched as part of the query, make sure comment meta lazy-loading is set up.
		if ( ! empty( $this->comments ) ) {
			wp_queue_comments_for_comment_meta_lazyload( $this->comments );
		}

		if ( ! $q['suppress_filters'] ) {
			/**
			 * Filters the array of retrieved posts after they've been fetched and
			 * internally processed.
			 *
			 * @since 1.5.0
			 *
			 * @param array    $posts The array of retrieved posts.
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			$this->posts = apply_filters_ref_array( 'the_posts', array( $this->posts, &$this ) );
		}

		// Ensure that any posts added/modified via one of the filters above are
		// of the type WP_Post and are filtered.
		if ( $this->posts ) {
			$this->post_count = count( $this->posts );

			$this->posts = array_map( 'get_post', $this->posts );

			if ( $q['cache_results'] )
				update_post_caches($this->posts, $post_type, $q['update_post_term_cache'], $q['update_post_meta_cache']);

			$this->post = reset( $this->posts );
		} else {
			$this->post_count = 0;
			$this->posts = array();
		}

		if ( $q['lazy_load_term_meta'] ) {
			wp_queue_posts_for_term_meta_lazyload( $this->posts );
		}

		return $this->posts;
	}

	/**
	 * Set up the amount of found posts and the number of pages (if limit clause was used)
	 * for the current query.
	 *
	 * @since 3.5.0
	 *
	 * @param array  $q      Query variables.
	 * @param string $limits LIMIT clauses of the query.
	 */
	private function set_found_posts( $q, $limits ) {
		global $wpdb;
		// Bail if posts is an empty array. Continue if posts is an empty string,
		// null, or false to accommodate caching plugins that fill posts later.
		if ( $q['no_found_rows'] || ( is_array( $this->posts ) && ! $this->posts ) )
			return;

		if ( ! empty( $limits ) ) {
			/**
			 * Filters the query to run for retrieving the found posts.
			 *
			 * @since 2.1.0
			 *
			 * @param string   $found_posts The query to run to find the found posts.
			 * @param WP_Query $this        The WP_Query instance (passed by reference).
			 */
			$this->found_posts = $wpdb->get_var( apply_filters_ref_array( 'found_posts_query', array( 'SELECT FOUND_ROWS()', &$this ) ) );
		} else {
			if ( is_array( $this->posts ) ) {
				$this->found_posts = count( $this->posts );
			} else {
				if ( null === $this->posts ) {  
					$this->found_posts = 0;
				} else {
					$this->found_posts = 1;
				}
			}
		}

		/**
		 * Filters the number of found posts for the query.
		 *
		 * @since 2.1.0
		 *
		 * @param int      $found_posts The number of posts found.
		 * @param WP_Query $this        The WP_Query instance (passed by reference).
		 */
		$this->found_posts = apply_filters_ref_array( 'found_posts', array( $this->found_posts, &$this ) );

		if ( ! empty( $limits ) )
			$this->max_num_pages = ceil( $this->found_posts / $q['posts_per_page'] );
	}

	/**
	 * Set up the next post and iterate current post index.
	 *
	 * @since 1.5.0
	 *
	 * @return WP_Post Next post.
	 */
	public function next_post() {

		$this->current_post++;

		$this->post = $this->posts[$this->current_post];
		return $this->post;
	}

	/**
	 * Sets up the current post.
	 *
	 * Retrieves the next post, sets up the post, sets the 'in the loop'
	 * property to true.
	 *
	 * @since 1.5.0
	 *
	 * @global WP_Post $post
	 */
	public function the_post() {
		global $post;
		$this->in_the_loop = true;

		if ( $this->current_post == -1 ) // loop has just started
			/**
			 * Fires once the loop is started.
			 *
			 * @since 2.0.0
			 *
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			do_action_ref_array( 'loop_start', array( &$this ) );

		$post = $this->next_post();
		$this->setup_postdata( $post );
	}

	/**
	 * Determines whether there are more posts available in the loop.
	 *
	 * Calls the {@see 'loop_end'} action when the loop is complete.
	 *
	 * @since 1.5.0
	 *
	 * @return bool True if posts are available, false if end of loop.
	 */
	public function have_posts() {
		if ( $this->current_post + 1 < $this->post_count ) {
			return true;
		} elseif ( $this->current_post + 1 == $this->post_count && $this->post_count > 0 ) {
			/**
			 * Fires once the loop has ended.
			 *
			 * @since 2.0.0
			 *
			 * @param WP_Query $this The WP_Query instance (passed by reference).
			 */
			do_action_ref_array( 'loop_end', array( &$this ) );
			// Do some cleaning up after the loop
			$this->rewind_posts();
		} elseif ( 0 === $this->post_count ) {
			/**
			 * Fires if no results are found in a post query.
			 *
			 * @since 4.9.0
			 *
			 * @param WP_Query $this The WP_Query instance.
			 */
			do_action( 'loop_no_results', $this );
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Rewind the posts and reset post index.
	 *
	 * @since 1.5.0
	 */
	public function rewind_posts() {
		$this->current_post = -1;
		if ( $this->post_count > 0 ) {
			$this->post = $this->posts[0];
		}
	}

	/**
	 * Iterate current comment index and return WP_Comment object.
	 *
	 * @since 2.2.0
	 *
	 * @return WP_Comment Comment object.
	 */
	public function next_comment() {
		$this->current_comment++;

		$this->comment = $this->comments[$this->current_comment];
		return $this->comment;
	}

	/**
	 * Sets up the current comment.
	 *
	 * @since 2.2.0
	 * @global WP_Comment $comment Current comment.
	 */
	public function the_comment() {
		global $comment;

		$comment = $this->next_comment();

		if ( $this->current_comment == 0 ) {
			/**
			 * Fires once the comment loop is started.
			 *
			 * @since 2.2.0
			 */
			do_action( 'comment_loop_start' );
		}
	}

	/**
	 * Whether there are more comments available.
	 *
	 * Automatically rewinds comments when finished.
	 *
	 * @since 2.2.0
	 *
	 * @return bool True, if more comments. False, if no more posts.
	 */
	public function have_comments() {
		if ( $this->current_comment + 1 < $this->comment_count ) {
			return true;
		} elseif ( $this->current_comment + 1 == $this->comment_count ) {
			$this->rewind_comments();
		}

		return false;
	}

	/**
	 * Rewind the comments, resets the comment index and comment to first.
	 *
	 * @since 2.2.0
	 */
	public function rewind_comments() {
		$this->current_comment = -1;
		if ( $this->comment_count > 0 ) {
			$this->comment = $this->comments[0];
		}
	}

	// refactored. public function query( $query ) {}

	/**
	 * Retrieve queried object.
	 *
	 * If queried object is not set, then the queried object will be set from
	 * the category, tag, taxonomy, posts page, single post, page, or author
	 * query variable. After it is set up, it will be returned.
	 *
	 * @since 1.5.0
	 *
	 * @return object
	 */
	public function get_queried_object() {
		if ( isset($this->queried_object) )
			return $this->queried_object;

		$this->queried_object = null;
		$this->queried_object_id = null;

		if ( $this->is_category || $this->is_tag || $this->is_tax ) {
			if ( $this->is_category ) {
				if ( $this->get( 'cat' ) ) {
					$term = get_term( $this->get( 'cat' ), 'category' );
				} elseif ( $this->get( 'category_name' ) ) {
					$term = get_term_by( 'slug', $this->get( 'category_name' ), 'category' );
				}
			} elseif ( $this->is_tag ) {
				if ( $this->get( 'tag_id' ) ) {
					$term = get_term( $this->get( 'tag_id' ), 'post_tag' );
				} elseif ( $this->get( 'tag' ) ) {
					$term = get_term_by( 'slug', $this->get( 'tag' ), 'post_tag' );
				}
			} else {
				// For other tax queries, grab the first term from the first clause.
				if ( ! empty( $this->tax_query->queried_terms ) ) {
					$queried_taxonomies = array_keys( $this->tax_query->queried_terms );
					$matched_taxonomy = reset( $queried_taxonomies );
					$query = $this->tax_query->queried_terms[ $matched_taxonomy ];

					if ( ! empty( $query['terms'] ) ) {
						if ( 'term_id' == $query['field'] ) {
							$term = get_term( reset( $query['terms'] ), $matched_taxonomy );
						} else {
							$term = get_term_by( $query['field'], reset( $query['terms'] ), $matched_taxonomy );
						}
					}
				}
			}

			if ( ! empty( $term ) && ! is_wp_error( $term ) )  {
				$this->queried_object = $term;
				$this->queried_object_id = (int) $term->term_id;

				if ( $this->is_category && 'category' === $this->queried_object->taxonomy )
					_make_cat_compat( $this->queried_object );
			}
		} elseif ( $this->is_post_type_archive ) {
			$post_type = $this->get( 'post_type' );
			if ( is_array( $post_type ) )
				$post_type = reset( $post_type );
			$this->queried_object = get_post_type_object( $post_type );
		} elseif ( $this->is_posts_page ) {
			$page_for_posts = get_option('page_for_posts');
			$this->queried_object = get_post( $page_for_posts );
			$this->queried_object_id = (int) $this->queried_object->ID;
		} elseif ( $this->is_singular && ! empty( $this->post ) ) {
			$this->queried_object = $this->post;
			$this->queried_object_id = (int) $this->post->ID;
		} elseif ( $this->is_author ) {
			$this->queried_object_id = (int) $this->get('author');
			$this->queried_object = get_userdata( $this->queried_object_id );
		}

		return $this->queried_object;
	}

	/**
	 * Retrieve ID of the current queried object.
	 *
	 * @since 1.5.0
	 *
	 * @return int
	 */
	public function get_queried_object_id() {
		$this->get_queried_object();

		if ( isset($this->queried_object_id) ) {
			return $this->queried_object_id;
		}

		return 0;
	}

	// refactored. public function __construct( $query = '' ) {}

	/**
	 * Make private properties readable for backward compatibility.
	 *
	 * @since 4.0.0
	 *
	 * @param string $name Property to get.
	 * @return mixed Property.
	 */
	public function __get( $name ) {
		if ( in_array( $name, $this->compat_fields ) ) {
			return $this->$name;
		}
	}

	/**
	 * Make private properties checkable for backward compatibility.
	 *
	 * @since 4.0.0
	 *
	 * @param string $name Property to check if set.
	 * @return bool Whether the property is set.
	 */
	public function __isset( $name ) {
		if ( in_array( $name, $this->compat_fields ) ) {
			return isset( $this->$name );
		}
	}

	/**
	 * Make private/protected methods readable for backward compatibility.
	 *
	 * @since 4.0.0
	 *
	 * @param callable $name      Method to call.
	 * @param array    $arguments Arguments to pass when calling.
	 * @return mixed|false Return value of the callback, false otherwise.
	 */
	public function __call( $name, $arguments ) {
		if ( in_array( $name, $this->compat_methods ) ) {
			return call_user_func_array( array( $this, $name ), $arguments );
		}
		return false;
	}

	/**
 	 * Is the query for an existing archive page?
 	 *
 	 * Month, Year, Category, Author, Post Type archive...
	 *
 	 * @since 3.1.0
 	 *
 	 * @return bool
 	 */
	public function is_archive() {
		return (bool) $this->is_archive;
	}

	/**
	 * Is the query for an existing post type archive page?
	 *
	 * @since 3.1.0
	 *
	 * @param mixed $post_types Optional. Post type or array of posts types to check against.
	 * @return bool
	 */
	public function is_post_type_archive( $post_types = '' ) {
		if ( empty( $post_types ) || ! $this->is_post_type_archive )
			return (bool) $this->is_post_type_archive;

		$post_type = $this->get( 'post_type' );
		if ( is_array( $post_type ) )
			$post_type = reset( $post_type );
		$post_type_object = get_post_type_object( $post_type );

		return in_array( $post_type_object->name, (array) $post_types );
	}

	/**
	 * Is the query for an existing attachment page?
	 *
	 * @since 3.1.0
	 *
	 * @param mixed $attachment Attachment ID, title, slug, or array of such.
	 * @return bool
	 */
	public function is_attachment( $attachment = '' ) {
		if ( ! $this->is_attachment ) {
			return false;
		}

		if ( empty( $attachment ) ) {
			return true;
		}

		$attachment = array_map( 'strval', (array) $attachment );

		$post_obj = $this->get_queried_object();

		if ( in_array( (string) $post_obj->ID, $attachment ) ) {
			return true;
		} elseif ( in_array( $post_obj->post_title, $attachment ) ) {
			return true;
		} elseif ( in_array( $post_obj->post_name, $attachment ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Is the query for an existing author archive page?
	 *
	 * If the $author parameter is specified, this function will additionally
	 * check if the query is for one of the authors specified.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed $author Optional. User ID, nickname, nicename, or array of User IDs, nicknames, and nicenames
	 * @return bool
	 */
	public function is_author( $author = '' ) {
		if ( !$this->is_author )
			return false;

		if ( empty($author) )
			return true;

		$author_obj = $this->get_queried_object();

		$author = array_map( 'strval', (array) $author );

		if ( in_array( (string) $author_obj->ID, $author ) )
			return true;
		elseif ( in_array( $author_obj->nickname, $author ) )
			return true;
		elseif ( in_array( $author_obj->user_nicename, $author ) )
			return true;

		return false;
	}

	/**
	 * Is the query for an existing category archive page?
	 *
	 * If the $category parameter is specified, this function will additionally
	 * check if the query is for one of the categories specified.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed $category Optional. Category ID, name, slug, or array of Category IDs, names, and slugs.
	 * @return bool
	 */
	public function is_category( $category = '' ) {
		if ( !$this->is_category )
			return false;

		if ( empty($category) )
			return true;

		$cat_obj = $this->get_queried_object();

		$category = array_map( 'strval', (array) $category );

		if ( in_array( (string) $cat_obj->term_id, $category ) )
			return true;
		elseif ( in_array( $cat_obj->name, $category ) )
			return true;
		elseif ( in_array( $cat_obj->slug, $category ) )
			return true;

		return false;
	}

	/**
	 * Is the query for an existing tag archive page?
	 *
	 * If the $tag parameter is specified, this function will additionally
	 * check if the query is for one of the tags specified.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed $tag Optional. Tag ID, name, slug, or array of Tag IDs, names, and slugs.
	 * @return bool
	 */
	public function is_tag( $tag = '' ) {
		if ( ! $this->is_tag )
			return false;

		if ( empty( $tag ) )
			return true;

		$tag_obj = $this->get_queried_object();

		$tag = array_map( 'strval', (array) $tag );

		if ( in_array( (string) $tag_obj->term_id, $tag ) )
			return true;
		elseif ( in_array( $tag_obj->name, $tag ) )
			return true;
		elseif ( in_array( $tag_obj->slug, $tag ) )
			return true;

		return false;
	}

	/**
	 * Is the query for an existing custom taxonomy archive page?
	 *
	 * If the $taxonomy parameter is specified, this function will additionally
	 * check if the query is for that specific $taxonomy.
	 *
	 * If the $term parameter is specified in addition to the $taxonomy parameter,
	 * this function will additionally check if the query is for one of the terms
	 * specified.
	 *
	 * @since 3.1.0
	 *
	 * @global array $wp_taxonomies
	 *
	 * @param mixed $taxonomy Optional. Taxonomy slug or slugs.
	 * @param mixed $term     Optional. Term ID, name, slug or array of Term IDs, names, and slugs.
	 * @return bool True for custom taxonomy archive pages, false for built-in taxonomies (category and tag archives).
	 */
	public function is_tax( $taxonomy = '', $term = '' ) {
		global $wp_taxonomies;

		if ( !$this->is_tax )
			return false;

		if ( empty( $taxonomy ) )
			return true;

		$queried_object = $this->get_queried_object();
		$tax_array = array_intersect( array_keys( $wp_taxonomies ), (array) $taxonomy );
		$term_array = (array) $term;

		// Check that the taxonomy matches.
		if ( ! ( isset( $queried_object->taxonomy ) && count( $tax_array ) && in_array( $queried_object->taxonomy, $tax_array ) ) )
			return false;

		// Only a Taxonomy provided.
		if ( empty( $term ) )
			return true;

		return isset( $queried_object->term_id ) &&
			count( array_intersect(
				array( $queried_object->term_id, $queried_object->name, $queried_object->slug ),
				$term_array
			) );
	}

	/**
	 * Whether the current URL is within the comments popup window.
	 *
	 * @since 3.1.0
	 * @deprecated 4.5.0
	 *
	 * @return bool
	 */
	public function is_comments_popup() {
		_deprecated_function( __FUNCTION__, '4.5.0' );

		return false;
	}

	/**
	 * Is the query for an existing date archive?
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_date() {
		return (bool) $this->is_date;
	}

	/**
	 * Is the query for an existing day archive?
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_day() {
		return (bool) $this->is_day;
	}

	/**
	 * Is the query for a feed?
	 *
	 * @since 3.1.0
	 *
	 * @param string|array $feeds Optional feed types to check.
	 * @return bool
	 */
	public function is_feed( $feeds = '' ) {
		if ( empty( $feeds ) || ! $this->is_feed )
			return (bool) $this->is_feed;
		$qv = $this->get( 'feed' );
		if ( 'feed' == $qv )
			$qv = get_default_feed();
		return in_array( $qv, (array) $feeds );
	}

	/**
	 * Is the query for a comments feed?
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_comment_feed() {
		return (bool) $this->is_comment_feed;
	}

	/**
	 * Is the query for the front page of the site?
	 *
	 * This is for what is displayed at your site's main URL.
	 *
	 * Depends on the site's "Front page displays" Reading Settings 'show_on_front' and 'page_on_front'.
	 *
	 * If you set a static page for the front page of your site, this function will return
	 * true when viewing that page.
	 *
	 * Otherwise the same as @see WP_Query::is_home()
	 *
	 * @since 3.1.0
	 *
	 * @return bool True, if front of site.
	 */
	public function is_front_page() {
		// most likely case
		if ( 'posts' == get_option( 'show_on_front') && $this->is_home() )
			return true;
		elseif ( 'page' == get_option( 'show_on_front') && get_option( 'page_on_front' ) && $this->is_page( get_option( 'page_on_front' ) ) )
			return true;
		else
			return false;
	}

	/**
	 * Is the query for the blog homepage?
	 *
	 * This is the page which shows the time based blog content of your site.
	 *
	 * Depends on the site's "Front page displays" Reading Settings 'show_on_front' and 'page_for_posts'.
	 *
	 * If you set a static page for the front page of your site, this function will return
	 * true only on the page you set as the "Posts page".
	 *
	 * @see WP_Query::is_front_page()
	 *
	 * @since 3.1.0
	 *
	 * @return bool True if blog view homepage.
	 */
	public function is_home() {
		return (bool) $this->is_home;
	}

	/**
	 * Is the query for an existing month archive?
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_month() {
		return (bool) $this->is_month;
	}

	/**
	 * Is the query for an existing single page?
	 *
	 * If the $page parameter is specified, this function will additionally
	 * check if the query is for one of the pages specified.
	 *
	 * @see WP_Query::is_single()
	 * @see WP_Query::is_singular()
	 *
	 * @since 3.1.0
	 *
	 * @param int|string|array $page Optional. Page ID, title, slug, path, or array of such. Default empty.
	 * @return bool Whether the query is for an existing single page.
	 */
	public function is_page( $page = '' ) {
		if ( !$this->is_page )
			return false;

		if ( empty( $page ) )
			return true;

		$page_obj = $this->get_queried_object();

		$page = array_map( 'strval', (array) $page );

		if ( in_array( (string) $page_obj->ID, $page ) ) {
			return true;
		} elseif ( in_array( $page_obj->post_title, $page ) ) {
			return true;
		} elseif ( in_array( $page_obj->post_name, $page ) ) {
			return true;
		} else {
			foreach ( $page as $pagepath ) {
				if ( ! strpos( $pagepath, '/' ) ) {
					continue;
				}
				$pagepath_obj = get_page_by_path( $pagepath );

				if ( $pagepath_obj && ( $pagepath_obj->ID == $page_obj->ID ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Is the query for paged result and not for the first page?
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_paged() {
		return (bool) $this->is_paged;
	}

	/**
	 * Is the query for a post or page preview?
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_preview() {
		return (bool) $this->is_preview;
	}

	/**
	 * Is the query for the robots file?
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_robots() {
		return (bool) $this->is_robots;
	}

	/**
	 * Is the query for a search?
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_search() {
		return (bool) $this->is_search;
	}

	/**
	 * Is the query for an existing single post?
	 *
	 * Works for any post type excluding pages.
	 *
	 * If the $post parameter is specified, this function will additionally
	 * check if the query is for one of the Posts specified.
	 *
	 * @see WP_Query::is_page()
	 * @see WP_Query::is_singular()
	 *
	 * @since 3.1.0
	 *
	 * @param int|string|array $post Optional. Post ID, title, slug, path, or array of such. Default empty.
	 * @return bool Whether the query is for an existing single post.
	 */
	public function is_single( $post = '' ) {
		if ( !$this->is_single )
			return false;

		if ( empty($post) )
			return true;

		$post_obj = $this->get_queried_object();

		$post = array_map( 'strval', (array) $post );

		if ( in_array( (string) $post_obj->ID, $post ) ) {
			return true;
		} elseif ( in_array( $post_obj->post_title, $post ) ) {
			return true;
		} elseif ( in_array( $post_obj->post_name, $post ) ) {
			return true;
		} else {
			foreach ( $post as $postpath ) {
				if ( ! strpos( $postpath, '/' ) ) {
					continue;
				}
				$postpath_obj = get_page_by_path( $postpath, OBJECT, $post_obj->post_type );

				if ( $postpath_obj && ( $postpath_obj->ID == $post_obj->ID ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Is the query for an existing single post of any post type (post, attachment, page,
	 * custom post types)?
	 *
	 * If the $post_types parameter is specified, this function will additionally
	 * check if the query is for one of the Posts Types specified.
	 *
	 * @see WP_Query::is_page()
	 * @see WP_Query::is_single()
	 *
	 * @since 3.1.0
	 *
	 * @param string|array $post_types Optional. Post type or array of post types. Default empty.
	 * @return bool Whether the query is for an existing single post of any of the given post types.
	 */
	public function is_singular( $post_types = '' ) {
		if ( empty( $post_types ) || !$this->is_singular )
			return (bool) $this->is_singular;

		$post_obj = $this->get_queried_object();

		return in_array( $post_obj->post_type, (array) $post_types );
	}

	/**
	 * Is the query for a specific time?
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_time() {
		return (bool) $this->is_time;
	}

	/**
	 * Is the query for a trackback endpoint call?
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_trackback() {
		return (bool) $this->is_trackback;
	}

	/**
	 * Is the query for an existing year archive?
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_year() {
		return (bool) $this->is_year;
	}

	/**
	 * Is the query a 404 (returns no results)?
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_404() {
		return (bool) $this->is_404;
	}

	/**
	 * Is the query for an embedded post?
	 *
	 * @since 4.4.0
	 *
	 * @return bool
	 */
	public function is_embed() {
		return (bool) $this->is_embed;
	}

	/**
	 * Is the query the main query?
	 *
	 * @since 3.3.0
	 *
	 * @global WP_Query $wp_query Global WP_Query instance.
	 *
	 * @return bool
	 */
	public function is_main_query() {
		global $wp_the_query;
		return $wp_the_query === $this;
	}

	/**
	 * Set up global post data.
	 *
	 * @since 4.1.0
	 * @since 4.4.0 Added the ability to pass a post ID to `$post`.
	 *
	 * @global int             $id
	 * @global WP_User         $authordata
	 * @global string|int|bool $currentday
	 * @global string|int|bool $currentmonth
	 * @global int             $page
	 * @global array           $pages
	 * @global int             $multipage
	 * @global int             $more
	 * @global int             $numpages
	 *
	 * @param WP_Post|object|int $post WP_Post instance or Post ID/object.
	 * @return true True when finished.
	 */
	public function setup_postdata( $post ) {
		global $id, $authordata, $currentday, $currentmonth, $page, $pages, $multipage, $more, $numpages;

		if ( ! ( $post instanceof WP_Post ) ) {
			$post = get_post( $post );
		}

		if ( ! $post ) {
			return;
		}

		$id = (int) $post->ID;

		$authordata = get_userdata($post->post_author);

		$currentday = mysql2date('d.m.y', $post->post_date, false);
		$currentmonth = mysql2date('m', $post->post_date, false);
		$numpages = 1;
		$multipage = 0;
		$page = $this->get( 'page' );
		if ( ! $page )
			$page = 1;

		/*
		 * Force full post content when viewing the permalink for the $post,
		 * or when on an RSS feed. Otherwise respect the 'more' tag.
		 */
		if ( $post->ID === get_queried_object_id() && ( $this->is_page() || $this->is_single() ) ) {
			$more = 1;
		} elseif ( $this->is_feed() ) {
			$more = 1;
		} else {
			$more = 0;
		}

		$content = $post->post_content;
		if ( false !== strpos( $content, '<!--nextpage-->' ) ) {
			$content = str_replace( "\n<!--nextpage-->\n", '<!--nextpage-->', $content );
			$content = str_replace( "\n<!--nextpage-->", '<!--nextpage-->', $content );
			$content = str_replace( "<!--nextpage-->\n", '<!--nextpage-->', $content );

			// Ignore nextpage at the beginning of the content.
			if ( 0 === strpos( $content, '<!--nextpage-->' ) )
				$content = substr( $content, 15 );

			$pages = explode('<!--nextpage-->', $content);
		} else {
			$pages = array( $post->post_content );
		}

		/**
		 * Filters the "pages" derived from splitting the post content.
		 *
		 * "Pages" are determined by splitting the post content based on the presence
		 * of `<!-- nextpage -->` tags.
		 *
		 * @since 4.4.0
		 *
		 * @param array   $pages Array of "pages" derived from the post content.
		 *                       of `<!-- nextpage -->` tags..
		 * @param WP_Post $post  Current post object.
		 */
		$pages = apply_filters( 'content_pagination', $pages, $post );

		$numpages = count( $pages );

		if ( $numpages > 1 ) {
			if ( $page > 1 ) {
				$more = 1;
			}
			$multipage = 1;
		} else {
	 		$multipage = 0;
	 	}

		/**
		 * Fires once the post data has been setup.
		 *
		 * @since 2.8.0
		 * @since 4.1.0 Introduced `$this` parameter.
		 *
		 * @param WP_Post  $post The Post object (passed by reference).
		 * @param WP_Query $this The current Query object (passed by reference).
		 */
		do_action_ref_array( 'the_post', array( &$post, &$this ) );

		return true;
	}
	/**
	 * After looping through a nested query, this function
	 * restores the $post global to the current post in this query.
	 *
	 * @since 3.7.0
	 *
	 * @global WP_Post $post
	 */
	public function reset_postdata() {
		if ( ! empty( $this->post ) ) {
			$GLOBALS['post'] = $this->post;
			$this->setup_postdata( $this->post );
		}
	}

	/**
	 * Lazyload term meta for posts in the loop.
	 *
	 * @since 4.4.0
	 * @deprecated 4.5.0 See wp_queue_posts_for_term_meta_lazyload().
	 *
	 * @param mixed $check
	 * @param int   $term_id
	 * @return mixed
	 */
	public function lazyload_term_meta( $check, $term_id ) {
		_deprecated_function( __METHOD__, '4.5.0' );
		return $check;
	}

	/**
	 * Lazyload comment meta for comments in the loop.
	 *
	 * @since 4.4.0
	 * @deprecated 4.5.0 See wp_queue_comments_for_comment_meta_lazyload().
	 *
	 * @param mixed $check
	 * @param int   $comment_id
	 * @return mixed
	 */
	public function lazyload_comment_meta( $check, $comment_id ) {
		_deprecated_function( __METHOD__, '4.5.0' );
		return $check;
	}
}
