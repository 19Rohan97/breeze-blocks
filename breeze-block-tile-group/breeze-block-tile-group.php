<?php
/**
 * Plugin Name: Breeze Block Tile Group
 * Description: A grid block whose tiles are Bricks component blocks — each tile individually editable in the block editor
 * Version: 1.2.0
 * Author: Your Name
 * Text Domain: breeze-block-tile-group
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/settings.php';

function breeze_block_tile_group_register() {
    // Register the block using block.json
    //
    // The tiles themselves are Bricks' native component blocks
    // (bricks-components/{id}), registered by Bricks when the
    // "Components in block editor" setting (bricksComponentsInBlockEditor)
    // is enabled. Bricks handles their property controls, rendering and CSS.
    register_block_type(__DIR__);

    // Frontend reveal animation script. Registered (not enqueued) here and
    // enqueued from template.php only when the global setting is on and a
    // Tile Group is actually rendered on the page.
    wp_register_script(
        'breeze-tile-group-reveal',
        plugins_url('reveal.js', __FILE__),
        array(),
        '1.3.0',
        true
    );
}
add_action('init', 'breeze_block_tile_group_register');

/**
 * Hand the plugin settings to the editor script, so the component picker
 * can hide components excluded on the Settings → Tile Group page.
 */
function breeze_block_tile_group_editor_data() {
    wp_localize_script(
        generate_block_asset_handle('breeze/tile-group', 'editorScript'),
        'BreezeTileGroupSettings',
        array(
            'excludedComponents' => breeze_block_tile_group_get_excluded_components(),
        )
    );
}
add_action('enqueue_block_editor_assets', 'breeze_block_tile_group_editor_data');
