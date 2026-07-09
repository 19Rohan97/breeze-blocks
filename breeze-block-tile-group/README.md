# Breeze Block Tile Group

A custom WordPress block that renders a grid of **Bricks component** instances — so tiles designed once in Bricks Builder become individually editable in the Gutenberg block editor.

## Features

- Parent "Tile Group" block with a responsive CSS grid (columns + gap controls)
- Component picker listing all saved Bricks components (pre-selects the one named "Tiles")
- Each grid cell is a "Tile" block — add or remove tiles freely with the + appender
- Clicking a tile shows its content controls in the sidebar, **generated automatically from the Bricks component's property schema** (text, textarea, number, select, toggle, color, image, link, icon)
- Tiles render server-side through Bricks' own renderer, so frontend output matches the component design exactly
- No build process required - uses vanilla JavaScript
- Works with WordPress 6.0+ and Bricks 1.12+ (Components with properties)

## Architecture

This plugin registers two blocks, both configured via `block.json`:

### `block.json` (breeze/tile-group)
Parent grid block:
- Attributes: `columns`, `gap`, `componentId`
- Provides `componentId` to child tiles via block context
- Registers `block.js`, `editor.css`, `style.css`, and `template.php`

### `tile/block.json` (breeze/tile)
Child tile block:
- Attributes: `properties` (the tile's component property values)
- Consumes `componentId` from the parent via block context
- Locked to the Tile Group parent
- Rendered by `tile/template.php`

### `breeze-block-tile-group.php`
Main plugin file and the Bricks bridge:
- Registers both blocks
- Reads saved Bricks components (and their property schemas) from the `bricks_components` option and hands them to the editor script
- `breeze_block_tile_group_render_tile()` builds a Bricks component instance from a tile's property values and renders it via `\Bricks\Frontend::render_data()`
- Best-effort generation of the component's CSS for pages Bricks doesn't render itself

### `block.js`
Client-side editor functionality for both blocks:
- Tile Group: grid preview, columns/gap controls, component picker
- Tile: ServerSideRender preview + sidebar controls generated from the selected component's properties

## Installation

1. Download the plugin folder
2. Upload the `breeze-block-tile-group` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. The "Tile Group" block will appear in the Design category in the block editor

## Usage

1. Create a component in Bricks Builder (e.g. "Tiles") and give it properties (heading, image, link, ...)
2. In the block editor, add a "Tile Group" block — it starts with 3 tiles
3. Set columns/gap and pick the component in the sidebar
4. Click any tile and edit its content in the "Tile Content" sidebar panel
5. Add more tiles with the + appender inside the grid

## Block Details

- **Block Names:** breeze/tile-group (parent), breeze/tile (child)
- **Display Names:** Tile Group, Tile
- **Category:** Design
- **Allowed Child Blocks:** Tile (locked to Tile Group)

## Extension points

Two filters are available for tuning the Bricks integration:

- `breeze_block_tile_group/instance` — the component instance element array before it is passed to the Bricks renderer (adjust here if property values don't reach the component on your Bricks version)
- `breeze_block_tile_group/component_css` — the generated component CSS string (adjust here if styles are missing or duplicated)

## Customization

You can modify the styles in:
- `editor.css` - Editor-only styles
- `style.css` - Frontend and editor styles (the grid)

To extend functionality, edit:
- `block.json` / `tile/block.json` - Add new attributes or supports
- `template.php` / `tile/template.php` - Modify HTML output structure
- `block.js` - Add new controls or editor features

## License

This plugin is provided as-is for use in your WordPress projects.
