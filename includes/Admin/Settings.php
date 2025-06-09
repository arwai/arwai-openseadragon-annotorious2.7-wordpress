<?php
namespace ARWAI\Admin;
// This class is dedicated solely to creating and managing the plugin's settings page.


class Settings {
    const OPTION_DEFAULT_MODE = 'arwai_openseadragon_default_new_post_mode';
    const OPTION_POST_TYPES = 'arwai_openseadragon_active_post_types';
    const OPTIONS_GROUP = 'arwai_openseadragon_options_group';
    const PAGE_SLUG = 'arwai-openseadragon-settings';

    public function add_menu_page() {
        add_options_page('Openseadragon-Annotorious Settings', 'Openseadragon-Annotorious', 'manage_options', self::PAGE_SLUG, array($this, 'render_page'));
    }

    public function register_settings() {
        register_setting(self::OPTIONS_GROUP, self::OPTION_DEFAULT_MODE, ['type' => 'string', 'default' => 'metabox_viewer']);
        register_setting(self::OPTIONS_GROUP, self::OPTION_POST_TYPES, ['type' => 'array', 'default' => ['post', 'page']]);

        add_settings_section('main_section', 'Global Settings', array($this, 'render_section_text'), self::PAGE_SLUG);
        add_settings_field('default_mode_field', 'Default Viewer Mode', array($this, 'render_default_mode_field'), self::PAGE_SLUG, 'main_section');
        add_settings_field('post_types_field', 'Activate on Post Types', array($this, 'render_post_types_field'), self::PAGE_SLUG, 'main_section');
    }

    public function render_page() {
        ?>
        <div class="wrap">
            <h1>Openseadragon-Annotorious Settings</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields(self::OPTIONS_GROUP);
                do_settings_sections(self::PAGE_SLUG);
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    public function render_section_text() {
        echo '<p>Configure global settings for the plugin.</p>';
    }

    public function render_default_mode_field() {
        $option = get_option(self::OPTION_DEFAULT_MODE, 'metabox_viewer');
        ?>
        <fieldset>
            <label><input type="radio" name="<?php echo esc_attr(self::OPTION_DEFAULT_MODE); ?>" value="metabox_viewer" <?php checked($option, 'metabox_viewer'); ?>> Default Viewer (uses Image Collection)</label><br>
            <label><input type="radio" name="<?php echo esc_attr(self::OPTION_DEFAULT_MODE); ?>" value="gutenberg_block" <?php checked($option, 'gutenberg_block'); ?>> Gutenberg Block</label>
        </fieldset>
        <?php
    }
    
    public function render_post_types_field() {
        $saved_options = get_option(self::OPTION_POST_TYPES, ['post', 'page']);
        $post_types = get_post_types(['public' => true], 'objects');
        ?>
        <fieldset>
            <?php foreach ($post_types as $post_type) : if ($post_type->name === 'attachment') continue; ?>
                <label>
                    <input type="checkbox" name="<?php echo esc_attr(self::OPTION_POST_TYPES); ?>[]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, $saved_options, true)); ?>>
                    <?php echo esc_html($post_type->labels->name); ?>
                </label><br>
            <?php endforeach; ?>
        </fieldset>
        <?php
    }
}