<?php
namespace Waip\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use Waip\AI\Providers\OpenAIProvider;
use Waip\AI\Utils\TokenCounter;
use Waip\Repositories\MessageRepository;
use Waip\Services\Logger;
use Waip\Config\SettingsManager;

if (!defined('ABSPATH')) {
    exit;
}

class ChatController {

    public function register_routes() {
        register_rest_route('waip/v1', '/chat', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_chat'],
            'permission_callback' => '__return_true', // Endpoint público para el widget
        ]);

        register_rest_route('waip/v1', '/chat/history', [
            'methods' => 'GET',
            'callback' => [$this, 'get_history'],
            'permission_callback' => '__return_true', 
        ]);

        register_rest_route('waip/v1', '/chat/idle', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_idle'],
            'permission_callback' => '__return_true', 
        ]);
    }

    public function handle_chat(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $message = sanitize_text_field($params['message'] ?? '');
        $attachment = $params['attachment'] ?? null; // Imagen en Base64
        $session_id = sanitize_text_field($params['session_id'] ?? wp_generate_uuid4());
        $ip_address = $request->get_header('x_forwarded_for') ?: $_SERVER['REMOTE_ADDR'];

        if (empty($message) && empty($attachment)) {
            return new WP_REST_Response(['error' => 'Message is empty'], 400);
        }

        try {
            $conversation_id = MessageRepository::getConversationIdBySession($session_id, $ip_address);
            
            // Guardar mensaje del usuario
            MessageRepository::saveMessage($conversation_id, 'user', $message, [], $attachment);

            // Captura de Leads: Extraer posible correo electrónico
            if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $message, $matches)) {
                $email = $matches[0];
                MessageRepository::updateConversationLead($conversation_id, null, $email);
            }
            
            // Captura de Leads: Extraer posible nombre (múltiples patrones)
            $name_patterns = [
                '/(?:me llamo|mi nombre es|soy|me dicen|hola[\s,]+(?:soy|me llamo))\s+([a-záéíóúñA-ZÁÉÍÓÚÑ]+(?:\s+[a-záéíóúñA-ZÁÉÍÓÚÑ]+){0,2})/iu',
                '/(?:hola|buenos?\s+d[ií]as?|buenas?\s+tardes?|buenas?\s+noches?)[\s,.:!]+([a-záéíóúñA-ZÁÉÍÓÚÑ]+(?:\s+[a-záéíóúñA-ZÁÉÍÓÚÑ]+)?)\s+(?:aqu[ií]|tengo|quisiera|necesito|quiero|estoy)/iu',
                '/(?:nombre)[\s:]+([a-záéíóúñA-ZÁÉÍÓÚÑ]+(?:\s+[a-záéíóúñA-ZÁÉÍÓÚÑ]+){0,2})/iu',
            ];
            // Si el mensaje es SOLO un nombre (1-3 palabras), también capturarlo
            if (preg_match('/^([a-záéíóúñA-ZÁÉÍÓÚÑ]+(?:\s+[a-záéíóúñA-ZÁÉÍÓÚÑ]+){0,2})$/iu', trim($message), $matches)) {
                $possible_name = trim($matches[1]);
                // Lista extensa de palabras prohibidas
                $excluded = [
                    'hola', 'buenos', 'buenas', 'gracias', 'ayuda', 'listo', 'claro', 'vale', 'perfecto', 'consulta', 
                    'si', 'no', 'monto', 'asesor', 'credito', 'préstamo', 'prestamo', 'info', 'informacion', 'interes', 
                    'plazo', 'requisitos', 'tasa', 'cuota', 'dinero', 'agente', 'humano', 'persona', 'bot', 'quiero', 
                    'necesito', 'bien', 'ok', 'okay', 'dale', 'super', 'excelente', 'tardes', 'dias', 'noches',
                    'chao', 'adios', 'okey', 'bueno', 'buen', 'dia', 'tarde', 'noche', 'ola', 'chat', 'chatbot',
                    'me', 'te', 'se', 'nos', 'le', 'les', 'que', 'como', 'cuando', 'donde', 'porque', 'para', 'pero', 'contestan'
                ];
                
                $is_valid = true;
                $words = explode(' ', strtolower($possible_name));
                foreach ($words as $w) {
                    if (in_array(trim($w), $excluded)) {
                        $is_valid = false;
                        break;
                    }
                }
                
                if ($is_valid && mb_strlen($possible_name) > 2) {
                    MessageRepository::updateConversationLead($conversation_id, $possible_name, null);
                }
            }
            foreach ($name_patterns as $pattern) {
                if (preg_match($pattern, $message, $matches)) {
                    $name = trim($matches[1]);
                    MessageRepository::updateConversationLead($conversation_id, $name, null);
                    break;
                }
            }
            
            // Captura de Leads: Extraer posible número de teléfono (Colombiano 10 dígitos o genérico con espacios)
            if (preg_match('/(?:\+?57)?[\s-]*(?:3\d{2})[\s-]*\d{3}[\s-]*\d{2}[\s-]*\d{2}/', $message, $matches)) {
                $phone = preg_replace('/[\s-]/', '', $matches[0]);
                // Guardar teléfono en su propia columna user_phone
                MessageRepository::updateConversationLead($conversation_id, null, null, $phone);
            }

            // Construir contexto usando PromptBuilder (Inyección de contexto RAG)
            // Nota: RAG utiliza el mensaje de texto para la búsqueda.
            $system_prompt = \Waip\AI\Chat\PromptBuilder::buildSystemPrompt($message);
            
            $history = MessageRepository::getMessagesForConversation($conversation_id, 10);
            
            $messages_payload = [];
            $messages_payload[] = ['role' => 'system', 'content' => $system_prompt];
            
            foreach ($history as $msg) {
                if (!empty($msg['attachment_url'])) {
                    // Formato de Visión
                    $messages_payload[] = [
                        'role' => $msg['role'],
                        'content' => [
                            ['type' => 'text', 'text' => $msg['content'] ?: 'Imagen adjunta'],
                            ['type' => 'image_url', 'image_url' => ['url' => $msg['attachment_url']]]
                        ]
                    ];
                } else {
                    $messages_payload[] = ['role' => $msg['role'], 'content' => $msg['content']];
                }
            }
            
            // Comprobar Modo Simulador
            if (SettingsManager::isSimulatorEnabled()) {
                sleep(1); // Simular retraso de red
                $dummy_responses = [
                    "¡Hola! Esto es una respuesta del Modo Simulador. Todo parece funcionar perfecto visualmente.",
                    "He recibido tu mensaje fuerte y claro (Modo Simulador).",
                    "Esta es una prueba de la plataforma WAIP. Si quieres respuestas reales, desactiva el Modo Simulador en los Ajustes."
                ];
                $response = [
                    'content' => $dummy_responses[array_rand($dummy_responses)],
                    'input_tokens' => 0,
                    'output_tokens' => 0
                ];
                $metrics = [
                    'input_cost' => 0,
                    'output_cost' => 0,
                    'total_cost' => 0
                ];
            } else {
                // Llamar a la IA
                $ai = new OpenAIProvider();
                $model = SettingsManager::getModel();
                
                $response = $ai->generateResponse($messages_payload, ['model' => $model]);

                // Calcular costos
                $metrics = TokenCounter::calculateCost($model, $response['input_tokens'], $response['output_tokens']);
            }
            
            $save_metrics = [
                'input_tokens' => $response['input_tokens'],
                'output_tokens' => $response['output_tokens'],
                'input_cost' => $metrics['input_cost'],
                'output_cost' => $metrics['output_cost'],
                'total_cost' => $metrics['total_cost'],
            ];

            // Guardar mensaje de la IA
            MessageRepository::saveMessage($conversation_id, 'assistant', $response['content'], $save_metrics);

            return new WP_REST_Response([
                'session_id' => $session_id,
                'response' => $response['content']
            ], 200);

        } catch (\Exception $e) {
            Logger::error('ChatController', $e->getMessage());
            
            $maintenance_msg = SettingsManager::getMaintenanceMessage();
            if (isset($conversation_id)) {
                MessageRepository::saveMessage($conversation_id, 'assistant', $maintenance_msg);
            }

            return new WP_REST_Response([
                'session_id' => $session_id,
                'response' => $maintenance_msg
            ], 200);
        }
    }

    public function get_history(WP_REST_Request $request) {
        $session_id = sanitize_text_field($request->get_param('session_id') ?? '');
        $ip_address = $request->get_header('x_forwarded_for') ?: $_SERVER['REMOTE_ADDR'];
        
        if (empty($session_id)) {
            return new WP_REST_Response(['messages' => []], 200);
        }

        try {
            // Llamar a esto devolverá una conversación activa válida o creará una nueva vacía si pasaron 24h
            $conversation_id = MessageRepository::getConversationIdBySession($session_id, $ip_address);
            
            // Obtener todos los mensajes para la conversación activa
            $messages = MessageRepository::getMessagesForConversation($conversation_id, 100);
            
            return new WP_REST_Response(['messages' => $messages], 200);
            
        } catch (\Exception $e) {
            Logger::error('ChatController_History', $e->getMessage());
            return new WP_REST_Response(['messages' => []], 200);
        }
    }

    public function handle_idle(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $message = sanitize_text_field($params['message'] ?? '');
        $session_id = sanitize_text_field($params['session_id'] ?? wp_generate_uuid4());
        $ip_address = $request->get_header('x_forwarded_for') ?: $_SERVER['REMOTE_ADDR'];

        if (empty($message)) {
            return new WP_REST_Response(['error' => 'Message is empty'], 400);
        }

        try {
            $conversation_id = MessageRepository::getConversationIdBySession($session_id, $ip_address);
            MessageRepository::saveMessage($conversation_id, 'assistant', $message);
            return new WP_REST_Response(['success' => true], 200);
        } catch (\Exception $e) {
            Logger::error('ChatController_Idle', $e->getMessage());
            return new WP_REST_Response(['error' => 'Failed to save idle message'], 500);
        }
    }
}
