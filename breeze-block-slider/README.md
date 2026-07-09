# Breeze Block Slider

An image slider / carousel Gutenberg block powered by [Splide](https://splidejs.com/). Pure Gutenberg — no Bricks components involved — but it **reuses the Splide library that the Bricks theme already registers** so the script/CSS are never loaded twice.

## Features

- Select multiple images (WordPress media library) and reorder them as slides
- Splide-powered: loop / rewind / fade transitions, drag, keyboard, autoplay
- Controls: slides per view, slides to move, gap, arrows, pagination dots
- Autoplay with interval + transition speed
- Appearance: corner radius (default 4px), slide height, image fit (cover/contain)
- Wide/full alignment and margin/padding supports
- No build step (vanilla JS using the `wp.*` editor globals)

## How Splide is loaded (no duplicates)

Bricks registers Splide 4.1.4 under the script/style handle **`bricks-splide`**
(`bricks/includes/setup.php`). Because WordPress de-duplicates enqueues by
handle, this plugin simply enqueues that same handle:

- If `bricks-splide` is registered (Bricks active) → we reuse it. One copy on
  the page, even if Bricks sliders are also present.
- If it isn't (Bricks inactive) → we register a matching Splide 4.1.4 build from
  a CDN under our own `breeze-splide` handle as a fallback.

Bricks' own Splide auto-init only targets `.brxe-slider-nested.splide`, so it
never touches our `.breeze-slider`, and our init (`view.js`) never touches
Bricks sliders. No collisions in either direction.

## Architecture

### `block.json`
Block metadata + attributes; registers `block.js`, `editor.css`, and
`template.php` (dynamic render).

### `breeze-block-slider.php`
- Registers the block
- On `wp_enqueue_scripts` (priority 20, after Bricks) resolves the Splide
  handles, then registers `view.js` (depends on Splide) and `style.css`
  (depends on the Splide CSS so overrides win)

### `template.php`
Frontend markup. Emits the Splide structure
(`.splide > .splide__track > .splide__list > .splide__slide`) and passes all
options as JSON in the `data-splide` attribute (Splide reads options from
there). Enqueues the assets only when the block actually has images.

### `view.js`
Mounts `new Splide(el).mount()` on each `.breeze-slider`. Guards against
double-mount and waits for `window.Splide` defensively.

### `block.js`
Editor UI: a media picker for the slides and an inspector for all options.
The editor shows a **static** preview strip (the real drag/autoplay slider
runs on the frontend) to stay robust inside the block-editor iframe.

## Installation

1. Upload the `breeze-block-slider` folder to `/wp-content/plugins/`
2. Activate it
3. Add the **Slider** block (Media category) and select images

## Block Details

- **Block Name:** breeze/slider
- **Category:** Media
- **Render:** dynamic (server-rendered via `template.php`)

## Notes / possible follow-ups

- Per-slide links or captions (currently images only, matching the design)
- Breakpoint-specific `perPage` (Splide supports `breakpoints`)
- Thumbnail navigation / a second synced slider
