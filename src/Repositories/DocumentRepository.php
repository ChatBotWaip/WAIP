<?php
namespace Waip\Repositories;

use Waip\Config\Constants;

if (!defined('ABSPATH')) {
    exit;
}

class DocumentRepository {

    public static function saveDocument($post_id, $title, $type, $url, $status = 'indexed') {
        global $wpdb;
        $table = Constants::tableDocuments();
        
        $existing_doc_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE source_url = %s", $url));

        if ($existing_doc_id) {
            $wpdb->update($table, [
                'title' => $title,
                'status' => $status,
                'last_indexed' => current_time('mysql', 1)
            ], ['id' => $existing_doc_id]);
            
            // Eliminar embeddings antiguos para este documento
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
        $table = Constants::tableEmbeddings();

        $wpdb->insert($table, [
            'document_id' => $document_id,
            'chunk_text' => $chunk_text,
            'vector_json' => json_encode($vector_array)
        ]);

        return $wpdb->insert_id;
    }

    public static function deleteEmbeddings($document_id) {
        global $wpdb;
        $table = Constants::tableEmbeddings();
        $wpdb->delete($table, ['document_id' => $document_id]);
    }

    public static function getAllEmbeddings() {
        global $wpdb;
        $table = Constants::tableEmbeddings();
        
        // Esto trae todos los vectores a memoria. Es exactamente lo solicitado para el MVP ("similitud del coseno se hará en memoria usando PHP")
        return $wpdb->get_results("SELECT id, document_id, chunk_text, vector_json FROM $table", ARRAY_A);
    }
}
