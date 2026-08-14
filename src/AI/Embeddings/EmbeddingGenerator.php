<?php
namespace Waip\AI\Embeddings;

use Waip\Config\SettingsManager;
use Waip\Services\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class EmbeddingGenerator {
    
    private $api_key;
    private $api_url = 'https://api.openai.com/v1/embeddings';

    public function __construct() {
        $this->api_key = SettingsManager::getApiKey();
    }

    /**
     * @param string $text The text to embed
     * @return array|bool Array of floats representing the vector, or false on error
     */
    public function generateEmbedding($text) {
        if (empty($this->api_key)) {
            Logger::error('EmbeddingGenerator', 'API Key no configurada al intentar generar embedding.');
            return false;
        }

        $body = array(
            'model' => 'text-embedding-3-small',
            'input' => $text,
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
            Logger::error('EmbeddingGenerator', "WP HTTP Error: " . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 200) {
            Logger::error('EmbeddingGenerator', "API Error ($response_code): " . $response_body);
            return false;
        }

        if (isset($data['data'][0]['embedding'])) {
            return $data['data'][0]['embedding'];
        }

        return false;
    }
}
