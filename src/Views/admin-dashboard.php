<?php
if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
use Waip\Config\Constants;

// Fetch some basic stats for the dashboard
$conversations_count = $wpdb->get_var("SELECT COUNT(*) FROM " . Constants::DB_CONVERSATIONS);
$messages_count = $wpdb->get_var("SELECT COUNT(*) FROM " . Constants::DB_MESSAGES);
$total_cost = $wpdb->get_var("SELECT SUM(total_cost) FROM " . Constants::DB_MESSAGES);

?>
<div class="wrap">
    <h1>WAIP Dashboard</h1>
    <p>Bienvenido al Motor de Asistentes de IA para WordPress.</p>
    
    <div style="display: flex; gap: 20px; margin-top: 20px;">
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccd0d4; min-width: 200px;">
            <h3 style="margin-top: 0;">Conversaciones Activas</h3>
            <p style="font-size: 24px; font-weight: bold; margin: 0; color: #0073aa;"><?php echo intval($conversations_count); ?></p>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccd0d4; min-width: 200px;">
            <h3 style="margin-top: 0;">Total Mensajes</h3>
            <p style="font-size: 24px; font-weight: bold; margin: 0; color: #0073aa;"><?php echo intval($messages_count); ?></p>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccd0d4; min-width: 200px;">
            <h3 style="margin-top: 0;">Costo Acumulado (USD)</h3>
            <p style="font-size: 24px; font-weight: bold; margin: 0; color: #d63638;">$<?php echo number_format((float)$total_cost, 4); ?></p>
        </div>
    </div>
    
    <h2 style="margin-top: 40px;">Sprint 1 - Motor Base</h2>
    <p>El motor base de chat se encuentra activo. Puedes configurar la API Key y el prompt desde el panel de <a href="?page=waip-settings">Ajustes</a>.</p>
</div>
