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
    const OPTION_PRIMARY_COLOR = 'waip_primary_color';

    // RAG configurations
    const OPTION_RAG_ENABLED = 'waip_rag_enabled';
    const OPTION_MAX_CHUNKS = 'waip_max_chunks';

    public static function getTableName($tableConstant) {
        global $wpdb;
        // Even though prefix is "wp_ai_", sometimes installations have custom prefixes.
        // Assuming we always hardcode the prefix exactly as requested by user or prepend WP's prefix.
        // The user explicitly specified "Tablas personalizadas con prefijo wp_ai_".
        // Therefore, the constants above already use "wp_ai_".
        // To be safe and compatible with $wpdb queries, we just return the constant.
        // Or if they mean $wpdb->prefix . "ai_", we would do that. The prompt said "Tablas BD: Prefijo wp_ai_".
        // Let's use the constants directly, assuming standard `wp_` prefix for the overall site as the prompt implied.
        return $tableConstant;
    }
}
