<?php
/**
 * Dependencies API: Scripts functions
 *
 * @since      2.6.0
 * @package    WordPress
 * @subpackage Dependencies
 */

/**
 * Initialize $wp_scripts if it has not been set.
 *
 * @global WP_Scripts $wp_scripts
 * @since  4.2.0
 *
 * @return WP_Scripts WP_Scripts instance.
 */
function wp_scripts()
{
	global $wp_scripts;

	if ( ! ( $wp_scripts instanceof WP_Scripts ) ) {
		$wp_scripts = new WP_Scripts();
	}

	return $wp_scripts;
}

/**
 * Helper function to output a _doing_it_wrong message when applicable.
 *
 * @ignore
 * @since  4.2.0
 *
 * @param string $function Function name.
 */
function _wp_scripts_maybe_doing_it_wrong( $function )
{
	if ( did_action( 'init' ) || did_action( 'admin_enqueue_scripts' ) || did_action( 'wp_enqueue_scripts' ) || did_action( 'login_enqueue_scripts' ) ) {
		return;
	}

	_doing_it_wrong( $function, sprintf( __( 'Scripts and styles should not be registered or enqueued until the %1$s, %2$s, or %3$s hooks.' ), '<code>wp_enqueue_scripts</code>', '<code>admin_enqueue_scripts</code>', '<code>login_enqueue_scripts</code>' ), '3.3.0' );
}

/**
 * Enqueue a script.
 *
 * Registers the script if $src provided (does NOT overwrite), and enqueues it.
 *
 * @see   WP_Dependencies::add()
 * @see   WP_Dependencies::add_data()
 * @see   WP_Dependencies::enqueue()
 * @since 2.1.0
 *
 * @param string           $handle    Name of the script.
 *                                    Should be unique.
 * @param string           $src       Full URL of the script, or path of the script relative to the WordPress root directory.
 *                                    Default empty.
 * @param array            $deps      Optional.
 *                                    An array of registered script handles this script depends on.
 *                                    Default empty array.
 * @param string|bool|null $ver       Optional.
 *                                    String specifying script version number, if it has one, which is added to the URL as a query string for cache busting purposes.
 *                                    If version is set to false, a version number is automatically added equal to current installed WordPress version.
 *                                    If set to null, no version is added.
 * @param bool             $in_footer Optional.
 *                                    Whether to enqueue the script before </body> instead of in the <head>.
 *                                    Default 'false'.
 */
function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = FALSE, $in_footer = FALSE )
{
	$wp_scripts = wp_scripts();
	_wp_scripts_maybe_doing_it_wrong( __FUNCTION__ );

	if ( $src || $in_footer ) {
		$_handle = explode( '?', $handle );

		if ( $src ) {
			$wp_scripts->add( $_handle[0], $src, $deps, $ver );
		}

		if ( $in_footer ) {
			$wp_scripts->add_data( $_handle[0], 'group', 1 );
/**
 * <-......: wp-blog-header.php
 * <-......: wp-load.php
 * <-......: wp-settings.php
 * <-......: wp-includes/default-filters.php
 * <-......: wp-includes/post-template.php: prepend_attachment( string $content )
 * <-......: wp-includes/media.php: wp_video_shortcode( array $attr [, string $content = ''] )
 * @NOW 007: wp-includes/functions.wp-scripts.php: wp_enqueue_script( string $handle [, string $src = '' [, array $deps = array() [, string|bool|null $ver = FALSE [, bool $in_footer = FALSE]]]] )
 * ......->: wp-includes/class-wp-dependency.php: _WP_Dependency::add_data( string $name, mixed $data )
 */
		}
	}
}
