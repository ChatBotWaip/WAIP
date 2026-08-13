<?php
namespace Waip\AI\Providers;

use Waip\Config\SettingsManager;
use Waip\Services\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class OpenAIProvider implements AIInterface {
    
    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';

    public function __construct() {
        $this->api_key = SettingsManager::getApiKey();
    }

    public function generateResponse(array $messages, array $options = []) {
        if (empty($this->api_key)) {
            Logger::error('OpenAIProvider', 'API Key no configurada.');
            throw new \Exception('API Key no configurada.');
        }

        $model = $options['model'] ?? SettingsManager::getModel();

        $body = array(
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
        );

        $args = array(
            'body'        => json_encode($body),
            'timeout'     => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ),
        );

        $response = wp_remote_post($this->api_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            Logger::error('OpenAIProvider', "WP HTTP Error: " . $error_message);
            throw new \Exception("Error al conectar con OpenAI.");
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 200) {
            Logger::error('OpenAIProvider', "API Error ($response_code): " . $response_body);
            throw new \Exception("Error de la API de OpenAI. Inténtalo más tarde.");
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        $input_tokens = $data['usage']['prompt_tokens'] ?? 0;
        $output_tokens = $data['usage']['completion_tokens'] ?? 0;

        return array(
            'content' => $content,
            'input_tokens' => $input_tokens,
            'output_tokens' => $output_tokens,
        );
    }
}
