<?php
namespace Waip\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Dashboard {

    public function init() {
        add_action('admin_menu', [$this, 'override_dashboard_menu']);
        add_action('admin_init', [$this, 'handle_export']);
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

    public function handle_export() {
        if (isset($_GET['page']) && $_GET['page'] === 'waip-dashboard' && isset($_GET['waip_export']) && $_GET['waip_export'] === 'csv') {
            if (!current_user_can('manage_options')) {
                wp_die('No tienes permisos suficientes.');
            }
            
            $conversations_data = \Waip\Repositories\MessageRepository::getAllConversations(1, 10000);
            $conversations = $conversations_data['items'];

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=waip-conversaciones-' . date('Y-m-d') . '.csv');
            
            // Añadir BOM para que Excel lea correctamente los acentos (UTF-8)
            echo "\xEF\xBB\xBF";

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Fecha', 'Nombre del Cliente', 'Email del Cliente', 'Observación / Necesidad', 'Estado']);

            foreach ($conversations as $conv) {
                // Obtener los mensajes del cliente para crear un resumen de su necesidad
                $messages = \Waip\Repositories\MessageRepository::getMessagesForConversation($conv['id'], 50);
                $user_messages = [];
                foreach ($messages as $msg) {
                    if ($msg['role'] === 'user') {
                        $user_messages[] = trim(preg_replace('/\s+/', ' ', $msg['content']));
                    }
                }
                
                $observacion = implode(" | ", $user_messages);
                if (strlen($observacion) > 1000) {
                    $observacion = substr($observacion, 0, 997) . '...';
                }
                
                // Si la conversación no tiene nombre, email, ni mensajes del usuario, omitirla para no ensuciar el Excel
                if (empty($conv['user_name']) && empty($conv['user_email']) && empty($observacion)) {
                    continue;
                }

                fputcsv($output, [
                    date('d/m/Y H:i', strtotime($conv['updated_at'])),
                    $conv['user_name'] ?: 'Anónimo',
                    $conv['user_email'] ?: 'No registrado',
                    $observacion,
                    $conv['status'] === 'active' ? 'En progreso' : 'Cerrada'
                ]);
            }
            fclose($output);
            exit;
        }
    }

    public function render_dashboard_page() {
        require WAIP_PLUGIN_DIR . 'src/Views/admin-dashboard.php';
    }

    public function render_knowledge_page() {
        require WAIP_PLUGIN_DIR . 'src/Views/admin-knowledge.php';
    }
}
