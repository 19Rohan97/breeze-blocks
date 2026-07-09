<?php
/**
 * Tile Group settings page (Settings → Tile Group).
 *
 * Lets admins choose which Bricks components are offered inside the
 * Tile Group block. Exclusions are stored (rather than inclusions) so
 * newly created components are available by default.
 */

if (!defined('ABSPATH')) {
    exit;
}

const BREEZE_TILE_GROUP_OPTION = 'breeze_block_tile_group_settings';

/**
 * IDs of components excluded from the Tile Group component picker.
 */
function breeze_block_tile_group_get_excluded_components() {
    $settings = get_option(BREEZE_TILE_GROUP_OPTION, array());
    $excluded = isset($settings['excluded_components']) ? (array) $settings['excluded_components'] : array();

    return array_values(array_map('strval', $excluded));
}

/**
 * Whether the progressive reveal animation is enabled (global setting).
 */
function breeze_block_tile_group_reveal_enabled() {
    $settings = get_option(BREEZE_TILE_GROUP_OPTION, array());

    return !empty($settings['reveal_animation']);
}

/**
 * All saved Bricks components as id => label pairs.
 */
function breeze_block_tile_group_get_bricks_components() {
    $stored = get_option('bricks_components', array());

    if (!is_array($stored)) {
        return array();
    }

    $components = array();

    foreach ($stored as $component) {
        if (empty($component['id']) || empty($component['elements'])) {
            continue;
        }

        // Bricks uses the first element's label as the component name
        $label = $component['elements'][0]['label'] ?? '';

        if (!$label) {
            $label = sprintf(__('Component %s', 'breeze-block-tile-group'), $component['id']);
        }

        $components[(string) $component['id']] = $label;
    }

    return $components;
}

function breeze_block_tile_group_admin_menu() {
    add_options_page(
        __('Tile Group', 'breeze-block-tile-group'),
        __('Tile Group', 'breeze-block-tile-group'),
        'manage_options',
        'breeze-tile-group',
        'breeze_block_tile_group_render_settings_page'
    );
}
add_action('admin_menu', 'breeze_block_tile_group_admin_menu');

function breeze_block_tile_group_register_settings() {
    register_setting('breeze_block_tile_group', BREEZE_TILE_GROUP_OPTION, array(
        'type'              => 'array',
        'sanitize_callback' => 'breeze_block_tile_group_sanitize_settings',
        'default'           => array(),
    ));
}
add_action('admin_init', 'breeze_block_tile_group_register_settings');

/**
 * The form submits the AVAILABLE components; store the inverse (exclusions)
 * so components created later are available without re-saving.
 */
function breeze_block_tile_group_sanitize_settings($input) {
    $available = array();

    if (isset($input['available_components']) && is_array($input['available_components'])) {
        $available = array_map('sanitize_text_field', $input['available_components']);
    }

    $all_ids  = array_keys(breeze_block_tile_group_get_bricks_components());
    $excluded = array_values(array_diff($all_ids, $available));

    return array(
        'excluded_components' => $excluded,
        'reveal_animation'    => !empty($input['reveal_animation']),
    );
}

function breeze_block_tile_group_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $components = breeze_block_tile_group_get_bricks_components();
    $excluded   = breeze_block_tile_group_get_excluded_components();

    $bricks_active          = class_exists('\Bricks\Database');
    $block_editor_enabled   = $bricks_active && \Bricks\Database::get_setting('bricksComponentsInBlockEditor');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Tile Group', 'breeze-block-tile-group'); ?></h1>

        <?php if (!$bricks_active) : ?>
            <div class="notice notice-error">
                <p><?php esc_html_e('The Bricks theme is not active. The Tile Group block requires Bricks.', 'breeze-block-tile-group'); ?></p>
            </div>
        <?php elseif (!$block_editor_enabled) : ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e('The Bricks setting "Components in block editor" is disabled, so no component blocks are registered. Enable it in Bricks → Settings.', 'breeze-block-tile-group'); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!$components) : ?>
            <p><?php esc_html_e('No Bricks components found. Create a component in the Bricks builder first.', 'breeze-block-tile-group'); ?></p>
        <?php else : ?>
            <form method="post" action="options.php">
                <?php settings_fields('breeze_block_tile_group'); ?>

                <h2><?php esc_html_e('Animation', 'breeze-block-tile-group'); ?></h2>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row" style="padding: 8px 10px 8px 0;">
                                <label for="breeze-tile-group-reveal">
                                    <?php esc_html_e('Progressive reveal', 'breeze-block-tile-group'); ?>
                                </label>
                            </th>
                            <td style="padding: 8px 10px;">
                                <input
                                    type="checkbox"
                                    id="breeze-tile-group-reveal"
                                    name="<?php echo esc_attr(BREEZE_TILE_GROUP_OPTION); ?>[reveal_animation]"
                                    value="1"
                                    <?php checked(breeze_block_tile_group_reveal_enabled()); ?>
                                />
                                <p class="description">
                                    <?php esc_html_e('Tiles fade in with a gentle upward lift, staggered one after another, when a Tile Group scrolls into view. Applies to all Tile Group blocks. Respects the visitor\'s reduced-motion preference.', 'breeze-block-tile-group'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Available components', 'breeze-block-tile-group'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Checked components are offered in the Tile Group block\'s component picker. New components are available by default.', 'breeze-block-tile-group'); ?>
                </p>

                <table class="form-table" role="presentation">
                    <tbody>
                        <?php foreach ($components as $id => $label) : ?>
                            <tr>
                                <th scope="row" style="padding: 8px 10px 8px 0;">
                                    <label for="breeze-tile-group-component-<?php echo esc_attr($id); ?>">
                                        <?php echo esc_html($label); ?>
                                    </label>
                                </th>
                                <td style="padding: 8px 10px;">
                                    <input
                                        type="checkbox"
                                        id="breeze-tile-group-component-<?php echo esc_attr($id); ?>"
                                        name="<?php echo esc_attr(BREEZE_TILE_GROUP_OPTION); ?>[available_components][]"
                                        value="<?php echo esc_attr($id); ?>"
                                        <?php checked(!in_array((string) $id, $excluded, true)); ?>
                                    />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php submit_button(); ?>
            </form>
        <?php endif; ?>
    </div>
    <?php
}
