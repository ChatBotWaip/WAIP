<?php
namespace Waip\Widgets;

use Waip\Config\SettingsManager;

if (!defined('ABSPATH')) {
    exit;
}

class ChatWidget {

    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_footer', [$this, 'render_widget']);
    }

    public function enqueue_assets() {
        wp_enqueue_style('waip-chat-css', WAIP_PLUGIN_URL . 'src/Assets/css/chat.css', [], WAIP_VERSION);
        wp_enqueue_script('waip-chat-js', WAIP_PLUGIN_URL . 'src/Assets/js/chat.js', [], WAIP_VERSION, true);

        wp_localize_script('waip-chat-js', 'waipData', [
            'apiUrl' => rest_url('waip/v1/chat'),
            'primaryColor' => SettingsManager::getPrimaryColor(),
            'secondaryColor' => SettingsManager::getSecondaryColor(),
            'assistantName' => SettingsManager::getAssistantName(),
            'assistantLogo' => SettingsManager::getAssistantLogo(),
            'welcomeMessage' => SettingsManager::getWelcomeMessage(),
            'idleMessage' => SettingsManager::getIdleMessage(),
            'idleTime' => SettingsManager::getIdleTime(),
            'whatsappNumber' => preg_replace('/[^0-9]/', '', SettingsManager::getWhatsappNumber()),
            'quickReplies' => [
                '❓ Preguntas frecuentes',
                '📝 Requisitos de crédito',
                '💰 Opciones de crédito'
            ]
        ]);
    }

    public function render_widget() {
        $assistant_name = SettingsManager::getAssistantName();
        $assistant_logo = SettingsManager::getAssistantLogo();
        $primary_color = SettingsManager::getPrimaryColor();
        $secondary_color = SettingsManager::getSecondaryColor();
        require WAIP_PLUGIN_DIR . 'src/Views/frontend-widget.php';
    }
}
