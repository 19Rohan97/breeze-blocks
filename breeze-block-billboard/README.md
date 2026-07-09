# Breeze Block Billboard

A custom WordPress block for creating billboard-style content sections with optional background images.

## Features

- Container block similar to WordPress Core Cover block
- Optional background image using `<img>` tag with `object-fit: cover`
- `.no-image` class added when no image is selected
- Inner container that matches theme's content width breakpoints
- Default content: Heading (H2), Paragraph, and Button
- Allowed child blocks: Heading, Paragraph, Button, Buttons
- Two alignment styles: Left (default) and Center
- Adjustable minimum height (40rem default)
- No build process required - uses vanilla JavaScript
- Works with WordPress 6.0+

## Architecture

This block uses modern WordPress block development best practices:

### `block.json`
Central configuration file containing:
- Block metadata (name, title, description, icon)
- Attribute definitions with defaults
- Block supports (alignment, spacing)
- Asset registration (scripts and styles)
- Reference to PHP template for rendering

### `template.php`
Server-side rendering template that:
- Handles final HTML output
- Uses `wp_get_attachment_image()` for proper responsive images with srcset
- Applies focal point positioning
- Wraps InnerBlocks content in proper structure

### `block.js`
Client-side editor functionality:
- Edit component for block editor interface
- InspectorControls for sidebar settings
- Image upload and focal point picker
- Simplified save function (delegates to PHP template)

### Benefits of this approach:
- **Maintainability**: Block configuration centralized in `block.json`
- **Extensibility**: Easy to add attributes or supports
- **Performance**: Server-side rendering with proper WordPress image handling
- **Standards**: Follows WordPress block development best practices
- **No build step**: Direct JavaScript, no compilation needed

## Installation

1. Download the plugin folder
2. Upload the `breeze-block-billboard` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. The "Billboard" block will appear in the Media category in the block editor

## Usage

1. Add a new block and search for "Billboard"
2. The block will automatically include a Heading, Paragraph, and Button
3. Optionally add a background image via the sidebar settings
4. Use the focal point picker to position the image
5. Select image size (excludes Full/Original, defaults to Large)
6. Choose between Left or Center alignment using the Styles panel

## Block Details

- **Block Name:** breeze/billboard
- **Display Name:** Billboard
- **Category:** Media
- **Allowed Child Blocks:** Heading, Paragraph, Button, Buttons

## Customization

You can modify the styles in:
- `editor.css` - Editor-only styles
- `style.css` - Frontend and editor styles

To extend functionality, edit:
- `block.json` - Add new attributes or supports
- `template.php` - Modify HTML output structure
- `block.js` - Add new controls or editor features

## License

This plugin is provided as-is for use in your WordPress projects.
