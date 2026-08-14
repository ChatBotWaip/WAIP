<?php
namespace Waip\Knowledge;

use Waip\AI\Embeddings\EmbeddingGenerator;
use Waip\Repositories\DocumentRepository;
use Waip\Services\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class BatchIndexer {

    public function init() {
        add_action('wp_ajax_waip_index_post', [$this, 'handle_ajax_index_post']);
    }

    public function handle_ajax_index_post() {
        // Basic security check
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para esto.', 403);
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error('post_id es requerido.', 400);
        }

        try {
            $result = self::indexPost($post_id);
            if ($result) {
                wp_send_json_success(['message' => 'Post indexado correctamente.']);
            } else {
                wp_send_json_error('No se pudo generar embeddings o post vacío.');
            }
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public static function indexPost($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return false;
        }

        $content = Extractor::extractPostContent($post_id);
        if (empty($content)) {
            return false;
        }

        $chunks = Chunker::chunkText($content);
        if (empty($chunks)) {
            return false;
        }

        $generator = new EmbeddingGenerator();
        
        $url = get_permalink($post_id);
        $title = $post->post_title;
        $type = $post->post_type;

        $document_id = DocumentRepository::saveDocument($post_id, $title, $type, $url);

        $success_count = 0;
        foreach ($chunks as $chunk) {
            $vector = $generator->generateEmbedding($chunk);
            if ($vector) {
                DocumentRepository::saveEmbedding($document_id, $chunk, $vector);
                $success_count++;
            }
        }

        Logger::info('BatchIndexer', "Indexado post ID $post_id. Chunks exitosos: $success_count/" . count($chunks));
        return $success_count > 0;
    }
}
