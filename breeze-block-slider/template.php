<?php
/**
 * Slider Block Template (frontend)
 *
 * @var array    $attributes Block attributes
 * @var string   $content    Block content (unused)
 * @var WP_Block $block      Block instance
 */

$images = isset($attributes['images']) && is_array($attributes['images']) ? $attributes['images'] : array();

// Nothing to show without images
if (empty($images)) {
    return;
}

// Enqueue Splide (reused Bricks handle or our fallback), our init + styles
if (function_exists('wp_enqueue_style')) {
    $splide_css = isset($GLOBALS['breeze_slider_splide_css']) ? $GLOBALS['breeze_slider_splide_css'] : 'bricks-splide';

    // If assets weren't registered (e.g. an unusual load order), register now
    if (!wp_style_is($splide_css, 'registered') && !wp_style_is('breeze-slider', 'registered')) {
        breeze_block_slider_register_assets();
        $splide_css = isset($GLOBALS['breeze_slider_splide_css']) ? $GLOBALS['breeze_slider_splide_css'] : 'breeze-splide';
    }

    if (wp_style_is($splide_css, 'registered')) {
        wp_enqueue_style($splide_css);
    }
    wp_enqueue_style('breeze-slider');
    wp_enqueue_script('breeze-slider-view');
}

// Sanitize simple size/enum values
$sanitize_size = function ($value, $fallback) {
    $value = preg_replace('/[^0-9a-z.%\s-]/i', '', trim((string) $value));
    return $value === '' ? $fallback : $value;
};

$rounded    = $sanitize_size($attributes['rounded'] ?? '', '4px');
$height     = $sanitize_size($attributes['height'] ?? '', '600px');
$object_fit = ($attributes['objectFit'] ?? 'cover') === 'contain' ? 'contain' : 'cover';

// Build Splide options (https://splidejs.com/guides/options/)
$type = in_array(($attributes['type'] ?? 'loop'), array('loop', 'slide', 'fade'), true) ? $attributes['type'] : 'loop';

$options = array(
    'type'       => $type,
    'perPage'    => max(1, (int) ($attributes['perPage'] ?? 1)),
    'perMove'    => max(1, (int) ($attributes['perMove'] ?? 1)),
    'gap'        => $sanitize_size($attributes['gap'] ?? '', '16px'),
    'arrows'     => !empty($attributes['arrows']),
    'pagination' => !empty($attributes['pagination']),
    'speed'      => max(0, (int) ($attributes['speed'] ?? 600)),
);

// 'fade' requires one slide per page
if ($type === 'fade') {
    $options['perPage'] = 1;
    $options['perMove'] = 1;
}

// Non-loop sliders rewind to the start instead of infinite looping
if ($type !== 'loop') {
    $options['rewind'] = true;
}

if (!empty($attributes['autoplay'])) {
    $options['autoplay'] = true;
    $options['interval'] = max(1000, (int) ($attributes['interval'] ?? 4000));
    $options['pauseOnHover'] = true;
}

$data_splide = wp_json_encode($options);

$style = sprintf('--brs-radius:%s;--brs-height:%s;--brs-fit:%s;', esc_attr($rounded), esc_attr($height), esc_attr($object_fit));

$wrapper_attributes = get_block_wrapper_attributes(array(
    'class' => 'breeze-slider splide',
    'style' => $style,
));
?>

<section <?php echo $wrapper_attributes; ?> data-splide="<?php echo esc_attr($data_splide); ?>" aria-label="<?php esc_attr_e('Slider', 'breeze-block-slider'); ?>">
    <div class="splide__track">
        <ul class="splide__list">
            <?php foreach ($images as $image) :
                $url = isset($image['url']) ? $image['url'] : '';
                if (!$url) {
                    continue;
                }
                $alt = isset($image['alt']) ? $image['alt'] : '';
                ?>
                <li class="splide__slide">
                    <img
                        class="breeze-slider__image"
                        src="<?php echo esc_url($url); ?>"
                        alt="<?php echo esc_attr($alt); ?>"
                        loading="lazy"
                    />
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
