<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../connexion.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';
SessionGuard::requireClient();

$idSinistre = (int)($_GET['id_sinistre'] ?? 0);
if (!$idSinistre) die('ID Sinistre manquant.');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Messages</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; margin: 0; height: 100vh; display: flex; flex-direction: column; }
        .chat-container { flex: 1; overflow-y: auto; padding: 20px; }
        .message { margin-bottom: 15px; max-width: 80%; display: flex; flex-direction: column; }
        .message.client { align-self: flex-end; align-items: flex-end; }
        .message.agent { align-self: flex-start; align-items: flex-start; }
        .bubble { padding: 10px 15px; border-radius: 15px; font-size: 14px; position: relative; }
        .message.client .bubble { background: #FF6B1A; color: white; border-bottom-right-radius: 2px; }
        .message.agent .bubble { background: #1A3A7A; color: white; border-bottom-left-radius: 2px; }
        .meta { font-size: 11px; color: #6c757d; margin-top: 4px; }
        .input-area { padding: 15px; background: white; border-top: 1px solid #dee2e6; display: flex; gap: 10px; }
        #msgInput { flex: 1; resize: none; border-radius: 20px; padding: 10px 15px; }
    </style>
</head>
<body>

<div class="chat-container" id="chatContainer">
    <!-- Messages loaded by JS -->
</div>

<div class="input-area">
    <textarea id="msgInput" class="form-control" rows="1" placeholder="Tapez votre message..."></textarea>
    <button class="btn" style="background:#FF6B1A; color:white; width:45px; height:45px; border-radius:50%;" onclick="sendMessage()">
        <i class="bi bi-send-fill"></i>
    </button>
</div>

<script>
const idSinistre = <?= $idSinistre ?>;
const chatContainer = document.getElementById('chatContainer');
const msgInput = document.getElementById('msgInput');

async function loadMessages() {
    try {
        const res = await fetch(`../../api.php?action=sinistre_get_messages&id_sinistre=${idSinistre}`);
        const json = await res.json();
        if (json.success) {
            chatContainer.innerHTML = json.data.map(m => {
                const isClient = m.role_sender === 'client';
                const cls = isClient ? 'client' : 'agent';
                const name = isClient ? 'Vous' : 'Assurance';
                return `
                <div class="message ${cls}" style="margin-left: ${isClient ? 'auto' : '0'}; margin-right: ${isClient ? '0' : 'auto'};">
                    <div class="bubble">${m.message}</div>
                    <div class="meta">${name} - ${m.created_at}</div>
                </div>`;
            }).join('');
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    } catch(e) { console.error('Error loading messages'); }
}

async function sendMessage() {
    const text = msgInput.value.trim();
    if (!text) return;
    
    msgInput.value = '';
    const fd = new FormData();
    fd.append('id_sinistre', idSinistre);
    fd.append('message', text);

    try {
        await fetch('../../api.php?action=sinistre_send_message', {
            method: 'POST',
            body: fd
        });
        loadMessages();
    } catch(e) { alert('Erreur'); }
}

// Polling every 5 seconds
loadMessages();
setInterval(loadMessages, 5000);

msgInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});
</script>
</body>
</html>
