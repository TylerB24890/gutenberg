<?php
/**
 * PHP and WordPress configuration compatibility functions for the Gutenberg
 * editor plugin changes related to REST API.
 *
 * @package gutenberg
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Silence is golden.' );
}

/**
 * Registers the block patterns REST API routes.
 * Use a -1 priority to ensure that this action runs first.
 */
function gutenberg_register_rest_block_patterns_routes_6_7() {
	$block_patterns = new Gutenberg_REST_Block_Patterns_Controller_6_7();
	$block_patterns->register_routes();
}
add_action( 'rest_api_init', 'gutenberg_register_rest_block_patterns_routes_6_7', -1 );
