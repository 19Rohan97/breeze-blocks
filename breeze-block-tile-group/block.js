(function(wp) {
    const { registerBlockType, createBlock } = wp.blocks;
    const { InspectorControls, InnerBlocks, BlockControls, useBlockProps, useInnerBlocksProps } = wp.blockEditor;
    const { PanelBody, SelectControl, RangeControl, TextControl, Notice, ToolbarGroup, ToolbarButton } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { useEffect, Fragment } = wp.element;
    const { createHigherOrderComponent } = wp.compose;
    const { addFilter } = wp.hooks;
    const { __ } = wp.i18n;
    const el = wp.element.createElement;

    // Bricks registers each component as its own block under this namespace
    // (requires the Bricks setting "Components in block editor")
    const BRICKS_PREFIX = 'bricks-components/';
    const TILE_GROUP_BLOCK = 'breeze/tile-group';

    // Plugin settings localized by PHP (Settings → Tile Group)
    const settings = window.BreezeTileGroupSettings || {};
    const excludedComponents = settings.excludedComponents || [];

    registerBlockType('breeze/tile-group', {

        edit: function(props) {
            const { attributes, setAttributes, clientId } = props;
            const { layoutMode, minTileWidth, columns, columnsTablet, columnsMobile, gap, rowGap, verticalAlign, componentId } = attributes;
            const isAutoFit = layoutMode === 'auto';

            // All Bricks component blocks currently registered (skip the
            // hidden placeholders Bricks registers for disabled components,
            // and components excluded on the Settings → Tile Group page)
            const componentBlocks = useSelect(function(select) {
                return select('core/blocks').getBlockTypes().filter(function(blockType) {
                    if (blockType.name.indexOf(BRICKS_PREFIX) !== 0) {
                        return false;
                    }
                    if (blockType.supports && blockType.supports.inserter === false) {
                        return false;
                    }
                    return excludedComponents.indexOf(blockType.name.slice(BRICKS_PREFIX.length)) === -1;
                });
            }, []);

            const innerBlocks = useSelect(function(select) {
                return select('core/block-editor').getBlocks(clientId);
            }, [clientId]);

            const { replaceInnerBlocks } = useDispatch('core/block-editor');

            // Pre-select the component named "Tiles" if it exists, else the first one
            useEffect(function() {
                if (!componentId && componentBlocks.length) {
                    const tiles = componentBlocks.find(function(blockType) {
                        return String(blockType.title).trim().toLowerCase() === 'tiles';
                    });
                    const chosen = tiles || componentBlocks[0];
                    setAttributes({ componentId: chosen.name.slice(BRICKS_PREFIX.length) });
                }
            }, [componentBlocks.length]);

            // Populate the grid with instances of the selected component, and
            // swap the tiles out when a different component is selected
            useEffect(function() {
                if (!componentId) {
                    return;
                }

                const blockName = BRICKS_PREFIX + componentId;

                const isRegistered = componentBlocks.some(function(blockType) {
                    return blockType.name === blockName;
                });

                if (!isRegistered) {
                    return;
                }

                const alreadyPopulated = innerBlocks.length > 0 && innerBlocks.every(function(block) {
                    return block.name === blockName;
                });

                if (alreadyPopulated) {
                    return;
                }

                const count = innerBlocks.length || columns || 3;
                const newBlocks = [];
                for (let i = 0; i < count; i++) {
                    newBlocks.push(createBlock(blockName));
                }

                replaceInnerBlocks(clientId, newBlocks, false);
            }, [componentId, componentBlocks.length]);

            const blockProps = useBlockProps({
                className: 'breeze-tile-group breeze-tile-group--editor' + (isAutoFit ? ' breeze-tile-group--auto' : ''),
                style: {
                    '--btg-columns': String(columns || 3),
                    '--btg-columns-tablet': String(columnsTablet || 2),
                    '--btg-columns-mobile': String(columnsMobile || 1),
                    '--btg-min-width': minTileWidth || '280px',
                    '--btg-gap': gap || '24px',
                    '--btg-row-gap': rowGap || gap || '24px',
                    '--btg-align': verticalAlign || 'stretch'
                }
            });

            const innerProps = useInnerBlocksProps(blockProps, {
                allowedBlocks: componentBlocks.map(function(blockType) {
                    return blockType.name;
                }),
                renderAppender: InnerBlocks.ButtonBlockAppender
            });

            const componentOptions = componentBlocks.map(function(blockType) {
                return {
                    label: blockType.title,
                    value: blockType.name.slice(BRICKS_PREFIX.length)
                };
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
                        el(SelectControl, {
                            label: __('Layout mode', 'breeze-block-tile-group'),
                            value: layoutMode,
                            options: [
                                { label: __('Fixed columns', 'breeze-block-tile-group'), value: 'columns' },
                                { label: __('Auto-fit (min tile width)', 'breeze-block-tile-group'), value: 'auto' }
                            ],
                            onChange: function(value) {
                                setAttributes({ layoutMode: value });
                            }
                        }),
                        isAutoFit && el(TextControl, {
                            label: __('Minimum tile width', 'breeze-block-tile-group'),
                            help: __('The grid fits as many columns as space allows, e.g. 280px', 'breeze-block-tile-group'),
                            value: minTileWidth,
                            onChange: function(value) {
                                setAttributes({ minTileWidth: value });
                            }
                        }),
                        !isAutoFit && el(RangeControl, {
                            label: __('Columns (desktop)', 'breeze-block-tile-group'),
                            min: 1,
                            max: 6,
                            value: columns,
                            onChange: function(value) {
                                setAttributes({ columns: value || 1 });
                            }
                        }),
                        !isAutoFit && el(RangeControl, {
                            label: __('Columns (tablet)', 'breeze-block-tile-group'),
                            min: 1,
                            max: 4,
                            value: columnsTablet,
                            onChange: function(value) {
                                setAttributes({ columnsTablet: value || 1 });
                            }
                        }),
                        !isAutoFit && el(RangeControl, {
                            label: __('Columns (mobile)', 'breeze-block-tile-group'),
                            min: 1,
                            max: 2,
                            value: columnsMobile,
                            onChange: function(value) {
                                setAttributes({ columnsMobile: value || 1 });
                            }
                        }),
                        el(TextControl, {
                            label: __('Gap', 'breeze-block-tile-group'),
                            help: __('Any CSS size, e.g. 24px or 2rem', 'breeze-block-tile-group'),
                            value: gap,
                            onChange: function(value) {
                                setAttributes({ gap: value });
                            }
                        }),
                        el(TextControl, {
                            label: __('Row gap', 'breeze-block-tile-group'),
                            help: __('Leave empty to use the same value as Gap', 'breeze-block-tile-group'),
                            value: rowGap,
                            onChange: function(value) {
                                setAttributes({ rowGap: value });
                            }
                        }),
                        el(SelectControl, {
                            label: __('Vertical alignment', 'breeze-block-tile-group'),
                            value: verticalAlign,
                            options: [
                                { label: __('Stretch (equal height)', 'breeze-block-tile-group'), value: 'stretch' },
                                { label: __('Top', 'breeze-block-tile-group'), value: 'start' },
                                { label: __('Center', 'breeze-block-tile-group'), value: 'center' },
                                { label: __('Bottom', 'breeze-block-tile-group'), value: 'end' }
                            ],
                            onChange: function(value) {
                                setAttributes({ verticalAlign: value });
                            }
                        })
                    ),
                    el(
                        PanelBody,
                        { title: __('Component', 'breeze-block-tile-group') },
                        componentBlocks.length
                            ? el(SelectControl, {
                                label: __('Bricks component', 'breeze-block-tile-group'),
                                help: __('Selecting a different component replaces the tiles in the grid.', 'breeze-block-tile-group'),
                                value: componentId,
                                options: componentOptions,
                                onChange: function(value) {
                                    setAttributes({ componentId: value });
                                }
                            })
                            : el(
                                Notice,
                                { status: 'warning', isDismissible: false },
                                __('No Bricks component blocks found. Create a component in Bricks and enable "Components in block editor" in the Bricks settings.', 'breeze-block-tile-group')
                            )
                    )
                ),
                el('div', innerProps)
            );
        },

        save: function() {
            // Return InnerBlocks content only (the Bricks component blocks)
            // The PHP template (template.php) renders the grid wrapper
            return el(InnerBlocks.Content);
        }
    });

    /* --------------------------------------------------------------------
     * "Duplicate tile" toolbar button
     *
     * The tiles are Bricks' own component blocks, so we can't edit their
     * `edit` component directly. Instead we extend every block's edit via
     * the editor.BlockEdit filter and add the button only when the block
     * sits directly inside a Tile Group. Unlike the + appender (which adds
     * a blank tile), this clones the tile WITH its current property values.
     * ------------------------------------------------------------------ */
    const withTileDuplicate = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            const rootClientId = useSelect(function(select) {
                return select('core/block-editor').getBlockRootClientId(props.clientId);
            }, [props.clientId]);

            const parentName = useSelect(function(select) {
                return rootClientId
                    ? select('core/block-editor').getBlockName(rootClientId)
                    : '';
            }, [rootClientId]);

            const { insertBlock } = useDispatch('core/block-editor');
            const { getBlockIndex } = useSelect(function(select) {
                return { getBlockIndex: select('core/block-editor').getBlockIndex };
            }, []);

            // Only tiles that live directly inside a Tile Group
            if (parentName !== TILE_GROUP_BLOCK) {
                return el(BlockEdit, props);
            }

            function duplicateTile() {
                // Reset blockId so Bricks assigns the clone a fresh unique ID
                // (Bricks derives the element ID from this attribute)
                const attributes = Object.assign({}, props.attributes, { blockId: '' });
                const clone = createBlock(props.name, attributes);
                const index = getBlockIndex(props.clientId);

                // Insert right after this tile and select the copy
                insertBlock(clone, index + 1, rootClientId, true);
            }

            return el(
                Fragment,
                null,
                el(BlockEdit, props),
                el(
                    BlockControls,
                    null,
                    el(
                        ToolbarGroup,
                        null,
                        el(ToolbarButton, {
                            icon: 'admin-page',
                            label: __('Duplicate tile', 'breeze-block-tile-group'),
                            onClick: duplicateTile
                        })
                    )
                )
            );
        };
    }, 'withTileDuplicate');

    addFilter('editor.BlockEdit', 'breeze/tile-group/duplicate', withTileDuplicate);
})(window.wp);
