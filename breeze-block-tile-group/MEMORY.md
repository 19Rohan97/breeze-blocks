# Tile Group — Project Memory

A working record of what the **Breeze Block Tile Group** plugin is, why it's
built the way it is, and the key decisions made along the way. Read this before
making changes so you don't re-derive (or undo) something we already settled.

---

## 1. What it is

A WordPress Gutenberg plugin that lets editors build a **responsive grid of
Bricks components** in the block editor, where **each tile is individually
editable** using Bricks' own native property controls.

- Repo: `19Rohan97/breeze-blocks`
- Plugin folder: `breeze-block-tile-group/`
- Sibling reference plugin (structure we copied): `breeze-block-billboard/`
- Bricks theme source (read-only reference): `bricks/`
- Bricks child theme (unrelated demo element lives here): `bricks-child/`

## 2. The core insight (read this first)

The single most important design decision: **we do NOT render Bricks components
ourselves.** Bricks already ships a native Gutenberg integration. When the
Bricks setting **"Components in block editor"** (`bricksComponentsInBlockEditor`)
is enabled, Bricks registers **every component as its own block** named
`bricks-components/{componentId}`, complete with:

- the correct property controls (real toggles, the visual icon picker, image
  picker, link control, variant selector, …),
- server-side rendering via `ServerSideRender`,
- CSS generation + scoping for the editor canvas and the frontend,
- webfont/icon-font loading.

See `bricks/includes/integrations/block-editor.php` and
`bricks/assets/js/integrations/gutenberg/component-blocks.js` for how Bricks
does this.

**Therefore the Tile Group is just a grid wrapper.** The tiles ARE Bricks'
component blocks, used as inner blocks. Everything about a tile's editing and
rendering is Bricks' responsibility, not ours. This keeps the plugin tiny and
means future Bricks component changes (new properties, variants, styling) flow
through automatically with zero code changes here.

> History: earlier versions (v1.0.x) reimplemented the bridge in PHP — reading
> the `bricks_components` option, generating generic Gutenberg controls from the
> property schema, building component instances, and calling
> `\Bricks\Frontend::render_data()`. That produced *wrong* controls (e.g. plain
> text fields instead of toggles / icon pickers) because it guessed at types.
> **We deleted all of it** in v1.1.0. Do not bring it back.

## 3. File map

```
breeze-block-tile-group/
├── breeze-block-tile-group.php   Plugin header; registers the block; localizes
│                                 settings (excluded components) to the editor.
├── block.json                    Block metadata + attributes + asset refs.
├── block.js                      Editor UI (vanilla JS, no build step):
│                                   - the Tile Group edit/save
│                                   - the "Duplicate tile" toolbar filter
├── template.php                  Frontend render: grid wrapper + CSS vars.
├── settings.php                  Settings → Tile Group admin page.
├── style.css                     Grid CSS (frontend + editor).
├── editor.css                    Editor-only (styles the + appender cell).
├── README.md                     User/developer facing docs.
└── MEMORY.md                     This file.
```

Only **one** block is registered: `breeze/tile-group`. There is intentionally no
child "tile" block of our own — the tiles are `bricks-components/*` blocks.

## 4. How it works

### Editor (`block.js`)
- Discovers registered `bricks-components/*` block types at runtime via
  `select('core/blocks').getBlockTypes()`.
- Filters out (a) Bricks' hidden placeholder blocks for disabled components
  (`supports.inserter === false`) and (b) components excluded on the settings
  page (`BreezeTileGroupSettings.excludedComponents`, localized from PHP).
- Pre-selects the component titled **"Tiles"** if present, else the first one.
- Auto-populates the grid with instances of the selected component using
  `replaceInnerBlocks`, and swaps the tiles when a different component is chosen.
- `allowedBlocks` on the inner blocks is restricted to the available
  `bricks-components/*` blocks.

### Frontend (`template.php`)
- Dynamic block; `save()` returns `InnerBlocks.Content` (the Bricks component
  blocks serialize themselves; Bricks renders them).
- The template only emits the grid `<div>` wrapper with CSS custom properties;
  it sanitizes numeric/size values and clamps the vertical-align enum.

### Layout is all CSS custom properties on the wrapper
`--btg-columns`, `--btg-columns-tablet`, `--btg-columns-mobile`,
`--btg-min-width`, `--btg-gap`, `--btg-row-gap`, `--btg-align`.
Breakpoints match Bricks defaults: tablet ≤ 991px, mobile ≤ 767px.

