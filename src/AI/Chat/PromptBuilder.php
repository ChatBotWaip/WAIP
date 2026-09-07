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
     * Construye el prompt del sistema inyectando el contexto RAG si está habilitado y disponible
     * 
     * @param string $user_message
     * @return string El prompt del sistema final
     */
    public static function buildSystemPrompt($user_message) {
        $base_prompt = SettingsManager::getSystemPrompt();
        
        // Asumimos que RAG está habilitado por defecto para el Sprint 2
        $rag_enabled = SettingsManager::isRagEnabled() || true; // Forzando a true para pruebas del Sprint 2 si falta la opción

        // Reglas Premium
        $premium_rules = "\n\nREGLAS DE ORO OBLIGATORIAS:\n";
        $premium_rules .= "1. IDIOMA: Detecta automáticamente el idioma en el que te habla el usuario y respóndele estrictamente en ese mismo idioma (Ej: si te habla en inglés, traduce el contexto y responde en inglés).\n";
        $premium_rules .= "2. CAPTURA DE DATOS: En algún momento natural de la conversación (preferiblemente en el primer o segundo mensaje), debes preguntar amablemente el nombre, el correo electrónico y, de manera opcional, el número de teléfono del usuario por si se corta la comunicación.\n";
        $premium_rules .= "3. PRECISIÓN Y CONCISIÓN: Responde ÚNICAMENTE a lo que el usuario te está preguntando de forma directa y conversacional. NUNCA listes otros servicios o créditos si el usuario no te los ha pedido. Usa el contexto solo para responder la duda específica.\n";
        
        $whatsapp_number = SettingsManager::getWhatsappNumber();
        if (!empty($whatsapp_number)) {
            // Eliminar espacios y signos de más para el link de WhatsApp
            $clean_number = preg_replace('/[^0-9]/', '', $whatsapp_number);
            $premium_rules .= "4. WHATSAPP Y CONTACTO: SIEMPRE que menciones WhatsApp, un número de teléfono, o sugieras contactar a la empresa, DEBES OBLIGATORIAMENTE usar este enlace exacto en Markdown: [Hablar por WhatsApp](https://wa.me/{$clean_number}). ESTÁ ESTRICTAMENTE PROHIBIDO escribir el número de teléfono en texto plano (ej: 301 620 5460), siempre debes usar el enlace Markdown para que se genere el botón.\n";
        }

        $base_prompt .= $premium_rules;

        if (!$rag_enabled) {
            return $base_prompt;
        }

        $generator = new EmbeddingGenerator();
        $query_vector = $generator->generateEmbedding($user_message);

        if (!$query_vector) {
            return $base_prompt; // Volver al prompt base si falla el embedding
        }

        $max_chunks = SettingsManager::getMaxChunks();
        $relevant_chunks = VectorSearch::search($query_vector, $max_chunks);

        if (empty($relevant_chunks)) {
            return $base_prompt;
        }

        // Ensamblar contexto
        $context_string = "\n\nINFORMACIÓN DE CONTEXTO (BASE DE CONOCIMIENTO):\n";
        $context_string .= "Usa la siguiente información para responder a la pregunta del usuario. Si la respuesta no está en el contexto y no está relacionada con la empresa, indica que no tienes esa información o sugiere contactar a un asesor.\n\n";

        foreach ($relevant_chunks as $chunk_data) {
            $context_string .= "- " . $chunk_data['chunk_text'] . "\n";
        }

        return $base_prompt . $context_string;
    }
}
