<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Entorno de Pruebas (Playground)</h1>
    
    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px; max-width: 800px; margin-top: 20px;">
        <h2>¡Bienvenido a la zona de pruebas seguras! 🧪</h2>
        <p style="font-size: 15px;">
            En esta página puedes probar e interactuar con el asistente de IA <strong>exactamente como lo verían tus clientes</strong>. 
            El widget de chat ya está flotando en la esquina inferior derecha de esta misma pantalla.
        </p>
        
        <ul style="list-style: square; padding-left: 20px;">
            <li><strong>Prueba de Colores:</strong> Si cambiaste los colores en "Ajustes", ábrelo para ver cómo lucen.</li>
            <li><strong>Prueba de Respuestas:</strong> Pregúntale cosas basadas en los textos o enlaces que le enseñaste en "Base de Conocimiento".</li>
            <li><strong>Sin riesgo:</strong> Mientras el interruptor <em>"Mostrar Chat en el Sitio Público"</em> esté apagado en los ajustes, nadie fuera de esta página podrá verlo.</li>
        </ul>

        <?php if (!\Waip\Config\SettingsManager::isPubliclyVisible()): ?>
            <div class="notice notice-warning inline" style="margin: 20px 0 0 0;">
                <p><strong>Aviso:</strong> El chat está oculto actualmente en tu página web principal. Usa esta página para ajustar todo, y cuando estés listo, ve a <strong>Ajustes</strong> y marca la casilla para activarlo al público.</p>
            </div>
        <?php else: ?>
            <div class="notice notice-success inline" style="margin: 20px 0 0 0;">
                <p><strong>El chat está activo en Producción.</strong> Actualmente todos tus visitantes pueden ver y usar el bot en tu página principal.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Renderizamos el widget real aquí mismo en el área de administración -->
    <?php $chatWidget->render_widget(); ?>
</div>
