<?php
namespace Waip\Knowledge;

use Waip\Repositories\DocumentRepository;

if (!defined('ABSPATH')) {
    exit;
}

class VectorSearch {

    /**
     * Search for the top K similar chunks based on cosine similarity
     * 
     * @param array $query_vector The embedding vector of the user's query
     * @param int $top_k Number of results to return
     * @return array Array of associative arrays with 'chunk_text' and 'similarity'
     */
    public static function search($query_vector, $top_k = 3) {
        $all_embeddings = DocumentRepository::getAllEmbeddings();
        if (empty($all_embeddings)) {
            return [];
        }

        $results = [];

        foreach ($all_embeddings as $row) {
            $db_vector = json_decode($row['vector_json'], true);
            if (!is_array($db_vector)) continue;

            $similarity = self::cosineSimilarity($query_vector, $db_vector);
            
            // Only consider somewhat relevant results
            if ($similarity > 0.5) {
                $results[] = [
                    'chunk_text' => $row['chunk_text'],
                    'similarity' => $similarity
                ];
            }
        }

        // Sort by similarity descending
        usort($results, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        // Return top K
        return array_slice($results, 0, $top_k);
    }

    /**
     * Calculates the cosine similarity between two vectors
     * 
     * @param array $vecA
     * @param array $vecB
     * @return float
     */
    private static function cosineSimilarity(array $vecA, array $vecB) {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        $count = min(count($vecA), count($vecB));
        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $vecA[$i] * $vecB[$i];
            $normA += pow($vecA[$i], 2);
            $normB += pow($vecB[$i], 2);
        }

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
