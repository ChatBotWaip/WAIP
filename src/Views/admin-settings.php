<?php
if (!defined('ABSPATH')) {
    exit;
}
use Waip\Config\Constants;
use Waip\Config\SettingsManager;
?>
<div class="wrap">
    <h1>Configuración de WAIP (WordPress AI Platform)</h1>
    <form method="post" action="options.php">
        <?php
        settings_fields(Constants::SETTINGS_GROUP);
        do_settings_sections(Constants::SETTINGS_GROUP);
        ?>
        <table class="form-table">
            <tr>
                <th colspan="2"><h2>Estado del Chatbot</h2></th>
            </tr>
            <tr valign="top">
                <th scope="row" style="color: #0073aa;"><strong>Visibilidad en Producción</strong></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(Constants::OPTION_IS_ACTIVE); ?>" value="1" <?php checked(SettingsManager::isPubliclyVisible(), true); ?> />
                        <strong>Mostrar el chat en el sitio web público</strong>
                    </label>
                    <p class="description">Desmarca esta casilla para ocultar el chat a tus clientes. Aún podrás probarlo internamente en la pestaña "Entorno de Pruebas".</p>
                </td>
            </tr>
            <tr>
                <th colspan="2"><hr style="margin: 10px 0;"><h2>Configuración General</h2></th>
            </tr>
            <tr valign="top">
                <th scope="row">Clave API de OpenAI</th>
                <td>
                    <input type="password" name="<?php echo esc_attr(Constants::OPTION_API_KEY); ?>" value="<?php echo esc_attr(SettingsManager::getApiKey()); ?>" class="regular-text" />
                    <p class="description">Tu clave secreta de la API de OpenAI.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Modelo de IA</th>
                <td>
                    <select name="<?php echo esc_attr(Constants::OPTION_MODEL); ?>">
                        <option value="gpt-5.6-luna" <?php selected(SettingsManager::getModel(), 'gpt-5.6-luna'); ?>>GPT-5.6 Luna (Rápido y económico)</option>
                        <option value="gpt-5.6-terra" <?php selected(SettingsManager::getModel(), 'gpt-5.6-terra'); ?>>GPT-5.6 Terra (Equilibrado)</option>
                        <option value="gpt-5.6-sol" <?php selected(SettingsManager::getModel(), 'gpt-5.6-sol'); ?>>GPT-5.6 Sol (Insignia, complejo)</option>
                        <option value="gpt-4o-mini" <?php selected(SettingsManager::getModel(), 'gpt-4o-mini'); ?>>GPT-4o Mini (Clásico)</option>
                        <option value="gpt-4o" <?php selected(SettingsManager::getModel(), 'gpt-4o'); ?>>GPT-4 Omni (Antiguo)</option>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Nombre del Asistente</th>
                <td>
                    <input type="text" name="<?php echo esc_attr(Constants::OPTION_ASSISTANT_NAME); ?>" value="<?php echo esc_attr(SettingsManager::getAssistantName()); ?>" class="regular-text" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Mensaje de Bienvenida</th>
                <td>
                    <textarea name="<?php echo esc_attr(Constants::OPTION_WELCOME_MSG); ?>" rows="3" class="large-text"><?php echo esc_textarea(SettingsManager::getWelcomeMessage()); ?></textarea>
                    <p class="description">El primer mensaje que el bot enviará al abrir el chat.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Mensaje de Inactividad</th>
                <td>
                    <textarea name="<?php echo esc_attr(Constants::OPTION_IDLE_MSG); ?>" rows="2" class="large-text"><?php echo esc_textarea(SettingsManager::getIdleMessage()); ?></textarea>
                    <p class="description">El mensaje que el bot enviará automáticamente si el usuario no responde.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Tiempo de Inactividad (Minutos)</th>
                <td>
                    <input type="number" name="<?php echo esc_attr(Constants::OPTION_IDLE_TIME); ?>" value="<?php echo esc_attr(SettingsManager::getIdleTime()); ?>" class="small-text" min="1" max="60" />
                    <p class="description">¿Cuántos minutos deben pasar para que se envíe el mensaje? (Por defecto: 5).</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Número de WhatsApp (Asesor)</th>
                <td>
                    <input type="text" name="<?php echo esc_attr(Constants::OPTION_WHATSAPP_NUMBER); ?>" value="<?php echo esc_attr(SettingsManager::getWhatsappNumber()); ?>" class="regular-text" placeholder="Ej: 573001234567" />
                    <p class="description">Ingresa el número con el código de país (sin el signo +). Se usará para generar el link (wa.me/numero) cuando el usuario pida hablar con un asesor.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Logo del Asistente (Opcional)</th>
                <td>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="hidden" id="waip_logo_url" name="<?php echo esc_attr(Constants::OPTION_ASSISTANT_LOGO); ?>" value="<?php echo esc_attr(SettingsManager::getAssistantLogo()); ?>" />
                        <div id="waip_logo_preview" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #ccc; overflow: hidden; background: #eee; display: flex; align-items: center; justify-content: center;">
                            <?php if (SettingsManager::getAssistantLogo()): ?>
                                <img src="<?php echo esc_url(SettingsManager::getAssistantLogo()); ?>" style="width: 100%; height: 100%; object-fit: cover;" />
                            <?php else: ?>
                                <span style="font-size: 10px; color: #999;">Vacío</span>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button" id="waip_upload_logo_btn">Seleccionar Imagen</button>
                        <button type="button" class="button" id="waip_remove_logo_btn" style="<?php echo SettingsManager::getAssistantLogo() ? '' : 'display: none;'; ?>">Quitar</button>
                    </div>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Color Principal (Widget y Asistente)</th>
                <td>
                    <input type="color" name="<?php echo esc_attr(Constants::OPTION_PRIMARY_COLOR); ?>" value="<?php echo esc_attr(SettingsManager::getPrimaryColor()); ?>" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Color Secundario (Usuario y Acentos)</th>
                <td>
                    <input type="color" name="<?php echo esc_attr(Constants::OPTION_SECONDARY_COLOR); ?>" value="<?php echo esc_attr(SettingsManager::getSecondaryColor()); ?>" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Prompt de Sistema (Instrucciones base)</th>
                <td>
                    <textarea name="<?php echo esc_attr(Constants::OPTION_SYSTEM_PROMPT); ?>" rows="10" cols="50" class="large-text"><?php echo esc_textarea(SettingsManager::getSystemPrompt()); ?></textarea>
                    <p class="description">Estas son las reglas de negocio base que el modelo de IA debe seguir para su comportamiento.</p>
                </td>
            </tr>
            <tr>
                <th colspan="2"><h2>Funciones Premium</h2></th>
            </tr>
            <tr valign="top">
                <th scope="row">Modo Simulador</th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(Constants::OPTION_SIMULATOR_MODE); ?>" value="1" <?php checked(SettingsManager::isSimulatorEnabled(), true); ?> />
                        Activar respuestas falsas de prueba (No consume saldo de OpenAI)
                    </label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Mensaje de Mantenimiento</th>
                <td>
                    <textarea name="<?php echo esc_attr(Constants::OPTION_MAINTENANCE_MSG); ?>" rows="3" cols="50" class="large-text"><?php echo esc_textarea(SettingsManager::getMaintenanceMessage()); ?></textarea>
                    <p class="description">Este mensaje se mostrará automáticamente si la API de OpenAI falla (ej. por falta de saldo).</p>
                </td>
            </tr>
        </table>
        
        <?php submit_button('Guardar Cambios'); ?>
    </form>
    
    <hr style="margin: 30px 0;">
    <h2>Mantenimiento de Base de Datos</h2>
    <form method="post" action="">
        <p class="description">Puedes borrar todos los registros del sistema (logs) con más de 30 días de antigüedad para liberar espacio y mejorar el rendimiento.</p>
        <button type="submit" name="waip_purge_logs" value="1" class="button button-secondary" onclick="return confirm('¿Seguro que deseas purgar los logs antiguos? Esto no se puede deshacer.');">Purgar Logs Antiguos (>30 días)</button>
    </form>
</div>

<script>
jQuery(document).ready(function($){
    var mediaUploader;

    $('#waip_upload_logo_btn').click(function(e) {
        e.preventDefault();
        
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Seleccionar Logo del Asistente',
            button: {
                text: 'Usar esta imagen'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#waip_logo_url').val(attachment.url);
            $('#waip_logo_preview').html('<img src="' + attachment.url + '" style="width: 100%; height: 100%; object-fit: cover;" />');
            $('#waip_remove_logo_btn').show();
        });
        
        mediaUploader.open();
    });

    $('#waip_remove_logo_btn').click(function(e){
        e.preventDefault();
        $('#waip_logo_url').val('');
        $('#waip_logo_preview').html('<span style="font-size: 10px; color: #999;">Vacío</span>');
        $(this).hide();
    });
});
</script>
