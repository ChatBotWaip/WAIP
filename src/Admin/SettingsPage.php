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
    }

    public function add_menu_page() {
        add_menu_page(
            'WAIP',
            'WAIP AI',
            'manage_options',
            'waip-dashboard',
            '', // Dashboard handles this, see Dashboard.php
            'dashicons-robot',
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
        register_setting($group, Constants::OPTION_PRIMARY_COLOR);
    }

    public function render_page() {
        require WAIP_PLUGIN_DIR . 'src/Views/admin-settings.php';
    }
}
