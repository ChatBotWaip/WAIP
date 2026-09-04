<?php
namespace Waip\Knowledge;

if (!defined('ABSPATH')) {
    exit;
}

class TextChunker {

    /**
     * Divide el texto en fragmentos superpuestos.
     *
     * @param string $text El texto a dividir.
     * @param int $chunk_size Longitud máxima de cada fragmento.
     * @param int $overlap Número de caracteres a superponer entre fragmentos.
     * @return array Array de fragmentos de texto.
     */
    public static function chunkText($text, $chunk_size = 1000, $overlap = 200) {
        $text = wp_strip_all_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (empty($text)) {
            return [];
        }

        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $chunk = mb_substr($text, $start, $chunk_size);
            
            // Si no estamos al final del texto, intentamos encontrar un punto de corte natural
            if ($start + $chunk_size < $length) {
                $last_period = mb_strrpos($chunk, '. ');
                if ($last_period !== false && $last_period > $chunk_size / 2) {
                    $chunk = mb_substr($chunk, 0, $last_period + 1);
                } else {
                    $last_space = mb_strrpos($chunk, ' ');
                    if ($last_space !== false) {
                        $chunk = mb_substr($chunk, 0, $last_space);
                    }
                }
            }

            $chunks[] = trim($chunk);
            
            $advance = mb_strlen($chunk) - $overlap;
            
            // Asegurarnos SIEMPRE de avanzar
            if ($advance <= 0) {
                $advance = mb_strlen($chunk); // Omitir superposición si causa retroceso
                if ($advance <= 0) {
                    $advance = $chunk_size; // Respaldo absoluto
                }
            }
            
            $start += $advance;
        }

        return $chunks;
    }
}
