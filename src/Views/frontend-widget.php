<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="waip-chat-container" style="--waip-primary: <?php echo esc_attr($primary_color); ?>;">
    <div id="waip-chat-button">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
    </div>
    
    <div id="waip-chat-window" class="waip-hidden">
        <div class="waip-chat-header">
            <h3><?php echo esc_html($assistant_name); ?></h3>
            <button id="waip-chat-close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div id="waip-chat-messages">
            <div class="waip-message waip-system-message">
                ¡Hola! Soy <?php echo esc_html($assistant_name); ?>. ¿En qué te puedo ayudar hoy?
            </div>
        </div>
        <div class="waip-chat-input-area">
            <input type="text" id="waip-chat-input" placeholder="Escribe tu mensaje..." autocomplete="off">
            <button id="waip-chat-send">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            </button>
        </div>
    </div>
</div>
