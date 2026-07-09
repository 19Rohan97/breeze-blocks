<?php
/**
 * Billboard Block Template
 * 
 * @var array $attributes Block attributes
 * @var string $content Block content
 * @var WP_Block $block Block instance
 */

$image_id = isset($attributes['backgroundImage']['id']) ? $attributes['backgroundImage']['id'] : null;
$has_image = !empty($image_id);
$caption = isset($attributes['caption']) ? $attributes['caption'] : '';
$video_url = isset($attributes['videoUrl']) ? $attributes['videoUrl'] : '';
$has_video = !empty($video_url);
$overlay = isset($attributes['overlay']) ? $attributes['overlay'] : 'default';
$wrapper_classes = 'billboard-block alignfull' . ($has_image || $has_video ? '' : ' no-image') . ($overlay === 'darken' ? ' has-darken-overlay' : '');

// Get block wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes(array(
    'class' => $wrapper_classes,
));
?>

<div <?php echo $wrapper_attributes; ?>>
    <?php if ($has_video) : ?>
        <video class="billboard-block__video" autoplay muted loop playsinline>
            <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
        </video>
    <?php elseif ($has_image) : 
        $image_size = isset($attributes['imageSize']) ? $attributes['imageSize'] : 'large';
        $focal_point = isset($attributes['focalPoint']) ? $attributes['focalPoint'] : array('x' => 0.5, 'y' => 0.5);
        $alt = isset($attributes['alt']) ? $attributes['alt'] : '';
        
        echo wp_get_attachment_image(
            $image_id,
            $image_size,
            false,
            array(
                'class' => 'billboard-block__image',
                'alt' => $alt,
                'style' => sprintf(
                    'object-position: %s%% %s%%; width: 100%%; height: 100%%;',
                    $focal_point['x'] * 100,
                    $focal_point['y'] * 100
                )
            )
        );
    endif; ?>
    
    <?php if (!$has_video && $has_image && !empty($caption)) : ?>
        <div class="billboard-block__caption"><?php echo esc_html($caption); ?></div>
    <?php endif; ?>
    
    <div class="billboard-block__container">
        <div class="billboard-block__content">
            <?php echo $content; ?>
        </div>
    </div>
</div>
