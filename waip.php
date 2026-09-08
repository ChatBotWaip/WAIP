<?php
/**
 * Plugin Name: WordPress AI Platform (WAIP)
 * Description: Motor de asistentes de IA modular y marca blanca para WordPress.
 * Version: 1.3.16
 * Author: Mariana Cubillos
 * Text Domain: waip
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Definir constantes del plugin antes del autoloader
if (!defined('WAIP_VERSION')) {
    define('WAIP_VERSION', '1.0.1');
}
if (!defined('WAIP_DB_VERSION')) {
    define('WAIP_DB_VERSION', '1.4.0');
}
if (!defined('WAIP_PLUGIN_DIR')) {
    define('WAIP_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('WAIP_PLUGIN_URL')) {
    define('WAIP_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Autoloader PSR-4
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

// Inicializar Plugin
if (!function_exists('waip_init')) {
    function waip_init() {
        $settingsManager = new \Waip\Config\SettingsManager();
        $settingsManager->init();

        if (!is_admin() && \Waip\Config\SettingsManager::isPubliclyVisible()) {
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
            
            $playground = new \Waip\Admin\PlaygroundPage();
            $playground->init();
        }
    }
}
add_action('rest_api_init', function() {
    $chatController = new \Waip\Controllers\ChatController();
    $chatController->register_routes();
});
add_action('plugins_loaded', 'waip_init');

// Ejecutar migraciones al activar
register_activation_hook(__FILE__, ['Waip\Database\Migrations', 'run']);

// Ejecutar migraciones si la versión cambió (para actualizaciones)
add_action('plugins_loaded', function() {
    if (get_option('waip_db_version') !== WAIP_DB_VERSION) {
        \Waip\Database\Migrations::run();
        update_option('waip_db_version', WAIP_DB_VERSION);
    }
    
    // Inicializar trabajos en segundo plano
    \Waip\Jobs\LeadAnalyzerJob::init();
});

// Añadir un trigger para pruebas manuales del cron de correos y actualización del DB
add_action('admin_init', function() {
    // Forzar actualización de esquema para añadir columna user_phone
    if (get_option('waip_db_version') !== '1.3.10') {
        \Waip\Database\Migrations::run();
        update_option('waip_db_version', '1.3.10');
    }

    if (isset($_GET['waip_test_cron']) && current_user_can('manage_options')) {
        \Waip\Jobs\LeadAnalyzerJob::run();
        wp_die('Cron ejecutado manualmente. Revisa tu correo o el dashboard.');
    }

    if (isset($_GET['waip_recalculate_leads']) && current_user_can('manage_options')) {
        global $wpdb;
        $messages = $wpdb->get_results("SELECT conversation_id, content FROM " . \Waip\Config\Constants::DB_MESSAGES . " WHERE sender = 'user'");
        
        $count = 0;
        foreach ($messages as $msg) {
            $message = $msg->content;
            $conversation_id = $msg->conversation_id;
            $updated = false;

            $name_patterns = [
                '/(?:me llamo|mi nombre es|soy|me dicen|hola[\s,]+(?:soy|me llamo))\s+([a-záéíóúñA-ZÁÉÍÓÚÑ]+(?:\s+[a-záéíóúñA-ZÁÉÍÓÚÑ]+){0,2})/iu',
                '/(?:hola|buenos?\s+d[ií]as?|buenas?\s+tardes?|buenas?\s+noches?)[\s,.:!]+([a-záéíóúñA-ZÁÉÍÓÚÑ]+(?:\s+[a-záéíóúñA-ZÁÉÍÓÚÑ]+)?)\s+(?:aqu[ií]|tengo|quisiera|necesito|quiero|estoy)/iu',
                '/(?:nombre)[\s:]+([a-záéíóúñA-ZÁÉÍÓÚÑ]+(?:\s+[a-záéíóúñA-ZÁÉÍÓÚÑ]+){0,2})/iu',
            ];
            if (preg_match('/^([a-záéíóúñA-ZÁÉÍÓÚÑ]+(?:\s+[a-záéíóúñA-ZÁÉÍÓÚÑ]+){0,2})$/iu', trim($message), $matches)) {
                $possible_name = trim($matches[1]);
                $excluded = ['Hola', 'Buenos', 'Buenas', 'Gracias', 'Ayuda', 'Listo', 'Claro', 'Vale', 'Perfecto', 'Consulta', 'Si', 'No'];
                if (!in_array(ucfirst(strtolower($possible_name)), $excluded) && mb_strlen($possible_name) > 2) {
                    \Waip\Repositories\MessageRepository::updateConversationLead($conversation_id, $possible_name, null);
                    $updated = true;
                }
            } else {
                foreach ($name_patterns as $pattern) {
                    if (preg_match($pattern, $message, $matches)) {
                        $name = trim($matches[1]);
                        \Waip\Repositories\MessageRepository::updateConversationLead($conversation_id, $name, null);
                        $updated = true;
                        break;
                    }
                }
            }

            if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $message, $matches)) {
                \Waip\Repositories\MessageRepository::updateConversationLead($conversation_id, null, $matches[0]);
                $updated = true;
            }

            if (preg_match('/(?:\+?57)?[\s-]*(?:3\d{2})[\s-]*\d{3}[\s-]*\d{2}[\s-]*\d{2}/', $message, $matches)) {
                $phone = preg_replace('/[\s-]/', '', $matches[0]);
                \Waip\Repositories\MessageRepository::updateConversationLead($conversation_id, null, null, $phone);
                $updated = true;
            }

            if ($updated) $count++;
        }
        
        wp_die("Recalculado con éxito. Se escanearon " . count($messages) . " mensajes antiguos y se rescataron o actualizaron datos de contacto en {$count} de ellos. <br><br><a href='" . admin_url('admin.php?page=waip-dashboard') . "'>Volver al Dashboard</a>");
    }
});

// Integración con GitHub Update Checker
if (file_exists(WAIP_PLUGIN_DIR . 'src/lib/plugin-update-checker/plugin-update-checker.php')) {
    require_once WAIP_PLUGIN_DIR . 'src/lib/plugin-update-checker/plugin-update-checker.php';
    
    $waipUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/ChatBotWaip/WAIP',
        __FILE__,
        'waip'
    );
    // Configurado para actualizar desde la rama main
    $waipUpdateChecker->setBranch('main');
    
    // IMPORTANTE: Si el repositorio es PRIVADO, necesitas un Personal Access Token (PAT)
    // Descomenta la siguiente línea y pon tu token ahí:
    // $waipUpdateChecker->setAuthentication('tu_token_aqui');
}
