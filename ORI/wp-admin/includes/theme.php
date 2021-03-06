<?php
/**
 * WordPress Theme Administration API
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Remove a theme
 *
 * @since 2.8.0
 *
 * @global WP_Filesystem_Base $wp_filesystem Subclass
 *
 * @param string $stylesheet Stylesheet of the theme to delete
 * @param string $redirect Redirect to page when complete.
 * @return void|bool|WP_Error When void, echoes content.
 */
function delete_theme($stylesheet, $redirect = '') {
	global $wp_filesystem;

	if ( empty($stylesheet) )
		return false;

	if ( empty( $redirect ) ) {
		$redirect = wp_nonce_url('themes.php?action=delete&stylesheet=' . urlencode( $stylesheet ), 'delete-theme_' . $stylesheet);
	}

	ob_start();
	$credentials = request_filesystem_credentials( $redirect );
	$data = ob_get_clean();

	if ( false === $credentials ) {
		if ( ! empty( $data ) ){
			include_once( ABSPATH . 'wp-admin/admin-header.php');
			echo $data;
			include( ABSPATH . 'wp-admin/admin-footer.php');
			exit;
		}
		return;
	}

	if ( ! WP_Filesystem( $credentials ) ) {
		ob_start();
		request_filesystem_credentials( $redirect, '', true ); // Failed to connect, Error and request again.
		$data = ob_get_clean();

		if ( ! empty($data) ) {
			include_once( ABSPATH . 'wp-admin/admin-header.php');
			echo $data;
			include( ABSPATH . 'wp-admin/admin-footer.php');
			exit;
		}
		return;
	}

	if ( ! is_object($wp_filesystem) )
		return new WP_Error('fs_unavailable', __('Could not access filesystem.'));

	if ( is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->get_error_code() )
		return new WP_Error('fs_error', __('Filesystem error.'), $wp_filesystem->errors);

	// Get the base plugin folder.
	$themes_dir = $wp_filesystem->wp_themes_dir();
	if ( empty( $themes_dir ) ) {
		return new WP_Error( 'fs_no_themes_dir', __( 'Unable to locate WordPress theme directory.' ) );
	}

	$themes_dir = trailingslashit( $themes_dir );
	$theme_dir = trailingslashit( $themes_dir . $stylesheet );
	$deleted = $wp_filesystem->delete( $theme_dir, true );

	if ( ! $deleted ) {
		return new WP_Error( 'could_not_remove_theme', sprintf( __( 'Could not fully remove the theme %s.' ), $stylesheet ) );
	}

	$theme_translations = wp_get_installed_translations( 'themes' );

	// Remove language files, silently.
	if ( ! empty( $theme_translations[ $stylesheet ] ) ) {
		$translations = $theme_translations[ $stylesheet ];

		foreach ( $translations as $translation => $data ) {
			$wp_filesystem->delete( WP_LANG_DIR . '/themes/' . $stylesheet . '-' . $translation . '.po' );
			$wp_filesystem->delete( WP_LANG_DIR . '/themes/' . $stylesheet . '-' . $translation . '.mo' );
		}
	}

	// Remove the theme from allowed themes on the network.
	if ( is_multisite() ) {
		WP_Theme::network_disable_theme( $stylesheet );
	}

	// Force refresh of theme update information.
	delete_site_transient( 'update_themes' );

	return true;
}

/**
 * Get the Page Templates available in this theme
 *
 * @since 1.5.0
 * @since 4.7.0 Added the `$post_type` parameter.
 *
 * @param WP_Post|null $post      Optional. The post being edited, provided for context.
 * @param string       $post_type Optional. Post type to get the templates for. Default 'page'.
 * @return array Key is the template name, value is the filename of the template
 */
function get_page_templates( $post = null, $post_type = 'page' ) {
	return array_flip( wp_get_theme()->get_page_templates( $post, $post_type ) );
}

/**
 * Tidies a filename for url display by the theme editor.
 *
 * @since 2.9.0
 * @access private
 *
 * @param string $fullpath Full path to the theme file
 * @param string $containingfolder Path of the theme parent folder
 * @return string
 */
