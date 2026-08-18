<?php
if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
use Waip\Config\Constants;

// Fetch full stats for the dashboard
$conversations_count = $wpdb->get_var("SELECT COUNT(*) FROM " . Constants::DB_CONVERSATIONS);
$messages_count = $wpdb->get_var("SELECT COUNT(*) FROM " . Constants::DB_MESSAGES);
$total_input_tokens = $wpdb->get_var("SELECT SUM(input_tokens) FROM " . Constants::DB_MESSAGES);
$total_output_tokens = $wpdb->get_var("SELECT SUM(output_tokens) FROM " . Constants::DB_MESSAGES);
$total_cost = $wpdb->get_var("SELECT SUM(total_cost) FROM " . Constants::DB_MESSAGES);

$documents_count = $wpdb->get_var("SELECT COUNT(*) FROM " . Constants::DB_DOCUMENTS);
$embeddings_count = $wpdb->get_var("SELECT COUNT(*) FROM " . Constants::DB_EMBEDDINGS);

// Get recent logs
$recent_logs = $wpdb->get_results("SELECT * FROM " . Constants::DB_LOGS . " ORDER BY created_at DESC LIMIT 5");

?>
<div class="wrap">
    <h1>WAIP Dashboard Analítico (Producción)</h1>
    <p>Panel de control del Motor de Asistentes de IA para WordPress. Versión RAG activa.</p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccd0d4; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0; color: #555;">Conversaciones</h3>
            <p style="font-size: 28px; font-weight: bold; margin: 0; color: #0073aa;"><?php echo intval($conversations_count); ?></p>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccd0d4; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0; color: #555;">Mensajes Totales</h3>
            <p style="font-size: 28px; font-weight: bold; margin: 0; color: #0073aa;"><?php echo intval($messages_count); ?></p>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccd0d4; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0; color: #555;">Tokens Consumidos</h3>
            <p style="font-size: 16px; margin: 0;"><strong>Input:</strong> <?php echo number_format(intval($total_input_tokens)); ?></p>
            <p style="font-size: 16px; margin: 0;"><strong>Output:</strong> <?php echo number_format(intval($total_output_tokens)); ?></p>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccd0d4; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0; color: #555;">Costo Acumulado (USD)</h3>
            <p style="font-size: 28px; font-weight: bold; margin: 0; color: #d63638;">$<?php echo number_format((float)$total_cost, 4); ?></p>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccd0d4; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0; color: #555;">Documentos Indexados</h3>
            <p style="font-size: 28px; font-weight: bold; margin: 0; color: #46b450;"><?php echo intval($documents_count); ?></p>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccd0d4; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0; color: #555;">Vectores RAG</h3>
            <p style="font-size: 28px; font-weight: bold; margin: 0; color: #46b450;"><?php echo intval($embeddings_count); ?></p>
        </div>
    </div>
    
    <div style="margin-top: 40px; background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccd0d4;">
        <h2 style="margin-top: 0;">Registro de Actividad Reciente</h2>
        <?php if (!empty($recent_logs)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 15%;">Fecha</th>
                        <th style="width: 10%;">Nivel</th>
                        <th style="width: 15%;">Componente</th>
                        <th>Mensaje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log->created_at); ?></td>
                            <td>
                                <span style="padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; color: #fff; background: <?php echo $log->level === 'ERROR' ? '#d63638' : ($log->level === 'INFO' ? '#00a0d2' : '#888'); ?>">
                                    <?php echo esc_html($log->level); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($log->component); ?></td>
                            <td><code><?php echo esc_html($log->message); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay registros recientes.</p>
        <?php endif; ?>
    </div>
</div>
