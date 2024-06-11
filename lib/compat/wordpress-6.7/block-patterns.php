<?php
/**
 * Register the block patterns and block patterns categories
 *
 * @package Gutenberg
 * @subpackage Editor
 */


/**
 * Register any patterns that the active theme may provide under its
 * `./patterns/` directory.
 *
 * This function is an override that uses `gutenberg_get_theme()` to get the theme object.
 * `gutenberg_get_theme()` will return the Gutenberg_Theme object instead of WP_Theme.
 *
 * @since 6.0.0
 * @since 6.1.0 The `postTypes` property was added.
 * @since 6.2.0 The `templateTypes` property was added.
 * @since 6.4.0 Uses the `WP_Theme::get_block_patterns` method.
 * @since 6.7.0 Add the `synced` property to the block pattern registry.
 * @access private
 */
function register_theme_block_patterns() {

	/*
	 * During the bootstrap process, a check for active and valid themes is run.
	 * If no themes are returned, the theme's functions.php file will not be loaded,
	 * which can lead to errors if patterns expect some variables or constants to
	 * already be set at this point, so bail early if that is the case.
	 */
	if ( empty( wp_get_active_and_valid_themes() ) ) {
		return;
	}

	/*
	 * Register patterns for the active theme. If the theme is a child theme,
	 * let it override any patterns from the parent theme that shares the same slug.
	 */
	$themes   = array();
	$theme    = gutenberg_get_theme();
	$themes[] = $theme;
	if ( $theme->parent() ) {
		$themes[] = $theme->parent();
	}
	$registry = WP_Block_Patterns_Registry::get_instance();

	foreach ( $themes as $theme ) {
		$patterns    = $theme->get_block_patterns();
		$dirpath     = $theme->get_stylesheet_directory() . '/patterns/';
		$text_domain = $theme->get( 'TextDomain' );

		foreach ( $patterns as $file => $pattern_data ) {
			if ( $registry->is_registered( $pattern_data['slug'] ) ) {
				continue;
			}

			$file_path = $dirpath . $file;

			if ( ! file_exists( $file_path ) ) {
				_doing_it_wrong(
					__FUNCTION__,
					sprintf(
						/* translators: %s: file name. */
						__( 'Could not register file "%s" as a block pattern as the file does not exist.' ),
						$file
					),
					'6.4.0'
				);
				$theme->delete_pattern_cache();
				continue;
			}

			$pattern_data['filePath'] = $file_path;

			// Translate the pattern metadata.
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText,WordPress.WP.I18n.NonSingularStringLiteralDomain,WordPress.WP.I18n.LowLevelTranslationFunction
			$pattern_data['title'] = translate_with_gettext_context( $pattern_data['title'], 'Pattern title', $text_domain );
			if ( ! empty( $pattern_data['description'] ) ) {
				// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText,WordPress.WP.I18n.NonSingularStringLiteralDomain,WordPress.WP.I18n.LowLevelTranslationFunction
				$pattern_data['description'] = translate_with_gettext_context( $pattern_data['description'], 'Pattern description', $text_domain );
			}

			register_block_pattern( $pattern_data['slug'], $pattern_data );
		}
	}
}
add_action( 'init', 'register_theme_block_patterns', -1 );
