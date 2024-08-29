<?php
/**
 * Blocks API: Gutenberg_Block_Type class
 *
 * @package WordPress
 * @subpackage Blocks
 * @since 5.0.0
 */

/**
 * Core class representing a block type.
 *
 * @since 5.0.0
 *
 * @see register_block_type()
 */
#[AllowDynamicProperties]
class Gutenberg_Block_Type extends WP_Block_Type {

	/**
	 * The bindable block attributes.
	 *
	 * @since 6.7.0
	 * @var array
	 */
	public $bindable_attributes = array();

	/**
	 * Get the bindable attributes for a block type.
	 *
	 * @since 6.7.0
	 *
	 * @param WP_Block_Type $block_type The block type.
	 * @return array The bindable attributes.
	 */
	public function get_block_bindable_attributes() {
		if ( ! isset( $this->attributes ) ) {
			return $this->bindable_attributes;
		}

		foreach ( $this->attributes as $attribute_name => $attribute ) {
			if ( isset( $attribute['bindable'] ) && $attribute['bindable'] ) {
				$this->bindable_attributes[ $attribute_name ] = $attribute;
			}
		}

		/**
		 * Filters the bindable attributes for a block type.
		 *
		 * @since 6.7.0
		 *
		 * @param array         $bindable_attributes The bindable attributes.
		 * @param WP_Block_Type $block_type          The block type instance.
		 */
		return apply_filters( 'get_block_type_bindable_attributes', $this->bindable_attributes, $this );
	}
}
