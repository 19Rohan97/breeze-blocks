<?php
/**
 * Plugin Name: Breeze Block Tile Group
 * Description: A grid block whose tiles are instances of a Bricks component — each tile individually editable in the block editor
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: breeze-block-tile-group
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

function breeze_block_tile_group_register() {
    // Register both blocks using their block.json files
    register_block_type(__DIR__);           // breeze/tile-group (parent grid)
    register_block_type(__DIR__ . '/tile'); // breeze/tile (one grid cell)
}
add_action('init', 'breeze_block_tile_group_register');

/**
 * Hand the saved Bricks components (and their property schemas) to the
 * editor script, so the tile inspector controls can be generated from them.
 */
function breeze_block_tile_group_editor_data() {
    $components = breeze_block_tile_group_get_components();

    wp_localize_script(
        generate_block_asset_handle('breeze/tile-group', 'editorScript'),
        'BreezeTileGroupData',
        array(
            'components'         => $components,
            'defaultComponentId' => breeze_block_tile_group_default_component($components),
        )
    );
}
add_action('enqueue_block_editor_assets', 'breeze_block_tile_group_editor_data');

/**
 * All saved Bricks components with their property schemas, in a shape the
 * editor script can consume.
 */
function breeze_block_tile_group_get_components() {
    $stored = get_option('bricks_components', array());

    if (!is_array($stored)) {
        return array();
    }

    $components = array();

    foreach ($stored as $component) {
        if (empty($component['id'])) {
            continue;
        }

        $properties = array();

        foreach ((array) ($component['properties'] ?? array()) as $property) {
            if (empty($property['id'])) {
                continue;
            }

            $properties[] = array(
                'id'      => (string) $property['id'],
                'label'   => $property['label'] ?? ($property['name'] ?? (string) $property['id']),
                'type'    => $property['type'] ?? 'text',
                'default' => $property['default'] ?? null,
                'options' => $property['options'] ?? null,
            );
        }

        $components[] = array(
            'id'         => (string) $component['id'],
            'label'      => breeze_block_tile_group_component_label($component),
            'properties' => $properties,
            // Debug aid: the raw keys Bricks stores for this component, so the
            // data shape can be inspected via window.BreezeTileGroupData
            '_keys'      => array_keys($component),
        );
    }

    return $components;
}

/**
 * Find the component's display name. Bricks has stored it under different
 * keys across versions, so try the known candidates in order, then fall back
 * to the root element's label.
 */
function breeze_block_tile_group_component_label($component) {
    foreach (array('label', 'title', 'name', 'desc') as $key) {
        if (!empty($component[$key]) && is_string($component[$key])) {
            return $component[$key];
        }
    }

    foreach ((array) ($component['elements'] ?? array()) as $element) {
        if (empty($element['parent'])) {
            if (!empty($element['label']) && is_string($element['label'])) {
                return $element['label'];
            }
            if (!empty($element['settings']['_label']) && is_string($element['settings']['_label'])) {
                return $element['settings']['_label'];
            }
            break;
        }
    }

    return __('(Untitled component)', 'breeze-block-tile-group');
}

/**
 * Pre-select the component named "Tiles" if it exists, otherwise the first one.
 */
function breeze_block_tile_group_default_component($components) {
    foreach ($components as $component) {
        if (strcasecmp(trim((string) $component['label']), 'tiles') === 0) {
            return $component['id'];
        }
    }

    return $components ? $components[0]['id'] : '';
}

function breeze_block_tile_group_get_component($cid) {
    $stored = get_option('bricks_components', array());

    foreach ((array) $stored as $component) {
        if (isset($component['id']) && (string) $component['id'] === (string) $cid) {
            return $component;
        }
    }

    return null;
}

/**
 * Render one tile: a Bricks component instance fed with this tile's property
 * values, rendered through Bricks' own renderer.
 *
 * @param string $cid        Bricks component ID.
 * @param array  $properties Property values keyed by property ID.
 * @return string
 */
