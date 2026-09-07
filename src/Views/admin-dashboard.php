<?php
if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
use Waip\Config\Constants;

// Obtener estadísticas completas para el dashboard
$conversations_count = $wpdb->get_var("SELECT COUNT(*) FROM " . Constants::DB_CONVERSATIONS);
$messages_count = $wpdb->get_var("SELECT COUNT(*) FROM " . Constants::DB_MESSAGES);
$total_input_tokens = $wpdb->get_var("SELECT SUM(input_tokens) FROM " . Constants::DB_MESSAGES);
$total_output_tokens = $wpdb->get_var("SELECT SUM(output_tokens) FROM " . Constants::DB_MESSAGES);
$total_cost = $wpdb->get_var("SELECT SUM(total_cost) FROM " . Constants::DB_MESSAGES);

$documents_count = $wpdb->get_var("SELECT COUNT(*) FROM " . Constants::DB_DOCUMENTS);
$embeddings_count = $wpdb->get_var("SELECT COUNT(*) FROM " . Constants::DB_EMBEDDINGS);

// Obtener registros recientes (Obtener hasta 500 para datatables)
$recent_logs = $wpdb->get_results("SELECT * FROM " . Constants::DB_LOGS . " ORDER BY created_at DESC LIMIT 500");

// Comprobar si se está viendo un chat específico
$view_chat_id = isset($_GET['view_chat_id']) ? intval($_GET['view_chat_id']) : 0;
$chat_messages = [];
if ($view_chat_id > 0) {
    $chat_messages = \Waip\Repositories\MessageRepository::getMessagesForConversation($view_chat_id, 200);
}

// Obtener lista de conversaciones (Obtener hasta 500 para datatables)
$conversations_data = \Waip\Repositories\MessageRepository::getAllConversations(1, 500);
$recent_conversations = $conversations_data['items'];

