<?php
if (!defined('ABSPATH')) {
    exit;
}

// Manejar el envío del formulario
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['waip_knowledge_action'])) {
        
        // Manejar Agregar
        if ($_POST['waip_knowledge_action'] === 'add_text' && check_admin_referer('waip_add_knowledge')) {
            $title = sanitize_text_field($_POST['doc_title']);
            $text = wp_unslash($_POST['doc_text']); // preservar formato antes de que el chunking lo elimine
            
            if (empty($title) || empty($text)) {
                $message = "El título y el texto son obligatorios.";
                $message_type = "error";
            } else {
                $result = \Waip\Knowledge\DocumentManager::ingestText($title, $text);
                if ($result === true) {
                    $message = "El texto fue procesado y aprendido exitosamente por la IA.";
                    $message_type = "updated";
                } else {
                    $message = $result; // Mensaje de error
                    $message_type = "error";
                }
            }
        }

        // Manejar URL
        if ($_POST['waip_knowledge_action'] === 'add_url' && check_admin_referer('waip_add_knowledge')) {
            $url = sanitize_url($_POST['doc_url']);
            
            if (empty($url)) {
                $message = "Debes ingresar una URL válida.";
                $message_type = "error";
            } else {
                $result = \Waip\Knowledge\DocumentManager::ingestUrl($url);
                if ($result === true) {
                    $message = "La página web fue escaneada y memorizada exitosamente por la IA.";
                    $message_type = "updated";
                } else {
                    $message = $result; // Mensaje de error
                    $message_type = "error";
                }
            }
        }

        // Manejar Eliminar
        if ($_POST['waip_knowledge_action'] === 'delete_doc' && check_admin_referer('waip_delete_doc')) {
            $doc_id = intval($_POST['doc_id']);
            \Waip\Knowledge\DocumentManager::deleteDocument($doc_id);
            $message = "Documento eliminado. La IA ya no usará esta información.";
            $message_type = "updated";
        }

        // Manejar Eliminación Múltiple/Individual vía Formulario
        if ($_POST['waip_knowledge_action'] === 'bulk_delete_docs' && check_admin_referer('waip_bulk_delete_docs')) {
            if (isset($_POST['single_delete_id'])) {
                // Eliminación individual clicada
                $doc_id = intval($_POST['single_delete_id']);
                \Waip\Knowledge\DocumentManager::deleteDocument($doc_id);
                $message = "Documento eliminado. La IA ya no usará esta información.";
                $message_type = "updated";
            } else {
                // Eliminación múltiple clicada
                $doc_ids = isset($_POST['doc_ids']) ? array_map('intval', (array)$_POST['doc_ids']) : [];
                if (!empty($doc_ids)) {
                    $deleted_count = 0;
                    foreach ($doc_ids as $doc_id) {
                        \Waip\Knowledge\DocumentManager::deleteDocument($doc_id);
                        $deleted_count++;
                    }
                    $message = "$deleted_count documentos eliminados.";
                    $message_type = "updated";
                } else {
                    $message = "No seleccionaste ningún documento para borrar.";
                    $message_type = "error";
                }
            }
        }

        // Manejar Actualización
        if ($_POST['waip_knowledge_action'] === 'update_doc' && check_admin_referer('waip_update_doc')) {
            $doc_id = intval($_POST['doc_id']);
            $title = sanitize_text_field($_POST['doc_title']);
            $text = wp_unslash($_POST['doc_text']);
            
            if (empty($title) || empty($text)) {
                $message = "El título y el texto son obligatorios.";
                $message_type = "error";
            } else {
                $result = \Waip\Knowledge\DocumentManager::updateDocument($doc_id, $title, $text);
                if ($result === true) {
                    $message = "El documento fue actualizado y memorizado exitosamente.";
                    $message_type = "updated";
                } else {
                    $message = $result; // Mensaje de error
                    $message_type = "error";
                }
            }
        }
    }
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$edit_doc_id = isset($_GET['doc']) ? intval($_GET['doc']) : 0;
$doc_to_edit = null;

if ($action === 'edit' && $edit_doc_id > 0) {
    $doc_to_edit = \Waip\Knowledge\DocumentManager::getDocument($edit_doc_id);
}

