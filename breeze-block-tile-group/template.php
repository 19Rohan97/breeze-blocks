<?php
/**
 * Tile Group Block Template
 *
 * @var array    $attributes Block attributes
 * @var string   $content    Block content (the rendered tiles)
 * @var WP_Block $block      Block instance
 */

$columns        = isset($attributes['columns']) ? max(1, (int) $attributes['columns']) : 3;
$columns_tablet = isset($attributes['columnsTablet']) ? max(1, (int) $attributes['columnsTablet']) : 2;
$columns_mobile = isset($attributes['columnsMobile']) ? max(1, (int) $attributes['columnsMobile']) : 1;

$sanitize_size = function ($value, $fallback) {
    $value = preg_replace('/[^0-9a-z.%\s-]/i', '', trim((string) $value));
    return $value === '' ? $fallback : $value;
};

$gap     = $sanitize_size($attributes['gap'] ?? '', '24px');
$row_gap = $sanitize_size($attributes['rowGap'] ?? '', $gap);

$align         = $attributes['verticalAlign'] ?? 'stretch';
$allowed_align = array('stretch', 'start', 'center', 'end');
if (!in_array($align, $allowed_align, true)) {
    $align = 'stretch';
}

$layout_mode = ($attributes['layoutMode'] ?? 'columns') === 'auto' ? 'auto' : 'columns';
$min_width   = $sanitize_size($attributes['minTileWidth'] ?? '', '280px');

$style = sprintf(
    '--btg-columns:%d;--btg-columns-tablet:%d;--btg-columns-mobile:%d;--btg-min-width:%s;--btg-gap:%s;--btg-row-gap:%s;--btg-align:%s;',
    $columns,
    $columns_tablet,
    $columns_mobile,
    esc_attr($min_width),
    esc_attr($gap),
    esc_attr($row_gap),
    esc_attr($align)
);

$classes = 'breeze-tile-group';

if ($layout_mode === 'auto') {
    $classes .= ' breeze-tile-group--auto';
}

// The reveal animation is a global setting (Settings → Tile Group)
if (breeze_block_tile_group_reveal_enabled()) {
    $classes .= ' breeze-tile-group--reveal';
    wp_enqueue_script('breeze-tile-group-reveal');
}

// Get block wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes(array(
    'class' => $classes,
    'style' => $style,
));
?>

<div <?php echo $wrapper_attributes; ?>>
    <?php echo $content; ?>
</div>
