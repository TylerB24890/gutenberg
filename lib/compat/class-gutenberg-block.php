<?php
/**
 * Blocks API: Gutenberg_Block class
 *
 * @package WordPress
 * @since 5.5.0
 */

/**
 * Class representing a parsed instance of a block.
 *
 * @since 5.5.0
 * @property array $attributes
 */
#[AllowDynamicProperties]
class Gutenberg_Block extends WP_Block {

	/**
	 * The block type instance.
	 * Gutenberg override for WP_Block_Type.
	 *
	 * @var Gutenberg_Block_Type
	 */
	public $block_type;

	/**
	 * Processes the block bindings and updates the block attributes with the values from the sources.
	 *
	 * A block might contain bindings in its attributes. Bindings are mappings
	 * between an attribute of the block and a source. A "source" is a function
	 * registered with `register_block_bindings_source()` that defines how to
	 * retrieve a value from outside the block, e.g. from post meta.
	 *
	 * This function will process those bindings and update the block's attributes
	 * with the values coming from the bindings.
	 *
	 * ### Example
	 *
	 * The "bindings" property for an Image block might look like this:
	 *
	 * ```json
	 * {
	 *   "metadata": {
	 *     "bindings": {
	 *       "title": {
	 *         "source": "core/post-meta",
	 *         "args": { "key": "text_custom_field" }
	 *       },
	 *       "url": {
	 *         "source": "core/post-meta",
	 *         "args": { "key": "url_custom_field" }
	 *       }
	 *     }
	 *   }
	 * }
	 * ```
	 *
	 * The above example will replace the `title` and `url` attributes of the Image
	 * block with the values of the `text_custom_field` and `url_custom_field` post meta.
	 *
	 * @since 6.5.0
	 * @since 6.6.0 Handle the `__default` attribute for pattern overrides.
	 *
	 * @return array The computed block attributes for the provided block bindings.
	 */
	private function process_block_bindings() {
		$parsed_block               = $this->parsed_block;
		$computed_attributes        = array();
		$bindable_attributes        = $this->block_type->get_block_bindable_attributes();
		$supported_block_attributes = array(
			'core/paragraph' => array( 'content' ),
			'core/heading'   => array( 'content' ),
			'core/image'     => array( 'id', 'url', 'title', 'alt' ),
			'core/button'    => array( 'url', 'text', 'linkTarget', 'rel' ),
		);

		// Add the bindable attributes to the supported attributes array
		if ( ! empty( $bindable_attributes ) ) {
			$supported_block_attributes[ $this->name ] = array_keys( $bindable_attributes );
		}

		// If the block isn't one of the supported block types and
		// doesn't have the bindings property, doesn't contain any bindable attributes,
		// or the bindings property is not an array, return the block content.
		if (
			! isset( $supported_block_attributes[ $this->name ] ) &&
			(
				empty( $parsed_block['attrs']['metadata']['bindings'] ) ||
				! is_array( $parsed_block['attrs']['metadata']['bindings'] )
			)
		) {
			return $computed_attributes;
		}

		$bindings = $parsed_block['attrs']['metadata']['bindings'];

		/*
		 * If the default binding is set for pattern overrides, replace it
		 * with a pattern override binding for all supported attributes.
		 */
		if (
			isset( $bindings['__default']['source'] ) &&
			'core/pattern-overrides' === $bindings['__default']['source']
		) {
			$updated_bindings = array();

			/*
			 * Build a binding array of all supported attributes.
			 * Note that this also omits the `__default` attribute from the
			 * resulting array.
			 */
			foreach ( $supported_block_attributes[ $parsed_block['blockName'] ] as $attribute_name ) {
				// Retain any non-pattern override bindings that might be present.
				$updated_bindings[ $attribute_name ] = isset( $bindings[ $attribute_name ] )
					? $bindings[ $attribute_name ]
					: array( 'source' => 'core/pattern-overrides' );
			}
			$bindings = $updated_bindings;
		}

		foreach ( $bindings as $attribute_name => $block_binding ) {
			// If the attribute is not in the supported list, process next attribute.
			if ( ! in_array( $attribute_name, $supported_block_attributes[ $this->name ], true ) ) {
				continue;
			}
			// If no source is provided, or that source is not registered, process next attribute.
			if ( ! isset( $block_binding['source'] ) || ! is_string( $block_binding['source'] ) ) {
				continue;
			}

			$block_binding_source = get_block_bindings_source( $block_binding['source'] );
			if ( null === $block_binding_source ) {
				continue;
			}

			$source_args  = ! empty( $block_binding['args'] ) && is_array( $block_binding['args'] ) ? $block_binding['args'] : array();
			$source_value = $block_binding_source->get_value( $source_args, $this, $attribute_name );

			// If the value is not null, process the HTML based on the block and the attribute.
			if ( ! is_null( $source_value ) ) {
				$computed_attributes[ $attribute_name ] = $source_value;
			}
		}

		return $computed_attributes;
	}
}
