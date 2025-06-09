<?php
namespace ARWAI\Admin;
// This class registers all admin-related functionality, including settings, metaboxes, scripts, and AJAX handlers.


use ARWAI\Data\AnnotationRepository;

class Admin {
    private $settings;
    private $metabox;
    private $repository;

    public function __construct() {
        $this->settings = new Settings();
        $this->metabox = new Metabox();
        $this->repository = new AnnotationRepository();
    }

    /**
     * Register all admin-related hooks.
     */
    public function register() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_init', array( $this->settings, 'register_settings' ) );
        add_action( 'admin_menu', array( $this->settings, 'add_menu_page' ) );
        add_action( 'add_meta_boxes', array( $this->metabox, 'add_metaboxes' ) );
        add_action( 'save_post', array( $this->metabox, 'save_metabox_data' ), 10, 2 );

        // Register AJAX hooks
        $this->register_ajax_hooks();
    }

    public function enqueue_scripts( $hook_suffix ) {
        if ( in_array($hook_suffix, array('post.php', 'post-new.php')) ) {
            $screen = get_current_screen();
            $active_types = get_option('arwai_openseadragon_active_post_types', ['post', 'page']);
            if ( $screen && in_array( $screen->post_type, $active_types ) ) {
                wp_enqueue_script('arwai-admin-js', ARWAI_OPENSEADRAGON_ANNOTORIOUS_URL . 'assets/js/admin/admin.js', array('jquery', 'jquery-ui-sortable'), ARWAI_OPENSEADRAGON_ANNOTORIOUS_VERSION, true);
                wp_enqueue_media();
            }
        }
    }

    private function register_ajax_hooks() {
        $ajax_actions = ['get', 'add', 'delete', 'update'];
        foreach ($ajax_actions as $action) {
            add_action("wp_ajax_arwai_anno_{$action}", array($this, "ajax_{$action}"));
            add_action("wp_ajax_nopriv_arwai_anno_{$action}", array($this, "ajax_{$action}"));
        }
        add_action("wp_ajax_arwai_get_annotorious_history", array($this, "ajax_get_history"));
        add_action("wp_ajax_nopriv_arwai_get_annotorious_history", array($this, "ajax_get_history"));
    }

    // --- AJAX Handlers ---
    public function ajax_get() {
        $attachment_id = isset($_GET['attachment_id']) ? intval($_GET['attachment_id']) : 0;
        if (empty($attachment_id)) wp_send_json_error('Missing attachment_id.');
        
        $data = $this->repository->get($attachment_id);
        wp_send_json($data);
    }
    
    public function ajax_add() {
        $annotation_json = isset($_POST['annotation']) ? wp_unslash($_POST['annotation']) : '';
        if (empty($annotation_json)) wp_send_json_error('Annotation data missing.');

        $result = $this->repository->add($annotation_json);
        $result ? wp_send_json_success($result) : wp_send_json_error("Failed to add annotation.");
    }
    
    public function ajax_delete() {
        $annoid = isset($_POST['annotationid']) ? sanitize_text_field($_POST['annotationid']) : '';
        $annotation_json = isset($_POST['annotation']) ? wp_unslash($_POST['annotation']) : '';
        if (empty($annoid) || empty($annotation_json)) wp_send_json_error('Missing data.');

        $result = $this->repository->delete($annoid, $annotation_json);
        $result ? wp_send_json_success() : wp_send_json_error("Failed to delete annotation.");
    }

    public function ajax_update() {
        $annoid = isset($_POST['annotationid']) ? sanitize_text_field($_POST['annotationid']) : '';
        $annotation_json = isset($_POST['annotation']) ? wp_unslash($_POST['annotation']) : '';
        if (empty($annoid) || empty($annotation_json)) wp_send_json_error('Missing data.');

        $result = $this->repository->update($annoid, $annotation_json);
        $result ? wp_send_json_success() : wp_send_json_error("Failed to update annotation.");
    }

    public function ajax_get_history() {
        $attachment_id = isset($_GET['attachment_id']) ? intval($_GET['attachment_id']) : 0;
        $annotation_id = isset($_GET['annotation_id']) ? sanitize_text_field($_GET['annotation_id']) : '';
        if (empty($attachment_id) && empty($annotation_id)) wp_send_json_error('Missing ID.');
        
        $data = $this->repository->get_history($attachment_id, $annotation_id);
        wp_send_json_success(['history' => $data]);
    }
}