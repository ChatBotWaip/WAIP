<?php
/**
 * Plugin Name: WordPress AI Platform (WAIP)
 * Description: Motor de asistentes de IA modular y marca blanca para WordPress.
 * Version: 1.0.0
 * Author: Coodelsur
 * Text Domain: waip
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants before autoloader
define('WAIP_VERSION', '1.0.0');
define('WAIP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WAIP_PLUGIN_URL', plugin_dir_url(__FILE__));

// PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'Waip\\';
    $base_dir = WAIP_PLUGIN_DIR . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize Plugin
function waip_init() {
    $settingsManager = new \Waip\Config\SettingsManager();
    $settingsManager->init();

    if (!is_admin()) {
        $chatWidget = new \Waip\Widgets\ChatWidget();
        $chatWidget->init();
    }

    if (is_admin()) {
        $settingsPage = new \Waip\Admin\SettingsPage();
        $settingsPage->init();
        
        $dashboard = new \Waip\Admin\Dashboard();
        $dashboard->init();

        $batchIndexer = new \Waip\Knowledge\BatchIndexer();
        $batchIndexer->init();
    }
}
add_action('rest_api_init', function() {
    $chatController = new \Waip\Controllers\ChatController();
    $chatController->register_routes();
});
add_action('plugins_loaded', 'waip_init');

// Database Migrations Hook
register_activation_hook(__FILE__, function() {
    require_once WAIP_PLUGIN_DIR . 'src/Database/Migrations.php';
    \Waip\Database\Migrations::run();
});
