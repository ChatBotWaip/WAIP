<?php
namespace Waip\Services;

if (!defined('ABSPATH')) {
    exit;
}

class LeadExtractor {
    
    /**
     * Analiza un mensaje y extrae email, teléfono y posible nombre.
     * 
     * @param string $message Mensaje del usuario
     * @return array Arreglo con claves 'email', 'phone', 'name' (pueden ser null)
     */
    public static function extractContactInfo($message) {
        $result = [
            'email' => null,
            'phone' => null,
            'name' => null
        ];

        // 1. Extraer Email
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $message, $matches)) {
            $result['email'] = sanitize_email($matches[0]);
        }

        // 2. Extraer Teléfono (acepta + al inicio, espacios, y asume formato local o internacional, ej. Colombia)
        if (preg_match('/(?:\+?\d{1,3}[\s-]?)?(?:\(?\d{3}\)?[\s-]?)?\d{3}[\s-]?\d{4}/', $message, $matches)) {
            // Limpiamos todo menos números
            $clean_phone = preg_replace('/[^0-9]/', '', $matches[0]);
            if (strlen($clean_phone) >= 7 && strlen($clean_phone) <= 15) {
                $result['phone'] = sanitize_text_field($clean_phone);
            }
        }

        // 3. Extraer Nombre (Basado en patrones heurísticos)
        $name_patterns = [
            '/mi nombre es ([a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+)/i',
            '/soy ([a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+)/i',
            '/me llamo ([a-zA-ZáéíóúñÁÉÍÓÚÑ\s]+)/i',
        ];
        
        foreach ($name_patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $posible_nombre = trim($matches[1]);
                $palabras = explode(' ', $posible_nombre);
                // Si son de 1 a 4 palabras, probablemente es un nombre
                if (count($palabras) <= 4) {
                    $result['name'] = sanitize_text_field($posible_nombre);
                    break;
                }
            }
        }

        // Si no detectó por patrones, pero el mensaje es MUY corto (1 a 3 palabras)
        // y no contiene números, emails ni palabras comunes de saludo/despedida
        if (!$result['name'] && preg_match('/^([a-záéíóúñA-ZÁÉÍÓÚÑ]+(?:\s+[a-záéíóúñA-ZÁÉÍÓÚÑ]+){0,2})$/iu', trim($message), $matches)) {
            $texto_limpio = strtolower(trim($matches[1]));
            $excluded = [
                'hola', 'buenos dias', 'buenas tardes', 'buenas noches', 'gracias', 'adios', 'chao', 'ok',
                'si', 'no', 'claro', 'dale', 'listo', 'bueno', 'excelente', 'vale', 'perfecto', 'saludos',
                'que tal', 'como estas', 'necesito', 'quiero', 'ayuda', 'informacion', 'precio', 'costo',
                'cuanto', 'donde', 'cuando', 'quien', 'por que', 'para que', 'hola robot', 'hola bot',
                'buen dia', 'buenas', 'hey'
            ];
            
            if (!in_array($texto_limpio, $excluded) && strlen($texto_limpio) > 2) {
                $result['name'] = sanitize_text_field($matches[1]);
            }
        }

        return $result;
    }
}
