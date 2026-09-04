<?php
namespace Waip\Knowledge;

if (!defined('ABSPATH')) {
    exit;
}

class Extractor {

    public static function extractPostContent($post_id) {
        $post = get_post($post_id);
        if (!$post) return '';

        // Aplicar filtros básicos de contenido pero eliminando etiquetas HTML para dejar texto limpio para el LLM
        $content = apply_filters('the_content', $post->post_content);
        
        // Eliminar shortcodes, etiquetas, saltos de línea
        $content = strip_shortcodes($content);
        $content = wp_strip_all_tags($content);
        
        // Eliminar múltiples espacios/saltos de línea
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }
}
