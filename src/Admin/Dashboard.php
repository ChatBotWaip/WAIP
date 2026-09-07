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
            
            $conversations_data = \Waip\Repositories\MessageRepository::getAllConversations(1, 10000);
            $conversations = $conversations_data['items'];

            // Generar Excel XML nativo (soporta colores, filtros, bordes)
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename=WAIP-Leads-' . date('Y-m-d') . '.xls');
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
            echo '<Column ss:Index="4" ss:Width="120"/>';  // Prioridad
            echo '<Column ss:Index="5" ss:Width="400"/>';  // Observación
            echo '<Column ss:Index="6" ss:Width="100"/>';  // Mensajes
            echo '<Column ss:Index="7" ss:Width="100"/>';  // Estado
            
            // Fila 1: Título
            echo '<Row ss:Height="35">
                <Cell ss:StyleID="sTitle" ss:MergeAcross="6"><Data ss:Type="String">Reporte de Leads - WAIP AI Platform</Data></Cell>
            </Row>' . "\n";
            
            // Fila 2: Subtítulo con fecha
            echo '<Row ss:Height="22">
                <Cell ss:StyleID="sSubtitle" ss:MergeAcross="6"><Data ss:Type="String">Generado el ' . date('d/m/Y') . ' a las ' . date('H:i') . ' | Total de leads: ' . $totalRows . '</Data></Cell>
            </Row>' . "\n";
            
            // Fila 3: Encabezados
            $headers = ['Fecha', 'Nombre del Cliente', 'Email / Teléfono', 'Prioridad (IA)', 'Observación / Necesidad (IA)', 'Mensajes', 'Estado'];
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
                echo '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="String">' . date('d/m/Y H:i', strtotime($conv['updated_at'])) . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="String">' . htmlspecialchars($conv['user_name'] ?: 'Anónimo') . '</Data></Cell>' . "\n";
                echo '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="String">' . htmlspecialchars($conv['user_email'] ?: 'No registrado') . '</Data></Cell>' . "\n";
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

    public function render_dashboard_page() {
        require WAIP_PLUGIN_DIR . 'src/Views/admin-dashboard.php';
    }

    public function render_knowledge_page() {
        require WAIP_PLUGIN_DIR . 'src/Views/admin-knowledge.php';
    }
}
