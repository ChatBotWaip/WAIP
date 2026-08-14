<?php
namespace Waip\Knowledge;

if (!defined('ABSPATH')) {
    exit;
}

class Chunker {

    /**
     * Chunks a text into smaller pieces roughly based on word count.
     * Overlaps the chunks slightly to preserve context between chunks.
     * 
     * @param string $text
     * @param int $chunk_size Number of words per chunk
     * @param int $overlap Number of words to overlap
     * @return array
     */
    public static function chunkText($text, $chunk_size = 200, $overlap = 50) {
        if (empty(trim($text))) {
            return [];
        }

        $words = explode(' ', $text);
        $total_words = count($words);
        $chunks = [];

        for ($i = 0; $i < $total_words; $i += ($chunk_size - $overlap)) {
            $chunk_words = array_slice($words, $i, $chunk_size);
            $chunk_text = implode(' ', $chunk_words);
            
            if (!empty(trim($chunk_text))) {
                $chunks[] = trim($chunk_text);
            }
        }

        return $chunks;
    }
}
