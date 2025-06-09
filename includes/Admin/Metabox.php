<?php
namespace ARWAI\Admin;
//This class handles the creation, rendering, and saving of the metaboxes.


class Metabox {
    const META_DISPLAY_MODE = '_arwai_openseadragon_post_display_mode';
    const META_SET_FEATURED = '_arwai_openseadragon_set_first_as_featured';
    const META_IMAGE_IDS = '_arwai_multi_image_ids';  

    public function add_metaboxes() {
        $active_post_types = get_option('arwai_openseadragon_active_post_types', ['post', 'page']);
        if (empty($active_post_types)) return;

        add_meta_box('arwai_openseadragon_display_mode', 'Viewer Mode', array($this, 'render_display_mode'), $active_post_types, 'side');
        add_meta_box('arwai_multi_image_uploader', 'Image Collection', array($this, 'render_image_uploader'), $active_post_types, 'normal', 'high');
    }

    public function render_display_mode($post) {
        $current_mode = get_post_meta($post->ID, self::META_DISPLAY_MODE, true) ?: get_option('arwai_openseadragon_default_new_post_mode', 'metabox_viewer');
        ?>
        <p><label><input type="radio" name="<?php echo esc_attr(self::META_DISPLAY_MODE); ?>" value="metabox_viewer" <?php checked($current_mode, 'metabox_viewer'); ?>> Default Viewer</label></p>
        <p><label><input type="radio" name="<?php echo esc_attr(self::META_DISPLAY_MODE); ?>" value="gutenberg_block" <?php checked($current_mode, 'gutenberg_block'); ?>> Gutenberg Block</label></p>
        <?php
    }

    public function render_image_uploader($post) {
        wp_nonce_field('arwai_multi_image_uploader_save', 'arwai_multi_image_uploader_nonce');
        $image_ids_json = get_post_meta($post->ID, self::META_IMAGE_IDS, true);
        $image_ids = json_decode($image_ids_json, true) ?: [];
        ?>
        <div id="arwai-multi-image-uploader-container">
            <p class="description">Select images from the media library. Drag to reorder.</p>
            <ul class="arwai-multi-image-list">
                <?php if (!empty($image_ids)) : foreach ($image_ids as $id) :
                    $thumb_url = wp_get_attachment_image_url($id, 'thumbnail');
                    if ($thumb_url) : ?>
                        <li data-id="<?php echo esc_attr($id); ?>">
                            <img src="<?php echo esc_url($thumb_url); ?>" style="max-width:100px; max-height:100px;" />
                            <a href="#" class="arwai-multi-image-remove dashicons dashicons-trash"></a>
                        </li>
                <?php endif; endforeach; endif; ?>
            </ul>
            <p>
                <a href="#" class="button arwai-multi-image-add-button">Add/Select Images</a>
                <input type="hidden" id="arwai_multi_image_ids_field" name="<?php echo esc_attr(self::META_IMAGE_IDS); ?>" value="<?php echo esc_attr($image_ids_json); ?>" />
            </p>
            <p><label><input type="checkbox" name="<?php echo esc_attr(self::META_SET_FEATURED); ?>" value="yes" <?php checked(get_post_meta($post->ID, self::META_SET_FEATURED, true), 'yes'); ?>> Use first image as post's featured image.</label></p>
        </div>
        <style>.arwai-multi-image-list { display: flex; flex-wrap: wrap; } .arwai-multi-image-list li { position: relative; cursor: move; width: 100px; height: 100px; margin: 5px; border: 1px solid #ccc; } .arwai-multi-image-remove { position: absolute; top: 0; right: 0; } .arwai-multi-image-placeholder { border: 1px dashed #ccc; width: 100px; height: 100px; margin: 5px; }</style>
        <?php
    }

    public function save_metabox_data($post_id, $post) {
        if (!isset($_POST['arwai_multi_image_uploader_nonce']) || !wp_verify_nonce($_POST['arwai_multi_image_uploader_nonce'], 'arwai_multi_image_uploader_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        // Save display mode
        if (isset($_POST[self::META_DISPLAY_MODE])) {
            update_post_meta($post_id, self::META_DISPLAY_MODE, sanitize_text_field($_POST[self::META_DISPLAY_MODE]));
        }

        // Save image IDs
        if (isset($_POST[self::META_IMAGE_IDS])) {
            $ids = json_decode(wp_unslash($_POST[self::META_IMAGE_IDS]), true);
            $sanitized_ids = is_array($ids) ? array_map('intval', $ids) : [];
            update_post_meta($post_id, self::META_IMAGE_IDS, json_encode($sanitized_ids));
        } else {
            delete_post_meta($post_id, self::META_IMAGE_IDS);
        }

        // Save "Set First as Featured"
        $set_featured = isset($_POST[self::META_SET_FEATURED]) ? 'yes' : 'no';
        update_post_meta($post_id, self::META_SET_FEATURED, $set_featured);
        if ('yes' === $set_featured) {
            $ids = json_decode(get_post_meta($post_id, self::META_IMAGE_IDS, true), true);
            if (!empty($ids) && intval($ids[0]) > 0) {
                set_post_thumbnail($post_id, intval($ids[0]));
            }
        }
    }
}