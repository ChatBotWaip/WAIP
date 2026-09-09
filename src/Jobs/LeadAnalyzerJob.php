<?php
namespace Waip\Jobs;

use Waip\Config\Constants;
use Waip\Config\SettingsManager;
use Waip\Repositories\MessageRepository;
use Waip\AI\Providers\OpenAIProvider;
use Waip\Services\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class LeadAnalyzerJob {

    public static function init() {
        add_action('waip_lead_analyzer_cron', [self::class, 'run']);
        
        // Programar si no existe
        if (!wp_next_scheduled('waip_lead_analyzer_cron')) {
            wp_schedule_event(time(), 'hourly', 'waip_lead_analyzer_cron');
        }
    }

    public static function run($force_all = false) {
        global $wpdb;
        $conversations_table = Constants::tableConversations();
        
        $time_condition = "";
        if (!$force_all) {
            $one_hour_ago = date('Y-m-d H:i:s', current_time('timestamp', 0) - 3600);
            $time_condition = $wpdb->prepare("AND updated_at < %s", $one_hour_ago);
        }
        
        // Buscar conversaciones con datos de contacto y sin procesar
        $query = "SELECT id, user_name, user_email, user_phone, updated_at 
             FROM {$conversations_table} 
             WHERE email_sent = 0 
             AND (user_email IS NOT NULL OR user_phone IS NOT NULL)
             AND (user_email != '' OR user_phone != '')
             $time_condition
             LIMIT 10"; // Procesar max 10 por ejecución para no agotar tiempo
        
        $leads = $wpdb->get_results($query, ARRAY_A);
        
        if (empty($leads)) {
            return;
        }

        $openai = new OpenAIProvider();
        
        foreach ($leads as $lead) {
            self::process_lead($lead, $openai);
        }
    }
    
    private static function process_lead($lead, $openai) {
        global $wpdb;
        $conversations_table = Constants::tableConversations();
        
        try {
            $messages = MessageRepository::getMessagesForConversation($lead['id'], 100);
            
            if (empty($messages)) {
                $wpdb->update($conversations_table, ['email_sent' => 1, 'ai_summary' => 'Sin mensajes', 'ai_priority' => 'Baja'], ['id' => $lead['id']]);
                return;
            }
            
            $formatted_chat = "";
            foreach ($messages as $msg) {
                $role = $msg['role'] === 'user' ? 'Cliente' : 'Asistente';
                $formatted_chat .= "{$role}: {$msg['content']}\n";
            }
            
            $system_prompt = "Actúa como un calificador de ventas (triage). Analiza la siguiente conversación.
Si el cliente mostró interés comercial (créditos, dudas sobre servicios, tarifas), devuelve un JSON con:
{
  \"resumen\": \"Breve resumen de 1 o 2 oraciones sobre lo que busca el cliente.\",
  \"prioridad\": \"Alta|Media|Baja\"
}
- Alta: Urgencia clara o altísima intención de adquirir producto.
- Media: Interés normal, pide información.
- Baja: Solo saludó, dejó datos pero no preguntó nada útil.

Si el cliente definitivamente NO mostró interés comercial o es spam, devuelve:
{
  \"resumen\": \"Sin interés comercial\",
  \"prioridad\": \"Baja\"
}";

            $response = $openai->generateResponse([
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => "Conversación:\n" . $formatted_chat]
            ], [
                'model' => 'gpt-4o-mini', // Usamos el modelo rápido y barato
                'temperature' => 0.1
            ]);
            
            $content = $response['content'];
            $content = str_replace(['```json', '```'], '', $content);
            $json = json_decode(trim($content), true);
            
            $resumen = $json['resumen'] ?? 'Resumen no generado adecuadamente';
            $prioridad = $json['prioridad'] ?? 'Baja';
            
            // Guardar en BD para que el Excel lo lea al instante
            $wpdb->update($conversations_table, [
                'ai_summary' => $resumen,
                'ai_priority' => $prioridad,
                'email_sent' => 1
            ], ['id' => $lead['id']]);
            
            // Solo enviar correo si hay interés
            if ($resumen !== 'Sin interés comercial') {
                self::send_email($lead, $resumen, $prioridad, $formatted_chat);
            }
            
        } catch (\Exception $e) {
            Logger::error('LeadAnalyzerJob', "Error procesando lead {$lead['id']}: " . $e->getMessage());
        }
    }
    
    private static function send_email($lead, $resumen, $prioridad, $chat) {
        $cartera_email = get_option('waip_cartera_email', 'cartera@miempresa.com');
        
        $emoji = '🟢';
        if ($prioridad === 'Alta') $emoji = '🔴';
        elseif ($prioridad === 'Media') $emoji = '🟡';
        
        $nombre = !empty($lead['user_name']) ? $lead['user_name'] : 'Lead Anónimo';
        $subject = "[{$emoji} Prioridad {$prioridad}] Nuevo Lead WAIP - {$nombre}";
        
        $fecha_dt = new \DateTime($lead['updated_at'], wp_timezone());
        $fecha_dt->setTimezone(new \DateTimeZone('America/Bogota'));
        $fecha_formateada = $fecha_dt->format('Y-m-d h:i A');

        $message = "<h2>Nuevo Lead Capturado por IA</h2>";
        $message .= "<p><strong>Nombre:</strong> " . esc_html($nombre) . "</p>";
        $message .= "<p><strong>Contacto:</strong> " . esc_html($lead['user_email']) . "</p>";
        $message .= "<p><strong>Fecha del chat:</strong> " . esc_html($fecha_formateada) . "</p>";
        $message .= "<h3>Resumen (IA)</h3>";
        $message .= "<p style='font-size: 16px; border-left: 4px solid #0073aa; padding-left: 10px;'><em>" . esc_html($resumen) . "</em></p>";
        $message .= "<h3>Historial Completo</h3>";
        $message .= "<pre style='background:#f4f4f4; padding:15px; border-radius: 5px; white-space: pre-wrap; font-family: monospace;'>" . esc_html($chat) . "</pre>";
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($cartera_email, $subject, $message, $headers);
    }
}
