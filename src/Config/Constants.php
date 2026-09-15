<?php
namespace Waip\Config;

if (!defined('ABSPATH')) {
    exit;
}

class Constants {
    public static function tableConversations() { global $wpdb; return $wpdb->prefix . 'ai_conversations'; }
    public static function tableMessages() { global $wpdb; return $wpdb->prefix . 'ai_messages'; }
    public static function tableLogs() { global $wpdb; return $wpdb->prefix . 'ai_logs'; }
    public static function tableDocuments() { global $wpdb; return $wpdb->prefix . 'ai_documents'; }
    public static function tableEmbeddings() { global $wpdb; return $wpdb->prefix . 'ai_embeddings'; }
    
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
    const OPTION_CARTERA_EMAIL = 'waip_cartera_email';

    // Configuraciones de RAG
    const OPTION_RAG_ENABLED = 'waip_rag_enabled';
    const OPTION_MAX_CHUNKS = 'waip_max_chunks';

    // Funciones Premium
    const OPTION_SIMULATOR_MODE = 'waip_simulator_mode';
    const OPTION_MAINTENANCE_MSG = 'waip_maintenance_msg';
    
    // Visibilidad
    const OPTION_IS_ACTIVE = 'waip_is_active';
}
