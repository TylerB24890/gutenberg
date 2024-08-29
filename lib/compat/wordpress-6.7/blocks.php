<?php
/**
 * Temporary compatibility shims for block APIs present in Gutenberg.
 *
 * @package gutenberg
 */

/**
 * Allow passing a PHP file as `variations` field for Core versions < 6.7
 *
 * @param array $settings Array of determined settings for registering a block type.
 * @param array $metadata Metadata provided for registering a block type.
 * @return array The block type settings
 */
function gutenberg_filter_block_type_metadata_settings_allow_variations_php_file( $settings, $metadata ) {
	// If `variations` is a string, it's the name of a PHP file that
	// generates the variations.
	if ( ! empty( $settings['variations'] ) && is_string( $settings['variations'] ) ) {
		$variations_path = wp_normalize_path(
			realpath(
				dirname( $metadata['file'] ) . '/' .
				remove_block_asset_path_prefix( $settings['variations'] )
			)
		);
		if ( $variations_path ) {
			/**
			 * Generates the list of block variations.
			 *
			 * @since 6.7.0
			 *
			 * @return string Returns the list of block variations.
			 */
			$settings['variation_callback'] = static function () use ( $variations_path ) {
				$variations = require $variations_path;
				return $variations;
			};
			// The block instance's `variations` field is only allowed to be an array
			// (of known block variations). We unset it so that the block instance will
			// provide a getter that returns the result of the `variation_callback` instead.
			unset( $settings['variations'] );
		}
	}
	return $settings;
}
add_filter( 'block_type_metadata_settings', 'gutenberg_filter_block_type_metadata_settings_allow_variations_php_file', 10, 2 );

/**
 * Add the bindings property to core block attributes.
 * Shim for Gutenberg Backport.
 *
 * @param array $metadata Metadata for registering a block type.
 * @return array The block type metadata
 */
function gutenberg_hook_core_block_bindings( $metadata ) {
	$bindable_blocks = [
		'core/paragraph' => [ 'content' ],
		'core/heading'   => [ 'content' ],
		'core/image'     => [ 'id', 'url', 'title', 'alt' ],
		'core/button'    => [ 'url', 'text', 'linkTarget', 'rel' ],
	];

	if ( isset( $bindable_blocks[ $metadata['name'] ] ) ) {
		$metadata['attributes'] = $metadata['attributes'] ?? [];

		foreach ( $bindable_blocks[ $metadata['name'] ] as $attribute ) {
			$metadata['attributes'][ $attribute ] = $metadata['attributes'][ $attribute ] ?? [];
			$metadata['attributes'][ $attribute ]['bindable'] = true;
		}
	}

	return $metadata;
}
add_filter( 'block_type_metadata', 'gutenberg_hook_core_block_bindings' );

/**
 * Registers a block type from the metadata stored in the `block.json` file.
 * Shim for Gutenberg Backport.
 *
 * @since 5.5.0
 * @since 5.7.0 Added support for `textdomain` field and i18n handling for all translatable fields.
 * @since 5.9.0 Added support for `variations` and `viewScript` fields.
 * @since 6.1.0 Added support for `render` field.
 * @since 6.3.0 Added `selectors` field.
 * @since 6.4.0 Added support for `blockHooks` field.
 * @since 6.5.0 Added support for `allowedBlocks`, `viewScriptModule`, and `viewStyle` fields.
 * @since 6.7.0 Allow PHP filename as `variations` argument.
 *
 * @param string $file_or_folder Path to the JSON file with metadata definition for
 *                               the block or path to the folder where the `block.json` file is located.
 *                               If providing the path to a JSON file, the filename must end with `block.json`.
 * @param array  $args           Optional. Array of block type arguments. Accepts any public property
 *                               of `WP_Block_Type`. See WP_Block_Type::__construct() for information
 *                               on accepted arguments. Default empty array.
 * @return Gutenberg_Block_Type|false The registered block type on success, or false on failure.
 */
