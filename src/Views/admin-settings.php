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
                        <option value="gpt-3.5-turbo" <?php selected(SettingsManager::getModel(), 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo (Rápido, económico)</option>
                        <option value="gpt-4o" <?php selected(SettingsManager::getModel(), 'gpt-4o'); ?>>GPT-4 Omni (Más inteligente)</option>
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
                <th scope="row">Color Principal (Widget)</th>
                <td>
                    <input type="color" name="<?php echo esc_attr(Constants::OPTION_PRIMARY_COLOR); ?>" value="<?php echo esc_attr(SettingsManager::getPrimaryColor()); ?>" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Prompt de Sistema (Instrucciones base)</th>
                <td>
                    <textarea name="<?php echo esc_attr(Constants::OPTION_SYSTEM_PROMPT); ?>" rows="10" cols="50" class="large-text"><?php echo esc_textarea(SettingsManager::getSystemPrompt()); ?></textarea>
                    <p class="description">Estas son las reglas de negocio base que el modelo debe seguir. En el Sprint 2 se le sumará dinámicamente el contexto RAG.</p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