function breeze_block_tile_group_render_tile($cid, $properties) {
    if (!$cid) {
        return '<!-- breeze/tile: no component selected on the parent Tile Group block -->';
    }

    if (!class_exists('\Bricks\Frontend')) {
        return '<!-- breeze/tile: Bricks is not the active theme -->';
    }

    $component = breeze_block_tile_group_get_component($cid);

    if (!$component) {
        return '<!-- breeze/tile: Bricks component "' . esc_html($cid) . '" not found -->';
    }

    // A component instance element must reference a real element type, so use
    // the component's own root element name.
    $root_name = 'block';

    foreach ((array) ($component['elements'] ?? array()) as $element) {
        if (empty($element['parent'])) {
            $root_name = $element['name'] ?? 'block';
            break;
        }
    }

    $properties = breeze_block_tile_group_normalize_properties($component, $properties);

    static $instance_count = 0;
    $instance_count++;

    $instance = array(
        'id'       => substr(md5('breeze-tile-' . $cid . '-' . $instance_count), 0, 6),
        'name'     => $root_name,
        'parent'   => 0,
        'children' => array(),
        // Property values are provided both flat and under 'properties' to
        // cover how different Bricks versions read instance settings.
        'settings' => array_merge($properties, array('properties' => $properties)),
        'cid'      => (string) $cid,
    );

    $instance = apply_filters('breeze_block_tile_group/instance', $instance, $properties, $component);

    ob_start();
    $returned = \Bricks\Frontend::render_data(array($instance));
    $echoed   = ob_get_clean();
    $html     = (is_string($returned) && $returned !== '') ? $returned : $echoed;

    $css = breeze_block_tile_group_component_css($cid, $component);

    $output = '<div class="breeze-tile">';

    if ($css) {
        $output .= '<style id="breeze-tile-css-' . esc_attr($cid) . '">' . $css . '</style>';
    }

    $output .= $html . '</div>';

    return $output;
}

/**
 * Convert the simple values stored in block attributes into the value shapes
 * Bricks expects per control type.
 */
function breeze_block_tile_group_normalize_properties($component, $values) {
    $schema = array();

    foreach ((array) ($component['properties'] ?? array()) as $property) {
        if (isset($property['id'])) {
            $schema[$property['id']] = $property['type'] ?? 'text';
        }
    }

    $normalized = array();

    foreach ($values as $id => $value) {
        $normalized[$id] = breeze_block_tile_group_normalize_value($schema[$id] ?? 'text', $value);
    }

    return $normalized;
}

function breeze_block_tile_group_normalize_value($type, $value) {
    switch (strtolower((string) $type)) {
        case 'image':
            if (is_numeric($value)) {
                return array(
                    'id'   => (int) $value,
                    'url'  => wp_get_attachment_url((int) $value),
                    'size' => 'full',
                );
            }
            if (is_array($value)) {
                $value = wp_parse_args($value, array('size' => 'full'));
                if (empty($value['url']) && !empty($value['id'])) {
                    $value['url'] = wp_get_attachment_url((int) $value['id']);
                }
                return $value;
            }
            return $value;

        case 'link':
            if (is_string($value) && $value !== '') {
                return array(
                    'type' => 'external',
                    'url'  => $value,
                );
            }
            return $value;

        case 'color':
            if (is_string($value) && $value !== '') {
                return array('hex' => $value);
            }
            return $value;

        case 'icon':
            if (is_string($value) && $value !== '') {
                return array(
                    'library' => 'fontawesome',
                    'icon'    => $value,
                );
            }
            return $value;

        case 'number':
            return is_numeric($value) ? $value + 0 : $value;

        default:
            return $value;
    }
}

/**
 * Best-effort CSS for the component's elements, inlined once per request.
 *
 * Bricks only generates styles for content it renders itself (Bricks
 * templates/pages), so on a block-editor page we generate the component's CSS
 * ourselves. Wrapped defensively since these are internal Bricks APIs.
 */
function breeze_block_tile_group_component_css($cid, $component) {
    static $done = array();

    if (isset($done[$cid])) {
        return '';
    }

    $done[$cid] = true;

    if (!class_exists('\Bricks\Assets')) {
        return '';
    }

    $elements = (array) ($component['elements'] ?? array());

    if (!$elements) {
        return '';
    }

    $css = '';

    try {
        if (is_callable(array('\Bricks\Assets', 'generate_css_from_elements'))) {
            $returned = \Bricks\Assets::generate_css_from_elements($elements, 'content');

            if (is_string($returned)) {
                $css .= $returned;
            }
        }

        // Some Bricks versions collect generated CSS in a static property
        // instead of returning it.
        $reflection = new \ReflectionClass('\Bricks\Assets');

        if ($reflection->hasProperty('inline_css')) {
            $property = $reflection->getProperty('inline_css');
            $property->setAccessible(true);
            $inline = $property->getValue();

            if (is_array($inline) && !empty($inline['content']) && is_string($inline['content']) && strpos($css, $inline['content']) === false) {
                $css .= $inline['content'];
            }
        }
    } catch (\Throwable $e) {
        // If Bricks internals changed, skip the CSS rather than break the page.
        $css = '';
    }

    return apply_filters('breeze_block_tile_group/component_css', $css, $cid, $component);
}
