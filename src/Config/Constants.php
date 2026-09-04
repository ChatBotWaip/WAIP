<?php
namespace Waip\Config;

if (!defined('ABSPATH')) {
    exit;
}

class Constants {
    const DB_CONVERSATIONS = 'wp_ai_conversations';
    const DB_MESSAGES = 'wp_ai_messages';
    const DB_LOGS = 'wp_ai_logs';
    const DB_DOCUMENTS = 'wp_ai_documents';
    const DB_EMBEDDINGS = 'wp_ai_embeddings';
    
    const SETTINGS_GROUP = 'waip_settings_group';
    const OPTION_API_KEY = 'waip_api_key';
    const OPTION_MODEL = 'waip_model';
    const OPTION_SYSTEM_PROMPT = 'waip_system_prompt';
    const OPTION_ASSISTANT_NAME = 'waip_assistant_name';
    const OPTION_ASSISTANT_LOGO = 'waip_assistant_logo';
    const OPTION_PRIMARY_COLOR = 'waip_primary_color';
    const OPTION_SECONDARY_COLOR = 'waip_secondary_color';
    const OPTION_WELCOME_MSG = 'waip_welcome_msg';
    const OPTION_IDLE_MSG = 'waip_idle_msg';
    const OPTION_IDLE_TIME = 'waip_idle_time';
    const OPTION_WHATSAPP_NUMBER = 'waip_whatsapp_number';

    // Configuraciones de RAG
    const OPTION_RAG_ENABLED = 'waip_rag_enabled';
    const OPTION_MAX_CHUNKS = 'waip_max_chunks';

    // Funciones Premium
    const OPTION_SIMULATOR_MODE = 'waip_simulator_mode';
    const OPTION_MAINTENANCE_MSG = 'waip_maintenance_msg';
    
    // Visibilidad
    const OPTION_IS_ACTIVE = 'waip_is_active';

    public static function getTableName($tableConstant) {
        global $wpdb;
        // Aunque el prefijo es "wp_ai_", a veces las instalaciones tienen prefijos personalizados.
        // Asumimos que siempre codificamos el prefijo exactamente como lo solicitó el usuario o le anteponemos el prefijo de WP.
        // El usuario especificó explícitamente "Tablas personalizadas con prefijo wp_ai_".
        // Por lo tanto, las constantes anteriores ya usan "wp_ai_".
        // Para estar seguros y ser compatibles con las consultas de $wpdb, simplemente devolvemos la constante.
        // O si se refieren a $wpdb->prefix . "ai_", haríamos eso.
        // Usaremos las constantes directamente asumiendo el prefijo estándar `wp_` para todo el sitio.
        return $tableConstant;
    }
}
