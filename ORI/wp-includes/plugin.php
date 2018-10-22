<?php
/**
 * The plugin API is located in this file, which allows for creating actions
 * and filters and hooking functions, and methods. The functions or methods will
 * then be run when the action or filter is called.
 *
 * The API callback examples reference functions, but can be methods of classes.
 * To hook methods, you'll need to pass an array one of two ways.
 *
 * Any of the syntaxes explained in the PHP documentation for the
 * {@link https://secure.php.net/manual/en/language.pseudo-types.php#language.types.callback 'callback'}
 * type are valid.
 *
 * Also see the {@link https://codex.wordpress.org/Plugin_API Plugin API} for
 * more information and examples on how to use a lot of these functions.
 *
 * This file should have no external dependencies.
 *
 * @package WordPress
 * @subpackage Plugin
 * @since 1.5.0
 */

// Initialize the filter globals.
require( dirname( __FILE__ ) . '/class-wp-hook.php' );

/** @var WP_Hook[] $wp_filter */
global $wp_filter, $wp_actions, $wp_current_filter;

if ( $wp_filter ) {
	$wp_filter = WP_Hook::build_preinitialized_hooks( $wp_filter );
} else {
	$wp_filter = array();
}

if ( ! isset( $wp_actions ) )
	$wp_actions = array();

if ( ! isset( $wp_current_filter ) )
	$wp_current_filter = array();

// refactored. function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {}
// :
// refactored. function remove_filter( $tag, $function_to_remove, $priority = 10 ) {}

/**
 * Remove all of the hooks from a filter.
 *
 * @since 2.7.0
 *
 * @global array $wp_filter  Stores all of the filters
 *
 * @param string   $tag      The filter to remove hooks from.
 * @param int|bool $priority Optional. The priority number to remove. Default false.
 * @return true True when finished.
 */
function remove_all_filters( $tag, $priority = false ) {
	global $wp_filter;

	if ( isset( $wp_filter[ $tag ]) ) {
		$wp_filter[ $tag ]->remove_all_filters( $priority );
		if ( ! $wp_filter[ $tag ]->has_filters() ) {
			unset( $wp_filter[ $tag ] );
		}
	}

	return true;
}

// refactored. function current_filter() {}

/**
 * Retrieve the name of the current action.
 *
 * @since 3.9.0
 *
 * @return string Hook name of the current action.
 */
function current_action() {
	return current_filter();
}

/**
 * Retrieve the name of a filter currently being processed.
 *
 * The function current_filter() only returns the most recent filter or action
 * being executed. did_action() returns true once the action is initially
 * processed.
 *
 * This function allows detection for any filter currently being
 * executed (despite not being the most recent filter to fire, in the case of
 * hooks called from hook callbacks) to be verified.
 *
 * @since 3.9.0
 *
 * @see current_filter()
 * @see did_action()
 * @global array $wp_current_filter Current filter.
 *
 * @param null|string $filter Optional. Filter to check. Defaults to null, which
 *                            checks if any filter is currently being run.
 * @return bool Whether the filter is currently in the stack.
 */
function doing_filter( $filter = null ) {
	global $wp_current_filter;

	if ( null === $filter ) {
		return ! empty( $wp_current_filter );
	}

	return in_array( $filter, $wp_current_filter );
}

/**
 * Retrieve the name of an action currently being processed.
 *
 * @since 3.9.0
 *
 * @param string|null $action Optional. Action to check. Defaults to null, which checks
 *                            if any action is currently being run.
 * @return bool Whether the action is currently in the stack.
 */
function doing_action( $action = null ) {
	return doing_filter( $action );
}

// refactored. function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {}
// :
// refactored. function do_action_ref_array($tag, $args) {}

/**
 * Check if any action has been registered for a hook.
 *
 * @since 2.5.0
 *
 * @see has_filter() has_action() is an alias of has_filter().
 *
 * @param string        $tag               The name of the action hook.
 * @param callable|bool $function_to_check Optional. The callback to check for. Default false.
 * @return bool|int If $function_to_check is omitted, returns boolean for whether the hook has
 *                  anything registered. When checking a specific function, the priority of that
 *                  hook is returned, or false if the function is not attached. When using the
 *                  $function_to_check argument, this function may return a non-boolean value
 *                  that evaluates to false (e.g.) 0, so use the === operator for testing the
 *                  return value.
 */
