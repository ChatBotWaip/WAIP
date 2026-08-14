<?php
namespace Waip\Knowledge;

if (!defined('ABSPATH')) {
    exit;
}

class Extractor {

    public static function extractPostContent($post_id) {
        $post = get_post($post_id);
        if (!$post) return '';

        // Apply basic content filters but strip tags to keep only clean text for the LLM
        $content = apply_filters('the_content', $post->post_content);
        
        // Remove shortcodes, tags, line breaks
        $content = strip_shortcodes($content);
        $content = wp_strip_all_tags($content);
        
        // Remove multiple spaces/newlines
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }
}
