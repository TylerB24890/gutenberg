<?php
/**
 * Theme, template, and stylesheet functions.
 *
 * @package Gutenberg
 * @subpackage Theme
 */

/**
 * Gets a Gutenberg_Theme object for a theme.
 *
 * @global array $wp_theme_directories
 *
 * @param string $stylesheet Optional. Directory name for the theme. Defaults to active theme.
 * @param string $theme_root Optional. Absolute path of the theme root to look in.
 *                           If not specified, get_raw_theme_root() is used to calculate
 *                           the theme root for the $stylesheet provided (or active theme).
 * @return Gutenberg_Theme Theme object. Be sure to check the object's exists() method
 *                  if you need to confirm the theme's existence.
 */
function gutenberg_get_theme( $stylesheet = '', $theme_root = '' ) {
	global $wp_theme_directories;

	if ( empty( $stylesheet ) ) {
		$stylesheet = get_stylesheet();
	}

	if ( empty( $theme_root ) ) {
		$theme_root = get_raw_theme_root( $stylesheet );
		if ( false === $theme_root ) {
			$theme_root = WP_CONTENT_DIR . '/themes';
		} elseif ( ! in_array( $theme_root, (array) $wp_theme_directories, true ) ) {
			$theme_root = WP_CONTENT_DIR . $theme_root;
		}
	}

	return new Gutenberg_Theme( $stylesheet, $theme_root );
}