function gutenberg_register_block_type_from_metadata( $file_or_folder, $args = array() ) {
	/*
	 * Get an array of metadata from a PHP file.
	 * This improves performance for core blocks as it's only necessary to read a single PHP file
	 * instead of reading a JSON file per-block, and then decoding from JSON to PHP.
	 * Using a static variable ensures that the metadata is only read once per request.
	 */
	static $core_blocks_meta;
	if ( ! $core_blocks_meta ) {
		$core_blocks_meta = require ABSPATH . WPINC . '/blocks/blocks-json.php';
	}

	$metadata_file = ( ! str_ends_with( $file_or_folder, 'block.json' ) ) ?
		trailingslashit( $file_or_folder ) . 'block.json' :
		$file_or_folder;

	$is_core_block = str_starts_with( $file_or_folder, ABSPATH . WPINC );
	// If the block is not a core block, the metadata file must exist.
	$metadata_file_exists = $is_core_block || file_exists( $metadata_file );
	if ( ! $metadata_file_exists && empty( $args['name'] ) ) {
		return false;
	}

	// Try to get metadata from the static cache for core blocks.
	$metadata = array();
	if ( $is_core_block ) {
		$core_block_name = str_replace( ABSPATH . WPINC . '/blocks/', '', $file_or_folder );
		if ( ! empty( $core_blocks_meta[ $core_block_name ] ) ) {
			$metadata = $core_blocks_meta[ $core_block_name ];
		}
	}

	// If metadata is not found in the static cache, read it from the file.
	if ( $metadata_file_exists && empty( $metadata ) ) {
		$metadata = wp_json_file_decode( $metadata_file, array( 'associative' => true ) );
	}

	if ( ! is_array( $metadata ) || ( empty( $metadata['name'] ) && empty( $args['name'] ) ) ) {
		return false;
	}

	$metadata['file'] = $metadata_file_exists ? wp_normalize_path( realpath( $metadata_file ) ) : null;

	/**
	 * Filters the metadata provided for registering a block type.
	 *
	 * @since 5.7.0
	 *
	 * @param array $metadata Metadata for registering a block type.
	 */
	$metadata = apply_filters( 'block_type_metadata', $metadata );

	// Add `style` and `editor_style` for core blocks if missing.
	if ( ! empty( $metadata['name'] ) && str_starts_with( $metadata['name'], 'core/' ) ) {
		$block_name = str_replace( 'core/', '', $metadata['name'] );

		if ( ! isset( $metadata['style'] ) ) {
			$metadata['style'] = "wp-block-$block_name";
		}
		if ( current_theme_supports( 'wp-block-styles' ) && wp_should_load_separate_core_block_assets() ) {
			$metadata['style']   = (array) $metadata['style'];
			$metadata['style'][] = "wp-block-{$block_name}-theme";
		}
		if ( ! isset( $metadata['editorStyle'] ) ) {
			$metadata['editorStyle'] = "wp-block-{$block_name}-editor";
		}
	}

	$settings          = array();
	$property_mappings = array(
		'apiVersion'      => 'api_version',
		'name'            => 'name',
		'title'           => 'title',
		'category'        => 'category',
		'parent'          => 'parent',
		'ancestor'        => 'ancestor',
		'icon'            => 'icon',
		'description'     => 'description',
		'keywords'        => 'keywords',
		'attributes'      => 'attributes',
		'providesContext' => 'provides_context',
		'usesContext'     => 'uses_context',
		'selectors'       => 'selectors',
		'supports'        => 'supports',
		'styles'          => 'styles',
		'variations'      => 'variations',
		'example'         => 'example',
		'allowedBlocks'   => 'allowed_blocks',
	);
	$textdomain        = ! empty( $metadata['textdomain'] ) ? $metadata['textdomain'] : null;
	$i18n_schema       = get_block_metadata_i18n_schema();

	foreach ( $property_mappings as $key => $mapped_key ) {
		if ( isset( $metadata[ $key ] ) ) {
			$settings[ $mapped_key ] = $metadata[ $key ];
			if ( $metadata_file_exists && $textdomain && isset( $i18n_schema->$key ) ) {
				$settings[ $mapped_key ] = translate_settings_using_i18n_schema( $i18n_schema->$key, $settings[ $key ], $textdomain );
			}
		}
	}

	if ( ! empty( $metadata['render'] ) ) {
		$template_path = wp_normalize_path(
			realpath(
				dirname( $metadata['file'] ) . '/' .
				remove_block_asset_path_prefix( $metadata['render'] )
			)
		);
		if ( $template_path ) {
			/**
			 * Renders the block on the server.
			 *
			 * @since 6.1.0
			 *
			 * @param array    $attributes Block attributes.
			 * @param string   $content    Block default content.
			 * @param WP_Block $block      Block instance.
			 *
			 * @return string Returns the block content.
			 */
			$settings['render_callback'] = static function ( $attributes, $content, $block ) use ( $template_path ) {
				ob_start();
				require $template_path;
				return ob_get_clean();
			};
		}
	}

	// If `variations` is a string, it's the name of a PHP file that
	// generates the variations.
	if ( ! empty( $metadata['variations'] ) && is_string( $metadata['variations'] ) ) {
		$variations_path = wp_normalize_path(
			realpath(
				dirname( $metadata['file'] ) . '/' .
				remove_block_asset_path_prefix( $metadata['variations'] )
			)
		);
		if ( $variations_path ) {
			/**
			 * Generates the list of block variations.
			 *
			 * @since 6.7.0
			 *
			 * @return string Returns the list of block variations.
			 */
			$settings['variation_callback'] = static function () use ( $variations_path ) {
				$variations = require $variations_path;
				return $variations;
			};
			// The block instance's `variations` field is only allowed to be an array
			// (of known block variations). We unset it so that the block instance will
			// provide a getter that returns the result of the `variation_callback` instead.
			unset( $settings['variations'] );
		}
	}

	$settings = array_merge( $settings, $args );

	$script_fields = array(
		'editorScript' => 'editor_script_handles',
		'script'       => 'script_handles',
		'viewScript'   => 'view_script_handles',
	);
	foreach ( $script_fields as $metadata_field_name => $settings_field_name ) {
		if ( ! empty( $settings[ $metadata_field_name ] ) ) {
			$metadata[ $metadata_field_name ] = $settings[ $metadata_field_name ];
		}
		if ( ! empty( $metadata[ $metadata_field_name ] ) ) {
			$scripts           = $metadata[ $metadata_field_name ];
			$processed_scripts = array();
			if ( is_array( $scripts ) ) {
				for ( $index = 0; $index < count( $scripts ); $index++ ) {
					$result = register_block_script_handle(
						$metadata,
						$metadata_field_name,
						$index
					);
					if ( $result ) {
						$processed_scripts[] = $result;
					}
				}
			} else {
				$result = register_block_script_handle(
					$metadata,
					$metadata_field_name
				);
				if ( $result ) {
					$processed_scripts[] = $result;
				}
			}
			$settings[ $settings_field_name ] = $processed_scripts;
		}
	}

	$module_fields = array(
		'viewScriptModule' => 'view_script_module_ids',
	);
	foreach ( $module_fields as $metadata_field_name => $settings_field_name ) {
		if ( ! empty( $settings[ $metadata_field_name ] ) ) {
			$metadata[ $metadata_field_name ] = $settings[ $metadata_field_name ];
		}
		if ( ! empty( $metadata[ $metadata_field_name ] ) ) {
			$modules           = $metadata[ $metadata_field_name ];
			$processed_modules = array();
			if ( is_array( $modules ) ) {
				for ( $index = 0; $index < count( $modules ); $index++ ) {
					$result = register_block_script_module_id(
						$metadata,
						$metadata_field_name,
						$index
					);
					if ( $result ) {
						$processed_modules[] = $result;
					}
				}
			} else {
				$result = register_block_script_module_id(
					$metadata,
					$metadata_field_name
				);
				if ( $result ) {
					$processed_modules[] = $result;
				}
			}
			$settings[ $settings_field_name ] = $processed_modules;
		}
	}

	$style_fields = array(
		'editorStyle' => 'editor_style_handles',
		'style'       => 'style_handles',
		'viewStyle'   => 'view_style_handles',
	);
	foreach ( $style_fields as $metadata_field_name => $settings_field_name ) {
		if ( ! empty( $settings[ $metadata_field_name ] ) ) {
			$metadata[ $metadata_field_name ] = $settings[ $metadata_field_name ];
		}
		if ( ! empty( $metadata[ $metadata_field_name ] ) ) {
			$styles           = $metadata[ $metadata_field_name ];
			$processed_styles = array();
			if ( is_array( $styles ) ) {
				for ( $index = 0; $index < count( $styles ); $index++ ) {
					$result = register_block_style_handle(
						$metadata,
						$metadata_field_name,
						$index
					);
					if ( $result ) {
						$processed_styles[] = $result;
					}
				}
			} else {
				$result = register_block_style_handle(
					$metadata,
					$metadata_field_name
				);
				if ( $result ) {
					$processed_styles[] = $result;
				}
			}
			$settings[ $settings_field_name ] = $processed_styles;
		}
	}

	if ( ! empty( $metadata['blockHooks'] ) ) {
		/**
		 * Map camelCased position string (from block.json) to snake_cased block type position.
		 *
		 * @var array
		 */
		$position_mappings = array(
			'before'     => 'before',
			'after'      => 'after',
			'firstChild' => 'first_child',
			'lastChild'  => 'last_child',
		);

		$settings['block_hooks'] = array();
		foreach ( $metadata['blockHooks'] as $anchor_block_name => $position ) {
			// Avoid infinite recursion (hooking to itself).
			if ( $metadata['name'] === $anchor_block_name ) {
				_doing_it_wrong(
					__METHOD__,
					__( 'Cannot hook block to itself.' ),
					'6.4.0'
				);
				continue;
			}

			if ( ! isset( $position_mappings[ $position ] ) ) {
				continue;
			}

			$settings['block_hooks'][ $anchor_block_name ] = $position_mappings[ $position ];
		}
	}

	/**
	 * Filters the settings determined from the block type metadata.
	 *
	 * @since 5.7.0
	 *
	 * @param array $settings Array of determined settings for registering a block type.
	 * @param array $metadata Metadata provided for registering a block type.
	 */
	$settings = apply_filters( 'block_type_metadata_settings', $settings, $metadata );

	$metadata['name'] = ! empty( $settings['name'] ) ? $settings['name'] : $metadata['name'];

	return Gutenberg_Block_Type_Registry::get_instance()->register(
		$metadata['name'],
		$settings
	);
}
