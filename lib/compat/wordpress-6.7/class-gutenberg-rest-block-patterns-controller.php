<?php
/**
 * REST API: Gutenberg_REST_Block_Patterns_Controller_6_7 class
 *
 * @package    Gutenberg
 * @subpackage REST_API
 */

/**
 * Core class used to access block patterns via the REST API.
 *
 * @see Gutenberg_REST_Block_Patterns_Controller_6_7
 */
class Gutenberg_REST_Block_Patterns_Controller_6_7 extends Gutenberg_REST_Block_Patterns_Controller {
	/**
	 * Add the `synced` property to the block pattern response.
	 *
	 * @since 6.0.0
	 * @since 6.7.0 Added `synced` property.
	 *
	 * @param array           $item    Raw pattern as registered, before any changes.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$response = parent::prepare_item_for_response( $item, $request );

		$response->data['synced'] = $item['synced'] ?? false;
		return rest_ensure_response( $response );
	}

	/**
	 * Add the `synced` property to the block pattern schema.
	 *
	 * @since 6.0.0
	 * @since 6.7.0 Added `source` property.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();
		if ( ! isset( $schema['properties']['synced'] ) ) {
			$schema['properties']['synced'] = array(
				'description' => __( 'Whether the pattern is synced or static.', 'gutenberg' ),
				'type'        => 'boolean',
				'readonly'    => true,
				'context'     => array( 'view', 'edit', 'embed' ),
			);
		}

		$this->schema = $schema;
		return $this->add_additional_fields_schema( $this->schema );
	}
}
