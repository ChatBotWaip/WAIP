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
        add_action('wp_ajax_waip_extract_links', [$this, 'handle_ajax_extract_links']);
        add_action('wp_ajax_waip_ingest_url', [$this, 'handle_ajax_ingest_url']);
    }

    public function handle_ajax_index_post() {
        // Control de seguridad básico
        if (!current_user_can('manage_options') || !check_ajax_referer('waip_add_knowledge', 'security', false)) {
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

    public function handle_ajax_extract_links() {
        if (!current_user_can('manage_options') || !check_ajax_referer('waip_add_knowledge', 'security', false)) {
            wp_send_json_error('Permiso denegado.', 403);
        }

        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        if (empty($url)) {
            wp_send_json_error('URL es requerida.');
        }

        $links = \Waip\Knowledge\DocumentManager::extractInternalLinks($url, 30);
        if (empty($links)) {
            wp_send_json_error('No se encontraron enlaces válidos.');
        }

        wp_send_json_success(['links' => $links]);
    }

    public function handle_ajax_ingest_url() {
        if (!current_user_can('manage_options') || !check_ajax_referer('waip_add_knowledge', 'security', false)) {
            wp_send_json_error('Permiso denegado.', 403);
        }

        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        if (empty($url)) {
            wp_send_json_error('URL es requerida.');
        }

        $result = \Waip\Knowledge\DocumentManager::ingestUrl($url);
        if ($result === true) {
            wp_send_json_success(['message' => 'URL procesada.']);
        } else {
            wp_send_json_error($result);
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

        $chunks = TextChunker::chunkText($content);
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
