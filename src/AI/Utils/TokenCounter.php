<?php
namespace Waip\AI\Utils;

if (!defined('ABSPATH')) {
    exit;
}

class TokenCounter {

    // Estas son estimaciones de costos simplificadas para el MVP. En una app real, los costos varían por modelo (ej. gpt-4o vs gpt-3.5-turbo).
    // Los precios son por cada millón de tokens, los almacenamos como precio por 1 token para el cálculo.
    private static $pricing = [
        'gpt-3.5-turbo' => [
            'input' => 0.50 / 1000000,
            'output' => 1.50 / 1000000
        ],
        'gpt-4o' => [
            'input' => 5.00 / 1000000,
            'output' => 15.00 / 1000000
        ],
        'gpt-4o-mini' => [
            'input' => 0.15 / 1000000,
            'output' => 0.60 / 1000000
        ],
        'gpt-5.6-luna' => [
            'input' => 0.20 / 1000000,
            'output' => 1.20 / 1000000
        ],
        'gpt-5.6-terra' => [
            'input' => 2.00 / 1000000,
            'output' => 12.00 / 1000000
        ],
        'gpt-5.6-sol' => [
            'input' => 5.00 / 1000000,
            'output' => 30.00 / 1000000
        ]
    ];

    /**
     * Calcula los costos basados en la cantidad de tokens reportados por OpenAI.
     * Retorna array con 'input_cost', 'output_cost', 'total_cost'.
     */
    public static function calculateCost($model, $input_tokens, $output_tokens) {
        $model = strtolower($model);
        
        // Usar los precios de gpt-4o-mini por defecto si el modelo exacto no coincide (como respaldo)
        $prices = self::$pricing[$model] ?? self::$pricing['gpt-4o-mini'];

        $input_cost = $input_tokens * $prices['input'];
        $output_cost = $output_tokens * $prices['output'];

        return [
            'input_cost' => $input_cost,
            'output_cost' => $output_cost,
            'total_cost' => $input_cost + $output_cost
        ];
    }
}
