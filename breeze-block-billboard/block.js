(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, InspectorAdvancedControls, InnerBlocks, MediaUpload, MediaUploadCheck, useBlockProps } = wp.blockEditor;
    const { PanelBody, Button, SelectControl, FocalPointPicker, TextControl } = wp.components;
    const { useSelect } = wp.data;
    const { __ } = wp.i18n;
    const el = wp.element.createElement;

    const ALLOWED_BLOCKS = ['core/heading', 'core/paragraph', 'core/button', 'core/buttons'];

    const TEMPLATE = [
        ['core/heading', { level: 2, placeholder: __('Add heading...', 'breeze-block-billboard') }],
        ['core/paragraph', { placeholder: __('Add description...', 'breeze-block-billboard') }],
        ['core/buttons', {}, [
            ['core/button', { text: __('Learn More', 'breeze-block-billboard') }]
        ]]
    ];

    registerBlockType('breeze/billboard', {

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { backgroundImage, imageSize, focalPoint, alt, caption, videoUrl, overlay } = attributes;

            const mediaData = useSelect(
                function(select) {
                    if (!backgroundImage?.id) return null;
                    return select('core').getMedia(backgroundImage.id);
                },
                [backgroundImage]
            );

            const imageUrl = mediaData?.media_details?.sizes?.[imageSize]?.source_url || 
                           mediaData?.source_url || 
                           backgroundImage?.url;

            const imageSizes = mediaData?.media_details?.sizes || {};
            const availableSizes = Object.keys(imageSizes).filter(function(size) {
                return size !== 'full';
            });

            const additionalClasses = 'billboard-block alignfull' + (imageUrl || videoUrl ? '' : ' no-image') + (overlay === 'darken' ? ' has-darken-overlay' : '');
            
            const blockProps = useBlockProps({
                className: additionalClasses
            });

            const focalPointStyle = focalPoint ? {
                objectPosition: (focalPoint.x * 100) + '% ' + (focalPoint.y * 100) + '%',
                width: '100%',
                height: '100%'
            } : {
                width: '100%',
                height: '100%'
            };

            return el(
                'div',
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __('Media Settings', 'breeze-block-billboard') },
                        !videoUrl && el(
                            MediaUploadCheck,
                            null,
                            el(
                                MediaUpload,
                                {
                                    onSelect: function(media) {
                                        setAttributes({
                                            backgroundImage: {
                                                id: media.id,
                                                url: media.url
                                            },
                                            alt: media.alt || '',
                                            caption: media.caption || ''
                                        });
                                    },
                                    allowedTypes: ['image'],
                                    value: backgroundImage?.id,
                                    render: function(obj) {
                                        return el(
                                            'div',
                                            { style: { marginBottom: '12px' } },
                                            el(
                                                Button,
                                                {
                                                    onClick: obj.open,
                                                    variant: 'secondary'
                                                },
                                                backgroundImage 
                                                    ? __('Replace Image', 'breeze-block-billboard') 
                                                    : __('Select Image', 'breeze-block-billboard')
                                            ),
                                            backgroundImage && el(
                                                Button,
                                                {
                                                    onClick: function() {
                                                        setAttributes({ 
                                                            backgroundImage: null,
                                                            alt: ''
                                                        });
                                                    },
                                                    variant: 'tertiary',
                                                    isDestructive: true,
                                                    style: { marginLeft: '8px' }
                                                },
                                                __('Remove', 'breeze-block-billboard')
                                            )
                                        );
                                    }
                                }
                            )
                        ),
                        backgroundImage && availableSizes.length > 0 && el(SelectControl, {
                            label: __('Image Size', 'breeze-block-billboard'),
                            value: imageSize,
                            options: availableSizes.map(function(size) {
                                return {
                                    label: size.charAt(0).toUpperCase() + size.slice(1),
                                    value: size
                                };
                            }),
                            onChange: function(value) {
                                setAttributes({ imageSize: value });
                            }
                        }),
                        backgroundImage && !videoUrl && imageUrl && el(
                            'div',
                            { style: { marginTop: '16px' } },
                            el('p', { style: { marginBottom: '8px', fontWeight: '500' } }, 
                                __('Focal Point', 'breeze-block-billboard')
                            ),
                            el(FocalPointPicker, {
                                url: imageUrl,
                                value: focalPoint,
                                onChange: function(value) {
                                    setAttributes({ focalPoint: value });
                                }
                            })
                        ),
                        (backgroundImage || videoUrl) && el(SelectControl, {
                            label: __('Overlay', 'breeze-block-billboard'),
                            value: overlay,
                            options: [
                                { label: __('Default', 'breeze-block-billboard'), value: 'default' },
                                { label: __('Darken', 'breeze-block-billboard'), value: 'darken' }
                            ],
                            onChange: function(value) {
                                setAttributes({ overlay: value });
                            }
                        })
                    )
                ),
                el(
                    InspectorAdvancedControls,
                    null,
                    el(TextControl, {
                        label: __('External media', 'breeze-block-billboard'),
                        value: videoUrl,
                        onChange: function(value) {
                            setAttributes({ videoUrl: value });
                        },
                        placeholder: 'https://example.com/video.mp4'
                    })
                ),
                el(
                    'div',
                    blockProps,
                    videoUrl && el('video', {
                        className: 'billboard-block__video',
                        src: videoUrl,
                        autoplay: true,
                        muted: true,
                        loop: true,
                        playsInline: true
                    }),
                    !videoUrl && imageUrl && el('img', {
                        className: 'billboard-block__image',
                        src: imageUrl,
                        alt: alt || '',
                        style: focalPointStyle
                    }),
                    !videoUrl && imageUrl && caption && el(
                        'div',
                        { className: 'billboard-block__caption' },
                        caption
                    ),
                    el(
                        'div',
                        { className: 'billboard-block__container' },
                        el(
                            'div',
                            { className: 'billboard-block__content' },
                            el(InnerBlocks, {
                                allowedBlocks: ALLOWED_BLOCKS,
                                template: TEMPLATE
                            })
                        )
                    )
                )
            );
        },

        save: function() {
            // Return InnerBlocks content only
            // The PHP template (template.php) handles the full rendering
            return el(InnerBlocks.Content);
        }
    });
})(window.wp);
