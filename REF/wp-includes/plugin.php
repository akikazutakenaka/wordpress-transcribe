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

/**
 * Call the functions added to a filter hook.
 *
 * The callback functions attached to filter hook $tag are invoked by calling this function.
 * This function can be used to create a new filter hook by simply calling this function with the name of the new hook specified using the $tag parameter.
 *
 * The function allows for additional arguments to be added and passed to hooks.
 *
 *     // Our filter callback funtion
 *     function example_callback( $string, $arg1, $arg2 )
 *     {
 *         // (maybe) modify $string
 *         return $string;
 *     }
 *     add_filter( 'example_filter', 'example_callback', 10, 3 );
 *
 *     /**
 *      * Apply the filters by calling the 'example_callback' function we "hooked" to 'example_filter' using the add_filter() function above.
 *      * - 'example_filter' is the filter hook $tag
 *      * - 'filter me' is the value being filtered
 *      * - $arg1 and $arg2 are the additional arguments passed to the callback.
 *     $value = apply_filters( 'example_filter', 'filter me', $arg1, $arg2 );
 *
 * @since  0.71
 * @global array $wp_filter         Stores all of the filters.
 * @global array $wp_current_filter Stores the list of current filters with the current one last.
 *
 * @param  string $tag      The name of the filter hook.
 * @param  mixed  $value    The value on which the filters hooked to `$tag` are applied on.
 * @param  mixed  $var, ... Additional variables passed to the functions hooked to `$tag`.
 * @return mixed  The filtered value after all hooked functions are applied to it.
 */
function apply_filters( $tag, $value )
{
	global $wp_filter, $wp_current_filter;
	$args = [];

	// Do 'all' actions first.
	if ( isset( $wp_filter['all'] ) ) {
		$wp_current_filter[] = $tag;
		$args = func_get_args();
		_wp_call_all_hook( $args );
	}

	if ( ! isset( $wp_filter[$tag] ) ) {
		if ( isset( $wp_filter['all'] ) )
			array_pop( $wp_current_filter );

		return $value;
	}

	if ( ! isset( $wp_filter['all'] ) )
		$wp_current_filter[] = $tag;

	if ( empty( $args ) )
		$args = func_get_args();

	// Don't pass the tag name to WP_Hook
	array_shift( $args );

	$filtered = $wp_filter[$tag]->apply_filters( $value, $args );
	// @NOW 007
}

/**
 * Retrieve the number of times an action is fired.
 *
 * @since  2.1.0
 * @global array $wp_action Increments the amount of times action was triggered.
 *
 * @param  string $tag The name of the action hook.
 * @return int    The number of times action hook $tag is fired.
 */
function did_action( $tag )
{
	global $wp_actions;

	if ( ! isset( $wp_actions[$tag] ) )
		return 0;

	return $wp_actions[$tag];
}

/**
 * Call the 'all' hook, which will process the functions hooked into it.
 *
 * The 'all' hook passes all of the arguments or parameters that were used for the hook, which this function was called for.
 *
 * This function is used internally for apply_filters(), do_action(), and do_action_ref_array() and is not meant to be used from outside those functions.
 * This function does not check for the existence of the all hook, so it will fail unless the all hook exists prior to this function call.
 *
 * @since  2.5.0
 * @access private
 * @global array $wp_filter Stores all of the filters.
 *
 * @param array $args The collected parameters from the hook that was called.
 */
function _wp_call_all_hook( $args )
{
	global $wp_filter;
	$wp_filter['all']->do_all_hook( $args );
}

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