?>
<style>
    .waip-dashboard-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
    .waip-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; }
    .waip-header h1 { margin: 0; font-size: 24px; font-weight: 600; color: #1d2327; }
    .waip-badge { background: #0073aa; color: white; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    
    .waip-section-title { font-size: 18px; font-weight: 600; color: #1d2327; margin: 30px 0 15px 0; padding-bottom: 10px; border-bottom: 1px solid #e2e4e7; }
    
    .waip-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
    .waip-stat-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #e2e4e7; display: flex; align-items: flex-start; gap: 15px; transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .waip-stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .waip-stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
    .waip-stat-info h3 { margin: 0 0 5px 0; font-size: 14px; color: #646970; font-weight: 500; }
    .waip-stat-info p { margin: 0; font-size: 24px; font-weight: 700; color: #1d2327; }
    .waip-stat-info small { font-size: 12px; color: #8c8f94; }
    
    .waip-icon-blue { background: #e0f0fa; color: #0073aa; }
    .waip-icon-green { background: #e6f6e8; color: #00a32a; }
    .waip-icon-purple { background: #f0e6fa; color: #8224e3; }
    .waip-icon-red { background: #fae6e6; color: #d63638; }

    .waip-table-container { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #e2e4e7; margin-top: 20px; }
    .waip-table-container h2 { margin-top: 0; font-size: 18px; border-bottom: none; padding-bottom: 0; }
    
    .waip-status { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .waip-status.active { background: #e6f6e8; color: #00a32a; }
    .waip-status.closed { background: #f0f0f1; color: #646970; }
    
    .waip-btn { display: inline-flex; align-items: center; gap: 5px; background: #fff; border: 1px solid #2271b1; color: #2271b1; padding: 5px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 500; transition: all 0.2s ease; }
    .waip-btn:hover { background: #f6f7f7; color: #135e96; border-color: #135e96; }
    
    .waip-chat-viewer { background: #f0f2f4; border-radius: 12px; border: 1px solid #e2e4e7; height: 600px; display: flex; flex-direction: column; overflow: hidden; }
    .waip-chat-viewer-header { background: #fff; padding: 15px 20px; border-bottom: 1px solid #e2e4e7; display: flex; justify-content: space-between; align-items: center; }
    .waip-chat-viewer-body { padding: 20px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 15px; }
    .waip-chat-bubble { max-width: 75%; padding: 12px 16px; border-radius: 12px; font-size: 14px; line-height: 1.5; box-shadow: 0 1px 2px rgba(0,0,0,0.05); position: relative; }
    .waip-chat-bubble.user { background: #0073aa; color: #fff; align-self: flex-end; border-bottom-right-radius: 4px; }
    .waip-chat-bubble.assistant { background: #fff; color: #1d2327; align-self: flex-start; border-bottom-left-radius: 4px; border: 1px solid #e2e4e7; }
    .waip-chat-meta { font-size: 11px; margin-top: 5px; opacity: 0.7; display: flex; justify-content: space-between; gap: 15px; }
    
    .waip-see-more-container { text-align: center; padding: 15px; background: #fafafa; border-top: 1px solid #e2e4e7; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; }
    .waip-see-more-btn { background: none; border: none; color: #0073aa; font-weight: 600; cursor: pointer; font-size: 14px; }
    .waip-see-more-btn:hover { color: #005177; text-decoration: underline; }
    .waip-hidden-row { display: none; }
    
    /* Sobrescribir estilos WP para DataTables */
    .dataTables_wrapper .dataTables_length select, .dataTables_wrapper .dataTables_filter input { border: 1px solid #8c8f94; border-radius: 4px; padding: 0 8px; min-height: 30px; }
    .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 5px 10px; margin: 0 2px; border-radius: 4px; border: 1px solid transparent; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #2271b1; color: white !important; border: 1px solid #2271b1; }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #f0f0f1; border: 1px solid #8c8f94; color: #1d2327 !important; }
</style>

<!-- DataTables CDN -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<div class="wrap waip-dashboard-wrap">
    <div class="waip-header">
        <div>
            <h1>Panel de Control WAIP</h1>
            <p style="color: #646970; margin-top: 5px;">Plataforma de Inteligencia Artificial para WordPress</p>
        </div>
        <span class="waip-badge"><span class="dashicons dashicons-admin-network" style="font-size: 14px; margin-top: 2px;"></span> RAG Activo</span>
    </div>

    <?php if ($view_chat_id > 0): ?>
        <!-- VISTA DE CHAT INDIVIDUAL -->
        <div class="waip-table-container" style="padding: 0;">
            <div class="waip-chat-viewer-header">
                <div>
                    <h2 style="margin: 0; font-size: 18px;">Conversación #<?php echo $view_chat_id; ?></h2>
                    <span style="font-size: 12px; color: #646970;">Transcripción completa del chat</span>
                </div>
                <a href="<?php echo admin_url('admin.php?page=waip-dashboard'); ?>" class="waip-btn">
                    <span class="dashicons dashicons-arrow-left-alt"></span> Volver al Historial
                </a>
            </div>
            
            <div class="waip-chat-viewer">
                <div class="waip-chat-viewer-body">
                    <?php if (empty($chat_messages)): ?>
                        <p style="text-align: center; color: #8c8f94; margin-top: 40px;">No hay mensajes registrados en esta conversación.</p>
                    <?php else: ?>
                        <?php foreach ($chat_messages as $msg): ?>
                            <div class="waip-chat-bubble <?php echo esc_attr($msg['role']); ?>">
                                <?php if (!empty($msg['attachment_url'])): ?>
                                    <div style="margin-bottom: 10px;">
                                        <img src="<?php echo esc_url($msg['attachment_url']); ?>" style="max-width: 100%; max-height: 250px; border-radius: 6px; border: 1px solid rgba(0,0,0,0.1);">
                                    </div>
                                <?php endif; ?>
                                
                                <div><?php echo nl2br(esc_html($msg['content'])); ?></div>
                                
                                <div class="waip-chat-meta">
                                    <span><?php echo $msg['role'] === 'user' ? '👤 Cliente' : '🤖 Asistente'; ?></span>
                                    <span><?php echo date('d M H:i', strtotime($msg['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- DASHBOARD PRINCIPAL -->
        
        <h2 class="waip-section-title">Estadísticas de Chat</h2>
        <div class="waip-stats-grid">
            <div class="waip-stat-card">
                <div class="waip-stat-icon waip-icon-blue"><span class="dashicons dashicons-format-chat"></span></div>
                <div class="waip-stat-info">
                    <h3>Conversaciones Totales</h3>
                    <p><?php echo number_format(intval($conversations_count)); ?></p>
                </div>
            </div>
            
            <div class="waip-stat-card">
                <div class="waip-stat-icon waip-icon-purple"><span class="dashicons dashicons-testimonial"></span></div>
                <div class="waip-stat-info">
                    <h3>Mensajes Intercambiados</h3>
                    <p><?php echo number_format(intval($messages_count)); ?></p>
                </div>
            </div>
            
            <div class="waip-stat-card">
                <div class="waip-stat-icon waip-icon-red"><span class="dashicons dashicons-chart-pie"></span></div>
                <div class="waip-stat-info">
                    <h3>Costo Acumulado API</h3>
                    <p>$<?php echo number_format((float)$total_cost, 4); ?></p>
                    <small>Tokens: <?php echo number_format(intval($total_input_tokens + $total_output_tokens)); ?></small>
                </div>
            </div>
        </div>

        <h2 class="waip-section-title">Base de Conocimiento (RAG)</h2>
        <div class="waip-stats-grid">
            <div class="waip-stat-card">
                <div class="waip-stat-icon waip-icon-green"><span class="dashicons dashicons-media-document"></span></div>
                <div class="waip-stat-info">
                    <h3>Documentos Indexados</h3>
                    <p><?php echo number_format(intval($documents_count)); ?></p>
                </div>
            </div>
            
            <div class="waip-stat-card">
                <div class="waip-stat-icon waip-icon-green"><span class="dashicons dashicons-networking"></span></div>
                <div class="waip-stat-info">
                    <h3>Vectores de Memoria</h3>
                    <p><?php echo number_format(intval($embeddings_count)); ?></p>
                </div>
            </div>
        </div>

        <div class="waip-table-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0;">Historial de Conversaciones (Últimos 30 días)</h2>
                <a href="<?php echo admin_url('admin.php?page=waip-dashboard&waip_export=csv'); ?>" class="waip-btn">
                    <span class="dashicons dashicons-download"></span> Exportar a Excel
                </a>
            </div>
            <?php if (!empty($recent_conversations)): ?>
                <table id="waip-conversations-table" class="wp-list-table widefat fixed striped waip-datatable" style="border: none; border-top: 1px solid #c3c4c7;">
                    <thead>
                        <tr>
                                <th style="width: 8%;">ID</th>
                                <th style="width: 25%;">Cliente / IP</th>
                                <th style="width: 20%;">Última Actividad</th>
                                <th style="width: 12%;">Mensajes</th>
                                <th style="width: 15%;">Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_conversations as $conv): ?>
                                <tr>
                                    <td data-sort="<?php echo esc_attr($conv['id']); ?>"><strong>#<?php echo esc_html($conv['id']); ?></strong></td>
                                    <td>
                                        <?php if (!empty($conv['user_email'])): ?>
                                            <strong style="color: #0073aa;"><?php echo esc_html($conv['user_name'] ?: 'Sin nombre'); ?></strong><br>
                                            <a href="mailto:<?php echo esc_attr($conv['user_email']); ?>" style="font-size: 12px; color: #646970; text-decoration: none;"><?php echo esc_html($conv['user_email']); ?></a>
                                        <?php else: ?>
                                            <span style="color: #646970;">Anónimo</span><br>
                                            <span style="font-size: 12px; color: #8c8f94;"><span class="dashicons dashicons-admin-network" style="font-size: 12px; line-height: 1.5;"></span> <?php echo esc_html($conv['ip_address'] ?: 'IP Desconocida'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <?php 
                                        $fecha_dt = new \DateTime($conv['updated_at'], new \DateTimeZone('UTC'));
                                        $fecha_dt->setTimezone(new \DateTimeZone('America/Bogota'));
                                    ?>
                                    <td data-sort="<?php echo esc_attr($fecha_dt->getTimestamp()); ?>"><?php echo esc_html($fecha_dt->format('d M Y, H:i')); ?></td>
                                    <td><?php echo esc_html($conv['message_count']); ?></td>
                                    <td>
                                        <span class="waip-status <?php echo esc_attr($conv['status']); ?>">
                                            <?php echo $conv['status'] === 'active' ? '🟢 Activa' : '⚪ Cerrada'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=waip-dashboard&view_chat_id=' . $conv['id']); ?>" class="waip-btn">
                                            <span class="dashicons dashicons-visibility"></span> Leer Chat
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
            <?php else: ?>
                <p style="color: #646970; padding: 20px 0; text-align: center;">No hay conversaciones recientes.</p>
            <?php endif; ?>
        </div>
        
        <div class="waip-table-container">
            <h2 style="margin-bottom: 15px;">Registro del Sistema (Logs)</h2>
            <?php if (!empty($recent_logs)): ?>
                <table id="waip-logs-table" class="wp-list-table widefat fixed striped waip-datatable" style="border: none; border-top: 1px solid #c3c4c7;">
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
                                    <?php 
                                        $log_dt = new \DateTime($log->created_at, new \DateTimeZone('UTC'));
                                        $log_dt->setTimezone(new \DateTimeZone('America/Bogota'));
                                    ?>
                                    <td style="font-size: 12px; color: #646970;"><?php echo esc_html($log_dt->format('d M, H:i:s')); ?></td>
                                    <td>
                                        <span style="padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; color: #fff; background: <?php echo $log->level === 'ERROR' ? '#d63638' : ($log->level === 'INFO' ? '#00a32a' : '#8c8f94'); ?>;">
                                            <?php echo esc_html($log->level); ?>
                                        </span>
                                    </td>
                                    <td style="font-weight: 500;"><?php echo esc_html($log->component); ?></td>
                                    <td style="font-family: monospace; font-size: 12px; color: #50575e;"><?php echo esc_html($log->message); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
            <?php else: ?>
                <p style="color: #646970; padding: 20px 0; text-align: center;">El sistema está funcionando correctamente. No hay errores recientes.</p>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<script>
    jQuery(document).ready(function($) {
        $('.waip-datatable').DataTable({
            language: {
                "sProcessing":     "Procesando...",
                "sLengthMenu":     "Mostrar _MENU_ registros",
                "sZeroRecords":    "No se encontraron resultados",
                "sEmptyTable":     "Ningún dato disponible en esta tabla",
                "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
                "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
                "sInfoPostFix":    "",
                "sSearch":         "Buscar:",
                "sUrl":            "",
                "sInfoThousands":  ",",
                "sLoadingRecords": "Cargando...",
                "oPaginate": {
                    "sFirst":    "Primero",
                    "sLast":     "Último",
                    "sNext":     "Siguiente",
                    "sPrevious": "Anterior"
                },
                "oAria": {
                    "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                    "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                }
            },
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
            order: [[0, "desc"]],
            initComplete: function () {
                var api = this.api();
                
                // Si es la tabla de conversaciones, añadir filtros
                if ($(this).attr('id') === 'waip-conversations-table') {
                    var filterContainer = $('<div class="waip-custom-filters" style="display:inline-block; margin-left: 15px;"></div>').appendTo('#waip-conversations-table_filter');
                    
                    // Filtro de Estado (Columna 4)
                    var stateSelect = $('<select style="vertical-align: middle; margin-left: 10px;"><option value="">Todos los estados</option><option value="Activa">Activa</option><option value="Cerrada">Cerrada</option></select>')
                        .appendTo(filterContainer)
                        .on('change', function () {
                            var val = $.fn.dataTable.util.escapeRegex($(this).val());
                            api.column(4).search(val ? val : '', true, false).draw();
                        });
                        
                    // Filtro de Contacto (Columna 1)
                    var contactSelect = $('<select style="vertical-align: middle; margin-left: 10px;"><option value="">Todos los clientes</option><option value="@">Con Correo</option><option value="Anónimo">Anónimos</option></select>')
                        .appendTo(filterContainer)
                        .on('change', function () {
                            var val = $.fn.dataTable.util.escapeRegex($(this).val());
                            api.column(1).search(val ? val : '', true, false).draw();
                        });
                }
            }
        });
    });
</script>
