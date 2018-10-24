<?php
/**
 * Sets up the default filters and actions for most of the WordPress hooks.
 *
 * If you need to remove a default hook, this file will give you the priority for which to use to remove the hook.
 *
 * Not all of the default hooks are found in default-filters.php
 *
 * @package WordPress
 */

// Strip, trim, kses, special chars for string saves
foreach ( array( 'pre_term_name', 'pre_comment_author_name', 'pre_link_name', 'pre_link_target', 'pre_link_rel', 'pre_user_display_name', 'pre_user_first_name', 'pre_user_last_name', 'pre_user_nickname' ) as $filter ) {
	add_filter( $filter, 'sanitize_text_field' );
	add_filter( $filter, 'wp_filter_kses' );
	add_filter( $filter, '_wp_specialchars', 30 );
}

// Strip, kses, special chars for string display
foreach ( array( 'term_name', 'comment_author_name', 'link_name', 'link_target', 'link_rel', 'user_display_name', 'user_first_name', 'user_last_name', 'user_nickname' ) as $filter ) {
	if ( is_admin() ) {
		/**
		 * These are expensive.
		 * Run only on admin pages for defense in depth.
		 */
		add_filter( $filter, 'sanitize_text_field' );
		add_filter( $filter, 'wp_kses_data' );
	}

	add_filter( $filter, '_wp_specialchars', 30 );
}

// Kses only for textarea saves
foreach ( array( 'pre_term_description', 'pre_link_description', 'pre_link_notes', 'pre_user_description' ) as $filter ) {
	add_filter( $filter, 'wp_filter_kses' );
}

// Kses only for textarea admin displays
if ( is_admin() ) {
	foreach ( array( 'term_description', 'link_description', 'link_notes', 'user_descrption' ) as $filter ) {
		add_filter( $filter, 'wp_kses_data' );
	}

	add_filter( 'comment_text', 'wp_kses_post' );
}

// Email saves
foreach ( array( 'pre_comment_author_email', 'pre_user_email' ) as $filter ) {
	add_filter( $filter, 'trim' );
	add_filter( $filter, 'sanitize_email' );
	add_filter( $filter, 'wp_filter_kses' );
}

// Email admin display
foreach ( array( 'comment_author_email', 'user_email' ) as $filter ) {
	add_filter( $filter, 'sanitize_email' );

	if ( is_admin() ) {
		add_filter( $filter, 'wp_kses_data' );
	}
}

// Save URL
foreach ( array( 'pre_comment_author_url', 'pre_user_url', 'pre_link_url', 'pre_link_image', 'pre_link_rss', 'pre_post_guid' ) as $filter ) {
	add_filter( $filter, 'wp_strip_all_tags' );
	add_filter( $filter, 'esc_url_raw' );
	add_filter( $filter, 'wp_filter_kses' );
}

// Display URL
foreach ( array( 'user_url', 'link_url', 'link_image', 'link_rss', 'comment_url', 'post_guid' ) as $filter ) {
	if ( is_admin() ) {
		add_filter( $filter, 'wp_strip_all_tags' );
	}

	add_filter( $filter, 'esc_url' );

	if ( is_admin() ) {
		add_filter( $filter, 'wp_kses_data' );
	}
}

// Slugs
add_filter( 'pre_term_slug',       'sanitize_title' );
add_filter( 'wp_insert_post_data', '_wp_customize_changeset_filter_insert_post_data', 10, 2 );
// @NOW 004
