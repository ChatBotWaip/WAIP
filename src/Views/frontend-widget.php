<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="waip-chat-container" style="--waip-primary: <?php echo esc_attr($primary_color); ?>; --waip-secondary: <?php echo esc_attr($secondary_color); ?>;">
    <div id="waip-chat-button">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
    </div>
    
    <div id="waip-chat-window" class="waip-hidden">
        <div class="waip-chat-header">
            <h3>Asistente virtual</h3>
            <button id="waip-chat-close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </button>
        </div>
        <!-- Mensajes del Chat -->
        <div id="waip-chat-messages">
            <!-- Los mensajes serán cargados dinámicamente por chat.js -->
        </div>
        <div class="waip-chat-input-area" style="position: relative;">
            <div style="display: flex; align-items: center; width: 100%; gap: 8px;">
                <input type="text" id="waip-chat-input" placeholder="Escribe un mensaje" autocomplete="off">
                <button id="waip-chat-send" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </button>
            </div>
        </div>
    </div>
</div>
