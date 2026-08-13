<?php
namespace Waip\AI\Providers;

if (!defined('ABSPATH')) {
    exit;
}

interface AIInterface {
    /**
     * @param array $messages Array of messages to send e.g. [['role' => 'user', 'content' => 'hello']]
     * @param array $options Additional options (model, temperature, etc)
     * @return array Returns response array containing 'content', 'input_tokens', 'output_tokens'
     */
    public function generateResponse(array $messages, array $options = []);
}
