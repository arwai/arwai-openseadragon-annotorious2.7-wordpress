<?php
namespace ARWAI\Public;
// Separates the the_content filter logic.

class ContentFilter {
    private $filter_called = 0;

    public function render_viewer($content) {
        $active_types = get_option('arwai_openseadragon_active_post_types', ['post', 'page']);
        if (!is_singular($active_types) || !in_the_loop() || !is_main_query() || $this->filter_called > 0) {
            return $content;
        }

        $post_id = get_the_ID();
        if (!$post_id) return $content;

        $display_mode = get_post_meta($post_id, '_arwai_openseadragon_post_display_mode', true) ?: get_option('arwai_openseadragon_default_new_post_mode', 'metabox_viewer');

        if ('metabox_viewer' === $display_mode) {
            $image_ids = json_decode(get_post_meta($post_id, '_arwai_multi_image_ids', true), true);
            if (!empty($image_ids)) {
                $this->filter_called++;
                $viewer_id = 'openseadragon-viewer-' . $post_id;
                $viewer_html = '<div id="' . esc_attr($viewer_id) . '" style="width: 100%; height: 600px; background-color: #000;"></div>';
                return $viewer_html . $content;
            }
        }

        return $content;
    }
}