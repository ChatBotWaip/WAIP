<?php
namespace Waip\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class PlaygroundPage {

    public function init() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_menu_page() {
        add_submenu_page(
            'waip-dashboard',
            'Entorno de Pruebas',
            'Pruebas (Playground)',
            'manage_options',
            'waip-playground',
            [$this, 'render_page']
        );
    }

    public function enqueue_assets($hook) {
        if (!isset($_GET['page']) || $_GET['page'] !== 'waip-playground') {
            return;
        }

        // Instanciamos explícitamente ChatWidget aquí para reutilizar su lógica de encolado de recursos
        $chatWidget = new \Waip\Widgets\ChatWidget();
        $chatWidget->enqueue_assets();
    }

    public function render_page() {
        // Instanciamos explícitamente ChatWidget aquí para reutilizar su lógica de renderizado
        $chatWidget = new \Waip\Widgets\ChatWidget();
        
        require WAIP_PLUGIN_DIR . 'src/Views/admin-playground.php';
    }
}
