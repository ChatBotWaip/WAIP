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
            'permission_callback' => '__return_true', // Public endpoint for the widget
        ]);
    }

    public function handle_chat(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $message = sanitize_text_field($params['message'] ?? '');
        $session_id = sanitize_text_field($params['session_id'] ?? wp_generate_uuid4());

        if (empty($message)) {
            return new WP_REST_Response(['error' => 'Message is empty'], 400);
        }

        try {
            $conversation_id = MessageRepository::getConversationIdBySession($session_id);
            
            // Save user message
            MessageRepository::saveMessage($conversation_id, 'user', $message);

            // Build context (Sprint 1: just system prompt + recent history)
            $system_prompt = SettingsManager::getSystemPrompt();
            
            $history = MessageRepository::getMessagesForConversation($conversation_id, 10);
            
            $messages_payload = [];
            $messages_payload[] = ['role' => 'system', 'content' => $system_prompt];
            
            foreach ($history as $msg) {
                $messages_payload[] = ['role' => $msg['role'], 'content' => $msg['content']];
            }
            
            // Call AI
            $ai = new OpenAIProvider();
            $model = SettingsManager::getModel();
            
            $response = $ai->generateResponse($messages_payload, ['model' => $model]);

            // Calculate costs
            $metrics = TokenCounter::calculateCost($model, $response['input_tokens'], $response['output_tokens']);
            
            $save_metrics = [
                'input_tokens' => $response['input_tokens'],
                'output_tokens' => $response['output_tokens'],
                'input_cost' => $metrics['input_cost'],
                'output_cost' => $metrics['output_cost'],
                'total_cost' => $metrics['total_cost'],
            ];

            // Save AI message
            MessageRepository::saveMessage($conversation_id, 'assistant', $response['content'], $save_metrics);

            return new WP_REST_Response([
                'session_id' => $session_id,
                'response' => $response['content']
            ], 200);

        } catch (\Exception $e) {
            Logger::error('ChatController', $e->getMessage());
            return new WP_REST_Response(['error' => 'Ocurrió un error al procesar tu solicitud.'], 500);
        }
    }
}
