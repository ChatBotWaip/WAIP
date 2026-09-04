<?php
require_once __DIR__ . '/../../../../wp-load.php';
$query = "tienen algun correo?";
$embedder = new \Waip\AI\Embeddings\EmbeddingGenerator();
$query_vector = $embedder->generateEmbedding($query);
$chunks = \Waip\Knowledge\VectorSearch::search($query_vector, 3); // Wait, VectorSearch skips < 0.5
// I'll write my own search here
$all = \Waip\Repositories\DocumentRepository::getAllEmbeddings();
echo "QUERY: $query\n";
foreach ($all as $row) {
    $db_vector = json_decode($row['vector_json'], true);
    if (!is_array($db_vector)) continue;
    $sim = 0;
    $dot = 0; $nA = 0; $nB = 0;
    $c = min(count($query_vector), count($db_vector));
    for ($i=0; $i<$c; $i++) {
        $dot += $query_vector[$i]*$db_vector[$i];
        $nA += pow($query_vector[$i], 2);
        $nB += pow($db_vector[$i], 2);
    }
    if ($nA > 0 && $nB > 0) $sim = $dot / (sqrt($nA) * sqrt($nB));
    
    if (strpos(strtolower($row['chunk_text']), 'correo') !== false || strpos($row['chunk_text'], '@') !== false || strpos(strtolower($row['chunk_text']), 'contacto') !== false) {
        echo "SIMILARITY: " . $sim . "\n";
        echo "CHUNK: " . substr($row['chunk_text'], 0, 100) . "...\n\n";
    }
}
