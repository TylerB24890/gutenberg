<?php
/**
 * Temporary compatibility code for new functionalities/changes related to block bindings APIs present in Gutenberg.
 *
 * @package gutenberg
 */

/**
 * Adds the block bindings sources registered in the server to the editor settings.
 *
 * This allows them to be bootstrapped in the editor.
 *
 * @param array $settings The block editor settings from the `block_editor_settings_all` filter.
 * @return array The editor settings including the block bindings sources.
 */
function gutenberg_add_server_block_bindings_sources_to_editor_settings( $editor_settings ) {
	// Check if the sources are already exposed in the editor settings.
	if ( isset( $editor_settings['blockBindingsSources'] ) ) {
		return $editor_settings;
	}

	$registered_block_bindings_sources = get_all_registered_block_bindings_sources();
	if ( ! empty( $registered_block_bindings_sources ) ) {
		// Initialize array.
		$editor_settings['blockBindingsSources'] = array();
		foreach ( $registered_block_bindings_sources as $source_name => $source_properties ) {
			// Add source with the label to editor settings.
			$editor_settings['blockBindingsSources'][ $source_name ] = array(
				'label' => $source_properties->label,
			);
			// Add `usesContext` property if exists.
			if ( ! empty( $source_properties->uses_context ) ) {
				$editor_settings['blockBindingsSources'][ $source_name ]['usesContext'] = $source_properties->uses_context;
			}
		}
	}
	return $editor_settings;
}

add_filter( 'block_editor_settings_all', 'gutenberg_add_server_block_bindings_sources_to_editor_settings', 10 );

/**
 * Get all bindable attributes for all registered block types.
 *
 * @return array Associative array of block type names and their bindable attribute names.
 *               If a block type has no bindable attributes, it will not be included in the array.
 */
function gutenberg_get_all_bindable_attributes() {
	$block_types_registry   = Gutenberg_Block_Type_Registry::get_instance();
	$registered_block_types = $block_types_registry->get_all_registered();
	$bindable_attributes    = array();

	foreach ( $registered_block_types as $block_type ) {
		$bindable_atts = $block_type->get_block_bindable_attributes();

		if ( ! empty( $bindable_atts ) ) {
			$bindable_attributes[ $block_type->name ] = array_keys( $bindable_atts );
		}
	}

	return $bindable_attributes;
}
