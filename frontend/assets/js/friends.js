/**
 * friends.js - Sistema completo de amistades para SoundConnect
 * Maneja: enviar solicitudes, aceptar/rechazar, mostrar estados
 */

(function() {
    'use strict';
    
    const API_BASE_URL = 'http://localhost/red-social/backend/api';
    
    // Estado global de amistades
    const friendshipCache = new Map();
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🤝 friends.js cargado');
        initializeFriendships();
    });
    
    /**
     * Inicializar sistema de amistades
     */
    function initializeFriendships() {
        // Cargar conteo de solicitudes pendientes
        loadPendingRequestsCount();
        
        // Actualizar botones de amistad en la página
        updateFriendshipButtons();
        
        // Actualizar cada 30 segundos
        setInterval(() => {
            loadPendingRequestsCount();
        }, 30000);
    }
    
    /**
     * Cargar y mostrar cantidad de solicitudes pendientes
     */
    async function loadPendingRequestsCount() {
        try {
            const response = await fetch(`${API_BASE_URL}/users.php?action=get-pending-requests`);
            const result = await response.json();
            
            if (result.success) {
                updateNotificationBadge(result.count);
            }
        } catch (error) {
            console.error('Error al cargar solicitudes pendientes:', error);
        }
    }
    
    /**
     * Actualizar badge de notificaciones
     */
    function updateNotificationBadge(count) {
        let badge = document.getElementById('friend-requests-badge');
        
        if (count > 0) {
            if (!badge) {
                // Crear badge si no existe
                const menuItem = document.querySelector('nav a[href*="amigos"]') || 
                                document.querySelector('.user-profile');
                
                if (menuItem) {
                    badge = document.createElement('span');
                    badge.id = 'friend-requests-badge';
                    badge.className = 'notification-badge';
                    badge.textContent = count;
                    menuItem.style.position = 'relative';
                    menuItem.appendChild(badge);
                }
            } else {
                badge.textContent = count;
                badge.style.display = 'flex';
            }
        } else if (badge) {
            badge.style.display = 'none';
        }
    }
    
    /**
     * Actualizar botones de amistad en posts y tarjetas
     */
    async function updateFriendshipButtons() {
        // Obtener todos los botones de amistad en la página
        const friendButtons = document.querySelectorAll('[data-friend-action]');
        
        if (friendButtons.length === 0) return;
        
        // Obtener IDs únicos de usuarios
        const userIds = [...new Set(
            Array.from(friendButtons)
                .map(btn => btn.dataset.userId)
                .filter(id => id)
        )];
        
        // Verificar estado de amistad para cada usuario
        for (const userId of userIds) {
            await checkFriendshipStatus(userId);
        }
    }
    
    /**
     * Verificar estado de amistad con un usuario
     */
    async function checkFriendshipStatus(userId) {
        // Verificar cache primero
        if (friendshipCache.has(userId)) {
            updateButtonsForUser(userId, friendshipCache.get(userId));
            return friendshipCache.get(userId);
        }
        
        try {
            const response = await fetch(`${API_BASE_URL}/users.php?action=check-friendship&user_id=${userId}`);
            const result = await response.json();
            
            if (result.success) {
                const status = result.data;
                friendshipCache.set(userId, status);
                updateButtonsForUser(userId, status);
                return status;
            }
        } catch (error) {
            console.error('Error al verificar amistad:', error);
        }
        
        return null;
    }
    
    /**
     * Actualizar botones para un usuario específico
     */
    function updateButtonsForUser(userId, status) {
        const buttons = document.querySelectorAll(`[data-friend-action][data-user-id="${userId}"]`);
        
        buttons.forEach(button => {
            updateFriendButton(button, status);
        });
    }
    
    /**
     * Actualizar apariencia de un botón según estado de amistad
     */
    function updateFriendButton(button, status) {
        const statusType = status.status;
        
        // Limpiar clases previas
        button.classList.remove('friend-none', 'friend-pending', 'friend-accepted');
        
        // Remover listeners antiguos
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
        
        switch (statusType) {
            case 'none':
                newButton.classList.add('friend-none');
                newButton.innerHTML = '<i class="fas fa-user-plus"></i> Agregar amigo';
                newButton.onclick = () => sendFriendRequest(newButton.dataset.userId);
                newButton.disabled = false;
                break;
                
            case 'pendiente':
                newButton.classList.add('friend-pending');
                if (status.initiated_by === parseInt(newButton.dataset.currentUser)) {
                    // Usuario actual envió la solicitud
                    newButton.innerHTML = '<i class="fas fa-clock"></i> Solicitud enviada';
                    newButton.onclick = () => cancelFriendRequest(status.friendship_id, newButton.dataset.userId);
                } else {
                    // Usuario actual recibió la solicitud
                    newButton.innerHTML = '<i class="fas fa-check"></i> Aceptar solicitud';
                    newButton.onclick = () => acceptFriendRequest(status.friendship_id, newButton.dataset.userId);
                }
                break;
                
            case 'aceptada':
                newButton.classList.add('friend-accepted');
                newButton.innerHTML = '<i class="fas fa-user-check"></i> Amigos';
                newButton.onclick = () => showFriendOptions(status.friendship_id, newButton.dataset.userId);
                break;
                
            default:
                newButton.innerHTML = '<i class="fas fa-user-plus"></i> Agregar';
                newButton.onclick = () => sendFriendRequest(newButton.dataset.userId);
        }
    }
    
    /**
     * Enviar solicitud de amistad
     */
    async function sendFriendRequest(friendId) {
        try {
            showLoading();
            
            const response = await fetch(`${API_BASE_URL}/users.php?action=send-friend-request`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ friend_id: parseInt(friendId) })
            });
            
            const result = await response.json();
            
            hideLoading();
            
            if (result.success) {
                showNotification('Solicitud enviada correctamente', 'success');
                
                // Actualizar cache
                friendshipCache.delete(friendId);
                
                // Actualizar botones
                await checkFriendshipStatus(friendId);
                
            } else {
                showNotification(result.message || 'Error al enviar solicitud', 'error');
            }
        } catch (error) {
            hideLoading();
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
        }
    }
    
    /**
     * Aceptar solicitud de amistad
     */
    async function acceptFriendRequest(requestId, userId) {
        try {
            showLoading();
            
            const response = await fetch(`${API_BASE_URL}/users.php?action=accept-friend-request`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ request_id: parseInt(requestId) })
            });
            
            const result = await response.json();
            
            hideLoading();
            
            if (result.success) {
                showNotification('¡Ahora son amigos!', 'success');
                
                // Actualizar cache
                friendshipCache.delete(userId);
                
                // Actualizar botones y contador
                await checkFriendshipStatus(userId);
                await loadPendingRequestsCount();
                
                // Si estamos en página de solicitudes, recargar
                if (window.location.pathname.includes('amigos.php')) {
                    setTimeout(() => location.reload(), 1000);
                }
                
            } else {
                showNotification(result.message || 'Error al aceptar solicitud', 'error');
            }
        } catch (error) {
            hideLoading();
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
        }
    }
    
    /**
     * Rechazar solicitud de amistad
     */
    async function rejectFriendRequest(requestId, userId) {
        if (!confirm('¿Rechazar esta solicitud de amistad?')) return;
        
        try {
            showLoading();
            
            const response = await fetch(`${API_BASE_URL}/users.php?action=reject-friend-request`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ request_id: parseInt(requestId) })
            });
            
            const result = await response.json();
            
            hideLoading();
            
            if (result.success) {
                showNotification('Solicitud rechazada', 'info');
                
                // Actualizar cache
                friendshipCache.delete(userId);
                
                // Actualizar contador
                await loadPendingRequestsCount();
                
                // Recargar si estamos en página de solicitudes
                if (window.location.pathname.includes('amigos.php')) {
                    setTimeout(() => location.reload(), 1000);
                }
                
            } else {
                showNotification(result.message || 'Error al rechazar solicitud', 'error');
            }
        } catch (error) {
            hideLoading();
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
        }
    }
    
    /**
     * Cancelar solicitud enviada
     */
    async function cancelFriendRequest(requestId, userId) {
        if (!confirm('¿Cancelar la solicitud de amistad?')) return;
        
        try {
            showLoading();
            
            const response = await fetch(`${API_BASE_URL}/users.php?action=cancel-friend-request`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ request_id: parseInt(requestId) })
            });
            
            const result = await response.json();
            
            hideLoading();
            
            if (result.success) {
                showNotification('Solicitud cancelada', 'info');
                
                // Actualizar cache
                friendshipCache.delete(userId);
                
                // Actualizar botones
                await checkFriendshipStatus(userId);
                
            } else {
                showNotification(result.message || 'Error al cancelar solicitud', 'error');
            }
        } catch (error) {
            hideLoading();
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
        }
    }
    
    /**
     * Eliminar amistad
     */
    async function removeFriend(friendshipId, userId) {
        if (!confirm('¿Eliminar esta amistad?')) return;
        
        try {
            showLoading();
            
            const response = await fetch(`${API_BASE_URL}/users.php?action=remove-friend`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ friendship_id: parseInt(friendshipId) })
            });
            
            const result = await response.json();
            
            hideLoading();
            
            if (result.success) {
                showNotification('Amistad eliminada', 'info');
                
                // Actualizar cache
                friendshipCache.delete(userId);
                
                // Actualizar botones
                await checkFriendshipStatus(userId);
                
            } else {
                showNotification(result.message || 'Error al eliminar amistad', 'error');
            }
        } catch (error) {
            hideLoading();
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
        }
    }
    
    /**
     * Mostrar opciones de amistad (eliminar, mensaje, etc)
     */
    function showFriendOptions(friendshipId, userId) {
        const options = confirm('¿Quieres eliminar esta amistad?');
        if (options) {
            removeFriend(friendshipId, userId);
        }
    }
    
    /**
     * Mostrar indicador de carga
     */
    function showLoading() {
        let loader = document.getElementById('friendship-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'friendship-loader';
            loader.className = 'friendship-loader';
            loader.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            document.body.appendChild(loader);
        }
        loader.style.display = 'flex';
    }
    
    /**
     * Ocultar indicador de carga
     */
    function hideLoading() {
        const loader = document.getElementById('friendship-loader');
        if (loader) {
            loader.style.display = 'none';
        }
    }
    
    /**
     * Mostrar notificación
     */
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `friendship-notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Exportar funciones globales
    window.FriendshipSystem = {
        sendRequest: sendFriendRequest,
        acceptRequest: acceptFriendRequest,
        rejectRequest: rejectFriendRequest,
        cancelRequest: cancelFriendRequest,
        removeFriend: removeFriend,
        checkStatus: checkFriendshipStatus,
        updateButtons: updateFriendshipButtons
    };
    
    // Agregar estilos
    const styles = document.createElement('style');
    styles.textContent = `
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .friend-none {
            background: #ff8a00 !important;
            color: white !important;
        }
        
        .friend-pending {
            background: #f39c12 !important;
            color: white !important;
        }
        
        .friend-accepted {
            background: #2ecc71 !important;
            color: white !important;
        }
        
        .friendship-loader {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            z-index: 10000;
            font-size: 1.2rem;
            display: none;
            align-items: center;
            gap: 10px;
        }
        
        .friendship-notification {
            position: fixed;
            top: -100px;
            right: 20px;
            background: white;
            color: #333;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            min-width: 250px;
        }
        
        .friendship-notification.show {
            top: 20px;
        }
        
        .friendship-notification.notification-success {
            border-left: 4px solid #2ecc71;
        }
        
        .friendship-notification.notification-error {
            border-left: 4px solid #e74c3c;
        }
        
        .friendship-notification.notification-info {
            border-left: 4px solid #3498db;
        }
        
        .friendship-notification i {
            font-size: 1.5rem;
        }
        
        .notification-success i { color: #2ecc71; }
        .notification-error i { color: #e74c3c; }
        .notification-info i { color: #3498db; }
    `;
    document.head.appendChild(styles);
    
    console.log('✅ Sistema de amistades inicializado correctamente');
})();