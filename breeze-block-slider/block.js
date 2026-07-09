(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, BlockControls, MediaUpload, MediaUploadCheck, MediaPlaceholder, useBlockProps } = wp.blockEditor;
    const { PanelBody, RangeControl, TextControl, ToggleControl, SelectControl, ToolbarGroup, ToolbarButton, Button } = wp.components;
    const { __ } = wp.i18n;
    const el = wp.element.createElement;
    const Fragment = wp.element.Fragment;

    function toImage(media) {
        return {
            id: media.id,
            url: media.url,
            alt: media.alt || ''
        };
    }

    registerBlockType('breeze/slider', {
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const {
                images, type, perPage, perMove, gap, arrows, pagination,
                autoplay, interval, speed, rounded, height, objectFit
            } = attributes;

            const hasImages = Array.isArray(images) && images.length > 0;

            const blockProps = useBlockProps({
                className: 'breeze-slider-editor',
                style: {
                    '--brs-radius': rounded || '4px',
                    '--brs-height': height || '600px',
                    '--brs-fit': objectFit || 'cover'
                }
            });

            function onSelectImages(media) {
                setAttributes({ images: (media || []).map(toImage) });
            }

            // Inspector: layout + behavior controls
            const inspector = el(
                InspectorControls,
                null,
                el(
                    PanelBody,
                    { title: __('Slides', 'breeze-block-slider'), initialOpen: true },
                    el(RangeControl, {
                        label: __('Slides per view', 'breeze-block-slider'),
                        min: 1,
                        max: 6,
                        value: perPage,
                        onChange: function(v) { setAttributes({ perPage: v || 1 }); }
                    }),
                    el(RangeControl, {
                        label: __('Slides to move', 'breeze-block-slider'),
                        min: 1,
                        max: 6,
                        value: perMove,
                        onChange: function(v) { setAttributes({ perMove: v || 1 }); }
                    }),
                    el(TextControl, {
                        label: __('Gap between slides', 'breeze-block-slider'),
                        help: __('Any CSS size, e.g. 16px or 1rem', 'breeze-block-slider'),
                        value: gap,
                        onChange: function(v) { setAttributes({ gap: v }); }
                    }),
                    el(SelectControl, {
                        label: __('Transition', 'breeze-block-slider'),
                        value: type,
                        options: [
                            { label: __('Loop (infinite)', 'breeze-block-slider'), value: 'loop' },
                            { label: __('Slide (rewind)', 'breeze-block-slider'), value: 'slide' },
                            { label: __('Fade', 'breeze-block-slider'), value: 'fade' }
                        ],
                        onChange: function(v) { setAttributes({ type: v }); }
                    })
                ),
                el(
                    PanelBody,
                    { title: __('Controls', 'breeze-block-slider'), initialOpen: false },
                    el(ToggleControl, {
                        label: __('Show arrows', 'breeze-block-slider'),
                        checked: !!arrows,
                        onChange: function(v) { setAttributes({ arrows: v }); }
                    }),
                    el(ToggleControl, {
                        label: __('Show pagination dots', 'breeze-block-slider'),
                        checked: !!pagination,
                        onChange: function(v) { setAttributes({ pagination: v }); }
                    })
                ),
                el(
                    PanelBody,
                    { title: __('Autoplay', 'breeze-block-slider'), initialOpen: false },
                    el(ToggleControl, {
                        label: __('Enable autoplay', 'breeze-block-slider'),
                        checked: !!autoplay,
                        onChange: function(v) { setAttributes({ autoplay: v }); }
                    }),
                    autoplay && el(RangeControl, {
                        label: __('Interval (ms)', 'breeze-block-slider'),
                        min: 1000,
                        max: 10000,
                        step: 250,
                        value: interval,
                        onChange: function(v) { setAttributes({ interval: v || 4000 }); }
                    }),
                    el(RangeControl, {
                        label: __('Transition speed (ms)', 'breeze-block-slider'),
                        min: 0,
                        max: 2000,
                        step: 50,
                        value: speed,
                        onChange: function(v) { setAttributes({ speed: v }); }
                    })
                ),
                el(
                    PanelBody,
                    { title: __('Appearance', 'breeze-block-slider'), initialOpen: false },
                    el(TextControl, {
                        label: __('Corner radius', 'breeze-block-slider'),
                        help: __('e.g. 4px or 0', 'breeze-block-slider'),
                        value: rounded,
                        onChange: function(v) { setAttributes({ rounded: v }); }
                    }),
                    el(TextControl, {
                        label: __('Slide height', 'breeze-block-slider'),
                        help: __('Any CSS size, e.g. 600px. Leave empty for auto.', 'breeze-block-slider'),
                        value: height,
                        onChange: function(v) { setAttributes({ height: v }); }
                    }),
                    el(SelectControl, {
                        label: __('Image fit', 'breeze-block-slider'),
                        value: objectFit,
                        options: [
                            { label: __('Cover', 'breeze-block-slider'), value: 'cover' },
                            { label: __('Contain', 'breeze-block-slider'), value: 'contain' }
                        ],
                        onChange: function(v) { setAttributes({ objectFit: v }); }
                    })
                )
            );

            // Empty state: pick images
            if (!hasImages) {
                return el(
                    'div',
                    blockProps,
                    inspector,
                    el(MediaPlaceholder, {
                        icon: 'images-alt2',
                        labels: {
                            title: __('Slider', 'breeze-block-slider'),
                            instructions: __('Select images to build the slider.', 'breeze-block-slider')
                        },
                        accept: 'image/*',
                        allowedTypes: ['image'],
                        multiple: true,
                        onSelect: onSelectImages
                    })
                );
            }

            // Toolbar: edit the image set
            const toolbar = el(
                BlockControls,
                null,
                el(
                    ToolbarGroup,
                    null,
                    el(MediaUploadCheck, null,
                        el(MediaUpload, {
                            multiple: true,
                            gallery: true,
                            allowedTypes: ['image'],
                            value: images.map(function(img) { return img.id; }),
                            onSelect: onSelectImages,
                            render: function(obj) {
                                return el(ToolbarButton, {
                                    icon: 'edit',
                                    label: __('Edit images', 'breeze-block-slider'),
                                    onClick: obj.open
                                });
                            }
                        })
                    )
                )
            );

            // Static preview: a horizontal strip of the slides
            const preview = el(
                'div',
                { className: 'breeze-slider-editor__strip' },
                images.map(function(img, index) {
                    return el(
                        'div',
                        { key: img.id || index, className: 'breeze-slider-editor__slide' },
                        el('img', { src: img.url, alt: img.alt || '' })
                    );
                })
            );

            const previewFooter = el(
                'div',
                { className: 'breeze-slider-editor__footer' },
                pagination && el(
                    'div',
                    { className: 'breeze-slider-editor__dots' },
                    images.map(function(img, index) {
                        return el('span', {
                            key: index,
                            className: 'breeze-slider-editor__dot' + (index === 0 ? ' is-active' : '')
                        });
                    })
                ),
                el(MediaUploadCheck, null,
                    el(MediaUpload, {
                        multiple: true,
                        gallery: true,
                        allowedTypes: ['image'],
                        value: images.map(function(img) { return img.id; }),
                        onSelect: onSelectImages,
                        render: function(obj) {
                            return el(Button, {
                                variant: 'secondary',
                                onClick: obj.open
                            }, __('Edit images', 'breeze-block-slider'));
                        }
                    })
                )
            );

            return el(
                Fragment,
                null,
                toolbar,
                inspector,
                el('div', blockProps,
                    el('div', { className: 'breeze-slider-editor__hint' },
                        __('Slider preview (drag/autoplay run on the frontend)', 'breeze-block-slider')
                    ),
                    preview,
                    previewFooter
                )
            );
        },

        // Dynamic block: template.php renders the frontend markup
        save: function() {
            return null;
        }
    });
})(window.wp);
