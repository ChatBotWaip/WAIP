<?php
namespace Waip\Admin;

use Waip\Config\Constants;

if (!defined('ABSPATH')) {
    exit;
}

class SettingsPage {

    public function init() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function enqueue_admin_scripts($hook) {
        if (!isset($_GET['page']) || $_GET['page'] !== 'waip-settings') {
            return;
        }
        wp_enqueue_media();
    }

    public function add_menu_page() {
        $robot_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a2 2 0 0 1 2 2v2h3a2 2 0 0 1 2 2v2h2v4h-2v2a2 2 0 0 1-2 2h-3v2a2 2 0 0 1-4 0v-2H7a2 2 0 0 1-2-2v-2H3v-4h2V8a2 2 0 0 1 2-2h3V4a2 2 0 0 1 2-2zm0 2a1 1 0 0 0-1 1v1h2V5a1 1 0 0 0-1-1zM7 8v10h10V8H7zm2 2h2v2H9v-2zm4 0h2v2h-2v-2zm-3 5h4v2H10v-2z"/></svg>';
        $icon_url = 'data:image/svg+xml;base64,' . base64_encode($robot_svg);

        add_menu_page(
            'WAIP',
            'WAIP AI',
            'manage_options',
            'waip-dashboard',
            '', // Dashboard maneja esto, ver Dashboard.php
            $icon_url,
            25
        );
        
        add_submenu_page(
            'waip-dashboard',
            'Configuración WAIP',
            'Ajustes',
            'manage_options',
            'waip-settings',
            [$this, 'render_page']
        );
    }

    public function register_settings() {
        $group = Constants::SETTINGS_GROUP;

        register_setting($group, Constants::OPTION_API_KEY);
        register_setting($group, Constants::OPTION_MODEL);
        register_setting($group, Constants::OPTION_SYSTEM_PROMPT);
        register_setting($group, Constants::OPTION_ASSISTANT_NAME);
        register_setting($group, Constants::OPTION_ASSISTANT_LOGO);
        register_setting($group, Constants::OPTION_PRIMARY_COLOR);
        register_setting($group, Constants::OPTION_SECONDARY_COLOR);
        register_setting($group, Constants::OPTION_WELCOME_MSG);
        register_setting($group, Constants::OPTION_IDLE_MSG);
        register_setting($group, Constants::OPTION_IDLE_TIME);
        register_setting($group, Constants::OPTION_WHATSAPP_NUMBER);
        register_setting($group, Constants::OPTION_SIMULATOR_MODE);
        register_setting($group, Constants::OPTION_MAINTENANCE_MSG);
        register_setting($group, Constants::OPTION_IS_ACTIVE);
    }

    public function render_page() {
        if (isset($_POST['waip_purge_logs']) && current_user_can('manage_options')) {
            global $wpdb;
            $wpdb->query("DELETE FROM " . Constants::DB_LOGS . " WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            echo '<div class="notice notice-success is-dismissible"><p>Logs antiguos purgados exitosamente.</p></div>';
        }
        require WAIP_PLUGIN_DIR . 'src/Views/admin-settings.php';
    }
}
