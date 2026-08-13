<?php
namespace Waip\AI\Utils;

if (!defined('ABSPATH')) {
    exit;
}

class TokenCounter {

    // These are simplified cost assumptions for MVP. In a real app, costs differ by model (e.g. gpt-4o vs gpt-3.5-turbo).
    // Prices are per 1M tokens, we store them as per 1 token for calculation.
    private static $pricing = [
        'gpt-3.5-turbo' => [
            'input' => 0.50 / 1000000,
            'output' => 1.50 / 1000000
        ],
        'gpt-4o' => [
            'input' => 5.00 / 1000000,
            'output' => 15.00 / 1000000
        ]
    ];

    /**
     * Calcula los costos basados en la cantidad de tokens reportados por OpenAI.
     * Retorna array con 'input_cost', 'output_cost', 'total_cost'.
     */
    public static function calculateCost($model, $input_tokens, $output_tokens) {
        $model = strtolower($model);
        
        // Default to gpt-4o pricing if exact model not matched (as fallback)
        $prices = self::$pricing[$model] ?? self::$pricing['gpt-4o'];

        $input_cost = $input_tokens * $prices['input'];
        $output_cost = $output_tokens * $prices['output'];

        return [
            'input_cost' => $input_cost,
            'output_cost' => $output_cost,
            'total_cost' => $input_cost + $output_cost
        ];
    }
}
