document.addEventListener('DOMContentLoaded', () => {
    const chatButton = document.getElementById('waip-chat-button');
    const chatWindow = document.getElementById('waip-chat-window');
    const closeBtn = document.getElementById('waip-chat-close');
    const sendBtn = document.getElementById('waip-chat-send');
    const inputField = document.getElementById('waip-chat-input');
    const messagesContainer = document.getElementById('waip-chat-messages');
    
    const attachBtn = document.getElementById('waip-chat-attach');
    const fileInput = document.getElementById('waip-chat-file');
    const previewContainer = document.getElementById('waip-image-preview-container');
    const previewImg = document.getElementById('waip-image-preview');
    const removeImgBtn = document.getElementById('waip-remove-image');
    
    // Sonido de Notificación (Un simple 'pop' limpio en base64)
    const notificationSound = new Audio('data:audio/mp3;base64,//NExAAAAANIAAAAAExBTUUzLjEwMKqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq//NExAAAAANIAAAAAExBTUUzLjEwMKqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq'); // Respaldo silencioso (usar un pequeño pitido generado por AudioContext es mejor para evitar strings masivos)
    
    // Es mejor usar AudioContext para un pitido limpio sin strings gigantescos
    const playNotification = () => {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(800, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(1200, ctx.currentTime + 0.1);
            gain.gain.setValueAtTime(0, ctx.currentTime);
            gain.gain.linearRampToValueAtTime(0.5, ctx.currentTime + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.1);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.1);
        } catch (e) {
            console.log('Audio disabled');
        }
    };

    let currentImageBase64 = null;

    // Recuperar datos inyectados
    const apiUrl = waipData.apiUrl;
    const assistantName = waipData.assistantName;
    const assistantLogo = waipData.assistantLogo;
    const idleMessage = waipData.idleMessage;
    // Por defecto es 5 si de alguna manera falta, multiplicar por 60,000 para milisegundos
    const idleTimeMs = (parseInt(waipData.idleTime) || 5) * 60000;
    
    let idleTimer = null;
    let hasFiredIdle = false;

    const resetIdleTimer = () => {
        if (idleTimer) clearTimeout(idleTimer);
        
        if (!hasFiredIdle && !chatWindow.classList.contains('waip-hidden')) {
            idleTimer = setTimeout(async () => {
                hasFiredIdle = true;
                appendMessage(idleMessage, 'assistant');
                playNotification();
                
                // Guardar en BD para que aparezca en el historial del dashboard
                try {
                    await fetch(apiUrl + '/idle', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message: idleMessage, session_id: sessionId })
                    });
                } catch (e) {
                    console.error('Failed to save idle message', e);
                }
            }, idleTimeMs);
        }
    };
    
    // Generar ID de sesión para esta pestaña del navegador
    let sessionId = localStorage.getItem('waip_session_id');
    if (!sessionId) {
        sessionId = 'session_' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('waip_session_id', sessionId);
    }

    const getFormattedTime = (dateStr = null) => {
        const date = dateStr ? new Date(dateStr) : new Date();
        return date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0');
    };

    const showQuickReplies = () => {
        if (!waipData.quickReplies || waipData.quickReplies.length === 0) return;
        
        const existing = document.getElementById('waip-quick-replies');
        if (existing) existing.remove();

        const qrContainer = document.createElement('div');
        qrContainer.id = 'waip-quick-replies';
        qrContainer.className = 'waip-quick-replies-container';

        waipData.quickReplies.forEach(replyText => {
            const btn = document.createElement('button');
            btn.className = 'waip-quick-reply-btn';
            btn.textContent = replyText;
            btn.addEventListener('click', () => {
                inputField.value = replyText;
                sendMessage();
                qrContainer.remove();
            });
            qrContainer.appendChild(btn);
        });

        messagesContainer.appendChild(qrContainer);
        scrollToBottom();
    };

    const loadChatHistory = async () => {
        try {
            const response = await fetch(`${apiUrl}/history?session_id=${sessionId}`);
            const data = await response.json();
            
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    appendMessage(msg.content, msg.role, msg.created_at, msg.attachment_url);
                });
            } else {
                appendMessage(waipData.welcomeMessage, 'assistant');
                showQuickReplies();
            }
            resetIdleTimer();
        } catch (error) {
            console.error('Error loading history', error);
            appendMessage(waipData.welcomeMessage, 'assistant');
            showQuickReplies();
        }
    };

    let hasOpenedChat = false;
    const toggleChat = () => {
        const isHidden = chatWindow.classList.contains('waip-hidden');
        if (isHidden) {
            chatWindow.classList.remove('waip-hidden');
            inputField.focus();
            scrollToBottom();
            hasOpenedChat = true; // Marcar como abierto manualmente
            resetIdleTimer();
        } else {
            chatWindow.classList.add('waip-hidden');
            if (idleTimer) clearTimeout(idleTimer);
        }
    };

    chatButton.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', toggleChat);

    // Lógica de apertura automática (engagement)
    setTimeout(() => {
        if (!hasOpenedChat && chatWindow.classList.contains('waip-hidden')) {
            chatWindow.classList.remove('waip-hidden');
            playNotification();
            hasOpenedChat = true;
        }
    }, 5000); // 5 segundos

    const appendMessage = (content, sender, timestamp = null, attachmentBase64 = null) => {
        const wrapper = document.createElement('div');
        wrapper.className = `waip-message-wrapper waip-${sender}-wrapper`;
        
        let avatarSvg = '';
        let senderName = '';
        let avatarStyle = '';
        if (sender === 'assistant') {
            if (assistantLogo && assistantLogo.trim() !== '') {
                avatarSvg = `<img src="${assistantLogo}" alt="Logo">`;
                avatarStyle = 'style="background: transparent;"';
            } else {
                avatarSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path><line x1="8" y1="16" x2="8" y2="16"></line><line x1="16" y1="16" x2="16" y2="16"></line></svg>';
            }
            senderName = assistantName;
            
            // Reproducir notificación si el chat está abierto y es un mensaje nuevo (no del historial)
            if (!timestamp && !chatWindow.classList.contains('waip-hidden')) {
                playNotification();
            }
        } else {
            avatarSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
            senderName = 'Tú';
        }

        let formattedContent = content ? content
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank" class="waip-button-link">$1</a>')
            .replace(/\n/g, '<br>') : '';

        // Forzar la aparición del botón de WhatsApp si la IA lo menciona pero no lo renderiza
        if (sender === 'assistant' && waipData.whatsappNumber) {
            const hasWhatsAppWord = /whatsapp/i.test(content);
            const hasButton = /waip-button-link/.test(formattedContent);
            if (hasWhatsAppWord && !hasButton) {
                formattedContent += `<br><br><a href="https://wa.me/${waipData.whatsappNumber}" target="_blank" class="waip-button-link">Hablar por WhatsApp</a>`;
            }
        }

        let imageHtml = attachmentBase64 ? `<div class="waip-message-image"><img src="${attachmentBase64}" style="max-width: 100%; border-radius: 8px; margin-bottom: 5px;"></div>` : '';

        wrapper.innerHTML = `
            <div class="waip-avatar-row">
                <div class="waip-avatar waip-${sender}-avatar" ${avatarStyle}>
                    ${avatarSvg}
                </div>
                <span class="waip-sender-name">${senderName}</span>
            </div>
            <div class="waip-message waip-${sender}-message">
                ${imageHtml}
                ${formattedContent}
            </div>
            <div class="waip-timestamp">${getFormattedTime(timestamp)}</div>
        `;

        messagesContainer.appendChild(wrapper);
        scrollToBottom();
        resetIdleTimer();
    };

    const showTypingIndicator = () => {
        const wrapper = document.createElement('div');
        wrapper.id = 'waip-typing-wrapper';
        wrapper.className = 'waip-message-wrapper waip-assistant-wrapper';
        
        let typingAvatarSvg = '';
        let typingAvatarStyle = '';
        if (assistantLogo && assistantLogo.trim() !== '') {
            typingAvatarSvg = `<img src="${assistantLogo}" alt="Logo">`;
            typingAvatarStyle = 'style="background: transparent;"';
        } else {
            typingAvatarSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path><line x1="8" y1="16" x2="8" y2="16"></line><line x1="16" y1="16" x2="16" y2="16"></line></svg>';
        }

        wrapper.innerHTML = `
            <div class="waip-avatar-row">
                <div class="waip-avatar waip-assistant-avatar" ${typingAvatarStyle}>
                    ${typingAvatarSvg}
                </div>
                <span class="waip-sender-name">${assistantName}</span>
            </div>
            <div class="waip-typing-indicator">
                <span></span><span></span><span></span>
            </div>
        `;
        messagesContainer.appendChild(wrapper);
        scrollToBottom();
    };

    const removeTypingIndicator = () => {
        const indicator = document.getElementById('waip-typing-wrapper');
        if (indicator) indicator.remove();
    };

    const scrollToBottom = () => {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    };

    // --- Lógica de Adjuntos ---
    if (attachBtn && fileInput) {
        attachBtn.addEventListener('click', () => {
            fileInput.click();
        });

        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('La imagen es muy grande. Máximo 2MB.');
                    fileInput.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = (event) => {
                    currentImageBase64 = event.target.result;
                    previewImg.src = currentImageBase64;
                    previewContainer.classList.remove('waip-hidden');
                };
                reader.readAsDataURL(file);
            }
        });

        removeImgBtn.addEventListener('click', () => {
            currentImageBase64 = null;
            fileInput.value = '';
            previewContainer.classList.add('waip-hidden');
            previewImg.src = '';
        });
    }

    // --- Lógica de Arrastrar y Soltar (Drag and Drop) ---
    const chatWindowElement = document.getElementById('waip-chat-window');
    
    if (chatWindowElement && fileInput) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            chatWindowElement.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        let dragCounter = 0;

        chatWindowElement.addEventListener('dragenter', (e) => {
            preventDefaults(e);
            dragCounter++;
            if (dragCounter === 1) {
                chatWindowElement.classList.add('waip-drag-active');
            }
        }, false);

        chatWindowElement.addEventListener('dragover', preventDefaults, false);

        chatWindowElement.addEventListener('dragleave', (e) => {
            preventDefaults(e);
            dragCounter--;
            if (dragCounter === 0) {
                chatWindowElement.classList.remove('waip-drag-active');
            }
        }, false);

        chatWindowElement.addEventListener('drop', (e) => {
            preventDefaults(e);
            dragCounter = 0;
            chatWindowElement.classList.remove('waip-drag-active');
            
            let dt = e.dataTransfer;
            let files = dt.files;

            if (files && files.length > 0) {
                const dtClone = new DataTransfer();
                dtClone.items.add(files[0]);
                fileInput.files = dtClone.files;
                
                const event = new Event('change');
                fileInput.dispatchEvent(event);
            }
        }, false);
    }

    const sendMessage = async () => {
        const message = inputField.value.trim();
        if (message === '' && !currentImageBase64) return;

        inputField.value = '';
        appendMessage(message, 'user', null, currentImageBase64);
        
        const imageToSend = currentImageBase64;
        if (removeImgBtn) removeImgBtn.click();
        
        showTypingIndicator();

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    attachment: imageToSend,
                    session_id: sessionId
                })
            });

            const data = await response.json();
            removeTypingIndicator();

            if (response.ok) {
                appendMessage(data.response, 'assistant');
            } else {
                appendMessage('Lo siento, ocurrió un error: ' + (data.error || 'Error desconocido'), 'assistant');
            }
        } catch (error) {
            removeTypingIndicator();
            appendMessage('Error de red al conectar con el servidor.', 'assistant');
        }
    };

    sendBtn.addEventListener('click', sendMessage);
    inputField.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    // Cargar historial al iniciar el script
    loadChatHistory();
});
