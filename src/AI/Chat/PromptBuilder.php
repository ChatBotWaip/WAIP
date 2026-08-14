<?php
namespace Waip\AI\Chat;

use Waip\AI\Embeddings\EmbeddingGenerator;
use Waip\Knowledge\VectorSearch;
use Waip\Config\SettingsManager;

if (!defined('ABSPATH')) {
    exit;
}

class PromptBuilder {

    /**
     * Builds the system prompt injecting RAG context if enabled and available
     * 
     * @param string $user_message
     * @return string The final system prompt
     */
    public static function buildSystemPrompt($user_message) {
        $base_prompt = SettingsManager::getSystemPrompt();
        
        // Let's assume RAG is enabled for Sprint 2 by default
        $rag_enabled = SettingsManager::isRagEnabled() || true; // Forcing true for Sprint 2 testing if option is missing

        if (!$rag_enabled) {
            return $base_prompt;
        }

        $generator = new EmbeddingGenerator();
        $query_vector = $generator->generateEmbedding($user_message);

        if (!$query_vector) {
            return $base_prompt; // Fallback to base prompt if embedding fails
        }

        $max_chunks = SettingsManager::getMaxChunks();
        $relevant_chunks = VectorSearch::search($query_vector, $max_chunks);

        if (empty($relevant_chunks)) {
            return $base_prompt;
        }

        // Assemble context
        $context_string = "\n\nINFORMACIÓN DE CONTEXTO (BASE DE CONOCIMIENTO):\n";
        $context_string .= "Usa la siguiente información para responder a la pregunta del usuario. Si la respuesta no está en el contexto y no está relacionada con la empresa, indica que no tienes esa información o sugiere contactar a un asesor.\n\n";

        foreach ($relevant_chunks as $chunk_data) {
            $context_string .= "- " . $chunk_data['chunk_text'] . "\n";
        }

        return $base_prompt . $context_string;
    }
}
