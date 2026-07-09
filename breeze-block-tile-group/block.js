(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, InnerBlocks, MediaUpload, MediaUploadCheck, useBlockProps, useInnerBlocksProps } = wp.blockEditor;
    const { PanelBody, Button, SelectControl, RangeControl, TextControl, TextareaControl, ToggleControl, ColorPalette, Notice } = wp.components;
    const { useEffect } = wp.element;
    const { __ } = wp.i18n;
    const el = wp.element.createElement;
    const ServerSideRender = wp.serverSideRender;

    // Saved Bricks components (with property schemas), localized by PHP
    const data = window.BreezeTileGroupData || {};
    const componentsList = data.components || [];

    function findComponent(cid) {
        return componentsList.find(function(component) {
            return component.id === cid;
        }) || null;
    }

    function componentOptions() {
        const options = [{ label: __('Select a component…', 'breeze-block-tile-group'), value: '' }];

        componentsList.forEach(function(component) {
            options.push({ label: component.label, value: component.id });
        });

        return options;
    }

    /**
     * Build the right inspector control for one component property,
     * based on the property's type.
     */
    function propertyControl(prop, value, onChange) {
        const type = (prop.type || 'text').toLowerCase();
        const label = prop.label || prop.id;
        const key = prop.id;

        switch (type) {
            case 'textarea':
            case 'richtext':
            case 'editor':
            case 'wysiwyg':
                return el(TextareaControl, {
                    key: key,
                    label: label,
                    value: value || '',
                    onChange: onChange
                });

            case 'number':
                return el(TextControl, {
                    key: key,
                    type: 'number',
                    label: label,
                    value: value === undefined || value === null ? '' : value,
                    onChange: onChange
                });

            case 'select': {
                const opts = [{ label: __('Default', 'breeze-block-tile-group'), value: '' }];
                if (prop.options && typeof prop.options === 'object') {
                    Object.keys(prop.options).forEach(function(optionValue) {
                        opts.push({ label: String(prop.options[optionValue]), value: optionValue });
                    });
                }
                return el(SelectControl, {
                    key: key,
                    label: label,
                    value: value || '',
                    options: opts,
                    onChange: onChange
                });
            }

            case 'checkbox':
            case 'toggle':
            case 'switch':
                return el(ToggleControl, {
                    key: key,
                    label: label,
                    checked: !!value,
                    onChange: onChange
                });

            case 'color': {
                let hex = '';
                if (value && value.hex) {
                    hex = value.hex;
                } else if (typeof value === 'string') {
                    hex = value;
                }
                return el(
                    'div',
                    { key: key, className: 'breeze-tile-prop' },
                    el('p', { className: 'breeze-tile-prop__label' }, label),
                    el(ColorPalette, {
                        value: hex || undefined,
                        onChange: function(newHex) {
                            onChange(newHex ? { hex: newHex } : '');
                        }
                    })
                );
            }

            case 'image': {
                const img = value && typeof value === 'object' ? value : null;
                return el(
                    'div',
                    { key: key, className: 'breeze-tile-prop' },
                    el('p', { className: 'breeze-tile-prop__label' }, label),
                    el(
                        MediaUploadCheck,
                        null,
                        el(MediaUpload, {
                            allowedTypes: ['image'],
                            value: img ? img.id : undefined,
                            onSelect: function(media) {
                                onChange({
                                    id: media.id,
                                    url: media.url,
                                    filename: media.filename || '',
                                    size: 'full'
                                });
                            },
                            render: function(obj) {
                                return el(
                                    'div',
                                    null,
                                    img && img.url && el('img', {
                                        src: img.url,
                                        style: { maxWidth: '100%', display: 'block', marginBottom: '8px' }
                                    }),
                                    el(
                                        Button,
                                        { onClick: obj.open, variant: 'secondary' },
                                        img
                                            ? __('Replace Image', 'breeze-block-tile-group')
                                            : __('Select Image', 'breeze-block-tile-group')
                                    ),
                                    img && el(
                                        Button,
                                        {
                                            onClick: function() { onChange(''); },
                                            variant: 'tertiary',
                                            isDestructive: true,
                                            style: { marginLeft: '8px' }
                                        },
                                        __('Remove', 'breeze-block-tile-group')
                                    )
                                );
                            }
                        })
                    )
                );
            }

            case 'link': {
                let url = '';
                if (typeof value === 'string') {
                    url = value;
                } else if (value && value.url) {
                    url = value.url;
                }
                return el(TextControl, {
                    key: key,
                    label: label,
                    type: 'url',
                    placeholder: 'https://',
                    value: url,
                    onChange: onChange
                });
            }

            case 'icon': {
                let icon = '';
                if (value && value.icon) {
                    icon = value.icon;
                } else if (typeof value === 'string') {
                    icon = value;
                }
                return el(TextControl, {
                    key: key,
                    label: label,
                    help: __('Icon class, e.g. "fas fa-star", "ti-bolt-alt" or "ion-md-alarm". The icon library is detected automatically.', 'breeze-block-tile-group'),
                    value: icon,
                    onChange: onChange
                });
            }

            default:
                // 'text' and any unknown types fall back to a plain text field
                return el(TextControl, {
                    key: key,
                    label: label,
                    value: value === undefined || value === null ? '' : String(value),
                    onChange: onChange
                });
        }
    }

    /* --------------------------------------------------------------------
     * Parent block: Tile Group
     * (metadata comes from block.json; JS only provides edit/save)
     * ------------------------------------------------------------------ */
    registerBlockType('breeze/tile-group', {

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { columns, gap, componentId } = attributes;

            // Pre-select the default component (the one named "Tiles") on insert
            useEffect(function() {
                if (!componentId && data.defaultComponentId) {
                    setAttributes({ componentId: data.defaultComponentId });
                }
            }, []);

            const blockProps = useBlockProps({
                className: 'breeze-tile-group breeze-tile-group--editor',
                style: {
                    '--btg-columns': String(columns || 3),
                    '--btg-gap': gap || '24px'
                }
            });

            const innerProps = useInnerBlocksProps(blockProps, {
                allowedBlocks: ['breeze/tile'],
                template: [['breeze/tile'], ['breeze/tile'], ['breeze/tile']],
                renderAppender: InnerBlocks.ButtonBlockAppender
            });

            return el(
                'div',
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __('Layout', 'breeze-block-tile-group') },
                        el(RangeControl, {
                            label: __('Columns', 'breeze-block-tile-group'),
                            min: 1,
                            max: 6,
                            value: columns,
                            onChange: function(value) {
                                setAttributes({ columns: value || 1 });
                            }
                        }),
                        el(TextControl, {
                            label: __('Gap', 'breeze-block-tile-group'),
                            help: __('Any CSS size, e.g. 24px or 2rem', 'breeze-block-tile-group'),
                            value: gap,
                            onChange: function(value) {
                                setAttributes({ gap: value });
                            }
                        })
                    ),
                    el(
                        PanelBody,
                        { title: __('Component', 'breeze-block-tile-group') },
                        componentsList.length
                            ? el(SelectControl, {
                                label: __('Bricks component', 'breeze-block-tile-group'),
                                value: componentId,
                                options: componentOptions(),
                                onChange: function(value) {
                                    setAttributes({ componentId: value });
                                }
                            })
                            : el(
                                Notice,
                                { status: 'warning', isDismissible: false },
                                __('No Bricks components found. Create one in the Bricks builder first.', 'breeze-block-tile-group')
                            )
                    )
                ),
                el('div', innerProps)
            );
        },

        save: function() {
            // Return InnerBlocks content only
            // The PHP template (template.php) handles the full rendering
            return el(InnerBlocks.Content);
        }
    });

    /* --------------------------------------------------------------------
     * Child block: Tile
     * ------------------------------------------------------------------ */
    registerBlockType('breeze/tile', {

        edit: function(props) {
            const { attributes, setAttributes, context } = props;
            const cid = context['breeze/componentId'] || '';
            const component = findComponent(cid);
            const properties = attributes.properties || {};

            function setProp(id, value) {
                const next = Object.assign({}, properties);

                if (value === '' || value === undefined || value === null) {
                    delete next[id];
                } else {
                    next[id] = value;
                }

                setAttributes({ properties: next });
            }

            let controls;
            if (!cid) {
                controls = el(
                    Notice,
                    { status: 'info', isDismissible: false },
                    __('Select a component on the parent Tile Group block first.', 'breeze-block-tile-group')
                );
            } else if (component && component.properties.length) {
                controls = component.properties.map(function(prop) {
                    return propertyControl(prop, properties[prop.id], function(value) {
                        setProp(prop.id, value);
                    });
                });
            } else {
                controls = el('p', null, __('This component has no editable properties.', 'breeze-block-tile-group'));
            }

            const blockProps = useBlockProps({ className: 'breeze-tile breeze-tile--editor' });

            const preview = cid && ServerSideRender
                ? el(ServerSideRender, {
                    block: 'breeze/tile',
                    attributes: {
                        properties: properties,
                        componentId: cid
                    }
                })
                : el(
                    'div',
                    { className: 'breeze-tile__placeholder' },
                    __('Tile — select a Bricks component on the parent block.', 'breeze-block-tile-group')
                );

            return el(
                'div',
                null,
                el(
                    InspectorControls,
                    null,
                    el(PanelBody, { title: __('Tile Content', 'breeze-block-tile-group') }, controls)
                ),
                el('div', blockProps, preview)
            );
        },

        save: function() {
            // Fully dynamic block: tile/template.php handles all rendering
            return null;
        }
    });
})(window.wp);
