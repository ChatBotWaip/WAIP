<?php
namespace Waip\Repositories;

use Waip\Config\Constants;

if (!defined('ABSPATH')) {
    exit;
}

class DocumentRepository {

    public static function saveDocument($post_id, $title, $type, $url, $status = 'indexed') {
        global $wpdb;
        $table = Constants::DB_DOCUMENTS;
        
        $existing_doc_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE source_url = %s", $url));

        if ($existing_doc_id) {
            $wpdb->update($table, [
                'title' => $title,
                'status' => $status,
                'last_indexed' => current_time('mysql', 1)
            ], ['id' => $existing_doc_id]);
            
            // Delete old embeddings for this document
            self::deleteEmbeddings($existing_doc_id);
            return $existing_doc_id;
        }

        $wpdb->insert($table, [
            'title' => $title,
            'type' => $type,
            'source_url' => $url,
            'status' => $status,
            'last_indexed' => current_time('mysql', 1)
        ]);

        return $wpdb->insert_id;
    }

    public static function saveEmbedding($document_id, $chunk_text, $vector_array) {
        global $wpdb;
        $table = Constants::DB_EMBEDDINGS;

        $wpdb->insert($table, [
            'document_id' => $document_id,
            'chunk_text' => $chunk_text,
            'vector_json' => json_encode($vector_array)
        ]);

        return $wpdb->insert_id;
    }

    public static function deleteEmbeddings($document_id) {
        global $wpdb;
        $table = Constants::DB_EMBEDDINGS;
        $wpdb->delete($table, ['document_id' => $document_id]);
    }

    public static function getAllEmbeddings() {
        global $wpdb;
        $table = Constants::DB_EMBEDDINGS;
        
        // This brings all vectors into memory. This is exactly what the user requested for MVP ("similitud del coseno se hará en memoria usando PHP")
        return $wpdb->get_results("SELECT id, document_id, chunk_text, vector_json FROM $table", ARRAY_A);
    }
}