function _get_template_edit_filename($fullpath, $containingfolder) {
	return str_replace(dirname(dirname( $containingfolder )) , '', $fullpath);
}

/**
 * Check if there is an update for a theme available.
 *
 * Will display link, if there is an update available.
 *
 * @since 2.7.0
 * @see get_theme_update_available()
 *
 * @param WP_Theme $theme Theme data object.
 */
function theme_update_available( $theme ) {
	echo get_theme_update_available( $theme );
}

/**
 * Retrieve the update link if there is a theme update available.
 *
 * Will return a link if there is an update available.
 *
 * @since 3.8.0
 *
 * @staticvar object $themes_update
 *
 * @param WP_Theme $theme WP_Theme object.
 * @return false|string HTML for the update link, or false if invalid info was passed.
 */
function get_theme_update_available( $theme ) {
	static $themes_update = null;

	if ( !current_user_can('update_themes' ) )
		return false;

	if ( !isset($themes_update) )
		$themes_update = get_site_transient('update_themes');

	if ( ! ( $theme instanceof WP_Theme ) ) {
		return false;
	}

	$stylesheet = $theme->get_stylesheet();

	$html = '';

	if ( isset($themes_update->response[ $stylesheet ]) ) {
		$update = $themes_update->response[ $stylesheet ];
		$theme_name = $theme->display('Name');
		$details_url = add_query_arg(array('TB_iframe' => 'true', 'width' => 1024, 'height' => 800), $update['url']); //Theme browser inside WP? replace this, Also, theme preview JS will override this on the available list.
		$update_url = wp_nonce_url( admin_url( 'update.php?action=upgrade-theme&amp;theme=' . urlencode( $stylesheet ) ), 'upgrade-theme_' . $stylesheet );

		if ( !is_multisite() ) {
			if ( ! current_user_can('update_themes') ) {
				/* translators: 1: theme name, 2: theme details URL, 3: additional link attributes, 4: version number */
				$html = sprintf( '<p><strong>' . __( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>.' ) . '</strong></p>',
					$theme_name,
					esc_url( $details_url ),
					sprintf( 'class="thickbox open-plugin-details-modal" aria-label="%s"',
						/* translators: 1: theme name, 2: version number */
						esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $theme_name, $update['new_version'] ) )
					),
					$update['new_version']
				);
			} elseif ( empty( $update['package'] ) ) {
				/* translators: 1: theme name, 2: theme details URL, 3: additional link attributes, 4: version number */
				$html = sprintf( '<p><strong>' . __( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>. <em>Automatic update is unavailable for this theme.</em>' ) . '</strong></p>',
					$theme_name,
					esc_url( $details_url ),
					sprintf( 'class="thickbox open-plugin-details-modal" aria-label="%s"',
						/* translators: 1: theme name, 2: version number */
						esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $theme_name, $update['new_version'] ) )
					),
					$update['new_version']
				);
			} else {
				/* translators: 1: theme name, 2: theme details URL, 3: additional link attributes, 4: version number, 5: update URL, 6: additional link attributes */
				$html = sprintf( '<p><strong>' . __( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a> or <a href="%5$s" %6$s>update now</a>.' ) . '</strong></p>',
					$theme_name,
					esc_url( $details_url ),
					sprintf( 'class="thickbox open-plugin-details-modal" aria-label="%s"',
						/* translators: 1: theme name, 2: version number */
						esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $theme_name, $update['new_version'] ) )
					),
					$update['new_version'],
					$update_url,
					sprintf( 'aria-label="%s" id="update-theme" data-slug="%s"',
						/* translators: %s: theme name */
						esc_attr( sprintf( __( 'Update %s now' ), $theme_name ) ),
						$stylesheet
					)
				);
			}
		}
	}

	return $html;
}

// refactored. function get_theme_feature_list( $api = true ) {}
// refactored. function themes_api( $action, $args = array() ) {}

