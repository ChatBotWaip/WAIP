<?php
namespace Waip\Database;

use Waip\Config\Constants;

if (!defined('ABSPATH')) {
    exit;
}

class Migrations {

    public static function run() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $conversations_table = Constants::DB_CONVERSATIONS;
        $messages_table = Constants::DB_MESSAGES;
        $logs_table = Constants::DB_LOGS;

        $sql_conversations = "CREATE TABLE {$conversations_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            status varchar(50) DEFAULT 'active' NOT NULL,
            PRIMARY KEY  (id),
            KEY session_id (session_id)
        ) $charset_collate;";

        $sql_messages = "CREATE TABLE {$messages_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) NOT NULL,
            role varchar(20) NOT NULL,
            content longtext NOT NULL,
            input_tokens int(11) DEFAULT 0,
            output_tokens int(11) DEFAULT 0,
            input_cost decimal(10,6) DEFAULT 0.000000,
            output_cost decimal(10,6) DEFAULT 0.000000,
            total_cost decimal(10,6) DEFAULT 0.000000,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY conversation_id (conversation_id)
        ) $charset_collate;";

        $sql_logs = "CREATE TABLE {$logs_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL,
            component varchar(50) NOT NULL,
            message longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $documents_table = Constants::DB_DOCUMENTS;
        $embeddings_table = Constants::DB_EMBEDDINGS;

        $sql_documents = "CREATE TABLE {$documents_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            source_url varchar(255) NOT NULL,
            last_indexed datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            status varchar(50) DEFAULT 'indexed' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_embeddings = "CREATE TABLE {$embeddings_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            document_id bigint(20) NOT NULL,
            chunk_text longtext NOT NULL,
            vector_json longtext NOT NULL,
            PRIMARY KEY  (id),
            KEY document_id (document_id)
        ) $charset_collate;";

        dbDelta($sql_conversations);
        dbDelta($sql_messages);
        dbDelta($sql_logs);
        dbDelta($sql_documents);
        dbDelta($sql_embeddings);
    }
}
