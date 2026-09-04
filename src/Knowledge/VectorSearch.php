<?php
namespace Waip\Knowledge;

use Waip\Repositories\DocumentRepository;

if (!defined('ABSPATH')) {
    exit;
}

class VectorSearch {

    /**
     * Buscar los K fragmentos más similares basados en similitud del coseno
     * 
     * @param array $query_vector El vector de embedding de la consulta del usuario
     * @param int $top_k Número de resultados a devolver
     * @return array Array de arrays asociativos con 'chunk_text' y 'similarity'
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
            
            // Solo considerar resultados algo relevantes
            if ($similarity > 0.20) {
                $results[] = [
                    'chunk_text' => $row['chunk_text'],
                    'similarity' => $similarity
                ];
            }
        }

        // Ordenar por similitud de forma descendente
        usort($results, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        // Retornar el top K
        return array_slice($results, 0, $top_k);
    }

    /**
     * Calcula la similitud del coseno entre dos vectores
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
