<?php
namespace ARWAI\Public;
//Handles the public-facing hooks.


class PublicController {
    private $content_filter;

    public function __construct() {
        $this->content_filter = new ContentFilter();
    }
    
    public function register() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this->content_filter, 'render_viewer'), 20);
    }

    public function enqueue_scripts() {
        $active_types = get_option('arwai_openseadragon_active_post_types', ['post', 'page']);
        if (!is_singular($active_types)) return;

        $post_id = get_the_ID();
        if (!$post_id) return;
        
        $display_mode = get_post_meta($post_id, '_arwai_openseadragon_post_display_mode', true) ?: get_option('arwai_openseadragon_default_new_post_mode', 'metabox_viewer');
        
        if ('metabox_viewer' === $display_mode) {
            $image_ids = json_decode(get_post_meta($post_id, '_arwai_multi_image_ids', true), true);
            if (!empty($image_ids) && is_array($image_ids)) {
                $image_sources = array_reduce($image_ids, function ($carry, $id) {
                    $src = wp_get_attachment_image_src($id, 'full');
                    if ($src) $carry[] = ['type' => 'image', 'url' => $src[0], 'post_id' => $id];
                    return $carry;
                }, []);
                
                if (!empty($image_sources)) {
                    wp_enqueue_style('arwai-annotorious-css', ARWAI_OPENSEADRAGON_ANNOTORIOUS_URL . 'assets/css/annotorious/annotorious.min.css', array(), ARWAI_OPENSEADRAGON_ANNOTORIOUS_VERSION);
                    wp_enqueue_script('arwai-openseadragon-js', ARWAI_OPENSEADRAGON_ANNOTORIOUS_URL . 'assets/js/openseadragon/openseadragon.min.js', array(), ARWAI_OPENSEADRAGON_ANNOTORIOUS_VERSION, true);
                    wp_enqueue_script('arwai-annotorious-core-js', ARWAI_OPENSEADRAGON_ANNOTORIOUS_URL . 'assets/js/annotorious/annotorious.min.js', array(), ARWAI_OPENSEADRAGON_ANNOTORIOUS_VERSION, true);
                    wp_enqueue_script('arwai-annotorious-osd-plugin-js', ARWAI_OPENSEADRAGON_ANNOTORIOUS_URL . 'assets/js/annotorious/openseadragon-annotorious.min.js', array('arwai-openseadragon-js', 'arwai-annotorious-core-js'), ARWAI_OPENSEADRAGON_ANNOTORIOUS_VERSION, true);
                    wp_enqueue_script('arwai-public-js', ARWAI_OPENSEADRAGON_ANNOTORIOUS_URL . 'assets/js/public/script.js', array('jquery', 'arwai-annotorious-osd-plugin-js'), ARWAI_OPENSEADRAGON_ANNOTORIOUS_VERSION, true);
                    
                    wp_localize_script('arwai-public-js', 'ArwaiOSD_ViewerConfig', ['id' => 'openseadragon-viewer-' . $post_id, 'images' => $image_sources]);
                    wp_localize_script('arwai-public-js', 'ArwaiOSD_Vars', ['ajax_url' => admin_url('admin-ajax.php'), 'prefixUrl' => ARWAI_OPENSEADRAGON_ANNOTORIOUS_URL . 'assets/images/']);
                }
            }
        }
    }
}