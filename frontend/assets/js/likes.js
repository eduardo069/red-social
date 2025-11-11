/**
 * likes.js - Sistema de Me Gusta
 */

(function() {
    'use strict';
    
    const API_BASE_URL = 'http://localhost/red-social/backend/api';
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔧 likes.js cargado');
        initializeLikes();
    });
    
    /**
     * Inicializar sistema de likes
     */
    function initializeLikes() {
        // Obtener todos los botones de like
        const likeButtons = document.querySelectorAll('.like-btn');
        
        console.log(`📊 Encontrados ${likeButtons.length} botones de like`);
        
        // Verificar qué posts ya tienen like del usuario actual
        checkUserLikes();
        
        // Agregar event listeners
        likeButtons.forEach(button => {
            button.addEventListener('click', handleLikeClick);
        });
    }
    
    /**
     * Verificar qué publicaciones ya tienen like
     */
    async function checkUserLikes() {
        const likeButtons = document.querySelectorAll('.like-btn');
        const postIds = Array.from(likeButtons).map(btn => btn.dataset.postId).filter(Boolean);
        
        if (postIds.length === 0) return;
        
        try {
            const response = await fetch(`${API_BASE_URL}/likes.php?action=check-multiple`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    publicacion_ids: postIds
                })
            });
            
            const result = await response.json();
            
            if (result.success && result.data.liked_posts) {
                result.data.liked_posts.forEach(postId => {
                    const button = document.querySelector(`.like-btn[data-post-id="${postId}"]`);
                    if (button) {
                        markAsLiked(button);
                    }
                });
            }
        } catch (error) {
            console.error('Error al verificar likes:', error);
        }
    }
    
    /**
     * Manejar click en botón de like
     */
    async function handleLikeClick(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const button = e.currentTarget;
        const postId = button.dataset.postId;
        
        if (!postId) {
            console.error('No se encontró el ID de la publicación');
            return;
        }
        
        // Deshabilitar botón temporalmente
        button.disabled = true;
        
        try {
            const response = await fetch(`${API_BASE_URL}/likes.php?action=toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    publicacion_id: postId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                console.log('✅ Like procesado:', result);
                
                // Actualizar UI
                if (result.data.liked) {
                    markAsLiked(button);
                    animateLike(button);
                } else {
                    markAsUnliked(button);
                }
                
                // Actualizar contador
                updateLikeCount(postId, result.data.total_likes);
                
            } else {
                console.error('Error:', result.message);
                showNotification('Error al procesar el like', 'error');
            }
        } catch (error) {
            console.error('Error al dar like:', error);
            showNotification('Error de conexión', 'error');
        } finally {
            // Rehabilitar botón
            button.disabled = false;
        }
    }
    
    /**
     * Marcar botón como "liked"
     */
    function markAsLiked(button) {
        const icon = button.querySelector('i');
        
        // Cambiar icono a corazón relleno
        icon.classList.remove('far');
        icon.classList.add('fas');
        
        // Agregar clase liked
        button.classList.add('liked');
        
        // Cambiar color
        button.style.color = '#e74c3c';
        icon.style.color = '#e74c3c';
        
        // Cambiar texto
        const textNode = button.childNodes[button.childNodes.length - 1];
        if (textNode && textNode.nodeType === 3) {
            textNode.textContent = ' Me gusta';
        }
    }
    
    /**
     * Marcar botón como "unliked"
     */
    function markAsUnliked(button) {
        const icon = button.querySelector('i');
        
        // Cambiar icono a corazón vacío
        icon.classList.remove('fas');
        icon.classList.add('far');
        
        // Quitar clase liked
        button.classList.remove('liked');
        
        // Restaurar color
        button.style.color = '';
        icon.style.color = '';
        
        // Cambiar texto
        const textNode = button.childNodes[button.childNodes.length - 1];
        if (textNode && textNode.nodeType === 3) {
            textNode.textContent = ' Me gusta';
        }
    }
    
    /**
     * Actualizar contador de likes
     */
    function updateLikeCount(postId, newCount) {
        const post = document.querySelector(`[data-post-id="${postId}"]`);
        if (!post) return;
        
        const statsDiv = post.querySelector('.post-stats');
        if (!statsDiv) return;
        
        const likesSpan = statsDiv.querySelector('span:first-child');
        if (likesSpan) {
            likesSpan.textContent = `${newCount} me gusta`;
        }
    }
    
    /**
     * Animación de like
     */
    function animateLike(button) {
        const icon = button.querySelector('i');
        
        // Agregar animación
        icon.style.transform = 'scale(0)';
        icon.style.transition = 'transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
        
        setTimeout(() => {
            icon.style.transform = 'scale(1.2)';
            
            setTimeout(() => {
                icon.style.transform = 'scale(1)';
            }, 200);
        }, 50);
        
        // Crear partículas (efecto corazones)
        createLikeParticles(button);
    }
    
    /**
     * Crear efecto de partículas
     */
    function createLikeParticles(button) {
        const rect = button.getBoundingClientRect();
        const container = document.body;
        
        for (let i = 0; i < 5; i++) {
            const particle = document.createElement('div');
            particle.innerHTML = '❤️';
            particle.style.cssText = `
                position: fixed;
                left: ${rect.left + rect.width / 2}px;
                top: ${rect.top}px;
                font-size: 20px;
                pointer-events: none;
                z-index: 9999;
                animation: likeParticle 1s ease-out forwards;
                animation-delay: ${i * 0.1}s;
            `;
            
            container.appendChild(particle);
            
            setTimeout(() => {
                particle.remove();
            }, 1000 + (i * 100));
        }
    }
    
    /**
     * Mostrar notificación
     */
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'error' ? '#e74c3c' : '#2ecc71'};
            color: white;
            border-radius: 5px;
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
    
    // Agregar estilos CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes likeParticle {
            0% {
                transform: translateY(0) scale(0);
                opacity: 1;
            }
            50% {
                transform: translateY(-30px) scale(1);
                opacity: 1;
            }
            100% {
                transform: translateY(-60px) scale(0);
                opacity: 0;
            }
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
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
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        .like-btn.liked {
            color: #e74c3c !important;
        }
        
        .like-btn.liked i {
            color: #e74c3c !important;
        }
        
        .like-btn:hover i {
            transform: scale(1.2);
            transition: transform 0.2s;
        }
    `;
    document.head.appendChild(style);
    
    console.log('✅ likes.js inicializado correctamente');
})();