<?php
/**
 * The plugin API is located in this file, which allows for creating actions and filters and hooking functions, and methods.
 * The functions or methods will then be run when the action or filter is called.
 *
 * The API callback examples reference functions, but can be methods of classes.
 * To hook methods, you'll need to pass an array one of two ways.
 *
 * Any of the syntaxes explained in the PHP documentation for the {@link https://secure.php.net/manual/en/language.pseudo-types.php#language.types.callback 'callback'} type are valid.
 *
 * Also see the {@link https://codex.wordpress.org/Plugin_API Plugin API} for more information and examples on how to use a lot of these functions.
 *
 * This file should have no external dependencies.
 *
 * @package    WordPress
 * @subpackage Plugin
 * @since      1.5.0
 */

// Initialize the filter globals.
require( dirname( __FILE__ ) . '/class-wp-hook.php' );

/**
 * @var WP_Hook[] $wp_filter
 */
global $wp_filter, $wp_actions, $wp_current_filter;

$wp_filter = $wp_filter
	? WP_Hook::build_preinitialized_hooks( $wp_filter )
	: [];

if ( ! isset( $wp_actions ) )
	$wp_actions = [];

if ( ! isset( $wp_current_filter ) )
	$wp_current_filter = [];

// @NOW 006

/**
 * Build Unique ID for storage and retrieval.
 *
 * The old way to serialize the callback caused issues and this function is the solution.
 * It works by checking for objects and creating a new property in the class to keep track of the object and new objects of the same class that need to be added.
 *
 * It also allows for the removal of actions and filters for objects after they change class properties.
 * It is possible to include the property $wp_filter_id in your class and set it to "null" or a number to bypass the workaround.
 * However this will prevent you from adding new classes and any new classes will overwrite the previous hook by the same class.
 *
 * Functions and static method callbacks are just returned as strings and shouldn't have any speed penalty.
 *
 * @link   https://core.trac.wordpress.org/ticket/3875
 * @since  2.2.3
 * @access private
 *
 * @global    array $wp_filter       Storage for all of the filters and actions.
 * @staticvar int   $filter_id_count
 *
 * @param     string       $tag      Used in counting how many hooks were applied.
 * @param     callable     $function Used for creating unique id.
 * @param     int|bool     $priority Used in counting how many hooks were applied.
 *                                   If === false and $function is an object reference, we return the unique id only if it already has one, false otherwise.
 * @return    string|false Unique ID for usage as array key or false if $priority === false and $function is an object reference, and it does not already have a unique id.
 */
function _wp_filter_build_unique_id( $tag, $function, $priority )
{
	global $wp_filter;
	static $filter_id_count = 0;

	if ( is_string( $function ) )
		return $function;

	$function = is_object( $function )
		? [$function, ''] // Closures are currently implemented as objects
		: ( array ) $function;

	if ( is_object( $function[0] ) ) {
		// Object Class Calling
		if ( function_exists( 'spl_object_hash' ) )
			return spl_object_hash( $function[0] ) . $function[1];
		else {
			$obj_idx = get_class( $function[0] ) . $function[1];

			if ( ! isset( $function[0]->wp_filter_id ) ) {
				if ( FALSE === $priority )
					return FALSE;

				$obj_idx .= isset( $wp_filter[$tag][$priority] ) ? count( ( array ) $wp_filter[$tag][$priority] ) : $filter_id_count;
				$function[0]->wp_filter_id = $filter_id_count;
				++$filter_id_count;
			} else
				$obj_idx .= $function[0]->wp_filter_id;

			return $obj_idx;
		}
	} elseif ( is_string( $function[0] ) )
		// Static Calling
		return $function[0] . '::' . $function[1];
}
