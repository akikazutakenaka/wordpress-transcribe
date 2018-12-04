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

// Key
foreach ( array( 'pre_post_type', 'pre_post_status', 'pre_post_comment_status', 'pre_post_ping_status' ) as $filter ) {
	add_filter( $filter, 'sanitize_key' );
}

// Mime types
add_filter( 'pre_post_mime_type', 'sanitize_mime_type' );
add_filter( 'post_mime_type',     'sanitize_mime_type' );

// Meta
add_filter( 'register_meta_args', '_wp_register_meta_args_whitelist', 10, 2 );

// Places to balance tags on input
foreach ( array( 'content_save_pre', 'excerpt_save_pre', 'comment_save_pre', 'pre_comment_content' ) as $filter ) {
	add_filter( $filter, 'convert_invalid_entities' );
	add_filter( $filter, 'balanceTags', 50 );
}

// Format strings for display.
foreach ( array( 'comment_author', 'term_name', 'link_name', 'link_description', 'link_notes', 'bloginfo', 'wp_title', 'widget_title' ) as $filter ) {
	add_filter( $filter, 'wptexturize' );
	add_filter( $filter, 'convert_chars' );
	add_filter( $filter, 'esc_html' );
}

// Format WordPress
foreach ( array( 'the_content', 'the_title', 'wp_title' ) as $filter ) {
	add_filter( $filter, 'capital_P_dangit', 11 );
}

add_filter( 'comment_text', 'capital_P_dangit', 31 );

// Format titles.
foreach ( array( 'single_post_title', 'single_cat_title', 'single_tag_title', 'single_month_title', 'nav_menu_attr_ttile', 'nav_menu_description' ) as $filter ) {
	add_filter( $filter, 'wptexturize' );
	add_filter( $filter, 'strip_tags' );
}

// Format text area for display.
foreach ( array( 'term_description', 'get_the_post_type_description' ) as $filter ) {
	add_filter( $filter, 'wptexturize' );
	add_filter( $filter, 'convert_chars' );
	add_filter( $filter, 'wpautop' );
	add_filter( $filter, 'shortcode_unautop' );
}

// Format for RSS
add_filter( 'term_name_rss', 'convert_chars' );

// Pre save hierarchy
add_filter( 'wp_insert_post_parent', 'wp_check_post_hierarchy_for_loops', 10, 2 );
add_filter( 'wp_update_term_parent', 'wp_check_term_hierarchy_for_loops', 10, 3 );

// Display filters
add_filter( 'the_title', 'wptexturize' );
add_filter( 'the_title', 'convert_chars' );
add_filter( 'the_title', 'trim' );

add_filter( 'the_content', 'wptexturize' );
add_filter( 'the_content', 'convert_smilies', 20 );
add_filter( 'the_content', 'wpautop' );
add_filter( 'the_content', 'shortcode_unautop' );
add_filter( 'the_content', 'prepend_attachment' );
/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * @NOW 004: wp-includes/default-filters.php
 * ......->: wp-includes/post-template.php: prepend_attachment( string $content )
 */
