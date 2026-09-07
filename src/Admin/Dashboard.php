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
            
            // Aumentar tiempo límite por si hay muchas conversaciones
            set_time_limit(0);
            
            $conversations_data = \Waip\Repositories\MessageRepository::getAllConversations(1, 10000);
            $conversations = $conversations_data['items'];

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=waip-leads-' . date('Y-m-d') . '.csv');
            
            // Añadir BOM para que Excel lea correctamente los acentos (UTF-8)
            echo "\xEF\xBB\xBF";

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Fecha', 'Nombre del Cliente', 'Email del Cliente', 'Prioridad (IA)', 'Observación / Necesidad (IA)', 'Cantidad de Mensajes', 'Estado']);

            foreach ($conversations as $conv) {
                // Solo exportar si tienen nombre o email (Leads identificados)
                if (empty($conv['user_name']) && empty($conv['user_email'])) {
                    continue;
                }
                
                $observacion = !empty($conv['ai_summary']) ? $conv['ai_summary'] : 'Pendiente de análisis';
                $prioridad = !empty($conv['ai_priority']) ? $conv['ai_priority'] : 'No asignada';
                $mensajes_count = isset($conv['message_count']) ? $conv['message_count'] : 0;

                fputcsv($output, [
                    date('d/m/Y H:i', strtotime($conv['updated_at'])),
                    $conv['user_name'] ?: 'Anónimo',
                    $conv['user_email'] ?: 'No registrado',
                    $prioridad,
                    $observacion,
                    $mensajes_count,
                    $conv['status'] === 'active' ? 'Activa' : 'Cerrada'
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
