# Breeze Block Tile Group

A custom WordPress block that arranges **Bricks component blocks** in a responsive grid — so tiles designed once in Bricks Builder become individually editable in the Gutenberg block editor, using Bricks' own native property controls.

## Features

- "Tile Group" block with two layout modes:
  - **Fixed columns** — per-breakpoint columns (desktop/tablet/mobile)
  - **Auto-fit** — set a minimum tile width and the grid fits as many columns as space allows
- Column + row gap and vertical alignment (stretch/top/center/bottom)
- Component picker listing all Bricks components registered as blocks (pre-selects the one named "Tiles")
- **Duplicate tile** toolbar button on each tile — clones it with its current property values (the + appender adds a blank tile)
- Settings page (Settings → Tile Group) to choose which components are offered in the picker — new components are available by default
- The grid auto-populates with instances of the selected component; switching the component swaps the tiles
- Each tile IS a native Bricks component block: clicking it shows Bricks' own Properties panel (real toggles, icon picker, image picker, link control, etc.)
- Rendering and CSS are handled entirely by Bricks — identical output to inserting the component anywhere else
- Add or remove tiles freely with the + appender
- No build process required - uses vanilla JavaScript
- Works with WordPress 6.0+ and Bricks 2.x

## Requirements

- The Bricks theme must be active
- The Bricks setting **"Components in block editor"** must be enabled (this is what makes Bricks register each component as a `bricks-components/{id}` block)

## Architecture

This plugin registers only ONE block — the grid wrapper. The tiles are Bricks' native component blocks.

### `block.json`
Central configuration:
- Attributes: `layoutMode`, `minTileWidth`, `columns`, `columnsTablet`, `columnsMobile`, `gap`, `rowGap`, `verticalAlign`, `componentId`
- Registers `block.js`, `editor.css`, `style.css`, and `template.php`

### `settings.php`
Admin settings page (Settings → Tile Group):
- Include/exclude Bricks components from the Tile Group picker
- Stores settings in the option `breeze_block_tile_group_settings`; component exclusions (not inclusions) are stored, so newly created components are available without re-saving

### `template.php`
Server-side rendering template:
- Wraps the inner Bricks component blocks in the grid container
- Grid layout via CSS custom properties (`--btg-columns`, `--btg-gap`)

### `block.js`
Client-side editor functionality:
- Discovers registered `bricks-components/*` block types at runtime
- Columns / gap / component picker in the sidebar
- Auto-populates the grid via `replaceInnerBlocks` and restricts inner blocks to Bricks component blocks

## Installation

1. Download the plugin folder
2. Upload the `breeze-block-tile-group` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. The "Tile Group" block will appear in the Design category in the block editor

## Usage

1. Create a component in Bricks Builder (e.g. "Tiles") and give it properties with connections
2. Enable "Components in block editor" in the Bricks settings
3. In the block editor, add a "Tile Group" block — the grid auto-fills with your component
4. Click any tile and edit its properties in the sidebar (Bricks' native panel)
5. Add more tiles with the + appender; set columns/gap on the parent block

## Block Details

- **Block Name:** breeze/tile-group
- **Display Name:** Tile Group
- **Category:** Design
- **Allowed Child Blocks:** all `bricks-components/*` blocks registered by Bricks

## Customization

You can modify the styles in:
- `editor.css` - Editor-only styles (the tile appender)
- `style.css` - Frontend and editor styles (the grid)

To extend functionality, edit:
- `block.json` - Add new attributes or supports
- `template.php` - Modify HTML output structure
- `block.js` - Add new controls or editor features

## License

This plugin is provided as-is for use in your WordPress projects.
