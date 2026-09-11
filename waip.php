<?php
/**
 * Plugin Name: WordPress AI Platform (WAIP)
 * Description: Motor de asistentes de IA modular y marca blanca para WordPress.
 * Version: 1.3.36
 * Author: Mariana Cubillos
 * Text Domain: waip
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Definir constantes del plugin antes del autoloader
if (!defined('WAIP_VERSION')) {
    define('WAIP_VERSION', '1.3.24');
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

    if (isset($_GET['waip_test_cron']) && current_user_can('manage_options')) {
        check_admin_referer('waip_manual_cron');
        \Waip\Jobs\LeadAnalyzerJob::run(true); // Forzar sin esperar 1 hora
        wp_die('Cron ejecutado manualmente (Ignorando espera de 1 hora). Revisa tu correo o el dashboard.');
    }

    register_setting(\Waip\Config\Constants::SETTINGS_GROUP, \Waip\Config\Constants::OPTION_IS_ACTIVE);

    // Filtro para ofuscar la API Key antes de guardarla en la base de datos
    add_filter('pre_update_option_' . \Waip\Config\Constants::OPTION_API_KEY, function($new_value, $old_value) {
        if (empty($new_value)) return $new_value;
        // Si contiene asteriscos, asumimos que el usuario no la cambió y devolvemos el valor original (encriptado o no)
        if (strpos($new_value, '****') !== false) {
            return $old_value;
        }
        return 'WAIP_ENC:' . base64_encode($new_value);
    }, 10, 2);

    if (isset($_GET['waip_recalculate_leads']) && current_user_can('manage_options')) {
        check_admin_referer('waip_recalculate_leads');
        global $wpdb;
        $messages = $wpdb->get_results("SELECT conversation_id, content FROM " . \Waip\Config\Constants::tableMessages() . " WHERE sender = 'user'");
        
        $count = 0;
        foreach ($messages as $msg) {
            $message = $msg->content;
            $conversation_id = $msg->conversation_id;
            $updated = false;

            $extracted = \Waip\Services\LeadExtractor::extractContactInfo($message);
            
            if ($extracted['email'] || $extracted['phone'] || $extracted['name']) {
                \Waip\Repositories\MessageRepository::updateConversationLead($conversation_id, $extracted['name'], $extracted['email'], $extracted['phone']);
                $updated = true;
            }

            if ($updated) $count++;
        wp_die("Recalculado con éxito. Se escanearon " . count($messages) . " mensajes antiguos y se rescataron o actualizaron datos de contacto en {$count} de ellos. <br><br><a href='" . admin_url('admin.php?page=waip-dashboard') . "'>Volver al Dashboard</a>");
    }

    if (isset($_GET['waip_update_prompt']) && current_user_can('manage_options')) {
        $new_prompt = "Eres el asesor virtual inteligente de Coodelsur. Tu objetivo principal es RESOLVER las dudas del cliente por ti mismo usando la base de conocimiento. Debes ser sumamente amable, claro y conversacional. NUNCA ofrezcas transferir a un asesor humano o soporte a menos que el cliente lo exija repetidamente o la información requerida sea confidencial y esté totalmente fuera de tu alcance. Puedes realizar simulaciones de crédito aclarando estrictamente que son valores estimados y puramente informativos (requisitos de crédito). Para pagos de créditos o aportes, entrega siempre este link exacto: https://sadminweb.sadmin.net/PayValida.aspx?id9MC/7TNSkqpyr3LpANIg==.";
        
        $new_welcome = "¡Hola! Soy tu asistente virtual de Coodelsur. ¿En qué te puedo colaborar el día de hoy?";

        update_option(\Waip\Config\Constants::OPTION_SYSTEM_PROMPT, $new_prompt);
        update_option(\Waip\Config\Constants::OPTION_WELCOME_MSG, $new_welcome);

        wp_die("¡Configuración actualizada con éxito! Se quitaron los botones de opciones, el mensaje de bienvenida ahora es directo y la IA ha sido configurada para resolver las dudas por sí misma sin escalar al asesor tan rápido. <br><br><a href='" . admin_url('admin.php?page=waip-settings') . "'>Ver Configuración</a>");
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
