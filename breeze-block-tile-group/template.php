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

$style = sprintf(
    '--btg-columns:%d;--btg-columns-tablet:%d;--btg-columns-mobile:%d;--btg-gap:%s;--btg-row-gap:%s;--btg-align:%s;',
    $columns,
    $columns_tablet,
    $columns_mobile,
    esc_attr($gap),
    esc_attr($row_gap),
    esc_attr($align)
);

// Get block wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes(array(
    'class' => 'breeze-tile-group',
    'style' => $style,
));
?>

<div <?php echo $wrapper_attributes; ?>>
    <?php echo $content; ?>
</div>
