<?php
/*
    Plugin Name: Openseadragon-Annotorious2.7
    Plugin URI: arwai.me
    Description: Extends WordPress to manage and annotate image collections via OpenSeadragon and Annotorious 2.7.
    Version: 1.2.0
    Author: Arwai
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) { die; }

// --- Plugin Constants ---
define( 'ARWAI_OPENSEADRAGON_ANNOTORIOUS_VERSION', '2.7.0' );
define( 'ARWAI_OPENSEADRAGON_ANNOTORIOUS_URL', plugin_dir_url( __FILE__ ) );
define( 'ARWAI_OPENSEADRAGON_ANNOTORIOUS_PATH', plugin_dir_path( __FILE__ ) );

// --- Autoloader ---
// This automatically loads the required class files from the /includes/ directory.
spl_autoload_register(function ( $class ) {
    $prefix = 'ARWAI\\';
    $base_dir = __DIR__ . '/includes/';
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }
    $relative_class = substr( $class, $len );
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
    if ( file_exists( $file ) ) {
        require $file;
    }
});

// --- Activation/Deactivation Hooks ---
require_once ARWAI_OPENSEADRAGON_ANNOTORIOUS_PATH . 'includes/Core/Database.php';
register_activation_hook( __FILE__, array( 'ARWAI\\Core\\Database', 'activate' ) );

// --- Run the Plugin ---
function run_arwai_openseadragon_annotorious_plugin() {
    $plugin = new ARWAI\Core\Plugin();
    $plugin->run();
}
run_arwai_openseadragon_annotorious_plugin();