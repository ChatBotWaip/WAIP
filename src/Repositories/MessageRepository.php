<?php
namespace Waip\Repositories;

use Waip\Config\Constants;

if (!defined('ABSPATH')) {
    exit;
}

class MessageRepository {

    public static function createConversation($session_id) {
        global $wpdb;
        $table = Constants::DB_CONVERSATIONS;
        
        $wpdb->insert($table, [
            'session_id' => $session_id,
            'status' => 'active',
            'created_at' => current_time('mysql', 1),
            'updated_at' => current_time('mysql', 1)
        ]);

        return $wpdb->insert_id;
    }

    public static function getConversationIdBySession($session_id) {
        global $wpdb;
        $table = Constants::DB_CONVERSATIONS;
        
        $sql = $wpdb->prepare("SELECT id FROM $table WHERE session_id = %s LIMIT 1", $session_id);
        $id = $wpdb->get_var($sql);

        if (!$id) {
            return self::createConversation($session_id);
        }
        return $id;
    }

    public static function getMessagesForConversation($conversation_id, $limit = 10) {
        global $wpdb;
        $table = Constants::DB_MESSAGES;
        
        $sql = $wpdb->prepare("SELECT role, content FROM $table WHERE conversation_id = %d ORDER BY created_at ASC LIMIT %d", $conversation_id, $limit);
        return $wpdb->get_results($sql, ARRAY_A);
    }

    public static function saveMessage($conversation_id, $role, $content, $metrics = []) {
        global $wpdb;
        $table = Constants::DB_MESSAGES;

        $data = [
            'conversation_id' => $conversation_id,
            'role' => $role,
            'content' => $content,
            'created_at' => current_time('mysql', 1)
        ];

        if (!empty($metrics)) {
            if (isset($metrics['input_tokens'])) $data['input_tokens'] = $metrics['input_tokens'];
            if (isset($metrics['output_tokens'])) $data['output_tokens'] = $metrics['output_tokens'];
            if (isset($metrics['input_cost'])) $data['input_cost'] = $metrics['input_cost'];
            if (isset($metrics['output_cost'])) $data['output_cost'] = $metrics['output_cost'];
            if (isset($metrics['total_cost'])) $data['total_cost'] = $metrics['total_cost'];
        }

        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }
}
