<?php
/**
 * Plugin Name: Breeze Block Billboard
 * Description: A custom cover-style block with optional image background
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: breeze-block-billboard
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

function breeze_block_billboard_register() {
    // Register the block using block.json
    register_block_type(__DIR__);
}
add_action('init', 'breeze_block_billboard_register');

