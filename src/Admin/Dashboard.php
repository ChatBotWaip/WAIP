<?php
namespace Waip\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Dashboard {

    public function init() {
        add_action('admin_menu', [$this, 'override_dashboard_menu']);
    }

    public function override_dashboard_menu() {
        // WordPress weirdness: add_menu_page creates a top level menu, and its first submenu is implicitly the same slug.
        // We redefine it here so we can provide a callback for the main 'waip-dashboard' page.
        add_submenu_page(
            'waip-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'waip-dashboard',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        require WAIP_PLUGIN_DIR . 'src/Views/admin-dashboard.php';
    }
}
