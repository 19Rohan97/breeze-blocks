<?php
/**
 * Editor script dependencies + version for block.js.
 *
 * WordPress reads this automatically for `"editorScript": "file:./block.js"`
 * (it looks for the same filename with `.js` replaced by `.asset.php`). Declaring
 * the wp.* dependencies guarantees they load before block.js, so `wp.blocks`,
 * `wp.blockEditor`, etc. are defined when the block registers.
 */
return array(
    'dependencies' => array(
        'wp-blocks',
        'wp-block-editor',
        'wp-components',
        'wp-element',
        'wp-i18n',
    ),
    'version' => '1.0.0',
);
