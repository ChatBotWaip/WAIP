<?php
namespace Waip\Config;

if (!defined('ABSPATH')) {
    exit;
}

class SettingsManager {
    
    public function init() {
        // Init settings logic if necessary
        // Mostly used as a centralized accessor for settings in Options API
    }

    public static function getApiKey() {
        return get_option(Constants::OPTION_API_KEY, '');
    }

    public static function getModel() {
        return get_option(Constants::OPTION_MODEL, 'gpt-4o');
    }

    public static function getSystemPrompt() {
        $default_prompt = "Eres el asistente de IA de Coodelsur. Solo debes dar información relacionada con la empresa y su base de conocimiento. No debes alucinar respuestas. Puedes realizar simulaciones de crédito aclarando estrictamente que son valores estimados y puramente informativos. No debes solicitar ni procesar pagos. Si no tienes información o piden un humano, ofrece hablar con un asesor (botón de WhatsApp).";
        return get_option(Constants::OPTION_SYSTEM_PROMPT, $default_prompt);
    }
    
    public static function getAssistantName() {
        return get_option(Constants::OPTION_ASSISTANT_NAME, 'Coodelsur Bot');
    }
    
    public static function getPrimaryColor() {
        return get_option(Constants::OPTION_PRIMARY_COLOR, '#0066cc');
    }
    
    public static function isRagEnabled() {
        return get_option(Constants::OPTION_RAG_ENABLED, '0') === '1';
    }

    public static function getMaxChunks() {
        return (int) get_option(Constants::OPTION_MAX_CHUNKS, 3);
    }
}
