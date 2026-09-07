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
            
            set_time_limit(0);
            
            // Forzar zona horaria a Colombia para evitar problemas de configuración del servidor
            $wp_tz = new \DateTimeZone('America/Bogota');
            
            // Limpiar conversaciones con 0 mensajes antes de exportar
            \Waip\Repositories\MessageRepository::deleteOldConversations();
            
            $conversations_data = \Waip\Repositories\MessageRepository::getAllConversations(1, 10000);
            $conversations = $conversations_data['items'];
            
            // Analizar con IA las conversaciones pendientes que tengan contacto
            $this->analyze_pending_leads($conversations);
            // Re-obtener los datos actualizados
            $conversations_data = \Waip\Repositories\MessageRepository::getAllConversations(1, 10000);
            $conversations = $conversations_data['items'];

            // Generar Excel XML nativo (soporta colores, filtros, bordes)
            header('Content-Type: application/vnd.ms-excel');
            $now = new \DateTime('now', new \DateTimeZone('America/Bogota'));
            header('Content-Disposition: attachment; filename=WAIP-Leads-' . $now->format('Ymd_Hi') . '.xls');
            header('Cache-Control: max-age=0');
            
            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
                xmlns:o="urn:schemas-microsoft-com:office:office"
                xmlns:x="urn:schemas-microsoft-com:office:excel"
                xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
                xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
            
            // Estilos
            echo '<Styles>
                <Style ss:ID="Default" ss:Name="Normal">
                    <Alignment ss:Vertical="Center" ss:WrapText="1"/>
                    <Font ss:FontName="Calibri" ss:Size="11"/>
                    <Borders>
                        <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                    </Borders>
                </Style>
                <Style ss:ID="sTitle">
                    <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
                    <Font ss:FontName="Calibri" ss:Size="16" ss:Bold="1" ss:Color="#1A3667"/>
                </Style>
                <Style ss:ID="sSubtitle">
                    <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
                    <Font ss:FontName="Calibri" ss:Size="10" ss:Color="#666666"/>
                </Style>
                <Style ss:ID="sHeader">
                    <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
                    <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/>
                    <Interior ss:Color="#1A3667" ss:Pattern="Solid"/>
                    <Borders>
                        <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#0D1B3E"/>
                        <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0D1B3E"/>
                        <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0D1B3E"/>
                        <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0D1B3E"/>
                    </Borders>
                </Style>
                <Style ss:ID="sAlta">
                    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                    <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#9C0006"/>
                    <Interior ss:Color="#FFC7CE" ss:Pattern="Solid"/>
                    <Borders>
                        <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                    </Borders>
                </Style>
                <Style ss:ID="sMedia">
                    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                    <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#9C6500"/>
                    <Interior ss:Color="#FFEB9C" ss:Pattern="Solid"/>
                    <Borders>
                        <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                    </Borders>
                </Style>
                <Style ss:ID="sBaja">
                    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                    <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#006100"/>
                    <Interior ss:Color="#C6EFCE" ss:Pattern="Solid"/>
                    <Borders>
                        <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                    </Borders>
                </Style>
                <Style ss:ID="sPendiente">
                    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                    <Font ss:FontName="Calibri" ss:Size="11" ss:Italic="1" ss:Color="#999999"/>
                    <Borders>
                        <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                    </Borders>
                </Style>
                <Style ss:ID="sRowAlt">
                    <Alignment ss:Vertical="Center" ss:WrapText="1"/>
                    <Font ss:FontName="Calibri" ss:Size="11"/>
                    <Interior ss:Color="#F2F6FC" ss:Pattern="Solid"/>
                    <Borders>
                        <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                        <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
                    </Borders>
                </Style>
            </Styles>' . "\n";
            
            // Contar filas para el filtro
            $rows = [];
            foreach ($conversations as $conv) {
                if (empty($conv['user_name']) && empty($conv['user_email'])) {
                    continue;
                }
                $rows[] = $conv;
            }
            $totalRows = count($rows);
            $filterRange = 'R3C1:R' . ($totalRows + 3) . 'C7';
            
            echo '<Worksheet ss:Name="Leads WAIP">' . "\n";
            echo '<Table ss:DefaultColumnWidth="120" ss:DefaultRowHeight="28">' . "\n";
            
            // Anchos de columna
            echo '<Column ss:Index="1" ss:Width="130"/>';  // Fecha
            echo '<Column ss:Index="2" ss:Width="180"/>';  // Nombre
            echo '<Column ss:Index="3" ss:Width="220"/>';  // Email
            echo '<Column ss:Index="4" ss:Width="120"/>';  // Telefono
            echo '<Column ss:Index="5" ss:Width="120"/>';  // Prioridad
            echo '<Column ss:Index="6" ss:Width="400"/>';  // Observación
            echo '<Column ss:Index="7" ss:Width="100"/>';  // Mensajes
            echo '<Column ss:Index="8" ss:Width="100"/>';  // Estado
            
            // Fila 1: Título
            echo '<Row ss:Height="35">
                <Cell ss:StyleID="sTitle" ss:MergeAcross="6"><Data ss:Type="String">Reporte de Leads - WAIP AI Platform</Data></Cell>
            </Row>' . "\n";
            
            // Fila 2: Subtítulo con fecha
            $now = new \DateTime('now', new \DateTimeZone('America/Bogota'));
            echo '<Row ss:Height="22">
                <Cell ss:StyleID="sSubtitle" ss:MergeAcross="6"><Data ss:Type="String">Generado el ' . $now->format('d/m/Y') . ' a las ' . $now->format('H:i') . ' | Total de leads: ' . $totalRows . '</Data></Cell>
            </Row>' . "\n";
            
            // Fila 3: Encabezados
            $headers = ['Fecha', 'Nombre del Cliente', 'Email', 'Teléfono', 'Prioridad (IA)', 'Observación / Necesidad (IA)', 'Mensajes', 'Estado'];
            echo '<Row ss:Height="35">' . "\n";
            foreach ($headers as $header) {
                echo '<Cell ss:StyleID="sHeader"><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>' . "\n";
            }
            echo '</Row>' . "\n";
            
            // Filas de datos
            $rowIndex = 0;
            foreach ($rows as $conv) {
                $observacion = !empty($conv['ai_summary']) ? $conv['ai_summary'] : 'Pendiente de análisis';
                $prioridad = !empty($conv['ai_priority']) ? $conv['ai_priority'] : 'No asignada';
                $mensajes_count = isset($conv['message_count']) ? $conv['message_count'] : 0;
                
                // Determinar estilo de prioridad
                $prioStyle = 'sPendiente';
                if ($prioridad === 'Alta') $prioStyle = 'sAlta';
                elseif ($prioridad === 'Media') $prioStyle = 'sMedia';
                elseif ($prioridad === 'Baja') $prioStyle = 'sBaja';
                
                // Alternar color de fila
                $rowStyle = ($rowIndex % 2 === 1) ? 'sRowAlt' : 'Default';
                
                echo '<Row ss:Height="30">' . "\n";
                $fecha_local = new \DateTime($conv['updated_at'], wp_timezone());
                $fecha_local->setTimezone(new \DateTimeZone('America/Bogota'));
                echo '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="String">' . $fecha_local->format('d/m/Y H:i') . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="String">' . htmlspecialchars($conv['user_name'] ?: 'Anónimo') . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="String">' . htmlspecialchars($conv['user_email'] ?: 'No registrado') . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="String">' . htmlspecialchars($conv['user_phone'] ?: 'No registrado') . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="' . $prioStyle . '"><Data ss:Type="String">' . htmlspecialchars($prioridad) . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="String">' . htmlspecialchars($observacion) . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="Number">' . intval($mensajes_count) . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="String">' . ($conv['status'] === 'active' ? 'Activa' : 'Cerrada') . '</Data></Cell>' . "\n";
                echo '</Row>' . "\n";
                
                $rowIndex++;
            }
            
            echo '</Table>' . "\n";
            
            // Autofiltro
            echo '<AutoFilter x:Range="' . $filterRange . '" xmlns="urn:schemas-microsoft-com:office:excel"></AutoFilter>' . "\n";
            
            // Configuraciones de impresión
            echo '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
                <FitToPage/>
                <Print>
                    <FitWidth>1</FitWidth>
                    <FitHeight>0</FitHeight>
                </Print>
                <FreezePanes/>
                <FrozenNoSplit/>
                <SplitHorizontal>3</SplitHorizontal>
                <TopRowBottomPane>3</TopRowBottomPane>
                <ActivePane>2</ActivePane>
            </WorksheetOptions>' . "\n";
            
            echo '</Worksheet>' . "\n";
            echo '</Workbook>';
            exit;
        }
    }
    
    /**
     * Analiza con IA las conversaciones que tienen contacto pero no tienen resumen aún.
     */
    private function analyze_pending_leads($conversations) {
        global $wpdb;
        $table = \Waip\Config\Constants::DB_CONVERSATIONS;
        
        $pending = [];
        foreach ($conversations as $conv) {
            // Solo analizar si tiene contacto y no tiene resumen
            if (!empty($conv['user_email']) && empty($conv['ai_summary'])) {
                $pending[] = $conv;
            }
        }
        
        if (empty($pending)) return;
        
        try {
            $openai = new \Waip\AI\Providers\OpenAIProvider();
            
            foreach ($pending as $lead) {
                $messages = \Waip\Repositories\MessageRepository::getMessagesForConversation($lead['id'], 100);
                if (empty($messages)) continue;
                
                $formatted_chat = "";
                foreach ($messages as $msg) {
                    $role = $msg['role'] === 'user' ? 'Cliente' : 'Asistente';
                    $formatted_chat .= "{$role}: {$msg['content']}\n";
                }
                
                $system_prompt = 'Actúa como calificador de ventas. Analiza la conversación y devuelve SOLO un JSON: {"resumen": "1-2 oraciones sobre lo que busca el cliente", "prioridad": "Alta|Media|Baja"}. Alta=urgencia o intención fuerte de compra. Media=interés normal. Baja=solo saludó.';
                
                $response = $openai->generateResponse([
                    ['role' => 'system', 'content' => $system_prompt],
                    ['role' => 'user', 'content' => "Conversación:\n" . $formatted_chat]
                ], ['model' => 'gpt-4o-mini', 'temperature' => 0.1]);
                
                $content = str_replace(['```json', '```'], '', $response['content']);
                $json = json_decode(trim($content), true);
                
                $resumen = $json['resumen'] ?? 'Resumen no disponible';
                $prioridad = $json['prioridad'] ?? 'Baja';
                
                // También intentar extraer nombre de la conversación si está vacío
                $nombre = $lead['user_name'];
                if (empty($nombre)) {
                    // Pedir a la IA que extraiga el nombre
                    $name_response = $openai->generateResponse([
                        ['role' => 'system', 'content' => 'Del siguiente chat, extrae SOLAMENTE el nombre del cliente. Si no se identifica, responde exactamente: ANONIMO. No agregues nada más.'],
                        ['role' => 'user', 'content' => $formatted_chat]
                    ], ['model' => 'gpt-4o-mini', 'temperature' => 0]);
                    
                    $extracted_name = trim($name_response['content']);
                    if ($extracted_name !== 'ANONIMO' && strlen($extracted_name) > 1 && strlen($extracted_name) < 60) {
                        $wpdb->update($table, ['user_name' => $extracted_name], ['id' => $lead['id']]);
                    }
                }
                
                $wpdb->update($table, [
                    'ai_summary' => $resumen,
                    'ai_priority' => $prioridad,
                ], ['id' => $lead['id']]);
            }
        } catch (\Exception $e) {
            \Waip\Services\Logger::error('ExportAnalyzer', $e->getMessage());
        }
    }

    public function render_dashboard_page() {
        require WAIP_PLUGIN_DIR . 'src/Views/admin-dashboard.php';
    }

    public function render_knowledge_page() {
        require WAIP_PLUGIN_DIR . 'src/Views/admin-knowledge.php';
    }
}
