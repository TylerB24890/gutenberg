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
 * @see Gutenberg_REST_Block_Patterns_Controller
 */
class Gutenberg_REST_Block_Patterns_Controller_6_7 extends Gutenberg_REST_Block_Patterns_Controller {
	/**
	 * Prepare a raw block pattern before it gets output in a REST API response.
	 *
	 * @since 6.0.0
	 * @since 6.3.0 Added `source` property.
	 *
	 * @param array           $item    Raw pattern as registered, before any changes.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$fields = $this->get_fields_for_response( $request );
		$keys   = array(
			'name'          => 'name',
			'title'         => 'title',
			'content'       => 'content',
			'description'   => 'description',
			'viewportWidth' => 'viewport_width',
			'inserter'      => 'inserter',
			'categories'    => 'categories',
			'keywords'      => 'keywords',
			'blockTypes'    => 'block_types',
			'postTypes'     => 'post_types',
			'templateTypes' => 'template_types',
			'source'        => 'source',
			'synced'        => 'synced',
		);
		$data   = array();
		foreach ( $keys as $item_key => $rest_key ) {
			if ( isset( $item[ $item_key ] ) && rest_is_field_included( $rest_key, $fields ) ) {
				$data[ $rest_key ] = $item[ $item_key ];
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );
		return rest_ensure_response( $data );
	}
}
