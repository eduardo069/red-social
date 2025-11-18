/**
 * main.js - Funciones generales de SoundConnect
 * Configuración global, utilidades y eventos comunes
 */

// ============================================
// NAMESPACE GLOBAL - SoundConnect
// ============================================
window.SoundConnect = window.SoundConnect || {};

// Configuración de la API
window.SoundConnect.API_BASE_URL = '../../backend/api';

// Utilidades generales
window.SoundConnect.Utils = {
    /**
     * Hacer petición fetch con manejo de errores
     */
    async fetchAPI(url, options = {}) {
        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                }
            });
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error en petición:', error);
            return {
                success: false,
                message: 'Error de conexión'
            };
        }
    },

    /**
     * Mostrar notificación toast
     */
    showNotification(message, type = 'info') {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        // Estilos inline para la notificación
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        // Eliminar después de 3 segundos
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    },

    /**
     * Formatear fecha relativa (hace X tiempo)
     */
    timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        const intervals = {
            año: 31536000,
            mes: 2592000,
            día: 86400,
            hora: 3600,
            minuto: 60
        };
        
        for (let [name, value] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / value);
            if (interval >= 1) {
                return `Hace ${interval} ${name}${interval > 1 ? (name === 'mes' ? 'es' : 's') : ''}`;
            }
        }
        
        return 'Hace un momento';
    },

    /**
     * Obtener iniciales del nombre
     */
    getInitials(name) {
        return name.split(' ')
            .map(word => word[0])
            .join('')
            .toUpperCase()
            .substring(0, 2);
    },

    /**
     * Escapar HTML para prevenir XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// ============================================
// EVENTOS GLOBALES AL CARGAR LA PÁGINA
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Sistema de tabs de autenticación
    const authTabs = document.querySelectorAll('.auth-tab');
    authTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remover active de todos
            authTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Mostrar formulario correspondiente
            const tabId = this.getAttribute('data-tab');
            document.querySelectorAll('.auth-form').forEach(form => {
                form.classList.remove('active');
            });
            document.getElementById(tabId + '-form').classList.add('active');
        });
    });
    
    // Toggle del menú móvil
    const mobileMenu = document.querySelector('.mobile-menu');
    if (mobileMenu) {
        mobileMenu.addEventListener('click', function() {
            const nav = document.getElementById('main-nav');
            nav.classList.toggle('active');
        });
    }
    
    // Cerrar menú móvil al hacer clic fuera
    document.addEventListener('click', function(event) {
        const nav = document.getElementById('main-nav');
        const mobileMenu = document.querySelector('.mobile-menu');
        
        if (nav && nav.classList.contains('active') && 
            !nav.contains(event.target) && 
            mobileMenu && !mobileMenu.contains(event.target)) {
            nav.classList.remove('active');
        }
    });
    
    // Menu del perfil de usuario
    const userProfile = document.querySelector('.user-profile');
    if (userProfile) {
        userProfile.addEventListener('click', function(e) {
            e.stopPropagation();
            const userMenu = this.querySelector('.user-menu');
            if (userMenu) {
                userMenu.classList.toggle('active');
            }
        });
        
        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', function() {
            const userMenu = document.querySelector('.user-menu');
            if (userMenu) {
                userMenu.classList.remove('active');
            }
        });
    }
    
    // Búsqueda de usuarios (evento de input con debounce)
    const searchInput = document.querySelector('.search-bar input');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 3) {
                searchTimeout = setTimeout(() => {
                    searchUsers(query);
                }, 500);
            }
        });
    }
});

// ============================================
// FUNCIONES DE BÚSQUEDA
// ============================================

/**
 * Buscar usuarios
 */
async function searchUsers(query) {
    const result = await window.SoundConnect.Utils.fetchAPI(
        `${window.SoundConnect.API_BASE_URL}/users.php?action=search&query=${encodeURIComponent(query)}`
    );
    
    if (result.success) {
        displaySearchResults(result.data);
    }
}

/**
 * Mostrar resultados de búsqueda
 */
function displaySearchResults(users) {
    // Crear o actualizar dropdown de resultados
    let resultsContainer = document.querySelector('.search-results');
    
    if (!resultsContainer) {
        resultsContainer = document.createElement('div');
        resultsContainer.className = 'search-results';
        resultsContainer.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #1a1a2e;
            border-radius: 8px;
            margin-top: 5px;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            z-index: 1000;
        `;
        document.querySelector('.search-bar').style.position = 'relative';
        document.querySelector('.search-bar').appendChild(resultsContainer);
    }
    
    resultsContainer.innerHTML = '';
    
    if (users.length === 0) {
        resultsContainer.innerHTML = '<div style="padding: 15px; text-align: center; color: #999;">No se encontraron usuarios</div>';
        return;
    }
    
    users.forEach(user => {
        const userItem = document.createElement('div');
        userItem.className = 'search-result-item';
        userItem.style.cssText = `
            padding: 12px 15px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: background 0.2s;
        `;
        userItem.innerHTML = `
            <div class="result-avatar" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; margin-right: 12px; color: white; font-weight: bold;">
                ${user.foto_perfil ? `<img src="../../backend/${window.SoundConnect.Utils.escapeHtml(user.foto_perfil)}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">` : window.SoundConnect.Utils.getInitials(user.nombre)}
            </div>
            <div>
                <div style="font-weight: 600; color: white;">${window.SoundConnect.Utils.escapeHtml(user.nombre)}</div>
                <div style="font-size: 0.85rem; color: #999;">@${window.SoundConnect.Utils.escapeHtml(user.usuario)}</div>
            </div>
        `;
        
        userItem.addEventListener('mouseenter', function() {
            this.style.background = 'rgba(255,255,255,0.05)';
        });
        
        userItem.addEventListener('mouseleave', function() {
            this.style.background = 'transparent';
        });
        
        userItem.addEventListener('click', function() {
            // Redirigir al perfil del usuario
            window.location.href = `perfil.php?user_id=${user.id}`;
        });
        
        resultsContainer.appendChild(userItem);
    });
}

// ============================================
// VERIFICAR SESIÓN
// ============================================

/**
 * Verificar si el usuario está autenticado
 */
async function checkSession() {
    const result = await window.SoundConnect.Utils.fetchAPI(
        `${window.SoundConnect.API_BASE_URL}/auth.php?action=check-session`
    );
    return result.authenticated;
}

// ============================================
// ANIMACIONES Y CSS DINÁMICO
// ============================================

// Agregar keyframes para animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .user-menu.active {
        display: block !important;
    }
`;
document.head.appendChild(style);

console.log('✅ main.js cargado - SoundConnect inicializado');