function has_action($tag, $function_to_check = false) {
	return has_filter($tag, $function_to_check);
}

// refactored. function remove_action( $tag, $function_to_remove, $priority = 10 ) {}

/**
 * Remove all of the hooks from an action.
 *
 * @since 2.7.0
 *
 * @param string   $tag      The action to remove hooks from.
 * @param int|bool $priority The priority number to remove them from. Default false.
 * @return true True when finished.
 */
function remove_all_actions($tag, $priority = false) {
	return remove_all_filters($tag, $priority);
}

/**
 * Fires functions attached to a deprecated filter hook.
 *
 * When a filter hook is deprecated, the apply_filters() call is replaced with
 * apply_filters_deprecated(), which triggers a deprecation notice and then fires
 * the original filter hook.
 *
 * Note: the value and extra arguments passed to the original apply_filters() call
 * must be passed here to `$args` as an array. For example:
 *
 *     // Old filter.
 *     return apply_filters( 'wpdocs_filter', $value, $extra_arg );
 *
 *     // Deprecated.
 *     return apply_filters_deprecated( 'wpdocs_filter', array( $value, $extra_arg ), '4.9', 'wpdocs_new_filter' );
 *
 * @since 4.6.0
 *
 * @see _deprecated_hook()
 *
 * @param string $tag         The name of the filter hook.
 * @param array  $args        Array of additional function arguments to be passed to apply_filters().
 * @param string $version     The version of WordPress that deprecated the hook.
 * @param string $replacement Optional. The hook that should have been used. Default false.
 * @param string $message     Optional. A message regarding the change. Default null.
 */
function apply_filters_deprecated( $tag, $args, $version, $replacement = false, $message = null ) {
	if ( ! has_filter( $tag ) ) {
		return $args[0];
	}

	_deprecated_hook( $tag, $version, $replacement, $message );

	return apply_filters_ref_array( $tag, $args );
}

/**
 * Fires functions attached to a deprecated action hook.
 *
 * When an action hook is deprecated, the do_action() call is replaced with
 * do_action_deprecated(), which triggers a deprecation notice and then fires
 * the original hook.
 *
 * @since 4.6.0
 *
 * @see _deprecated_hook()
 *
 * @param string $tag         The name of the action hook.
 * @param array  $args        Array of additional function arguments to be passed to do_action().
 * @param string $version     The version of WordPress that deprecated the hook.
 * @param string $replacement Optional. The hook that should have been used.
 * @param string $message     Optional. A message regarding the change.
 */
function do_action_deprecated( $tag, $args, $version, $replacement = false, $message = null ) {
	if ( ! has_action( $tag ) ) {
		return;
	}

	_deprecated_hook( $tag, $version, $replacement, $message );

	do_action_ref_array( $tag, $args );
}

//
// Functions for handling plugins.
//

// refactored. function plugin_basename( $file ) {}

/**
 * Register a plugin's real path.
 *
 * This is used in plugin_basename() to resolve symlinked paths.
 *
 * @since 3.9.0
 *
 * @see wp_normalize_path()
 *
 * @global array $wp_plugin_paths
 *
 * @staticvar string $wp_plugin_path
 * @staticvar string $wpmu_plugin_path
 *
 * @param string $file Known path to the file.
 * @return bool Whether the path was able to be registered.
 */
function wp_register_plugin_realpath( $file ) {
	global $wp_plugin_paths;

	// Normalize, but store as static to avoid recalculation of a constant value
	static $wp_plugin_path = null, $wpmu_plugin_path = null;
	if ( ! isset( $wp_plugin_path ) ) {
		$wp_plugin_path   = wp_normalize_path( WP_PLUGIN_DIR   );
		$wpmu_plugin_path = wp_normalize_path( WPMU_PLUGIN_DIR );
	}

	$plugin_path = wp_normalize_path( dirname( $file ) );
	$plugin_realpath = wp_normalize_path( dirname( realpath( $file ) ) );

	if ( $plugin_path === $wp_plugin_path || $plugin_path === $wpmu_plugin_path ) {
		return false;
	}

	if ( $plugin_path !== $plugin_realpath ) {
		$wp_plugin_paths[ $plugin_path ] = $plugin_realpath;
	}

	return true;
}

