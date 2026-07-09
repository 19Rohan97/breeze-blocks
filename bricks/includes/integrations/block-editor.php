<?php
namespace Bricks\Integrations;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Block_Editor {
	public function __construct() {
		add_action( 'init', [ $this, 'register_blocks' ] );
		add_shortcode( 'bricks_component', [ $this, 'render_component_shortcode' ] );
	}

	/**
	 * Register all component blocks
	 */
	public function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$this->register_bricks_components_as_blocks();
	}

	/**
	 * Register all components as blocks, if enabled for block editor
	 */
	public function register_bricks_components_as_blocks() {
		if ( ! \Bricks\Database::get_setting( 'bricksComponentsInBlockEditor' ) ) {
			return;
		}

		$components = get_option( BRICKS_DB_COMPONENTS, [] );

		foreach ( $components as $component ) {
			if ( empty( $component['id'] ) || empty( $component['elements'] ) ) {
				continue;
			}

			// NOTE: Register ALL components as blocks (not just enabled ones)
			// The JavaScript side will handle showing placeholders for disabled components
			$this->register_component_block( $component );
		}
	}

	/**
	 * Register a single component block
	 *
	 * @param array $component Component data.
	 * @return void
	 */
	public function register_component_block( $component ) {
		// Skip if no data
		if ( ! $component || empty( $component['elements'] ) ) {
			return;
		}

		// Use component ID directly for block name (matches JavaScript registration)
		$block_name = 'bricks-components/' . $component['id'];

		// Get component name from the first element or use ID
		$component_name = '';
		if ( isset( $component['elements'][0]['label'] ) ) {
			$component_name = $component['elements'][0]['label'];
		} else {
			$component_name = sprintf(
				/* translators: %s: Component ID */
				__( 'Component %s', 'bricks' ),
				$component['id']
			);
		}

		$attributes = [
			'componentId' => [
				'type'    => 'string',
				'default' => $component['id'],
			],
			'properties'  => [
				'type'    => 'object',
				'default' => [],
			],
			'blockId'     => [
				'type'    => 'string',
				'default' => '',
			],
			'variant'     => [
				'type'    => 'string',
				'default' => '',
			],
			'_preview'    => [
				'type'    => 'boolean',
				'default' => false,
			],
		];

		// Register block type
		register_block_type(
			$block_name,
			[
				'attributes'      => $attributes,
				'render_callback' => [ $this, 'render_component_block' ],
				'category'        => $component['blockCategory'] ?? 'bricks',
				'supports'        => [
					'align' => [ 'wide', 'full' ],
				],
			]
		);
	}

	/**
	 * Render component block
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render_component_block( $attributes ) {
		try {
			$component_id = $attributes['componentId'] ?? '';

			if ( ! $component_id ) {
				return '';
			}

			// Check if component is enabled for block editor before rendering
			$components = get_option( BRICKS_DB_COMPONENTS, [] );
			$component  = null;

			foreach ( $components as $comp ) {
				if ( isset( $comp['id'] ) && $comp['id'] === $component_id ) {
					$component = $comp;
					break;
				}
			}

			// Return empty string if component not found or not enabled for block editor
			if ( ! $component ) {
				return '';
			}

			// Check if component is enabled for block editor
			if ( \Bricks\Database::get_setting( 'bricksComponentsInBlockEditor' ) === 'manual' && empty( $component['blockEditor'] ) ) {
				return '';
			}

			// Translate attributes if WPML is active
			if ( \Bricks\Integrations\Wpml\Wpml::is_wpml_active() ) {
				$attributes = \Bricks\Integrations\Wpml\Wpml::translate_component_block_attributes( $attributes, get_the_ID() );
			}

			// Render component directly with attributes
			$content = $this->render_component_shortcode( $attributes );

			if ( ! $content ) {
				return '';
			}

			// Apply alignment class wrapper
			if ( ! empty( $attributes['align'] ) ) {
				return '<div class="align' . esc_attr( $attributes['align'] ) . '">' . $content . '</div>';
			}

			return $content;
		} catch ( \Exception $e ) {
			return '';
		}
	}

	/**
	 * Render component shortcode: [bricks_component id="component_id"]
	 *
	 * Simplified version that leverages Bricks' native component system
	 */
	public function render_component_shortcode( $attributes = [] ) {
		try {
			// Handle both direct calls (from blocks) and shortcode calls
			$component_id = ! empty( $attributes['componentId'] ) ? sanitize_text_field( $attributes['componentId'] ) :
							( ! empty( $attributes['id'] ) ? sanitize_text_field( $attributes['id'] ) : false );

			if ( ! $component_id ) {
				return '';
			}

			// Check if component exists
			$component = \Bricks\Helpers::get_component_by_cid( $component_id );
			if ( ! $component ) {
				return '';
			}

			// Check if component is enabled for block editor
			if ( \Bricks\Database::get_setting( 'bricksComponentsInBlockEditor' ) === 'manual' && empty( $component['blockEditor'] ) ) {
				return '';
			}

			// Get properties from attributes (handle both new and legacy formats)
			$properties = [];
			if ( isset( $attributes['properties'] ) && is_array( $attributes['properties'] ) ) {
				// New format: single properties object
				$properties = $attributes['properties'];
			}

			// Get block ID for unique element ID
			$block_id = ! empty( $attributes['blockId'] ) ? sanitize_text_field( $attributes['blockId'] ) : '';

			// Get variant
			$variant = ! empty( $attributes['variant'] ) ? sanitize_text_field( $attributes['variant'] ) : '';

			// Get the main element from the component
			$main_element = null;
			foreach ( $component['elements'] as $element ) {
				if ( $element['id'] === $component_id ) {
					$main_element = $element;
					break;
				}
			}

			if ( ! $main_element ) {
				return '';
			}

			// Create component element using the main element's name and structure
			// Use blockId for consistent element ID, fallback to component ID if no blockId
			$element_id = $block_id ? $component_id . '-' . $block_id : $component_id;

			$component_element = [
				'id'         => $element_id,
				'name'       => $main_element['name'], // Use the actual element name (e.g., 'post - title')
				'cid'        => $component_id,
				'properties' => $properties,
			];

			// Add variant if specified
			if ( $variant ) {
				$component_element['variant'] = $variant;
			}

			// Generate CSS for this component instance
			\Bricks\Assets::generate_css_from_elements( [ $component_element ], "component_$component_id" );

			// Prepare all settings into enqueue_setting_specific_scripts
			$all_elements       = [];
			$component_instance = \Bricks\Helpers::get_component_instance( $component_element );

			if ( ! empty( $component_instance ) ) {
				// Get all nested elements for this component instance (#86c7ac7wk; @since 2.2)
				\Bricks\Helpers::get_component_elements_recursive( $component_instance, $all_elements );
			} else {
				$all_elements = [ $component_element ];
			}

			// Enqueue icon fonts and other setting-specific scripts for this component instance
			\Bricks\Assets::enqueue_setting_specific_scripts( $all_elements );

			// Ensure theme styles are loaded for Gutenberg context
			if ( $this->is_gutenberg_render() && empty( \Bricks\Theme_Styles::$settings_by_id ) ) {
				\Bricks\Theme_Styles::load_set_styles();
			}

			// Handle CSS output based on context
			$html = '';
			if ( bricks_is_builder() || bricks_is_builder_call() || $this->is_gutenberg_render() ) {
				// For builder/Gutenberg: Add inline CSS for immediate preview
				$component_css = \Bricks\Assets::$inline_css[ "component_$component_id" ] ?? '';

				$global_css = '';

				// Gutenberg loads shared globals once via the block editor stylesheet to preserve frontend cascade order (@since 2.3.8)
				if ( ! $this->is_gutenberg_render() ) {
					// Add global styles for editor contexts
					$global_classes_css = \Bricks\Assets::generate_global_classes();
					if ( $global_classes_css ) {
						$global_css .= "\n/* Global Classes */\n" . $global_classes_css;
					}

					$global_variables = \Bricks\Assets::get_global_variables();
					if ( $global_variables ) {
						$variables_css = \Bricks\Assets::format_variables_as_css( $global_variables );
						if ( $variables_css ) {
							$global_css .= "\n/* Global Variables */\n" . $variables_css;
						}
					}

					$global_colors = \Bricks\Assets::generate_inline_css_color_vars( \Bricks\Database::$global_data['colorPalette'] );
					if ( $global_colors ) {
						$global_css .= "\n/* Global Colors */\n" . $global_colors;
					}

					// Add theme styles that apply to current page
					$theme_style_css = '';
					if ( ! empty( \Bricks\Theme_Styles::$settings_by_id ) ) {
						foreach ( \Bricks\Theme_Styles::$settings_by_id as $style_id => $settings ) {
							$theme_style_css .= \Bricks\Assets::generate_inline_css_theme_style( $settings );
						}
					}
					if ( $theme_style_css ) {
						$global_css .= "\n/* Theme Styles */\n" . $theme_style_css;
					}
				}

				// Disable links in editor
				$editor_css    = "\n/* Disable links in editor */\n.brxe-{$component_id} a { pointer-events: none; }\n";
				$webfont_links = '';

				// Scope CSS to Gutenberg editor canvas
				if ( $this->is_gutenberg_render() ) {
					$all_css = $global_css . $component_css . $editor_css;

					// Add webfonts
					$webfont_links = $component_css ? \Bricks\Assets::load_webfonts( $component_css, true ) : '';

					if ( $all_css ) {
						$scoped_css = self::scope_css_for_gutenberg( $all_css );
						$html      .= "{$webfont_links}<style id=\"bricks-inline-css-component-{$component_id}\">{$scoped_css}</style>";
					} else {
						$html .= $webfont_links;
					}
				} else {
					if ( $component_css ) {
						\Bricks\Assets::load_webfonts( $component_css );
					}

					$html .= "{$webfont_links}<style id=\"bricks-inline-css-component-{$component_id}\">{$global_css}{$component_css}{$editor_css}</style>";
				}
			} else {
				// For frontend: Add CSS to Bricks' normal CSS handling system
				$component_css = \Bricks\Assets::$inline_css[ "component_$component_id" ] ?? '';
				if ( $component_css ) {
					// Add to dynamic CSS for frontend output
					\Bricks\Assets::$inline_css_dynamic_data .= $component_css;

					// Load webfonts for frontend
					\Bricks\Assets::load_webfonts( $component_css );
				}
			}

			// Prevent infinite loops
			static $rendered_components = [];
			if ( in_array( $component_id, $rendered_components, true ) ) {
				return '';
			}

			$rendered_components[] = $component_id;

			// Let Bricks handle everything - this is the key simplification!
			// But first, ensure we have post context for post-related elements
			global $post;
			$original_post = $post;

			// If no post context in Gutenberg, try to get the current editing post
			if ( ! $post && $this->is_gutenberg_render() ) {
				$post_id = get_the_ID();
				if ( ! $post_id && isset( $_GET['post'] ) ) {
					$post_id = intval( $_GET['post'] );
				}
				if ( ! $post_id && isset( $_POST['post_id'] ) ) {
					$post_id = intval( $_POST['post_id'] );
				}

				if ( $post_id ) {
					$post = get_post( $post_id );
					setup_postdata( $post );
				}
			}

			// Add parent component to Frontend::$elements so nested components can resolve parent properties
			// See: Helpers::resolve_parent_property_value()
			\Bricks\Frontend::$elements[ $element_id ] = $component_element;

			$html .= \Bricks\Frontend::render_element( $component_element );

			// Restore original post context
			if ( $original_post ) {
				$post = $original_post;
				setup_postdata( $post );
			} elseif ( ! $original_post && $post ) {
				wp_reset_postdata();
			}

			array_pop( $rendered_components );

			return $html;

		} catch ( \Exception $e ) {
			return '';
		}
	}

	/**
	 * Scope CSS for Gutenberg editor while preserving root-level declarations.
	 *
	 * Keeps @font-face and @keyframes at root level and scopes regular rules to the editor iframe body.
	 *
	 * @param string $css CSS to scope.
	 * @return string Scoped CSS.
	 */
	public static function scope_css_for_gutenberg( $css ) {
		if ( empty( $css ) ) {
			return $css;
		}

		$root_level_css = '';

		// STEP: Extract @font-face blocks
		if ( preg_match_all( '/@font-face\s*\{[^}]*\}/s', $css, $font_matches ) ) {
			foreach ( $font_matches[0] as $font_block ) {
				$root_level_css .= $font_block . "\n";
				$css             = str_replace( $font_block, '', $css );
			}
		}

		// STEP: Extract @keyframes blocks
		if ( preg_match_all( '/@(?:-webkit-)?keyframes\s+[^{]+\{(?:[^{}]*\{[^}]*\})*[^}]*\}/s', $css, $keyframe_matches ) ) {
			foreach ( $keyframe_matches[0] as $keyframe_block ) {
				$root_level_css .= $keyframe_block . "\n";
				$css             = str_replace( $keyframe_block, '', $css );
			}
		}

		return $root_level_css . self::scope_css_rules_for_gutenberg( $css );
	}

	/**
	 * Generate global class CSS needed by component blocks in the Gutenberg canvas.
	 *
	 * @param int $post_id Edited post ID.
	 * @return string
	 *
	 * @since 2.3.8
	 */
	public static function generate_gutenberg_global_classes_css( $post_id = 0 ) {
		if ( ! \Bricks\Database::get_setting( 'bricksComponentsInBlockEditor' ) ) {
			return '';
		}

		$global_classes_elements = [];
		$components              = \Bricks\Database::$global_data['components'] ?? get_option( BRICKS_DB_COMPONENTS, [] );

		if ( $post_id && function_exists( 'parse_blocks' ) ) {
			$post = get_post( $post_id );

			if ( $post && ! empty( $post->post_content ) ) {
				self::collect_component_blocks_global_class_usage( parse_blocks( $post->post_content ), $global_classes_elements );
			}
		}

		if ( is_array( $components ) ) {
			foreach ( $components as $component ) {
				if ( ! self::is_component_enabled_for_gutenberg( $component ) ) {
					continue;
				}

				self::collect_component_global_class_usage( $component['id'], [], '', $global_classes_elements );
				self::collect_component_property_global_class_usage( $component, $global_classes_elements );
			}
		}

		if ( empty( $global_classes_elements ) ) {
			return '';
		}

		$original_global_classes_elements = \Bricks\Assets::$global_classes_elements;
		$original_inline_css              = \Bricks\Assets::$inline_css;
		$original_inline_css_breakpoints  = \Bricks\Assets::$inline_css_breakpoints;
		$original_unique_inline_css       = \Bricks\Assets::$unique_inline_css;
		$original_inline_css_dynamic_data = \Bricks\Assets::$inline_css_dynamic_data;
		$original_generating_element      = \Bricks\Assets::$current_generating_element;
		$css                              = '';

		try {
			\Bricks\Assets::$global_classes_elements = $global_classes_elements;
			$css                                     = \Bricks\Assets::generate_global_classes( 'gutenberg_global_classes' );
		} finally {
			\Bricks\Assets::$global_classes_elements    = $original_global_classes_elements;
			\Bricks\Assets::$inline_css                 = $original_inline_css;
			\Bricks\Assets::$inline_css_breakpoints     = $original_inline_css_breakpoints;
			\Bricks\Assets::$unique_inline_css          = $original_unique_inline_css;
			\Bricks\Assets::$inline_css_dynamic_data    = $original_inline_css_dynamic_data;
			\Bricks\Assets::$current_generating_element = $original_generating_element;
		}

		return $css ? $css : '';
	}

	/**
	 * Check if a component can render in Gutenberg.
	 *
	 * @param array $component Component data.
	 * @return bool
	 *
	 * @since 2.3.8
	 */
	private static function is_component_enabled_for_gutenberg( $component ) {
		if ( empty( $component['id'] ) || empty( $component['elements'] ) || ! is_array( $component['elements'] ) ) {
			return false;
		}

		return \Bricks\Database::get_setting( 'bricksComponentsInBlockEditor' ) !== 'manual' || ! empty( $component['blockEditor'] );
	}

	/**
	 * Collect global class usage for a component instance.
	 *
	 * @param string $component_id Component ID.
	 * @param array  $properties Component block properties.
	 * @param string $variant Component variant ID.
	 * @param array  $global_classes_elements Global class usage map.
	 * @return void
	 *
	 * @since 2.3.8
	 */
	private static function collect_component_global_class_usage( $component_id, $properties, $variant, &$global_classes_elements ) {
		$component = \Bricks\Helpers::get_component_by_cid( $component_id );

		if ( ! $component || ! self::is_component_enabled_for_gutenberg( $component ) ) {
			return;
		}

		$main_element = self::get_component_main_element( $component );

		if ( ! $main_element ) {
			return;
		}

		$component_element = [
			'id'         => $component_id,
			'name'       => $main_element['name'],
			'cid'        => $component_id,
			'properties' => is_array( $properties ) ? $properties : [],
		];

		if ( $variant ) {
			$component_element['variant'] = $variant;
		}

		$component_instance = \Bricks\Helpers::get_component_instance( $component_element );
		$elements           = [];

		if ( ! empty( $component_instance ) ) {
			\Bricks\Helpers::get_component_elements_recursive( $component_instance, $elements );
		} else {
			$elements = [ $component_element ];
		}

		foreach ( $elements as $element ) {
			self::add_element_global_class_usage( $element, $global_classes_elements );
		}
	}

	/**
	 * Get the root element for a component.
	 *
	 * @param array $component Component data.
	 * @return array|false
	 *
	 * @since 2.3.8
	 */
	private static function get_component_main_element( $component ) {
		foreach ( $component['elements'] as $element ) {
			if ( isset( $element['id'] ) && $element['id'] === $component['id'] ) {
				return $element;
			}
		}

		return false;
	}

	/**
	 * Collect global class options exposed through component class properties.
	 *
	 * @param array $component Component data.
	 * @param array $global_classes_elements Global class usage map.
	 * @return void
	 *
	 * @since 2.3.8
	 */
	private static function collect_component_property_global_class_usage( $component, &$global_classes_elements ) {
		if ( empty( $component['properties'] ) || ! is_array( $component['properties'] ) ) {
			return;
		}

		foreach ( $component['properties'] as $property ) {
			if ( ( $property['type'] ?? '' ) !== 'class' || empty( $property['connections'] ) || ! is_array( $property['connections'] ) ) {
				continue;
			}

			$property_class_ids = self::get_property_global_class_ids( $property );

			if ( empty( $property_class_ids ) ) {
				continue;
			}

			foreach ( $property['connections'] as $element_id => $setting_keys ) {
				$element = self::get_component_element_by_id( $component, $element_id );

				if ( ! $element ) {
					continue;
				}

				foreach ( $property_class_ids as $class_id ) {
					self::add_global_class_usage( $class_id, $element['name'], $global_classes_elements );
				}
			}
		}
	}

	/**
	 * Get global class IDs referenced by a class property.
	 *
	 * @param array $property Component property.
	 * @return array
	 *
	 * @since 2.3.8
	 */
	private static function get_property_global_class_ids( $property ) {
		$class_ids = [];

		if ( ! empty( $property['default'] ) ) {
			$class_ids = array_merge( $class_ids, self::normalize_global_class_ids( $property['default'] ) );
		}

		if ( empty( $property['options'] ) || ! is_array( $property['options'] ) ) {
			return array_values( array_unique( array_filter( $class_ids ) ) );
		}

		foreach ( $property['options'] as $option ) {
			if ( ! empty( $option['id'] ) ) {
				$class_ids[] = $option['id'];
			}

			if ( ! empty( $option['value'] ) ) {
				$class_ids = array_merge( $class_ids, self::normalize_global_class_ids( $option['value'] ) );
			}
		}

		return array_values( array_unique( array_filter( $class_ids ) ) );
	}

	/**
	 * Get a component element by ID.
	 *
	 * @param array  $component Component data.
	 * @param string $element_id Element ID.
	 * @return array|false
	 *
	 * @since 2.3.8
	 */
	private static function get_component_element_by_id( $component, $element_id ) {
		foreach ( $component['elements'] as $element ) {
			if ( isset( $element['id'] ) && (string) $element['id'] === (string) $element_id ) {
				return $element;
			}
		}

		return false;
	}

	/**
	 * Collect component block global class usage from parsed Gutenberg blocks.
	 *
	 * @param array $blocks Parsed blocks.
	 * @param array $global_classes_elements Global class usage map.
	 * @return void
	 *
	 * @since 2.3.8
	 */
	private static function collect_component_blocks_global_class_usage( $blocks, &$global_classes_elements ) {
		if ( ! is_array( $blocks ) ) {
			return;
		}

		foreach ( $blocks as $block ) {
			$block_name = $block['blockName'] ?? '';

			if ( strpos( $block_name, 'bricks-components/' ) === 0 ) {
				$attrs        = $block['attrs'] ?? [];
				$component_id = ! empty( $attrs['componentId'] ) ? sanitize_text_field( $attrs['componentId'] ) : str_replace( 'bricks-components/', '', $block_name );
				$properties   = ! empty( $attrs['properties'] ) && is_array( $attrs['properties'] ) ? $attrs['properties'] : [];
				$variant      = ! empty( $attrs['variant'] ) ? sanitize_text_field( $attrs['variant'] ) : '';

				self::collect_component_global_class_usage( $component_id, $properties, $variant, $global_classes_elements );
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				self::collect_component_blocks_global_class_usage( $block['innerBlocks'], $global_classes_elements );
			}
		}
	}

	/**
	 * Add global class usage for an element.
	 *
	 * @param array $element Element data.
	 * @param array $global_classes_elements Global class usage map.
	 * @return void
	 *
	 * @since 2.3.8
	 */
	private static function add_element_global_class_usage( $element, &$global_classes_elements ) {
		$element_name = $element['name'] ?? '';

		if ( ! $element_name ) {
			return;
		}

		$class_ids = $element['settings']['_cssGlobalClasses'] ?? false;

		foreach ( self::normalize_global_class_ids( $class_ids ) as $class_id ) {
			self::add_global_class_usage( $class_id, $element_name, $global_classes_elements );
		}
	}

	/**
	 * Normalize a global class ID value to an array.
	 *
	 * @param mixed $class_ids Global class ID value.
	 * @return array
	 *
	 * @since 2.3.8
	 */
	private static function normalize_global_class_ids( $class_ids ) {
		if ( is_string( $class_ids ) ) {
			$class_ids = explode( ' ', $class_ids );
		}

		return is_array( $class_ids ) ? array_values( array_filter( $class_ids ) ) : [];
	}

	/**
	 * Add one global class/element pair to the usage map.
	 *
	 * @param string $class_id Global class ID.
	 * @param string $element_name Element name.
	 * @param array  $global_classes_elements Global class usage map.
	 * @return void
	 *
	 * @since 2.3.8
	 */
	private static function add_global_class_usage( $class_id, $element_name, &$global_classes_elements ) {
		if ( ! $class_id || ! $element_name ) {
			return;
		}

		if ( ! isset( $global_classes_elements[ $class_id ] ) ) {
			$global_classes_elements[ $class_id ] = [];
		}

		if ( ! in_array( $element_name, $global_classes_elements[ $class_id ], true ) ) {
			$global_classes_elements[ $class_id ][] = $element_name;
		}
	}

	/**
	 * Scope regular CSS rules to the block editor canvas.
	 *
	 * @param string $css CSS to scope.
	 * @return string
	 */
	private static function scope_css_rules_for_gutenberg( $css ) {
		$scoped_css = '';
		$offset     = 0;
		$length     = strlen( $css );

		while ( $offset < $length ) {
			$open_position = strpos( $css, '{', $offset );

			if ( $open_position === false ) {
				$scoped_css .= substr( $css, $offset );
				break;
			}

			$selector = substr( $css, $offset, $open_position - $offset );
			$close    = self::find_matching_css_brace( $css, $open_position );

			if ( $close === false ) {
				$scoped_css .= substr( $css, $offset );
				break;
			}

			$body   = substr( $css, $open_position + 1, $close - $open_position - 1 );
			$prefix = '';

			if ( preg_match( '/^(\\s*(?:\\/\\*.*?\\*\\/\\s*)*)(.*?)$/s', $selector, $matches ) ) {
				$prefix   = $matches[1];
				$selector = $matches[2];
			}

			$selector = trim( $selector );

			if ( $selector === '' ) {
				$scoped_css .= $prefix . '{' . $body . '}';
			} elseif ( strpos( $selector, '@' ) === 0 ) {
				$scoped_css .= $prefix . $selector . '{' . self::scope_css_at_rule_body_for_gutenberg( $selector, $body ) . '}';
			} else {
				$scoped_css .= $prefix . self::scope_css_selector_list_for_gutenberg( $selector ) . '{' . $body . '}';
			}

			$offset = $close + 1;
		}

		return $scoped_css;
	}

	/**
	 * Scope at-rule contents when they contain regular style rules.
	 *
	 * @param string $selector At-rule selector.
	 * @param string $body At-rule body.
	 * @return string
	 */
	private static function scope_css_at_rule_body_for_gutenberg( $selector, $body ) {
		if ( preg_match( '/^@(media|supports|container|layer)\\b/i', $selector ) ) {
			return self::scope_css_rules_for_gutenberg( $body );
		}

		return $body;
	}

	/**
	 * Find the matching closing brace for a CSS block.
	 *
	 * @param string $css CSS string.
	 * @param int    $open_position Opening brace position.
	 * @return int|false
	 */
	private static function find_matching_css_brace( $css, $open_position ) {
		$depth  = 0;
		$length = strlen( $css );

		for ( $i = $open_position; $i < $length; $i++ ) {
			if ( $css[ $i ] === '{' ) {
				$depth++;
			} elseif ( $css[ $i ] === '}' ) {
				$depth--;

				if ( $depth === 0 ) {
					return $i;
				}
			}
		}

		return false;
	}

	/**
	 * Scope a comma-separated selector list to the block editor canvas.
	 *
	 * @param string $selector_list CSS selector list.
	 * @return string
	 */
	private static function scope_css_selector_list_for_gutenberg( $selector_list ) {
		$selectors = self::split_css_selector_list( $selector_list );
		$scoped    = [];

		foreach ( $selectors as $selector ) {
			$scoped[] = self::scope_css_selector_for_gutenberg( $selector );
		}

		return implode( ', ', $scoped );
	}

	/**
	 * Split a selector list while respecting functional pseudo selectors.
	 *
	 * @param string $selector_list CSS selector list.
	 * @return array
	 */
	private static function split_css_selector_list( $selector_list ) {
		$selectors     = [];
		$current       = '';
		$paren_depth   = 0;
		$bracket_depth = 0;
		$length        = strlen( $selector_list );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $selector_list[ $i ];

			if ( $char === '(' ) {
				$paren_depth++;
			} elseif ( $char === ')' ) {
				$paren_depth = max( 0, $paren_depth - 1 );
			} elseif ( $char === '[' ) {
				$bracket_depth++;
			} elseif ( $char === ']' ) {
				$bracket_depth = max( 0, $bracket_depth - 1 );
			}

			if ( $char === ',' && $paren_depth === 0 && $bracket_depth === 0 ) {
				$selectors[] = trim( $current );
				$current     = '';
				continue;
			}

			$current .= $char;
		}

		if ( trim( $current ) !== '' ) {
			$selectors[] = trim( $current );
		}

		return $selectors;
	}

	/**
	 * Scope one selector to the block editor canvas.
	 *
	 * @param string $selector CSS selector.
	 * @return string
	 */
	private static function scope_css_selector_for_gutenberg( $selector ) {
		$selector = trim( $selector );

		if ( $selector === '' ) {
			return $selector;
		}

		if (
			self::selector_starts_with_class( $selector, 'block-editor-iframe__html' ) ||
			self::selector_starts_with_class( $selector, 'block-editor-iframe__body' )
		) {
			return $selector;
		}

		if ( self::selector_starts_with_class( $selector, 'editor-styles-wrapper' ) ) {
			return self::scope_css_selector_to_editor_wrapper( substr( $selector, strlen( '.editor-styles-wrapper' ) ) );
		}

		if ( self::selector_starts_with_class( $selector, 'is-root-container' ) ) {
			return self::scope_css_selector_to_editor_wrapper( ' ' . $selector );
		}

		if ( strpos( $selector, ':root' ) === 0 ) {
			return self::scope_css_selector_to_editor_html( substr( $selector, strlen( ':root' ) ) );
		}

		if ( preg_match( '/^body([^\\s>+~]*)(.*)$/', $selector, $matches ) ) {
			return self::scope_css_selector_to_editor_body( $matches[1] . $matches[2] );
		}

		if ( preg_match( '/^html(?:[^\\s>+~]*)?(.*)$/', $selector, $matches ) ) {
			return self::scope_css_selector_to_editor_html( $matches[1] );
		}

		return self::scope_css_selector_to_editor_wrapper( ' ' . $selector );
	}

	/**
	 * Return the block editor iframe body selector.
	 *
	 * @param string $suffix Selector suffix.
	 * @return string
	 */
	private static function scope_css_selector_to_editor_wrapper( $suffix = '' ) {
		return '.block-editor-iframe__body' . $suffix;
	}

	/**
	 * Return the block editor iframe html selector.
	 *
	 * @param string $suffix Selector suffix.
	 * @return string
	 */
	private static function scope_css_selector_to_editor_html( $suffix = '' ) {
		return '.block-editor-iframe__html' . $suffix;
	}

	/**
	 * Return the block editor iframe body selector.
	 *
	 * @param string $suffix Selector suffix.
	 * @return string
	 */
	private static function scope_css_selector_to_editor_body( $suffix = '' ) {
		return '.block-editor-iframe__body' . $suffix;
	}

	/**
	 * Check if a selector starts with the given class name.
	 *
	 * @param string $selector   CSS selector.
	 * @param string $class_name CSS class name without the leading dot.
	 * @return bool
	 */
	private static function selector_starts_with_class( $selector, $class_name ) {
		$class_selector = '.' . $class_name;

		if ( strpos( $selector, $class_selector ) !== 0 ) {
			return false;
		}

		$next_char = substr( $selector, strlen( $class_selector ), 1 );

		return $next_char === '' || ! preg_match( '/[a-zA-Z0-9_-]/', $next_char );
	}

	/**
	 * Check if we're in a Gutenberg ServerSideRender context
	 */
	private function is_gutenberg_render() {
		// Check if we're in a REST API call for block rendering
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		// Check for AJAX request from Gutenberg block renderer
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			if ( isset( $_POST['action'] ) && $_POST['action'] === 'gutenberg_render_block' ) {
				return true;
			}
		}

		// Check if we're in admin and not in Bricks builder
		if ( is_admin() && ! bricks_is_builder() && ! bricks_is_builder_call() ) {
			return true;
		}

		// Check for specific Gutenberg query parameters
		if ( isset( $_GET['context'] ) && sanitize_text_field( wp_unslash( $_GET['context'] ) ) === 'edit' ) {
			return true;
		}

		// Check if current screen is Gutenberg editor
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen && method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {
				return true;
			}
		}

		// Check for block editor specific headers or request attributes
		if ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) && is_admin() ) {
			return true;
		}

		return false;
	}

	/**
	 * Get select options from the first connected element with a select control
	 *
	 * @param array $property    The component property array.
	 * @param array $elements    The component elements array.
	 * @return array|null The select options array or null if not found.
	 */
	public function get_select_options_from_connected_elements( $property, $elements ) {
		// Only process select properties that have connections
		if ( $property['type'] !== 'select' || empty( $property['connections'] ) || ! is_array( $property['connections'] ) ) {
			return null;
		}

		// Check each connected element
		foreach ( $property['connections'] as $element_id => $connection_paths ) {
			// Find the element in the elements array
			$element = $this->find_element_by_id( $elements, $element_id );
			if ( ! $element ) {
				continue;
			}

			// Get the element's controls to find select controls
			$element_controls = \Bricks\Elements::get_element( $element, 'controls' );
			if ( empty( $element_controls ) ) {
				continue;
			}

			// Check each connection path to find select controls
			foreach ( $connection_paths as $path ) {
				// For simple paths (most common case)
				if ( strpos( $path, '.' ) === false ) {
					if ( isset( $element_controls[ $path ] ) ) {
						$control = $element_controls[ $path ];
						if ( isset( $control['type'] ) && $control['type'] === 'select' && ! empty( $control['options'] ) ) {
							return $control['options'];
						}
					}
				}
			}
		}

		return null;
	}

	/**
	 * Find element by ID in elements array (recursive)
	 *
	 * @param array  $elements   The elements array to search.
	 * @param string $element_id The element ID to find.
	 * @return array|null The element array or null if not found.
	 */
	private function find_element_by_id( $elements, $element_id ) {
		foreach ( $elements as $element ) {
			if ( isset( $element['id'] ) && $element['id'] === $element_id ) {
				return $element;
			}

			// Search recursively in children
			if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
				$found = $this->find_element_by_id( $element['children'], $element_id );
				if ( $found ) {
					return $found;
				}
			}
		}

		return null;
	}

}
