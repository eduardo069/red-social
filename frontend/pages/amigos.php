<?php
/**
 * amigos.php - Página de gestión de amigos y solicitudes
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Amigos - SoundConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .friends-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .friends-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .friends-header h1 {
            margin: 0 0 10px;
            font-size: 2.5rem;
        }
        
        .friends-tabs {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }
        
        .friends-tab {
            padding: 15px 30px;
            cursor: pointer;
            color: #999;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .friends-tab.active {
            color: #ff8a00;
            border-bottom-color: #ff8a00;
        }
        
        .friends-tab:hover {
            color: #ff8a00;
        }
        
        .friends-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .friend-card {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .friend-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.08);
        }
        
        .friend-card h3 {
            margin: 0 0 5px;
            font-size: 1.1rem;
            cursor: pointer;
        }
        
        .friend-card h3:hover {
            color: #ff8a00;
        }
        
        .friend-card .username {
            color: #999;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .friend-card-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .friend-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.85rem;
        }
        
        .btn-accept {
            background: #2ecc71;
            color: white;
        }
        
        .btn-reject {
            background: #e74c3c;
            color: white;
        }
        
        .btn-view {
            background: #3498db;
            color: white;
        }
        
        .btn-message {
            background: #9b59b6;
            color: white;
        }
        
        .friend-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .status-online {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 15px;
            height: 15px;
            background: #2ecc71;
            border: 2px solid white;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <div class="main-content">
        <div class="friends-container">
            <div class="friends-header">
                <h1><i class="fas fa-user-friends"></i> Mis Amigos</h1>
                <p>Gestiona tus amistades y solicitudes pendientes</p>
            </div>
            
            <div class="friends-tabs">
                <div class="friends-tab active" data-tab="friends" onclick="window.switchTab('friends')">
                    <i class="fas fa-users"></i> Mis Amigos (<span id="friends-count">0</span>)
                </div>
                <div class="friends-tab" data-tab="requests" onclick="window.switchTab('requests')">
                    <i class="fas fa-user-clock"></i> Solicitudes Recibidas (<span id="requests-count">0</span>)
                </div>
                <div class="friends-tab" data-tab="sent" onclick="window.switchTab('sent')">
                    <i class="fas fa-paper-plane"></i> Solicitudes Enviadas (<span id="sent-count">0</span>)
                </div>
            </div>
            
            <!-- Tab Mis Amigos -->
            <div class="tab-content active" id="friends-tab">
                <div class="friends-grid" id="friends-list">
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                        <p>Cargando amigos...</p>
                    </div>
                </div>
            </div>
            
            <!-- Tab Solicitudes Recibidas -->
            <div class="tab-content" id="requests-tab" style="display: none;">
                <div class="friends-grid" id="requests-list">
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                        <p>Cargando solicitudes...</p>
                    </div>
                </div>
            </div>
            
            <!-- Tab Solicitudes Enviadas -->
            <div class="tab-content" id="sent-tab" style="display: none;">
                <div class="friends-grid" id="sent-list">
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                        <p>Cargando...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <p>SoundConnect &copy; 2025 - Conectando a través de la música</p>
    </footer>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/friends.js"></script>
    <script>
        (function() {
            console.log('🔍 DEBUG: Iniciando amigos.php...');
            
            // Usar configuración global
            const API_BASE_URL = window.SoundConnect?.API_BASE_URL || 'http://localhost/red-social/backend/api';
            const Utils = window.SoundConnect?.Utils || {
                fetchAPI: async function(url, options = {}) {
                    const response = await fetch(url, {
                        ...options,
                        headers: {
                            'Content-Type': 'application/json',
                            ...options.headers
                        }
                    });
                    return await response.json();
                },
                escapeHtml: function(text) {
                    const map = {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    };
                    return text.replace(/[&<>"']/g, m => map[m]);
                }
            };
            
            console.log('📡 API_BASE_URL:', API_BASE_URL);
            
            // Funciones globales
            window.switchTab = function(tab) {
                document.querySelectorAll('.friends-tab').forEach(t => t.classList.remove('active'));
                document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
                document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
                document.getElementById(`${tab}-tab`).style.display = 'block';
            };
            
            async function loadFriends() {
                const friendsList = document.getElementById('friends-list');
                friendsList.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>Cargando...</p></div>';
                
                try {
                    const result = await Utils.fetchAPI(`${API_BASE_URL}/users.php?action=get-friends`);
                    
                    if (result.success && result.data && result.data.length > 0) {
                        document.getElementById('friends-count').textContent = result.data.length;
                        friendsList.innerHTML = '';
                        
                        result.data.forEach(friend => {
                            friendsList.appendChild(createFriendCard(friend, 'friend'));
                        });
                    } else {
                        document.getElementById('friends-count').textContent = '0';
                        friendsList.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-user-friends"></i>
                                <p>Aún no tienes amigos</p>
                                <p style="margin-top: 10px;"><a href="explorar.php" style="color: #ff8a00;">Explorar usuarios</a></p>
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('❌ Error cargando amigos:', error);
                }
            }
            
            async function loadPendingRequests() {
                const requestsList = document.getElementById('requests-list');
                requestsList.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>Cargando...</p></div>';
                
                console.log('🔄 Cargando solicitudes...');
                
                try {
                    const result = await Utils.fetchAPI(`${API_BASE_URL}/users.php?action=get-pending-requests`);
                    
                    console.log('📥 Respuesta:', result);
                    
                    if (result.success && result.data && result.data.length > 0) {
                        console.log('✅ Solicitudes encontradas:', result.data.length);
                        document.getElementById('requests-count').textContent = result.data.length;
                        requestsList.innerHTML = '';
                        
                        result.data.forEach(request => {
                            requestsList.appendChild(createFriendCard(request, 'request'));
                        });
                    } else {
                        console.log('ℹ️ Sin solicitudes');
                        document.getElementById('requests-count').textContent = '0';
                        requestsList.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-user-clock"></i>
                                <p>No tienes solicitudes pendientes</p>
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('❌ Error:', error);
                    requestsList.innerHTML = `<div class="empty-state"><p>Error: ${error.message}</p></div>`;
                }
            }
            
            async function loadSentRequests() {
                const sentList = document.getElementById('sent-list');
                sentList.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>Cargando...</p></div>';
                
                try {
                    const result = await Utils.fetchAPI(`${API_BASE_URL}/users.php?action=get-sent-requests`);
                    
                    if (result.success && result.data && result.data.length > 0) {
                        document.getElementById('sent-count').textContent = result.data.length;
                        sentList.innerHTML = '';
                        
                        result.data.forEach(request => {
                            sentList.appendChild(createFriendCard(request, 'sent'));
                        });
                    } else {
                        document.getElementById('sent-count').textContent = '0';
                        sentList.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-paper-plane"></i>
                                <p>No has enviado solicitudes</p>
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('❌ Error cargando enviadas:', error);
                }
            }
            
            function createFriendCard(data, type) {
                const card = document.createElement('div');
                card.className = 'friend-card';
                
                const avatarContent = data.foto_perfil ? 
                    `<img src="../../backend/${data.foto_perfil}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">` :
                    data.nombre[0];
                
                const onlineStatus = data.estado === 'online' ? '<span class="status-online"></span>' : '';
                
                let actions = '';
                if (type === 'friend') {
                    actions = `
                        <button class="friend-btn btn-view" onclick="window.viewProfile(${data.user_id})">
                            <i class="fas fa-eye"></i> Ver perfil
                        </button>
                        <button class="friend-btn btn-message" onclick="window.sendMessage(${data.user_id})">
                            <i class="fas fa-envelope"></i> Mensaje
                        </button>
                    `;
                } else if (type === 'request') {
                    actions = `
                        <button class="friend-btn btn-accept" onclick="window.acceptRequest(${data.request_id}, ${data.user_id})">
                            <i class="fas fa-check"></i> Aceptar
                        </button>
                        <button class="friend-btn btn-reject" onclick="window.rejectRequest(${data.request_id}, ${data.user_id})">
                            <i class="fas fa-times"></i> Rechazar
                        </button>
                    `;
                } else if (type === 'sent') {
                    actions = `
                        <button class="friend-btn btn-view" onclick="window.viewProfile(${data.user_id})">
                            <i class="fas fa-eye"></i> Ver perfil
                        </button>
                        <button class="friend-btn btn-reject" onclick="window.cancelRequest(${data.request_id}, ${data.user_id})">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    `;
                }
                
                card.innerHTML = `
                    <div class="friend-card-avatar" onclick="window.viewProfile(${data.user_id})">
                        ${avatarContent}
                        ${onlineStatus}
                    </div>
                    <h3 onclick="window.viewProfile(${data.user_id})">${Utils.escapeHtml(data.nombre)}</h3>
                    <div class="username">@${Utils.escapeHtml(data.usuario)}</div>
                    ${data.genero_musical_favorito ? `<div style="color: #ff8a00; margin-bottom: 10px;"><i class="fas fa-music"></i> ${Utils.escapeHtml(data.genero_musical_favorito)}</div>` : ''}
                    <div class="friend-card-actions">${actions}</div>
                `;
                
                return card;
            }
            
            window.viewProfile = (userId) => window.location.href = `perfil.php?user_id=${userId}`;
            window.sendMessage = (userId) => window.location.href = `mensajes.php?user_id=${userId}`;
            
            window.acceptRequest = async (requestId, userId) => {
                await window.FriendshipSystem.acceptRequest(requestId, userId);
                loadFriends();
                loadPendingRequests();
            };
            
            window.rejectRequest = async (requestId, userId) => {
                await window.FriendshipSystem.rejectRequest(requestId, userId);
                loadPendingRequests();
            };
            
            window.cancelRequest = async (requestId, userId) => {
                await window.FriendshipSystem.cancelRequest(requestId, userId);
                loadSentRequests();
            };
            
            // Inicializar cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    loadFriends();
                    loadPendingRequests();
                    loadSentRequests();
                });
            } else {
                loadFriends();
                loadPendingRequests();
                loadSentRequests();
            }
        })();
    </script>
</body>
</html>