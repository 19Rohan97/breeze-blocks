<?php
/**
 * Tile Block Template
 *
 * Renders one Bricks component instance with this tile's property values.
 *
 * @var array    $attributes Block attributes
 * @var string   $content    Block content (unused, tile has no inner blocks)
 * @var WP_Block $block      Block instance
 */

// The component is chosen on the parent Tile Group block and arrives via
// block context; the attribute fallback covers editor previews.
$cid = '';

if (!empty($block->context['breeze/componentId'])) {
    $cid = $block->context['breeze/componentId'];
} elseif (!empty($attributes['componentId'])) {
    $cid = $attributes['componentId'];
}

$properties = isset($attributes['properties']) ? (array) $attributes['properties'] : array();

echo breeze_block_tile_group_render_tile($cid, $properties);
