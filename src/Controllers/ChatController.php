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
        $ip_address = $_SERVER['REMOTE_ADDR'];

        $rate_limit_key = 'waip_rate_limit_' . md5($ip_address);
        $attempts = get_transient($rate_limit_key) ?: 0;
        if ($attempts >= 20) {
            return new WP_REST_Response(['error' => 'Too many requests. Please wait a minute.'], 429);
        }
        set_transient($rate_limit_key, $attempts + 1, 60);

        if (empty($message) && empty($attachment)) {
            return new WP_REST_Response(['error' => 'Message is empty'], 400);
        }

        try {
            $conversation_id = MessageRepository::getConversationIdBySession($session_id, $ip_address);
            
            // Guardar mensaje del usuario
            MessageRepository::saveMessage($conversation_id, 'user', $message, [], $attachment);

            // Captura de Leads centralizada
            $extracted = \Waip\Services\LeadExtractor::extractContactInfo($message, $conversation_id);
            if ($extracted['email'] || $extracted['phone'] || $extracted['name']) {
                MessageRepository::updateConversationLead($conversation_id, $extracted['name'], $extracted['email'], $extracted['phone']);
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
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
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
        $ip_address = $_SERVER['REMOTE_ADDR'];

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