/**
 * Get the filesystem directory path (with trailing slash) for the plugin __FILE__ passed in.
 *
 * @since 2.8.0
 *
 * @param string $file The filename of the plugin (__FILE__).
 * @return string the filesystem path of the directory that contains the plugin.
 */
function plugin_dir_path( $file ) {
	return trailingslashit( dirname( $file ) );
}

/**
 * Get the URL directory path (with trailing slash) for the plugin __FILE__ passed in.
 *
 * @since 2.8.0
 *
 * @param string $file The filename of the plugin (__FILE__).
 * @return string the URL path of the directory that contains the plugin.
 */
function plugin_dir_url( $file ) {
	return trailingslashit( plugins_url( '', $file ) );
}

/**
 * Set the activation hook for a plugin.
 *
 * When a plugin is activated, the action 'activate_PLUGINNAME' hook is
 * called. In the name of this hook, PLUGINNAME is replaced with the name
 * of the plugin, including the optional subdirectory. For example, when the
 * plugin is located in wp-content/plugins/sampleplugin/sample.php, then
 * the name of this hook will become 'activate_sampleplugin/sample.php'.
 *
 * When the plugin consists of only one file and is (as by default) located at
 * wp-content/plugins/sample.php the name of this hook will be
 * 'activate_sample.php'.
 *
 * @since 2.0.0
 *
 * @param string   $file     The filename of the plugin including the path.
 * @param callable $function The function hooked to the 'activate_PLUGIN' action.
 */
function register_activation_hook($file, $function) {
	$file = plugin_basename($file);
	add_action('activate_' . $file, $function);
}

/**
 * Set the deactivation hook for a plugin.
 *
 * When a plugin is deactivated, the action 'deactivate_PLUGINNAME' hook is
 * called. In the name of this hook, PLUGINNAME is replaced with the name
 * of the plugin, including the optional subdirectory. For example, when the
 * plugin is located in wp-content/plugins/sampleplugin/sample.php, then
 * the name of this hook will become 'deactivate_sampleplugin/sample.php'.
 *
 * When the plugin consists of only one file and is (as by default) located at
 * wp-content/plugins/sample.php the name of this hook will be
 * 'deactivate_sample.php'.
 *
 * @since 2.0.0
 *
 * @param string   $file     The filename of the plugin including the path.
 * @param callable $function The function hooked to the 'deactivate_PLUGIN' action.
 */
function register_deactivation_hook($file, $function) {
	$file = plugin_basename($file);
	add_action('deactivate_' . $file, $function);
}

/**
 * Set the uninstallation hook for a plugin.
 *
 * Registers the uninstall hook that will be called when the user clicks on the
 * uninstall link that calls for the plugin to uninstall itself. The link won't
 * be active unless the plugin hooks into the action.
 *
 * The plugin should not run arbitrary code outside of functions, when
 * registering the uninstall hook. In order to run using the hook, the plugin
 * will have to be included, which means that any code laying outside of a
 * function will be run during the uninstallation process. The plugin should not
 * hinder the uninstallation process.
 *
 * If the plugin can not be written without running code within the plugin, then
 * the plugin should create a file named 'uninstall.php' in the base plugin
 * folder. This file will be called, if it exists, during the uninstallation process
 * bypassing the uninstall hook. The plugin, when using the 'uninstall.php'
 * should always check for the 'WP_UNINSTALL_PLUGIN' constant, before
 * executing.
 *
 * @since 2.7.0
 *
 * @param string   $file     Plugin file.
 * @param callable $callback The callback to run when the hook is called. Must be
 *                           a static method or function.
 */
function register_uninstall_hook( $file, $callback ) {
	if ( is_array( $callback ) && is_object( $callback[0] ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Only a static class method or function can be used in an uninstall hook.' ), '3.1.0' );
		return;
	}

	/*
	 * The option should not be autoloaded, because it is not needed in most
	 * cases. Emphasis should be put on using the 'uninstall.php' way of
	 * uninstalling the plugin.
	 */
	$uninstallable_plugins = (array) get_option('uninstall_plugins');
	$uninstallable_plugins[plugin_basename($file)] = $callback;

	update_option('uninstall_plugins', $uninstallable_plugins);
}

// refactored. function _wp_call_all_hook($args) {}
// refactored. function _wp_filter_build_unique_id($tag, $function, $priority) {}
