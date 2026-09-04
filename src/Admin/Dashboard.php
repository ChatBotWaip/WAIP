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
        // Rareza de WordPress: add_menu_page crea un menú de nivel superior, y su primer submenú es implícitamente el mismo slug.
        // Lo redefinimos aquí para poder proporcionar un callback para la página principal 'waip-dashboard'.
        add_submenu_page(
            'waip-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'waip-dashboard',
            [$this, 'render_dashboard_page']
        );

        add_submenu_page(
            'waip-dashboard',
            'Base de Conocimiento',
            'Conocimiento (RAG)',
            'manage_options',
            'waip-knowledge',
            [$this, 'render_knowledge_page']
        );
    }

    public function render_dashboard_page() {
        require WAIP_PLUGIN_DIR . 'src/Views/admin-dashboard.php';
    }

    public function render_knowledge_page() {
        require WAIP_PLUGIN_DIR . 'src/Views/admin-knowledge.php';
    }
}
