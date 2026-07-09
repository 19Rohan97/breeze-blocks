<?php
/**
 * Plugin Name: Breeze Block Slider
 * Description: An image slider/carousel Gutenberg block powered by Splide (reuses Bricks' Splide library when present)
 * Version: 1.0.0
 * Author: Breeze Digital
 * Text Domain: breeze-block-slider
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Splide version to match Bricks (bricks-splide is registered at 4.1.4)
const BREEZE_SLIDER_SPLIDE_VERSION = '4.1.4';

function breeze_block_slider_register() {
    register_block_type(__DIR__);
}
add_action('init', 'breeze_block_slider_register');

/**
 * Register the frontend assets.
 *
 * Splide is loaded from Bricks' own registration (handle "bricks-splide")
 * when available, so WordPress serves a single copy no matter how many
 * sliders (Bricks or ours) are on the page. If Bricks isn't active we fall
 * back to a matching Splide build from a CDN.
 *
 * Runs at priority 20 so Bricks (which registers at the default priority)
 * has already registered "bricks-splide".
 */
function breeze_block_slider_register_assets() {
    // Splide library JS
    $splide_js = 'bricks-splide';
    if (!wp_script_is('bricks-splide', 'registered')) {
        wp_register_script(
            'breeze-splide',
            'https://cdn.jsdelivr.net/npm/@splidejs/splide@' . BREEZE_SLIDER_SPLIDE_VERSION . '/dist/js/splide.min.js',
            array(),
            BREEZE_SLIDER_SPLIDE_VERSION,
            true
        );
        $splide_js = 'breeze-splide';
    }

    // Splide library CSS
    $splide_css = 'bricks-splide';
    if (!wp_style_is('bricks-splide', 'registered')) {
        wp_register_style(
            'breeze-splide',
            'https://cdn.jsdelivr.net/npm/@splidejs/splide@' . BREEZE_SLIDER_SPLIDE_VERSION . '/dist/css/splide.min.css',
            array(),
            BREEZE_SLIDER_SPLIDE_VERSION
        );
        $splide_css = 'breeze-splide';
    }

    // Our init script depends on whichever Splide handle we resolved, so
    // Splide is guaranteed to be loaded first. The script is also defensive
    // (waits for window.Splide) in case the dependency ordering is bypassed.
    wp_register_script(
        'breeze-slider-view',
        plugins_url('view.js', __FILE__),
        array($splide_js),
        '1.0.0',
        true
    );

    // Our slider styling loads after the Splide CSS so overrides win
    wp_register_style(
        'breeze-slider',
        plugins_url('style.css', __FILE__),
        array($splide_css),
        '1.0.0'
    );

    // Remember the CSS handle for the render template
    $GLOBALS['breeze_slider_splide_css'] = $splide_css;
}
add_action('wp_enqueue_scripts', 'breeze_block_slider_register_assets', 20);