### "Duplicate tile" button
Tiles are Bricks blocks, so we can't touch their `edit`. We extend **every**
block via `addFilter('editor.BlockEdit', …)` and add a `BlockControls` toolbar
button **only** when the block's parent is `breeze/tile-group`. It clones the
tile *with its current attributes* (so filled-in property values come along),
resets `blockId` to `''` (Bricks derives a unique element ID from it — sharing
would collide), and inserts the copy right after, selected.
Contrast: the `+` appender adds a **blank** tile.

## 5. Settings page (`settings.php`)
- Menu: **Settings → Tile Group** (`options-general.php?page=breeze-tile-group`).
- Option name: `breeze_block_tile_group_settings`.
- Stores **exclusions**, not inclusions: the form submits the list of *available*
  components and we save the inverse. This way **newly created components are
  available by default** without anyone re-visiting settings.
- Warns if Bricks is inactive or "Components in block editor" is off.

## 6. Attributes (`block.json`)
| Attribute | Default | Meaning |
|---|---|---|
| `layoutMode` | `"columns"` | `"columns"` (fixed) or `"auto"` (auto-fit) |
| `minTileWidth` | `"280px"` | Min tile width in auto-fit mode |
| `columns` | `3` | Desktop columns (fixed mode) |
| `columnsTablet` | `2` | Tablet columns (≤991px) |
| `columnsMobile` | `1` | Mobile columns (≤767px) |
| `gap` | `"24px"` | Column gap |
| `rowGap` | `""` | Row gap (falls back to `gap`) |
| `verticalAlign` | `"stretch"` | `stretch`/`start`/`center`/`end` |
| `componentId` | `""` | Selected component's ID (block name minus prefix) |

Auto-fit CSS: `repeat(auto-fill, minmax(min(var(--btg-min-width), 100%), 1fr))`.
The `min(…, 100%)` guard prevents overflow on very narrow screens.

## 7. Requirements
- Bricks theme active.
- Bricks setting **"Components in block editor"** enabled (this is what registers
  the `bricks-components/*` blocks — without it the picker is empty).
- WordPress 6.0+. No build step (plain JS using the `wp.*` editor globals:
  blocks, blockEditor, components, data, element, compose, hooks, i18n).

## 8. Decisions we deliberately made (don't silently reverse)
- **Reuse Bricks' native component blocks** instead of a custom render bridge.
- **Store exclusions** so new components default to available.
- **Duplicate = clone with values**; `+` appender = blank. Both kept.
- **Reveal/stagger animation was built (v1.3.0) then removed entirely (v1.4.0)**
  at the user's request. If it's ever wanted again, it was a global settings
  toggle + a frontend `reveal.js` using `IntersectionObserver`, soft fade +
  `translateY(18px)`, staggered `transition-delay`, easing
  `cubic-bezier(0.16, 1, 0.3, 1)`, respecting `prefers-reduced-motion`. Recover
  it from git history around commit `7178aee` rather than rewriting.

## 9. Version history
- **1.0.0–1.0.1** — Standalone plugin; PHP bridge reimplementing Bricks rendering
  and generating controls from the schema. Wrong control types. (Superseded.)
- **1.1.0** — Rewrite: tiles became Bricks' native `bricks-components/*` blocks;
  deleted the whole PHP bridge and the custom child "tile" block.
- **1.2.0** — Settings page (include/exclude components); responsive per-breakpoint
  columns, row gap, vertical alignment.
- **1.3.0** — Auto-fit layout mode; global progressive-reveal animation.
- **1.4.0** — Removed the reveal animation; added the "Duplicate tile" toolbar
  button. **Current.**

## 10. Testing checklist
1. Bricks active + "Components in block editor" on; at least one component.
2. Add **Tile Group** → grid auto-fills with the "Tiles" component.
3. Click a tile → Bricks' native **Properties** panel appears; edits render.
4. **Duplicate** button on a filled tile → identical filled copy beside it.
5. `+` appender → a blank tile.
6. Layout mode → **Auto-fit**, resize the window → columns reflow by width.
7. **Settings → Tile Group** → uncheck a component → it disappears from the
   picker and the allowed inner blocks.
8. Frontend matches the editor (Bricks handles the CSS).

## 11. Known follow-up ideas (not built)
Featured/spanning tiles, masonry, carousel mode. Auto-fit already shipped.
