/**
 * posts.js - Funcionalidad de publicaciones
 * Crear posts, likes, comentarios
 * ACTUALIZADO CON SOPORTE PARA MÚSICA - 16/11/2025
 */

// Ejecutar cuando el DOM y SoundConnect estén listos
(function() {
    'use strict';
    
    // Esperar a que SoundConnect esté disponible
    function init() {
        if (!window.SoundConnect) {
            setTimeout(init, 50);
            return;
        }
        
        const Utils = window.SoundConnect.Utils;
        const API_BASE_URL = window.SoundConnect.API_BASE_URL;
        
        console.log('✅ posts.js inicializado correctamente');
        
        // ============================================
        // CREAR ELEMENTO HTML DE PUBLICACIÓN
        // ============================================
        
        function createPostElement(post) {
            const postDiv = document.createElement('div');
            postDiv.className = 'post';
            postDiv.setAttribute('data-post-id', post.id);
            
            const avatarContent = post.foto_perfil 
                ? `<img src="../../backend/${Utils.escapeHtml(post.foto_perfil)}" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`
                : Utils.getInitials(post.nombre);
            
            // Construir HTML de música si existe
            let musicHTML = '';
            if (post.cancion_id || post.cancion_nombre) {
                musicHTML = `
                    <div class="post-song">
                        <i class="fas fa-music"></i>
                        <span>${Utils.escapeHtml(post.cancion_nombre || 'Sin título')}</span>
                        ${post.cancion_artista ? ` - <span>${Utils.escapeHtml(post.cancion_artista)}</span>` : ''}
                    </div>
                `;
            }
            
            postDiv.innerHTML = `
                <div class="post-user">
                    <div class="post-avatar">${avatarContent}</div>
                    <div class="post-user-info">
                        <div class="post-username">${Utils.escapeHtml(post.usuario)}</div>
                        <div class="post-time">${Utils.timeAgo(post.fecha_creacion)}</div>
                    </div>
                </div>
                <div class="post-content">${Utils.escapeHtml(post.contenido).replace(/\n/g, '<br>')}</div>
                ${post.imagen_url ? `<img src="${Utils.escapeHtml(post.imagen_url)}" alt="Post image" class="post-image">` : ''}
                ${musicHTML}
                <div class="post-stats">
                    <span class="likes-count">${post.total_likes || 0} me gusta</span>
                    <span class="comments-count">${post.total_comentarios || 0} comentarios</span>
                </div>
                <div class="post-interactions">
                    <button class="interaction-btn like-btn" data-post-id="${post.id}">
                        <i class="far fa-heart"></i> Me gusta
                    </button>
                    <button class="interaction-btn comment-btn" data-post-id="${post.id}">
                        <i class="far fa-comment"></i> Comentar
                    </button>
                    <button class="interaction-btn share-btn">
                        <i class="far fa-share-square"></i> Compartir
                    </button>
                </div>
                <div class="comments-section" style="display: none;">
                    <div class="comments-list"></div>
                    <div class="comment-form">
                        <input type="text" class="comment-input" placeholder="Escribe un comentario..." data-post-id="${post.id}">
                        <button class="comment-submit" data-post-id="${post.id}">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            `;
            
            return postDiv;
        }
        
        // ============================================
        // CREAR COMENTARIO HTML
        // ============================================
        
        function createCommentElement(comment) {
            const commentDiv = document.createElement('div');
            commentDiv.className = 'comment-item';
            commentDiv.style.cssText = `
                display: flex;
                gap: 10px;
                padding: 10px 0;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            `;
            
            const avatarContent = comment.foto_perfil 
                ? `<img src="../../backend/${Utils.escapeHtml(comment.foto_perfil)}" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`
                : Utils.getInitials(comment.nombre);
            
            commentDiv.innerHTML = `
                <div class="comment-avatar" style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: white; font-size: 0.8rem; font-weight: bold;">
                    ${avatarContent}
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 4px;">
                        ${Utils.escapeHtml(comment.nombre)}
                        <span style="font-weight: 400; color: #999; font-size: 0.8rem; margin-left: 8px;">
                            ${Utils.timeAgo(comment.fecha_creacion)}
                        </span>
                    </div>
                    <div style="color: #ddd; font-size: 0.9rem;">
                        ${Utils.escapeHtml(comment.contenido)}
                    </div>
                </div>
            `;
            
            return commentDiv;
        }
        
        // ============================================
        // INICIALIZACIÓN
        // ============================================
        
        function initializePosts() {
            // Evento para crear publicación
            const postBtn = document.querySelector('.post-btn');
            const postInput = document.querySelector('.post-input');
            
            if (postBtn && postInput) {
                postBtn.addEventListener('click', handleCreatePost);
                
                // Permitir enter para publicar
                postInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        handleCreatePost();
                    }
                });
            }
            
            // Eventos de interacción en posts existentes
            initializePostInteractions();
        }
        
        // ============================================
        // CREAR PUBLICACIÓN (CON MÚSICA)
        // ============================================
        
        async function handleCreatePost() {
            const postInput = document.querySelector('.post-input');
            const contenido = postInput.value.trim();
            
            if (!contenido) {
                Utils.showNotification('Escribe algo para publicar', 'error');
                return;
            }
            
            // Deshabilitar botón mientras se procesa
            const postBtn = document.querySelector('.post-btn');
            postBtn.disabled = true;
            postBtn.textContent = 'Publicando...';
            
            // Preparar datos del post
            const postData = { contenido };
            
            // NUEVO: Verificar si hay música adjunta
            const songId = window.MusicUploader?.getUploadedSongId();
            if (songId) {
                postData.cancion_id = songId;
            }
            
            const result = await Utils.fetchAPI(`${API_BASE_URL}/posts.php?action=create`, {
                method: 'POST',
                body: JSON.stringify(postData)
            });
            
            postBtn.disabled = false;
            postBtn.textContent = 'Publicar';
            
            if (result.success) {
                Utils.showNotification('Publicación creada exitosamente', 'success');
                postInput.value = '';
                
                // Limpiar música adjunta
                if (window.MusicUploader?.removeAttachedSong) {
                    window.MusicUploader.removeAttachedSong();
                }
                
                // Agregar la nueva publicación al feed
                addPostToFeed(result.data);
            } else {
                Utils.showNotification(result.message || 'Error al crear publicación', 'error');
            }
        }
        
        function addPostToFeed(post) {
            const feed = document.querySelector('.feed');
            const createPostDiv = document.querySelector('.create-post');
            
            const postElement = createPostElement(post);
            
            // Insertar después del formulario de crear post
            createPostDiv.insertAdjacentElement('afterend', postElement);
            
            // Inicializar eventos de la nueva publicación
            initializePostInteractions(postElement);
            
            // Animación de entrada
            postElement.style.animation = 'fadeIn 0.5s ease-out';
        }
        
        // ============================================
        // INTERACCIONES CON POSTS
        // ============================================
        
        function initializePostInteractions(container = document) {
            // Botones de like
            const likeButtons = container.querySelectorAll('.like-btn');
            likeButtons.forEach(btn => {
                btn.addEventListener('click', handleLike);
            });
            
            // Botones de comentar
            const commentButtons = container.querySelectorAll('.comment-btn');
            commentButtons.forEach(btn => {
                btn.addEventListener('click', handleCommentToggle);
            });
            
            // Enviar comentario
            const commentSubmits = container.querySelectorAll('.comment-submit');
            commentSubmits.forEach(btn => {
                btn.addEventListener('click', handleAddComment);
            });
            
            // Enter en input de comentario
            const commentInputs = container.querySelectorAll('.comment-input');
            commentInputs.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const postId = this.getAttribute('data-post-id');
                        const submitBtn = container.querySelector(`.comment-submit[data-post-id="${postId}"]`);
                        if (submitBtn) submitBtn.click();
                    }
                });
            });
        }
        
        // ============================================
        // LIKES
        // ============================================
        
        async function handleLike(e) {
            const button = e.currentTarget;
            const postId = button.getAttribute('data-post-id');
            
            // Optimistic UI update
            const icon = button.querySelector('i');
            const wasLiked = icon.classList.contains('fas');
            
            if (wasLiked) {
                icon.classList.remove('fas');
                icon.classList.add('far');
                button.style.color = '';
            } else {
                icon.classList.remove('far');
                icon.classList.add('fas');
                button.style.color = '#ff8a00';
            }
            
            // Actualizar contador
            const post = button.closest('.post');
            const likesCount = post.querySelector('.likes-count');
            const currentCount = parseInt(likesCount.textContent);
            likesCount.textContent = `${wasLiked ? currentCount - 1 : currentCount + 1} me gusta`;
            
            // Llamada a la API
            const result = await Utils.fetchAPI(`${API_BASE_URL}/posts.php?action=like`, {
                method: 'POST',
                body: JSON.stringify({ post_id: parseInt(postId) })
            });
            
            if (result.success) {
                // Actualizar con el valor real del servidor
                likesCount.textContent = `${result.total_likes} me gusta`;
            } else {
                // Revertir cambios si falla
                if (wasLiked) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    button.style.color = '#ff8a00';
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    button.style.color = '';
                }
                likesCount.textContent = `${currentCount} me gusta`;
                Utils.showNotification('Error al dar like', 'error');
            }
        }
        
        // ============================================
        // COMENTARIOS
        // ============================================
        
        async function handleCommentToggle(e) {
            const button = e.currentTarget;
            const postId = button.getAttribute('data-post-id');
            const post = button.closest('.post');
            const commentsSection = post.querySelector('.comments-section');
            
            if (commentsSection.style.display === 'none') {
                commentsSection.style.display = 'block';
                
                // Cargar comentarios si aún no se han cargado
                const commentsList = commentsSection.querySelector('.comments-list');
                if (commentsList.children.length === 0) {
                    await loadComments(postId, commentsList);
                }
                
                // Focus en el input
                const commentInput = commentsSection.querySelector('.comment-input');
                commentInput.focus();
            } else {
                commentsSection.style.display = 'none';
            }
        }
        
        async function loadComments(postId, commentsList) {
            commentsList.innerHTML = '<div style="text-align: center; padding: 10px; color: #999;">Cargando comentarios...</div>';
            
            const result = await Utils.fetchAPI(`${API_BASE_URL}/posts.php?action=get-comments&post_id=${postId}`);
            
            if (result.success) {
                commentsList.innerHTML = '';
                
                if (result.data.length === 0) {
                    commentsList.innerHTML = '<div style="text-align: center; padding: 10px; color: #999;">No hay comentarios aún</div>';
                    return;
                }
                
                result.data.forEach(comment => {
                    const commentElement = createCommentElement(comment);
                    commentsList.appendChild(commentElement);
                });
            } else {
                commentsList.innerHTML = '<div style="text-align: center; padding: 10px; color: #f44336;">Error al cargar comentarios</div>';
            }
        }
        
        async function handleAddComment(e) {
            const button = e.currentTarget;
            const postId = button.getAttribute('data-post-id');
            const post = button.closest('.post');
            const commentInput = post.querySelector(`.comment-input[data-post-id="${postId}"]`);
            const contenido = commentInput.value.trim();
            
            if (!contenido) {
                Utils.showNotification('Escribe un comentario', 'error');
                return;
            }
            
            button.disabled = true;
            
            const result = await Utils.fetchAPI(`${API_BASE_URL}/posts.php?action=comment`, {
                method: 'POST',
                body: JSON.stringify({
                    post_id: parseInt(postId),
                    contenido
                })
            });
            
            button.disabled = false;
            
            if (result.success) {
                commentInput.value = '';
                
                // Agregar comentario a la lista
                const commentsList = post.querySelector('.comments-list');
                const commentElement = createCommentElement(result.data);
                commentsList.appendChild(commentElement);
                
                // Actualizar contador
                const commentsCount = post.querySelector('.comments-count');
                const currentCount = parseInt(commentsCount.textContent);
                commentsCount.textContent = `${currentCount + 1} comentarios`;
                
                Utils.showNotification('Comentario agregado', 'success');
            } else {
                Utils.showNotification(result.message || 'Error al agregar comentario', 'error');
            }
        }
        
        // Agregar estilos CSS dinámicos
        const style = document.createElement('style');
        style.textContent = `
            .comments-section {
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid rgba(255,255,255,0.1);
            }
            
            .comments-list {
                max-height: 300px;
                overflow-y: auto;
                margin-bottom: 15px;
            }
            
            .comment-form {
                display: flex;
                gap: 10px;
                align-items: center;
            }
            
            .comment-input {
                flex: 1;
                background: rgba(255,255,255,0.05);
                border: 1px solid rgba(255,255,255,0.1);
                border-radius: 20px;
                padding: 10px 15px;
                color: white;
                font-size: 0.9rem;
            }
            
            .comment-input:focus {
                outline: none;
                border-color: #ff8a00;
            }
            
            .comment-submit {
                background: #ff8a00;
                border: none;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                cursor: pointer;
                transition: background 0.3s;
            }
            
            .comment-submit:hover {
                background: #e67700;
            }
            
            .comment-submit:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            
            /* NUEVO: Estilos para música en posts */
            .post-song {
                margin: 15px 0;
                padding: 15px;
                background: rgba(255, 138, 0, 0.1);
                border-left: 4px solid #ff8a00;
                border-radius: 8px;
                display: flex;
                align-items: center;
                gap: 10px;
                color: white;
            }
            
            .post-song i {
                color: #ff8a00;
                font-size: 1.5rem;
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
        
        // Exportar e inicializar
        window.initializePosts = initializePosts;
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializePosts);
        } else {
            initializePosts();
        }
    }
    
    // Iniciar
    init();
})();