<?php
/**
 * Plugin Name: Breeze Block Tile Group
 * Description: A grid block whose tiles are Bricks component blocks — each tile individually editable in the block editor
 * Version: 1.1.0
 * Author: Your Name
 * Text Domain: breeze-block-tile-group
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

function breeze_block_tile_group_register() {
    // Register the block using block.json
    //
    // The tiles themselves are Bricks' native component blocks
    // (bricks-components/{id}), registered by Bricks when the
    // "Components in block editor" setting (bricksComponentsInBlockEditor)
    // is enabled. Bricks handles their property controls, rendering and CSS.
    register_block_type(__DIR__);
}
add_action('init', 'breeze_block_tile_group_register');
