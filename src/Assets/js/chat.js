document.addEventListener('DOMContentLoaded', () => {
    const chatButton = document.getElementById('waip-chat-button');
    const chatWindow = document.getElementById('waip-chat-window');
    const closeBtn = document.getElementById('waip-chat-close');
    const sendBtn = document.getElementById('waip-chat-send');
    const inputField = document.getElementById('waip-chat-input');
    const messagesContainer = document.getElementById('waip-chat-messages');

    // Retrieve injected data
    const apiUrl = waipData.apiUrl;
    
    // Generate session ID for this browser tab
    let sessionId = localStorage.getItem('waip_session_id');
    if (!sessionId) {
        sessionId = 'session_' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('waip_session_id', sessionId);
    }

    const toggleChat = () => {
        chatWindow.classList.toggle('waip-hidden');
        if (!chatWindow.classList.contains('waip-hidden')) {
            inputField.focus();
        }
    };

    chatButton.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', toggleChat);

    const appendMessage = (content, sender) => {
        const msgDiv = document.createElement('div');
        msgDiv.className = `waip-message waip-${sender}-message`;
        
        // Basic markdown-like parsing (bold and newlines)
        let formattedContent = content
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
            
        msgDiv.innerHTML = formattedContent;
        messagesContainer.appendChild(msgDiv);
        scrollToBottom();
    };

    const showTypingIndicator = () => {
        const indicator = document.createElement('div');
        indicator.id = 'waip-typing';
        indicator.className = 'waip-typing-indicator';
        indicator.innerHTML = '<span></span><span></span><span></span>';
        messagesContainer.appendChild(indicator);
        scrollToBottom();
    };

    const removeTypingIndicator = () => {
        const indicator = document.getElementById('waip-typing');
        if (indicator) indicator.remove();
    };

    const scrollToBottom = () => {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    };

    const sendMessage = async () => {
        const message = inputField.value.trim();
        if (!message) return;

        inputField.value = '';
        appendMessage(message, 'user');
        showTypingIndicator();

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    session_id: sessionId
                })
            });

            const data = await response.json();
            removeTypingIndicator();

            if (response.ok) {
                appendMessage(data.response, 'assistant');
            } else {
                appendMessage('Lo siento, ocurrió un error: ' + (data.error || 'Error desconocido'), 'system');
            }
        } catch (error) {
            removeTypingIndicator();
            appendMessage('Error de red al conectar con el servidor.', 'system');
        }
    };

    sendBtn.addEventListener('click', sendMessage);
    inputField.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
});
