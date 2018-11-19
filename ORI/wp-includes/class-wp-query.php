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

	// refactored. public function set($query_var, $value) {}
	// :
	// refactored. private function set_found_posts( $q, $limits ) {}

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

	// refactored. public function is_main_query() {}

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