/**
 * Prepare themes for JavaScript.
 *
 * @since 3.8.0
 *
 * @param array $themes Optional. Array of WP_Theme objects to prepare.
 *                      Defaults to all allowed themes.
 *
 * @return array An associative array of theme data, sorted by name.
 */
function wp_prepare_themes_for_js( $themes = null ) {
	$current_theme = get_stylesheet();

	/**
	 * Filters theme data before it is prepared for JavaScript.
	 *
	 * Passing a non-empty array will result in wp_prepare_themes_for_js() returning
	 * early with that value instead.
	 *
	 * @since 4.2.0
	 *
	 * @param array      $prepared_themes An associative array of theme data. Default empty array.
	 * @param null|array $themes          An array of WP_Theme objects to prepare, if any.
	 * @param string     $current_theme   The current theme slug.
	 */
	$prepared_themes = (array) apply_filters( 'pre_prepare_themes_for_js', array(), $themes, $current_theme );

	if ( ! empty( $prepared_themes ) ) {
		return $prepared_themes;
	}

	// Make sure the current theme is listed first.
	$prepared_themes[ $current_theme ] = array();

	if ( null === $themes ) {
		$themes = wp_get_themes( array( 'allowed' => true ) );
		if ( ! isset( $themes[ $current_theme ] ) ) {
			$themes[ $current_theme ] = wp_get_theme();
		}
	}

	$updates = array();
	if ( current_user_can( 'update_themes' ) ) {
		$updates_transient = get_site_transient( 'update_themes' );
		if ( isset( $updates_transient->response ) ) {
			$updates = $updates_transient->response;
		}
	}

	WP_Theme::sort_by_name( $themes );

	$parents = array();

	foreach ( $themes as $theme ) {
		$slug = $theme->get_stylesheet();
		$encoded_slug = urlencode( $slug );

		$parent = false;
		if ( $theme->parent() ) {
			$parent = $theme->parent();
			$parents[ $slug ] = $parent->get_stylesheet();
			$parent = $parent->display( 'Name' );
		}

		$customize_action = null;
		if ( current_user_can( 'edit_theme_options' ) && current_user_can( 'customize' ) ) {
			$customize_action = esc_url( add_query_arg(
				array(
					'return' => urlencode( esc_url_raw( remove_query_arg( wp_removable_query_args(), wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) ),
				),
				wp_customize_url( $slug )
			) );
		}

		$prepared_themes[ $slug ] = array(
			'id'           => $slug,
			'name'         => $theme->display( 'Name' ),
			'screenshot'   => array( $theme->get_screenshot() ), // @todo multiple
			'description'  => $theme->display( 'Description' ),
			'author'       => $theme->display( 'Author', false, true ),
			'authorAndUri' => $theme->display( 'Author' ),
			'version'      => $theme->display( 'Version' ),
			'tags'         => $theme->display( 'Tags' ),
			'parent'       => $parent,
			'active'       => $slug === $current_theme,
			'hasUpdate'    => isset( $updates[ $slug ] ),
			'hasPackage'   => isset( $updates[ $slug ] ) && ! empty( $updates[ $slug ][ 'package' ] ),
			'update'       => get_theme_update_available( $theme ),
			'actions'      => array(
				'activate' => current_user_can( 'switch_themes' ) ? wp_nonce_url( admin_url( 'themes.php?action=activate&amp;stylesheet=' . $encoded_slug ), 'switch-theme_' . $slug ) : null,
				'customize' => $customize_action,
				'delete'   => current_user_can( 'delete_themes' ) ? wp_nonce_url( admin_url( 'themes.php?action=delete&amp;stylesheet=' . $encoded_slug ), 'delete-theme_' . $slug ) : null,
			),
		);
	}

	// Remove 'delete' action if theme has an active child
	if ( ! empty( $parents ) && array_key_exists( $current_theme, $parents ) ) {
		unset( $prepared_themes[ $parents[ $current_theme ] ]['actions']['delete'] );
	}

	/**
	 * Filters the themes prepared for JavaScript, for themes.php.
	 *
	 * Could be useful for changing the order, which is by name by default.
	 *
	 * @since 3.8.0
	 *
	 * @param array $prepared_themes Array of themes.
	 */
	$prepared_themes = apply_filters( 'wp_prepare_themes_for_js', $prepared_themes );
	$prepared_themes = array_values( $prepared_themes );
	return array_filter( $prepared_themes );
}

/**
 * Print JS templates for the theme-browsing UI in the Customizer.
 *
 * @since 4.2.0
 */
function customize_themes_print_templates() {
	?>
	<script type="text/html" id="tmpl-customize-themes-details-view">
		<div class="theme-backdrop"></div>
		<div class="theme-wrap wp-clearfix" role="document">
			<div class="theme-header">
				<button type="button" class="left dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Show previous theme' ); ?></span></button>
				<button type="button" class="right dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Show next theme' ); ?></span></button>
				<button type="button" class="close dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Close details dialog' ); ?></span></button>
			</div>
			<div class="theme-about wp-clearfix">
				<div class="theme-screenshots">
				<# if ( data.screenshot && data.screenshot[0] ) { #>
					<div class="screenshot"><img src="{{ data.screenshot[0] }}" alt="" /></div>
				<# } else { #>
					<div class="screenshot blank"></div>
				<# } #>
				</div>

				<div class="theme-info">
					<# if ( data.active ) { #>
						<span class="current-label"><?php _e( 'Current Theme' ); ?></span>
					<# } #>
					<h2 class="theme-name">{{{ data.name }}}<span class="theme-version"><?php printf( __( 'Version: %s' ), '{{ data.version }}' ); ?></span></h2>
					<h3 class="theme-author"><?php printf( __( 'By %s' ), '{{{ data.authorAndUri }}}' ); ?></h3>

					<# if ( data.stars && 0 != data.num_ratings ) { #>
						<div class="theme-rating">
							{{{ data.stars }}}
							<span class="num-ratings">
								<?php
								/* translators: %s: number of ratings */
								echo sprintf( __( '(%s ratings)' ), '{{ data.num_ratings }}' );
								?>
							</span>
						</div>
					<# } #>

					<# if ( data.hasUpdate ) { #>
						<div class="notice notice-warning notice-alt notice-large" data-slug="{{ data.id }}">
							<h3 class="notice-title"><?php _e( 'Update Available' ); ?></h3>
							{{{ data.update }}}
						</div>
					<# } #>

					<# if ( data.parent ) { #>
						<p class="parent-theme"><?php printf( __( 'This is a child theme of %s.' ), '<strong>{{{ data.parent }}}</strong>' ); ?></p>
					<# } #>

					<p class="theme-description">{{{ data.description }}}</p>

					<# if ( data.tags ) { #>
						<p class="theme-tags"><span><?php _e( 'Tags:' ); ?></span> {{{ data.tags }}}</p>
					<# } #>
				</div>
			</div>

			<div class="theme-actions">
				<# if ( data.active ) { #>
					<button type="button" class="button button-primary customize-theme"><?php _e( 'Customize' ); ?></button>
				<# } else if ( 'installed' === data.type ) { #>
					<?php if ( current_user_can( 'delete_themes' ) ) { ?>
						<# if ( data.actions && data.actions['delete'] ) { #>
							<a href="{{{ data.actions['delete'] }}}" data-slug="{{ data.id }}" class="button button-secondary delete-theme"><?php _e( 'Delete' ); ?></a>
						<# } #>
					<?php } ?>
					<button type="button" class="button button-primary preview-theme" data-slug="{{ data.id }}"><?php _e( 'Live Preview' ); ?></button>
				<# } else { #>
					<button type="button" class="button theme-install" data-slug="{{ data.id }}"><?php _e( 'Install' ); ?></button>
					<button type="button" class="button button-primary theme-install preview" data-slug="{{ data.id }}"><?php _e( 'Install &amp; Preview' ); ?></button>
				<# } #>
			</div>
		</div>
	</script>
	<?php
}
