<?php
/**
 * Tile Group Block Template
 *
 * @var array    $attributes Block attributes
 * @var string   $content    Block content (the rendered tiles)
 * @var WP_Block $block      Block instance
 */

$columns = isset($attributes['columns']) ? max(1, (int) $attributes['columns']) : 3;

$gap = isset($attributes['gap']) ? trim((string) $attributes['gap']) : '24px';
$gap = preg_replace('/[^0-9a-z.%\s-]/i', '', $gap);
if ($gap === '') {
    $gap = '24px';
}

// Get block wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes(array(
    'class' => 'breeze-tile-group',
    'style' => sprintf('--btg-columns:%d;--btg-gap:%s;', $columns, esc_attr($gap)),
));
?>

<div <?php echo $wrapper_attributes; ?>>
    <?php echo $content; ?>
</div>
