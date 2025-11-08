<?php
/**
 * post-card.php - Componente reutilizable de tarjeta de publicación
 * Uso: include 'post-card.php' y pasar la variable $post
 */

// Verificar que la variable $post existe
if (!isset($post)) {
    return;
}

// Función para calcular tiempo relativo
function timeAgoFromPost($dateString) {
    $fecha = new DateTime($dateString);
    $ahora = new DateTime();
    $diff = $ahora->diff($fecha);
    
    if ($diff->y > 0) {
        return "Hace " . $diff->y . " año" . ($diff->y > 1 ? "s" : "");
    } elseif ($diff->m > 0) {
        return "Hace " . $diff->m . " mes" . ($diff->m > 1 ? "es" : "");
    } elseif ($diff->d > 0) {
        return "Hace " . $diff->d . " día" . ($diff->d > 1 ? "s" : "");
    } elseif ($diff->h > 0) {
        return "Hace " . $diff->h . " hora" . ($diff->h > 1 ? "s" : "");
    } elseif ($diff->i > 0) {
        return "Hace " . $diff->i . " minuto" . ($diff->i > 1 ? "s" : "");
    } else {
        return "Hace un momento";
    }
}
?>

<div class="post" data-post-id="<?php echo $post['id']; ?>">
    <!-- Usuario de la publicación -->
    <div class="post-user">
        <div class="post-avatar">
            <?php if (!empty($post['foto_perfil'])): ?>
                <img src="../../backend/<?php echo htmlspecialchars($post['foto_perfil']); ?>" 
                     alt="Avatar" 
                     style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
            <?php else: ?>
                <?php echo substr($post['nombre'], 0, 1); ?>
            <?php endif; ?>
        </div>
        <div class="post-user-info">
            <div class="post-username">
                <a href="perfil.php?user_id=<?php echo $post['usuario_id']; ?>" style="color: inherit; text-decoration: none;">
                    <?php echo htmlspecialchars($post['usuario']); ?>
                </a>
            </div>
            <div class="post-time"><?php echo timeAgoFromPost($post['fecha_creacion']); ?></div>
        </div>
        
        <!-- Menú de opciones (si es el dueño del post) -->
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['usuario_id']): ?>
        <div class="post-options">
            <button class="post-options-btn" onclick="togglePostMenu(event, <?php echo $post['id']; ?>)">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div class="post-menu" id="post-menu-<?php echo $post['id']; ?>" style="display: none;">
                <button onclick="editPost(<?php echo $post['id']; ?>)">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button onclick="deletePost(<?php echo $post['id']; ?>)" style="color: #f44336;">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Contenido de la publicación -->
    <div class="post-content">
        <?php echo nl2br(htmlspecialchars($post['contenido'])); ?>
    </div>
    
    <!-- Imagen de la publicación (si existe) -->
    <?php if (!empty($post['imagen_url'])): ?>
        <img src="<?php echo htmlspecialchars($post['imagen_url']); ?>" 
             alt="Post image" 
             class="post-image"
             onclick="openImageModal('<?php echo htmlspecialchars($post['imagen_url']); ?>')">
    <?php endif; ?>
    
    <!-- Información de canción (si existe) -->
    <?php if (!empty($post['cancion_nombre'])): ?>
        <div class="post-song">
            <i class="fas fa-music"></i>
            <div class="song-info">
                <span class="song-name"><?php echo htmlspecialchars($post['cancion_nombre']); ?></span>
                <?php if (!empty($post['cancion_artista'])): ?>
                    <span class="song-artist"> - <?php echo htmlspecialchars($post['cancion_artista']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Ubicación (si existe) -->
    <?php if (!empty($post['ubicacion'])): ?>
        <div class="post-location">
            <i class="fas fa-map-marker-alt"></i>
            <span><?php echo htmlspecialchars($post['ubicacion']); ?></span>
        </div>
    <?php endif; ?>
    
    <!-- Estadísticas -->
    <div class="post-stats">
        <span class="likes-count"><?php echo $post['total_likes'] ?? 0; ?> me gusta</span>
        <span class="comments-count"><?php echo $post['total_comentarios'] ?? 0; ?> comentarios</span>
    </div>
    
    <!-- Botones de interacción -->
    <div class="post-interactions">
        <button class="interaction-btn like-btn" data-post-id="<?php echo $post['id']; ?>">
            <i class="far fa-heart"></i> Me gusta
        </button>
        <button class="interaction-btn comment-btn" data-post-id="<?php echo $post['id']; ?>">
            <i class="far fa-comment"></i> Comentar
        </button>
        <button class="interaction-btn share-btn" onclick="sharePost(<?php echo $post['id']; ?>)">
            <i class="far fa-share-square"></i> Compartir
        </button>
    </div>
    
    <!-- Sección de comentarios (inicialmente oculta) -->
    <div class="comments-section" style="display: none;">
        <div class="comments-list"></div>
        <div class="comment-form">
            <input type="text" 
                   class="comment-input" 
                   placeholder="Escribe un comentario..." 
                   data-post-id="<?php echo $post['id']; ?>">
            <button class="comment-submit" data-post-id="<?php echo $post['id']; ?>">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<style>
    .post {
        background: rgba(255,255,255,0.05);
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    
    .post:hover {
        background: rgba(255,255,255,0.07);
    }
    
    .post-user {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 15px;
        position: relative;
    }
    
    .post-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        font-size: 1.2rem;
    }
    
    .post-user-info {
        flex: 1;
    }
    
    .post-username {
        font-weight: 600;
        margin-bottom: 3px;
    }
    
    .post-time {
        font-size: 0.85rem;
        color: #999;
    }
    
    .post-options {
        position: relative;
    }
    
    .post-options-btn {
        background: transparent;
        border: none;
        color: #999;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        transition: all 0.3s;
    }
    
    .post-options-btn:hover {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }
    
    .post-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: #16213e;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        z-index: 100;
        min-width: 150px;
    }
    
    .post-menu button {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 12px 15px;
        background: transparent;
        border: none;
        color: #fff;
        cursor: pointer;
        text-align: left;
        transition: background 0.3s;
    }
    
    .post-menu button:hover {
        background: rgba(255,255,255,0.1);
    }
    
    .post-content {
        margin-bottom: 15px;
        line-height: 1.6;
        word-wrap: break-word;
    }
    
    .post-image {
        width: 100%;
        border-radius: 12px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .post-image:hover {
        opacity: 0.9;
    }
    
    .post-song {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        background: rgba(255,138,0,0.1);
        border-left: 3px solid #ff8a00;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    
    .post-song i {
        color: #ff8a00;
        font-size: 1.2rem;
    }
    
    .song-info {
        flex: 1;
    }
    
    .song-name {
        font-weight: 600;
    }
    
    .song-artist {
        color: #999;
    }
    
    .post-location {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #999;
        font-size: 0.9rem;
        margin-bottom: 15px;
    }
    
    .post-location i {
        color: #ff8a00;
    }
    
    .post-stats {
        display: flex;
        gap: 20px;
        padding: 12px 0;
        border-top: 1px solid rgba(255,255,255,0.1);
        border-bottom: 1px solid rgba(255,255,255,0.1);
        margin-bottom: 12px;
        font-size: 0.9rem;
        color: #999;
    }
    
    .post-interactions {
        display: flex;
        gap: 10px;
    }
    
    .interaction-btn {
        flex: 1;
        background: transparent;
        border: none;
        padding: 10px;
        color: #fff;
        cursor: pointer;
        border-radius: 8px;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .interaction-btn:hover {
        background: rgba(255,255,255,0.05);
    }
    
    .interaction-btn i {
        font-size: 1.1rem;
    }
</style>

<script>
    function togglePostMenu(event, postId) {
        event.stopPropagation();
        const menu = document.getElementById('post-menu-' + postId);
        
        // Cerrar otros menús
        document.querySelectorAll('.post-menu').forEach(m => {
            if (m !== menu) m.style.display = 'none';
        });
        
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }
    
    // Cerrar menús al hacer clic fuera
    document.addEventListener('click', function() {
        document.querySelectorAll('.post-menu').forEach(m => m.style.display = 'none');
    });
    
    function editPost(postId) {
        window.SoundConnect.Utils.showNotification('Función de edición próximamente', 'info');
    }
    
    async function deletePost(postId) {
        if (!confirm('¿Estás seguro de eliminar esta publicación?')) return;
        
        const result = await window.SoundConnect.Utils.fetchAPI(
            `${window.SoundConnect.API_BASE_URL}/posts.php?action=delete`,
            {
                method: 'POST',
                body: JSON.stringify({ post_id: postId })
            }
        );
        
        if (result.success) {
            document.querySelector(`[data-post-id="${postId}"]`).remove();
            window.SoundConnect.Utils.showNotification('Publicación eliminada', 'success');
        } else {
            window.SoundConnect.Utils.showNotification(result.message, 'error');
        }
    }
    
    function sharePost(postId) {
        window.SoundConnect.Utils.showNotification('Función de compartir próximamente', 'info');
    }
    
    function openImageModal(imageUrl) {
        // Crear modal para ver imagen en grande
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            cursor: pointer;
        `;
        
        modal.innerHTML = `<img src="${imageUrl}" style="max-width: 90%; max-height: 90%; border-radius: 10px;">`;
        modal.onclick = () => modal.remove();
        
        document.body.appendChild(modal);
    }
</script>