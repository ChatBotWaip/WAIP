<?php
namespace Waip\Repositories;

use Waip\Config\Constants;

if (!defined('ABSPATH')) {
    exit;
}

class MessageRepository {

    public static function createConversation($session_id, $ip_address = null) {
        global $wpdb;
        $table = Constants::tableConversations();
        
        $wpdb->insert($table, [
            'session_id' => $session_id,
            'ip_address' => $ip_address,
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]);

        return $wpdb->insert_id;
    }

    public static function getConversationIdBySession($session_id, $ip_address = null) {
        global $wpdb;
        $table = Constants::tableConversations();
        
        $sql = $wpdb->prepare("SELECT id, updated_at FROM $table WHERE session_id = %s AND status = 'active' ORDER BY id DESC LIMIT 1", $session_id);
        $row = $wpdb->get_row($sql);

        if (!$row) {
            return self::createConversation($session_id, $ip_address);
        }

        // Comprobar si ha estado inactivo durante 24 horas
        $updated_timestamp = strtotime($row->updated_at);
        $current_timestamp = current_time('timestamp', 1);
        
        if (($current_timestamp - $updated_timestamp) > 86400) { // 24 horas en segundos
            // Cerrar conversación antigua
            $wpdb->update($table, ['status' => 'closed'], ['id' => $row->id]);
            // Crear una nueva
            return self::createConversation($session_id, $ip_address);
        }

        // Actualizar la marca de tiempo y la IP (en caso de que la IP cambie para la misma sesión)
        $update_data = ['updated_at' => current_time('mysql')];
        if ($ip_address) {
            $update_data['ip_address'] = $ip_address;
        }
        $wpdb->update($table, $update_data, ['id' => $row->id]);

        return $row->id;
    }

    public static function updateConversationLead($conversation_id, $name = null, $email = null, $phone = null) {
        global $wpdb;
        $table = Constants::tableConversations();
        
        $data = [];
        
        if ($name) {
            $name = sanitize_text_field(trim($name));
            // Obtener el nombre actual para evitar sobreescribir un nombre bueno con uno malo
            $current_name = $wpdb->get_var($wpdb->prepare("SELECT user_name FROM $table WHERE id = %d", $conversation_id));
            
            $should_update_name = true;
            if (!empty($current_name)) {
                $current_words = str_word_count(trim($current_name));
                $new_words = str_word_count($name);
                
                // Si el nombre viejo tiene 2 o más palabras (ej: "Juan Perez"), y el nuevo solo tiene 1 (ej: "Monto"),
                // NO lo sobreescribimos, protegemos el nombre original.
                if ($current_words >= 2 && $new_words == 1) {
                    $should_update_name = false;
                }
            }
            
            if ($should_update_name) {
                $data['user_name'] = $name;
            }
        }

        if ($email) $data['user_email'] = sanitize_email($email);
        if ($phone) $data['user_phone'] = sanitize_text_field($phone);
        
        if (!empty($data)) {
            $wpdb->update($table, $data, ['id' => $conversation_id]);
        }
    }

    public static function getMessagesForConversation($conversation_id, $limit = 100) {
        global $wpdb;
        $table = Constants::tableMessages();
        
        // Obtener los ÚLTIMOS $limit mensajes, pero devolverlos en orden cronológico (ASC)
        $sql = $wpdb->prepare("
            SELECT role, content, attachment_url, created_at 
            FROM (
                SELECT role, content, attachment_url, created_at, id
                FROM $table 
                WHERE conversation_id = %d 
                ORDER BY created_at DESC, id DESC
                LIMIT %d
            ) AS subquery
            ORDER BY created_at ASC, id ASC
        ", $conversation_id, $limit);
        
        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Guarda un mensaje en la base de datos.
     * 
     * @param int $conversation_id
     * @param string $role ('user', 'assistant', 'system')
     * @param string $content
     * @param array $metrics Métricas de tokens opcionales
     * @param string $attachment_url Imagen en Base64 opcional
     * @return int|false
     */
    public static function saveMessage($conversation_id, $role, $content, $metrics = [], $attachment_url = null) {
        global $wpdb;
        $table = Constants::tableMessages();
        $conversations_table = Constants::tableConversations();

        $data = [
            'conversation_id' => $conversation_id,
            'role'            => sanitize_text_field($role),
            'content'         => sanitize_textarea_field($content),
            'attachment_url'  => $attachment_url ? esc_url_raw($attachment_url) : null,
            'created_at'      => current_time('mysql')
        ];

        if (!empty($metrics)) {
            if (isset($metrics['input_tokens'])) $data['input_tokens'] = $metrics['input_tokens'];
            if (isset($metrics['output_tokens'])) $data['output_tokens'] = $metrics['output_tokens'];
            if (isset($metrics['input_cost'])) $data['input_cost'] = $metrics['input_cost'];
            if (isset($metrics['output_cost'])) $data['output_cost'] = $metrics['output_cost'];
            if (isset($metrics['total_cost'])) $data['total_cost'] = $metrics['total_cost'];
        }

        $wpdb->insert($table, $data);
        
        // Actualizar updated_at de la conversación
        $wpdb->update($conversations_table, ['updated_at' => current_time('mysql')], ['id' => $conversation_id]);
        
        return $wpdb->insert_id;
    }

    public static function deleteOldConversations() {
        global $wpdb;
        $conversations_table = Constants::tableConversations();
        $messages_table = Constants::tableMessages();

        // Eliminar conversaciones con 0 mensajes (basura)
        $wpdb->query("DELETE FROM $conversations_table WHERE id NOT IN (SELECT DISTINCT conversation_id FROM $messages_table)");

        // Eliminar conversaciones más antiguas de 30 días
        $thirty_days_ago = date('Y-m-d H:i:s', current_time('timestamp', 1) - (30 * 86400));
        
        // Encontrar IDs a eliminar
        $sql = $wpdb->prepare("SELECT id FROM $conversations_table WHERE updated_at < %s", $thirty_days_ago);
        $ids = $wpdb->get_col($sql);

        if (!empty($ids)) {
            $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
            // Eliminar mensajes
            $wpdb->query($wpdb->prepare("DELETE FROM $messages_table WHERE conversation_id IN ($ids_placeholder)", ...$ids));
            // Eliminar conversaciones
            $wpdb->query($wpdb->prepare("DELETE FROM $conversations_table WHERE id IN ($ids_placeholder)", ...$ids));
        }
    }

    public static function getAllConversations($page = 1, $per_page = 20) {
        global $wpdb;
        $table = Constants::tableConversations();
        $messages_table = Constants::tableMessages();
        $offset = ($page - 1) * $per_page;

        // Obtener recuento total
        $total = $wpdb->get_var("SELECT COUNT(id) FROM $table");

        // Obtener conversaciones con recuento de mensajes
        $sql = $wpdb->prepare("
            SELECT c.*, 
            (SELECT COUNT(*) FROM $messages_table m WHERE m.conversation_id = c.id) as message_count
            FROM $table c 
            ORDER BY c.updated_at DESC 
            LIMIT %d OFFSET %d
        ", $per_page, $offset);
        
        $results = $wpdb->get_results($sql, ARRAY_A);

        return [
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'items' => $results
        ];
    }
}