$documents = \Waip\Knowledge\DocumentManager::getAllDocuments();
?>
<div class="wrap">
    <h1>Base de Conocimiento (RAG)</h1>
    <p>Alimenta al asistente virtual con las políticas, manuales e información de tu empresa. Al pegar un texto aquí, la IA lo convertirá en vectores y lo recordará para siempre.</p>

    <?php if ($message): ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><strong><?php echo esc_html($message); ?></strong></p>
        </div>
    <?php endif; ?>

    <?php if ($doc_to_edit): ?>
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px; max-width: 800px; margin-bottom: 20px;">
            <h2>Editar Documento: <?php echo esc_html($doc_to_edit['title']); ?></h2>
            <form method="post" action="?page=waip-knowledge">
                <?php wp_nonce_field('waip_update_doc'); ?>
                <input type="hidden" name="waip_knowledge_action" value="update_doc">
                <input type="hidden" name="doc_id" value="<?php echo esc_attr($doc_to_edit['id']); ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="doc_title">Título del Documento</label></th>
                        <td>
                            <input name="doc_title" type="text" id="doc_title" class="regular-text" value="<?php echo esc_attr($doc_to_edit['title']); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="doc_text">Contenido (Texto Puro)</label></th>
                        <td>
                            <textarea name="doc_text" id="doc_text" rows="20" class="large-text" required><?php echo esc_textarea($doc_to_edit['raw_text']); ?></textarea>
                            <p class="description">Puedes modificar este texto. Al guardar, la IA olvidará la versión anterior y memorizará esta.</p>
                            <?php if (empty($doc_to_edit['raw_text'])): ?>
                                <p style="color: red;"><strong>Atención:</strong> Este documento fue creado con una versión anterior del sistema y no conservó su texto original. Deberás volver a escribirlo.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <p class="submit" style="display: flex; gap: 10px;">
                    <button type="submit" class="button button-primary">Guardar Cambios</button>
                    <a href="?page=waip-knowledge" class="button button-secondary">Cancelar</a>
                </p>
            </form>
        </div>
    <?php endif; ?>

    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <!-- Formulario -->
        <div style="flex: 1; min-width: 300px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px;">
            <h2>Añadir Nuevo Texto</h2>
            <form method="post" action="">
                <?php wp_nonce_field('waip_add_knowledge'); ?>
                <input type="hidden" name="waip_knowledge_action" value="add_text">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="doc_title">Título del Documento</label></th>
                        <td>
                            <input name="doc_title" type="text" id="doc_title" class="regular-text" placeholder="Ej: Políticas de Devolución" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="doc_text">Contenido (Texto Puro)</label></th>
                        <td>
                            <textarea name="doc_text" id="doc_text" rows="15" class="large-text" placeholder="Pega aquí todo el texto de tu manual o documento..." required></textarea>
                            <p class="description">Puedes pegar cientos de páginas de texto aquí. El sistema lo dividirá automáticamente.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary" onclick="this.innerHTML='Procesando... No cierres esta ventana'; this.style.opacity='0.7';">Aprender Texto</button>
                </p>
            </form>
        </div>

        <!-- Formulario URL -->
        <div style="flex: 1; min-width: 300px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px;">
            <h2>Escanear Enlaces (Crawler)</h2>
            <form method="post" action="" id="waip-crawler-form">
                <?php wp_nonce_field('waip_add_knowledge', 'waip_knowledge_nonce'); ?>
                <input type="hidden" name="waip_knowledge_action" value="add_url">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="doc_url">URL Base</label></th>
                        <td>
                            <input name="doc_url" type="url" id="doc_url" class="regular-text" placeholder="Ej: https://coodelsur.com" required style="width: 100%;">
                            <p class="description">Puedes escanear una sola página, o pedirle al robot que busque todas las páginas de tu sitio automáticamente.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit" style="display: flex; gap: 10px;">
                    <button type="submit" class="button button-secondary" id="btn-scan-single">Escanear Solo Esta URL</button>
                    <button type="button" class="button button-primary" id="btn-scan-full">Escanear Sitio Completo</button>
                </p>
            </form>

            <div id="waip-crawler-progress" style="display: none; margin-top: 15px; padding: 15px; background: #f0f0f1; border-radius: 6px;">
                <h4 style="margin-top: 0;" id="waip-crawler-status">Iniciando escáner...</h4>
                <div style="width: 100%; background: #ddd; border-radius: 4px; overflow: hidden; height: 10px; margin-bottom: 10px;">
                    <div id="waip-crawler-bar" style="width: 0%; height: 100%; background: #0073aa; transition: width 0.3s;"></div>
                </div>
                <ul id="waip-crawler-log" style="font-size: 11px; color: #646970; max-height: 100px; overflow-y: auto; margin: 0; padding-left: 15px; list-style-type: square;"></ul>
            </div>
        </div>

        <!-- Tabla -->
        <div style="flex: 1; min-width: 400px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px;">
            <h2>Documentos Indexados</h2>
            <?php if (empty($documents)): ?>
                <p>El asistente no tiene documentos en su memoria aún. No podrá usar el sistema RAG hasta que le enseñes algo.</p>
            <?php else: ?>
                <form method="post" action="" id="waip-docs-form">
                    <?php wp_nonce_field('waip_bulk_delete_docs'); ?>
                    <input type="hidden" name="waip_knowledge_action" value="bulk_delete_docs">
                    
                    <div class="tablenav top" style="margin-bottom: 10px;">
                        <div class="alignleft actions bulkactions">
                            <button type="submit" class="button action" onclick="return confirm('¿Borrar documentos seleccionados?');" style="color: #a00;">Borrar Seleccionados</button>
                        </div>
                    </div>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <td id="cb" class="manage-column column-cb check-column" style="width: 30px;">
                                    <input type="checkbox" id="cb-select-all-1">
                                </td>
                                <th>Título</th>
                                <th style="width: 15%;">Vectores</th>
                                <th style="width: 20%;">Indexado</th>
                                <th style="width: 25%;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" name="doc_ids[]" value="<?php echo esc_attr($doc['id']); ?>">
                                    </th>
                                    <td><strong><?php echo esc_html($doc['title']); ?></strong></td>
                                    <td><?php echo esc_html($doc['chunk_count']); ?> fragmentos</td>
                                    <td><?php echo date('d M Y, H:i', strtotime($doc['last_indexed'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <?php if ($doc['type'] === 'url'): ?>
                                                <span style="font-size: 11px; color: #666; font-style: italic; max-width: 80px; line-height: 1.2;">Para actualizar, borra y re-escanea.</span>
                                            <?php else: ?>
                                                <a href="?page=waip-knowledge&action=edit&doc=<?php echo esc_attr($doc['id']); ?>" class="button">Editar</a>
                                            <?php endif; ?>
                                            <button type="submit" name="single_delete_id" value="<?php echo esc_attr($doc['id']); ?>" class="button button-link-delete" style="color: #a00;" onclick="return confirm('¿Estás seguro de que quieres borrar este documento?');">Borrar</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let urlsToProcess = [];
    let currentIndex = 0;

    // Lógica para seleccionar todos los checkboxes
    $('#cb-select-all-1').on('click', function() {
        $('input[name="doc_ids[]"]').prop('checked', this.checked);
    });

    $('#btn-scan-single').on('click', function() {
        if ($('#doc_url').val()) {
            $(this).text('Escaneando...').css('opacity', '0.7');
        }
    });

    $('#btn-scan-full').on('click', function() {
        const baseUrl = $('#doc_url').val();
        if (!baseUrl) {
            alert("Ingresa una URL base primero.");
            return;
        }

        if (!confirm("¿Iniciar escaneo de todo el sitio web? Esto podría tomar unos minutos.")) {
            return;
        }

        // Bloquear UI
        $('#btn-scan-single, #btn-scan-full').prop('disabled', true);
        $('#waip-crawler-progress').show();
        $('#waip-crawler-log').empty();
        $('#waip-crawler-status').text('Buscando enlaces en el sitio...');
        $('#waip-crawler-bar').css('width', '5%');

        const nonce = $('#waip_knowledge_nonce').val();

        // 1. Extraer Enlaces
        $.post(ajaxurl, {
            action: 'waip_extract_links',
            url: baseUrl,
            security: nonce
        }, function(response) {
            if (response.success && response.data.links && response.data.links.length > 0) {
                urlsToProcess = response.data.links;
                currentIndex = 0;
                $('#waip-crawler-status').text(`Encontrados ${urlsToProcess.length} enlaces. Procesando...`);
                processNextUrl(nonce);
            } else {
                $('#waip-crawler-status').text('Error: ' + (response.data || 'No se encontraron enlaces.'));
                $('#btn-scan-single, #btn-scan-full').prop('disabled', false);
            }
        }).fail(function() {
            $('#waip-crawler-status').text('Error de conexión al buscar enlaces.');
            $('#btn-scan-single, #btn-scan-full').prop('disabled', false);
        });
    });

    function processNextUrl(nonce) {
        if (currentIndex >= urlsToProcess.length) {
            $('#waip-crawler-status').text('¡Escaneo Completo! La página se recargará.');
            $('#waip-crawler-bar').css('width', '100%');
            setTimeout(() => location.reload(), 2000);
            return;
        }

        const currentUrl = urlsToProcess[currentIndex];
        const progressPct = Math.round((currentIndex / urlsToProcess.length) * 100);
        $('#waip-crawler-bar').css('width', Math.max(5, progressPct) + '%');
        $('#waip-crawler-status').text(`Procesando ${currentIndex + 1} de ${urlsToProcess.length}...`);
        
        const $logItem = $('<li>').text(`Indexando: ${currentUrl} ...`);
        $('#waip-crawler-log').append($logItem);
        $('#waip-crawler-log').scrollTop($('#waip-crawler-log')[0].scrollHeight);

        $.post(ajaxurl, {
            action: 'waip_ingest_url',
            url: currentUrl,
            security: nonce
        }, function(response) {
            if (response.success) {
                $logItem.append(' <span style="color: green;">OK</span>');
            } else {
                $logItem.append(' <span style="color: red;">Error: ' + response.data + '</span>');
            }
            currentIndex++;
            processNextUrl(nonce);
        }).fail(function() {
            $logItem.append(' <span style="color: red;">Error de red</span>');
            currentIndex++;
            processNextUrl(nonce);
        });
    }
});
</script>
