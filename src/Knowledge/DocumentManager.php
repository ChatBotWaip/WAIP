<?php
namespace Waip\Knowledge;

use Waip\Config\Constants;
use Waip\AI\Embeddings\EmbeddingGenerator;
use Waip\Services\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class DocumentManager {

    /**
     * Ingiere texto sin formato en la base de datos RAG.
     * 
     * @param string $title
     * @param string $text
     * @return bool|string True si tiene éxito, mensaje de error si falla
     */
    public static function ingestText($title, $text, $type = 'manual_text', $source_url = 'admin_input') {
        global $wpdb;
        $doc_table = Constants::tableDocuments();
        $emb_table = Constants::tableEmbeddings();

        // 1. Dividir texto en fragmentos (Chunks)
        $chunks = TextChunker::chunkText($text);
        if (empty($chunks)) {
            return "El texto provisto está vacío o no se pudo procesar.";
        }

        // 2. Guardar registro del Documento
        $wpdb->insert($doc_table, [
            'title' => sanitize_text_field($title),
            'type' => sanitize_text_field($type),
            'source_url' => sanitize_text_field($source_url),
            'status' => 'indexing',
            'raw_text' => $text
        ]);
        
        $document_id = $wpdb->insert_id;
        if (!$document_id) {
            return "Error al guardar el documento en la base de datos.";
        }

        // 3. Generar Embeddings para cada fragmento
        $embedder = new EmbeddingGenerator();
        $success_count = 0;

        foreach ($chunks as $chunk) {
            $vector = $embedder->generateEmbedding($chunk);
            
            if ($vector && is_array($vector)) {
                $wpdb->insert($emb_table, [
                    'document_id' => $document_id,
                    'chunk_text' => $chunk,
                    'vector_json' => json_encode($vector)
                ]);
                $success_count++;
            } else {
                Logger::error('DocumentManager', "Falló al generar embedding para el doc ID: $document_id");
            }
        }

        // 4. Actualizar estado
        if ($success_count > 0) {
            $wpdb->update($doc_table, ['status' => 'indexed'], ['id' => $document_id]);
            return true;
        } else {
            $wpdb->update($doc_table, ['status' => 'failed'], ['id' => $document_id]);
            return "Error conectando con OpenAI. Verifica tu API Key o saldo.";
        }
    }

    /**
     * Extrae el contenido de una URL, limpia el HTML y procesa el texto.
     * 
     * @param string $url
     * @return bool|string True si tiene éxito, mensaje de error si falla
     */
    public static function ingestUrl($url) {
        $url = esc_url_raw($url);
        if (empty($url)) {
            return "La URL proporcionada no es válida.";
        }

        $response = wp_remote_get($url, [
            'timeout' => 20,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) WAIP Bot'
            ]
        ]);

        if (is_wp_error($response)) {
            return "Error al intentar acceder a la URL: " . $response->get_error_message();
        }

        $html = wp_remote_retrieve_body($response);
        if (empty($html)) {
            return "La página web no devolvió ningún contenido.";
        }

        // Extraer <title> si está presente
        $title = '';
        if (preg_match('#<title(.*?)>(.*?)</title>#is', $html, $matches)) {
            $title = trim(wp_strip_all_tags($matches[2]));
        }
        
        if (empty($title)) {
            $parsed_url = wp_parse_url($url);
            $domain = isset($parsed_url['host']) ? $parsed_url['host'] : 'Sitio Web';
            $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
            $title = $domain . $path;
        }

        // Limpiar HTML: Eliminar scripts, estilos y bloques SVG antes de quitar las etiquetas HTML
        $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
        $html = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $html);
        $html = preg_replace('#<svg(.*?)>(.*?)</svg>#is', '', $html);
        
        // Convertir algunos elementos de bloque a saltos de línea para preservar el espaciado
        $html = preg_replace('#<(div|p|h1|h2|h3|h4|h5|h6|li|br)(.*?)>#i', "\n", $html);

        $text = wp_strip_all_tags($html);
        
        // Normalizar espacios en blanco (reducir múltiples espacios/saltos de línea a un solo espacio o doble salto de línea)
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/[\r\n]+/', "\n\n", trim($text));

        if (empty($text) || strlen($text) < 50) {
            return "No se encontró suficiente texto legible en esta URL.";
        }

        return self::ingestText($title, $text, 'url', $url);
    }

    /**
     * Elimina un documento y sus embeddings.
     */
    public static function deleteDocument($id) {
        global $wpdb;
        $wpdb->delete(Constants::tableEmbeddings(), ['document_id' => $id]);
        $wpdb->delete(Constants::tableDocuments(), ['id' => $id]);
        return true;
    }

    /**
     * Actualiza un documento existente y regenera sus embeddings.
     */
    public static function updateDocument($id, $title, $text) {
        global $wpdb;
        $doc_table = Constants::tableDocuments();
        $emb_table = Constants::tableEmbeddings();

        $doc = $wpdb->get_row($wpdb->prepare("SELECT * FROM $doc_table WHERE id = %d", $id));
        if (!$doc) return "Documento no encontrado.";

        $chunks = TextChunker::chunkText($text);
        if (empty($chunks)) return "El texto provisto está vacío o no se pudo procesar.";

        $wpdb->update($doc_table, [
            'title' => sanitize_text_field($title),
            'status' => 'indexing',
            'raw_text' => $text
        ], ['id' => $id]);

        $wpdb->delete($emb_table, ['document_id' => $id]);

        $embedder = new EmbeddingGenerator();
        $success_count = 0;

        foreach ($chunks as $chunk) {
            $vector = $embedder->generateEmbedding($chunk);
            if ($vector && is_array($vector)) {
                $wpdb->insert($emb_table, [
                    'document_id' => $id,
                    'chunk_text' => $chunk,
                    'vector_json' => json_encode($vector)
                ]);
                $success_count++;
            } else {
                Logger::error('DocumentManager', "Falló al actualizar embedding para el doc ID: $id");
            }
        }

        if ($success_count > 0) {
            $wpdb->update($doc_table, ['status' => 'indexed'], ['id' => $id]);
            return true;
        } else {
            $wpdb->update($doc_table, ['status' => 'failed'], ['id' => $id]);
            return "Error conectando con OpenAI al actualizar.";
        }
    }

    /**
     * Obtener un documento específico por ID
     */
    public static function getDocument($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . Constants::tableDocuments() . " WHERE id = %d", $id), ARRAY_A);
    }

    /**
     * Obtener todos los documentos con su conteo de vectores.
     */
    public static function getAllDocuments() {
        global $wpdb;
        $doc_table = Constants::tableDocuments();
        $emb_table = Constants::tableEmbeddings();

        $sql = "
            SELECT d.*, 
            (SELECT COUNT(*) FROM $emb_table e WHERE e.document_id = d.id) as chunk_count
            FROM $doc_table d 
            ORDER BY d.last_indexed DESC
        ";

        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Extrae enlaces internos de una URL dada.
     */
    public static function extractInternalLinks($base_url, $limit = 30) {
        $base_url = esc_url_raw($base_url);
        if (empty($base_url)) {
            return [];
        }

        $parsed_base = wp_parse_url($base_url);
        if (empty($parsed_base['host'])) {
            return [];
        }
        $base_domain = $parsed_base['host'];

        $response = wp_remote_get($base_url, [
            'timeout' => 15,
            'headers' => ['User-Agent' => 'WAIP Crawler']
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $html = wp_remote_retrieve_body($response);
        if (empty($html)) {
            return [];
        }

        // Usar DOMDocument para analizar los enlaces
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        libxml_clear_errors();

        $links = [];
        $tags = $doc->getElementsByTagName('a');

        $ignore_exts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'pdf', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'mp4', 'mp3'];

        foreach ($tags as $tag) {
            $href = $tag->getAttribute('href');
            if (empty($href) || strpos($href, '#') === 0 || strpos($href, 'javascript:') === 0 || strpos($href, 'mailto:') === 0 || strpos($href, 'tel:') === 0) {
                continue;
            }

            // Normalizar URL
            if (strpos($href, 'http') !== 0) {
                if (strpos($href, '/') === 0) {
                    $href = $parsed_base['scheme'] . '://' . $base_domain . $href;
                } else {
                    continue; // Omitir rutas relativas complejas por simplicidad
                }
            }

            $parsed_href = wp_parse_url($href);
            if (empty($parsed_href['host']) || $parsed_href['host'] !== $base_domain) {
                continue; // Enlace externo
            }

            // Limpiar URL (eliminar query strings y fragmentos)
            $clean_url = $parsed_href['scheme'] . '://' . $parsed_href['host'] . (isset($parsed_href['path']) ? $parsed_href['path'] : '/');
            $clean_url = rtrim($clean_url, '/');

            // Comprobar extensión
            $ext = strtolower(pathinfo($clean_url, PATHINFO_EXTENSION));
            if (in_array($ext, $ignore_exts)) {
                continue;
            }

            $links[] = $clean_url;
        }

        // Asegurarse de que la URL base siempre se incluya primero
        $base_clean = rtrim($parsed_base['scheme'] . '://' . $parsed_base['host'] . (isset($parsed_base['path']) ? $parsed_base['path'] : '/'), '/');
        array_unshift($links, $base_clean);

        $links = array_values(array_unique($links));
        return array_slice($links, 0, $limit);
    }
}
