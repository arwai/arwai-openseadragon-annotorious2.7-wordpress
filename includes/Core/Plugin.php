<?php
namespace ARWAI\Core;
// This is the main orchestrator. It loads the Admin and Public parts of the plugin.


use ARWAI\Admin\Admin;
use ARWAI\Public\PublicController;

/**
 * The main plugin orchestrator.
 */
class Plugin {
    public function run() {
        if ( is_admin() ) {
            $admin = new Admin();
            $admin->register();
        }

        $public = new PublicController();
        $public->register();
    }
}