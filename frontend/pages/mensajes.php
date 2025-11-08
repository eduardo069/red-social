<?php
/**
 * mensajes.php - Página de mensajería
 */

session_start();

require_once __DIR__ . '/../../backend/controllers/AuthController.php';
require_once __DIR__ . '/../../backend/controllers/FriendController.php';

$authController = new AuthController();
$friendController = new FriendController();

// Verificar autenticación
$sessionCheck = $authController->checkSession();
if (!$sessionCheck['authenticated']) {
    header("Location: index.php");
    exit();
}

$currentUserId = $sessionCheck['user']['user_id'];

// Obtener lista de amigos para conversaciones
$friendsResult = $friendController->getFriends($currentUserId);
$friends = $friendsResult['success'] ? $friendsResult['data'] : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes - SoundConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .messages-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            height: calc(100vh - 200px);
            max-height: 700px;
        }
        
        @media (max-width: 768px) {
            .messages-container {
                grid-template-columns: 1fr;
            }
        }
        
        .conversations-list {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 20px;
            overflow-y: auto;
        }
        
        .conversations-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .conversations-header h2 {
            margin: 0;
            font-size: 1.3rem;
        }
        
        .conversation-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 8px;
        }
        
        .conversation-item:hover {
            background: rgba(255,255,255,0.05);
        }
        
        .conversation-item.active {
            background: rgba(255,138,0,0.2);
            border-left: 3px solid #ff8a00;
        }
        
        .conversation-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            position: relative;
            flex-shrink: 0;
        }
        
        .status-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid #1a1a2e;
        }
        
        .status-dot.online {
            background: #4CAF50;
        }
        
        .status-dot.offline {
            background: #999;
        }
        
        .conversation-info {
            flex: 1;
            min-width: 0;
        }
        
        .conversation-name {
            font-weight: 600;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-preview {
            font-size: 0.85rem;
            color: #999;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .chat-container {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .chat-header-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        
        .chat-header-info h3 {
            margin: 0 0 4px;
            font-size: 1.1rem;
        }
        
        .chat-header-status {
            font-size: 0.85rem;
            color: #999;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .message {
            display: flex;
            gap: 10px;
            max-width: 70%;
        }
        
        .message.own {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: bold;
            color: white;
            flex-shrink: 0;
        }
        
        .message-content {
            background: rgba(255,255,255,0.08);
            padding: 12px 15px;
            border-radius: 15px;
            word-wrap: break-word;
        }
        
        .message.own .message-content {
            background: #ff8a00;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #999;
            margin-top: 4px;
        }
        
        .chat-input-container {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 25px;
            padding: 12px 20px;
            color: white;
            resize: none;
            max-height: 100px;
        }
        
        .chat-input:focus {
            outline: none;
            border-color: #ff8a00;
        }
        
        .send-btn {
            background: #ff8a00;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            flex-shrink: 0;
        }
        
        .send-btn:hover {
            background: #e67700;
            transform: scale(1.05);
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
            text-align: center;
            padding: 40px;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <div class="main-content">
        <div class="messages-container">
            <!-- Lista de conversaciones -->
            <div class="conversations-list">
                <div class="conversations-header">
                    <h2><i class="fas fa-envelope"></i> Mensajes</h2>
                </div>
                
                <?php if (!empty($friends)): ?>
                    <?php foreach ($friends as $friend): ?>
                        <div class="conversation-item" onclick="openChat(<?php echo $friend['user_id']; ?>, '<?php echo htmlspecialchars($friend['nombre']); ?>', '<?php echo htmlspecialchars($friend['usuario']); ?>', '<?php echo $friend['estado']; ?>')">
                            <div class="conversation-avatar">
                                <?php if (!empty($friend['foto_perfil'])): ?>
                                    <img src="../../backend/<?php echo htmlspecialchars($friend['foto_perfil']); ?>" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <?php echo substr($friend['nombre'], 0, 1); ?>
                                <?php endif; ?>
                                <span class="status-dot <?php echo $friend['estado']; ?>"></span>
                            </div>
                            <div class="conversation-info">
                                <div class="conversation-name"><?php echo htmlspecialchars($friend['nombre']); ?></div>
                                <div class="conversation-preview">
                                    <?php echo $friend['estado'] === 'online' ? 'En línea' : 'Desconectado'; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; color: #999;">
                        <i class="fas fa-user-friends" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                        <p>No tienes amigos aún</p>
                        <button onclick="window.location.href='explorar.php'" style="margin-top: 15px; padding: 10px 20px; background: #ff8a00; border: none; border-radius: 20px; color: white; cursor: pointer;">
                            Explorar usuarios
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Área de chat -->
            <div class="chat-container" id="chat-container">
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>Selecciona una conversación</h3>
                    <p>Elige un amigo de la lista para comenzar a chatear</p>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <p>SoundConnect &copy; 2025 - Conectando a través de la música</p>
    </footer>
    
    <script src="../assets/js/main.js"></script>
    <script>
        const { Utils } = window.SoundConnect;
        let currentChatUser = null;
        
        function openChat(userId, nombre, usuario, estado) {
            currentChatUser = { userId, nombre, usuario, estado };
            
            // Marcar conversación como activa
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Mostrar chat
            const chatContainer = document.getElementById('chat-container');
            chatContainer.innerHTML = `
                <div class="chat-header">
                    <div class="chat-header-avatar">${nombre[0]}</div>
                    <div class="chat-header-info">
                        <h3>${Utils.escapeHtml(nombre)}</h3>
                        <div class="chat-header-status">
                            <i class="fas fa-circle" style="color: ${estado === 'online' ? '#4CAF50' : '#999'}; font-size: 0.6rem;"></i>
                            ${estado === 'online' ? 'En línea' : 'Desconectado'}
                        </div>
                    </div>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <div class="empty-state">
                        <i class="fas fa-comment-dots"></i>
                        <p>Esta funcionalidad estará disponible próximamente</p>
                        <p style="font-size: 0.9rem; margin-top: 10px;">Por ahora puedes interactuar mediante publicaciones y comentarios</p>
                    </div>
                </div>
                <div class="chat-input-container">
                    <textarea class="chat-input" placeholder="Escribe un mensaje..." rows="1" id="message-input" disabled></textarea>
                    <button class="send-btn" onclick="sendMessage()" disabled>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            `;
            
            // Auto-resize textarea
            const textarea = document.getElementById('message-input');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
                
                // Enter para enviar
                textarea.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }
        }
        
        function sendMessage() {
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            
            if (!message || !currentChatUser) return;
            
            Utils.showNotification('La mensajería estará disponible próximamente', 'info');
            
            // Aquí irá la lógica de envío de mensajes
            // Por ahora solo limpiamos el input
            input.value = '';
            input.style.height = 'auto';
        }
        
        // Cargar conversación si viene de un enlace directo
        const urlParams = new URLSearchParams(window.location.search);
        const userId = urlParams.get('user_id');
        if (userId) {
            // Buscar el amigo en la lista y abrir chat
            const friendItem = document.querySelector(`[onclick*="openChat(${userId}"]`);
            if (friendItem) {
                friendItem.click();
            }
        }
    </script>
</body>
</html>