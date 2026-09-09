<?php
namespace Waip\Config;

if (!defined('ABSPATH')) {
    exit;
}

class SettingsManager {
    
    public function init() {
        // Inicializar lógica de ajustes si es necesario
        // Principalmente usado como un acceso centralizado para ajustes en la API de Opciones
    }

    public static function getApiKey() {
        $key = get_option(Constants::OPTION_API_KEY, '');
        if (strpos($key, 'WAIP_ENC:') === 0) {
            return base64_decode(substr($key, 9));
        }
        return $key;
    }

    public static function getMaskedApiKey() {
        $key = self::getApiKey();
        if (empty($key)) return '';
        if (strlen($key) < 10) return str_repeat('*', strlen($key));
        return substr($key, 0, 4) . str_repeat('*', 20) . substr($key, -4);
    }

    public static function getModel() {
        return get_option(Constants::OPTION_MODEL, 'gpt-5.6-luna');
    }

    public static function getSystemPrompt() {
        $default_prompt = "Eres el asistente de IA de Coodelsur. Solo debes dar información relacionada con la empresa y su base de conocimiento. No debes alucinar respuestas. Puedes realizar simulaciones de crédito aclarando estrictamente que son valores estimados y puramente informativos. No debes solicitar ni procesar pagos. Si no tienes información o piden un humano, ofrece hablar con un asesor (botón de WhatsApp).";
        return get_option(Constants::OPTION_SYSTEM_PROMPT, $default_prompt);
    }
    
    public static function getAssistantName() {
        return get_option(Constants::OPTION_ASSISTANT_NAME, 'Coodelsur Bot');
    }
    
    public static function getWelcomeMessage() {
        $default = "¡Hola! Soy " . self::getAssistantName() . ", tu asistente virtual. ¿En qué te puedo ayudar?";
        return get_option(Constants::OPTION_WELCOME_MSG, $default);
    }
    
    public static function getAssistantLogo() {
        return get_option(Constants::OPTION_ASSISTANT_LOGO, '');
    }

    public static function getPrimaryColor() {
        return get_option(Constants::OPTION_PRIMARY_COLOR, '#406ff3'); // Default actualizado para coincidir con la nueva imagen
    }

    public static function getIdleMessage() {
        return get_option(Constants::OPTION_IDLE_MSG, '¿Sigues por ahí? Si necesitas más ayuda con Coodelsur, aquí estoy.');
    }

    public static function getIdleTime() {
        return (int) get_option(Constants::OPTION_IDLE_TIME, 5);
    }

    public static function getWhatsappNumber() {
        return get_option(Constants::OPTION_WHATSAPP_NUMBER, '');
    }

    public static function getSecondaryColor() {
        return get_option(Constants::OPTION_SECONDARY_COLOR, '#a855f7'); // Morado de la imagen
    }
    
    public static function isRagEnabled() {
        return get_option(Constants::OPTION_RAG_ENABLED, '0') === '1';
    }

    public static function getMaxChunks() {
        return (int) get_option(Constants::OPTION_MAX_CHUNKS, 3);
    }

    public static function isSimulatorEnabled() {
        return get_option(Constants::OPTION_SIMULATOR_MODE, '0') === '1';
    }

    public static function getMaintenanceMessage() {
        return get_option(Constants::OPTION_MAINTENANCE_MSG, 'En este momento nuestros sistemas de IA están en mantenimiento. Por favor comunícate a nuestras líneas de atención o intenta más tarde.');
    }

    public static function isPubliclyVisible() {
        return get_option(Constants::OPTION_IS_ACTIVE, '0') === '1';
    }
}
