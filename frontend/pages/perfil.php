<?php
/**
 * perfil.php - Página de perfil de usuario
 * CON SISTEMA DE AVATARES INTEGRADO
 */

session_start();

require_once __DIR__ . '/../../backend/controllers/AuthController.php';
require_once __DIR__ . '/../../backend/controllers/UserController.php';
require_once __DIR__ . '/../../backend/controllers/PostController.php';
require_once __DIR__ . '/../../backend/controllers/FriendController.php';

$authController = new AuthController();
$userController = new UserController();
$postController = new PostController();
$friendController = new FriendController();

// Verificar autenticación
$sessionCheck = $authController->checkSession();
if (!$sessionCheck['authenticated']) {
    header("Location: index.php");
    exit();
}

$currentUserId = $sessionCheck['user']['user_id'];

// Obtener ID del perfil a mostrar
$profileUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : $currentUserId;
$isOwnProfile = ($profileUserId === $currentUserId);

// Obtener datos del perfil
$profileResult = $userController->getProfile($profileUserId);
if (!$profileResult['success']) {
    header("Location: index.php");
    exit();
}
$profileUser = $profileResult['data'];

// Obtener publicaciones del usuario
$postsResult = $postController->getUserPosts($profileUserId, 20, 0);
$posts = $postsResult['success'] ? $postsResult['data'] : [];

// Obtener estadísticas
$statsResult = $userController->getUserStats($profileUserId);
$stats = $statsResult['data'];

// Verificar estado de amistad si no es el propio perfil
$friendshipStatus = null;
if (!$isOwnProfile) {
    $friendshipResult = $friendController->getFriendshipStatus($currentUserId, $profileUserId);
    if ($friendshipResult['success']) {
        $friendshipStatus = $friendshipResult['data'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profileUser['nombre']); ?> - SoundConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            text-align: center;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        /* NUEVO: Contenedor del avatar con botón */
        .profile-avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            display: inline-block;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            background: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            color: white;
            overflow: hidden;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* NUEVO: Botón de cambiar avatar */
        .change-avatar-btn {
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            opacity: 0;
            white-space: nowrap;
        }
        
        .profile-avatar-container:hover .change-avatar-btn {
            opacity: 1;
        }
        
        .change-avatar-btn:hover {
            background: #ff8a00;
            transform: translateX(-50%) translateY(-2px);
            box-shadow: 0 4px 10px rgba(255, 138, 0, 0.4);
        }
        
        .profile-info h1 {
            margin: 0 0 10px;
            font-size: 2rem;
        }
        
        .profile-username {
            color: rgba(255,255,255,0.8);
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        
        .profile-bio {
            max-width: 600px;
            margin: 0 auto 20px;
            color: rgba(255,255,255,0.9);
        }
        
        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 20px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.8);
        }
        
        .profile-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .profile-btn {
            padding: 10px 25px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: white;
            color: #667eea;
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
        }
        
        .profile-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        
        .profile-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .profile-tabs {
            display: flex;
            gap: 20px;
            border-bottom: 2px solid rgba(255,255,255,0.1);
            margin-bottom: 30px;
        }
        
        .profile-tab {
            padding: 15px 20px;
            cursor: pointer;
            color: #999;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .profile-tab.active {
            color: #ff8a00;
            border-bottom-color: #ff8a00;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <div class="main-content">
        <div class="profile-container">
            <!-- Header del perfil -->
            <div class="profile-header">
                <!-- Avatar con botón de cambio (solo si es tu perfil) -->
                <div class="profile-avatar-container">
                    <div class="profile-avatar">
                        <?php if (!empty($profileUser['foto_perfil'])): ?>
                            <img src="../../backend/<?php echo htmlspecialchars($profileUser['foto_perfil']); ?>" 
                                 alt="Avatar">
                        <?php else: ?>
                            <?php echo strtoupper(substr($profileUser['nombre'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($isOwnProfile): ?>
                        <button class="change-avatar-btn" data-open-avatar-modal>
                            <i class="fas fa-camera"></i> Cambiar foto
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($profileUser['nombre']); ?></h1>
                    <div class="profile-username">@<?php echo htmlspecialchars($profileUser['usuario']); ?></div>
                    
                    <?php if (!empty($profileUser['biografia'])): ?>
                        <div class="profile-bio"><?php echo nl2br(htmlspecialchars($profileUser['biografia'])); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($profileUser['genero_musical_favorito'])): ?>
                        <div style="margin-top: 10px;">
                            <i class="fas fa-music"></i> <?php echo htmlspecialchars($profileUser['genero_musical_favorito']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($profileUser['cancion_estado'])): ?>
                        <div style="margin-top: 10px; font-style: italic;">
                            <i class="fas fa-headphones"></i> <?php echo htmlspecialchars($profileUser['cancion_estado']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Estadísticas -->
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['total_posts']; ?></span>
                        <span class="stat-label">Publicaciones</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['total_friends']; ?></span>
                        <span class="stat-label">Amigos</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['total_likes']; ?></span>
                        <span class="stat-label">Me gusta</span>
                    </div>
                </div>
                
                <!-- Acciones -->
                <div class="profile-actions">
                    <?php if ($isOwnProfile): ?>
                        <button class="profile-btn btn-primary" onclick="window.location.href='editar-perfil.php'">
                            <i class="fas fa-edit"></i> Editar perfil
                        </button>
                    <?php else: ?>
                        <?php if ($friendshipStatus['status'] === 'none'): ?>
                            <button class="profile-btn btn-primary" onclick="sendFriendRequest(<?php echo $profileUserId; ?>)">
                                <i class="fas fa-user-plus"></i> Agregar amigo
                            </button>
                        <?php elseif ($friendshipStatus['status'] === 'pendiente'): ?>
                            <?php if ($friendshipStatus['initiated_by'] == $currentUserId): ?>
                                <button class="profile-btn btn-secondary" disabled>
                                    <i class="fas fa-clock"></i> Solicitud enviada
                                </button>
                            <?php else: ?>
                                <button class="profile-btn btn-primary" onclick="acceptFriendRequest(<?php echo $friendshipStatus['friendship_id']; ?>)">
                                    <i class="fas fa-check"></i> Aceptar solicitud
                                </button>
                            <?php endif; ?>
                        <?php elseif ($friendshipStatus['status'] === 'aceptada'): ?>
                            <button class="profile-btn btn-secondary">
                                <i class="fas fa-user-check"></i> Amigos
                            </button>
                        <?php endif; ?>
                        
                        <button class="profile-btn btn-secondary" onclick="sendMessage(<?php echo $profileUserId; ?>)">
                            <i class="fas fa-envelope"></i> Mensaje
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Contenido del perfil -->
            <div class="profile-content">
                <div class="profile-tabs">
                    <div class="profile-tab active" data-tab="posts">
                        <i class="fas fa-th-large"></i> Publicaciones
                    </div>
                    <div class="profile-tab" data-tab="friends">
                        <i class="fas fa-user-friends"></i> Amigos
                    </div>
                </div>
                
                <!-- Tab de publicaciones -->
                <div class="tab-content" id="posts-tab">
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="post" data-post-id="<?php echo $post['id']; ?>">
                                <div class="post-user">
                                    <div class="post-avatar">
                                        <?php if (!empty($post['foto_perfil'])): ?>
                                            <img src="../../backend/<?php echo htmlspecialchars($post['foto_perfil']); ?>" alt="Avatar">
                                        <?php else: ?>
                                            <?php echo substr($post['nombre'], 0, 1); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="post-user-info">
                                        <div class="post-username"><?php echo htmlspecialchars($post['usuario']); ?></div>
                                        <div class="post-time">
                                            <?php 
                                            $fecha = new DateTime($post['fecha_creacion']);
                                            $ahora = new DateTime();
                                            $diff = $ahora->diff($fecha);
                                            
                                            if ($diff->d > 0) {
                                                echo "Hace " . $diff->d . " día" . ($diff->d > 1 ? "s" : "");
                                            } elseif ($diff->h > 0) {
                                                echo "Hace " . $diff->h . " hora" . ($diff->h > 1 ? "s" : "");
                                            } else {
                                                echo "Hace " . $diff->i . " minuto" . ($diff->i > 1 ? "s" : "");
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="post-content">
                                    <?php echo nl2br(htmlspecialchars($post['contenido'])); ?>
                                </div>
                                
                                <?php if (!empty($post['imagen_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($post['imagen_url']); ?>" alt="Post image" class="post-image">
                                <?php endif; ?>
                                
                                <div class="post-stats">
                                    <span><?php echo $post['total_likes']; ?> me gusta</span>
                                    <span><?php echo $post['total_comentarios']; ?> comentarios</span>
                                </div>
                                <div class="post-interactions">
                                    <button class="interaction-btn like-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="far fa-heart"></i> Me gusta
                                    </button>
                                    <button class="interaction-btn comment-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="far fa-comment"></i> Comentar
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-posts" style="text-align: center; padding: 60px 20px; color: #999;">
                            <i class="fas fa-music" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.3;"></i>
                            <p>No hay publicaciones aún</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab de amigos -->
                <div class="tab-content" id="friends-tab" style="display: none;">
                    <div id="friends-list">Cargando amigos...</div>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <p>SoundConnect &copy; 2025 - Conectando a través de la música</p>
    </footer>
    
    <!-- SCRIPTS -->
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/posts.js"></script>
    <script src="../assets/js/avatar-selector.js"></script>
    
    <script>
        // Tabs del perfil
        document.querySelectorAll('.profile-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const tabName = this.getAttribute('data-tab');
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.style.display = 'none';
                });
                document.getElementById(tabName + '-tab').style.display = 'block';
                
                // Cargar amigos si se selecciona ese tab
                if (tabName === 'friends') {
                    loadFriends(<?php echo $profileUserId; ?>);
                }
            });
        });
        
        // Enviar solicitud de amistad
        async function sendFriendRequest(friendId) {
            try {
                const response = await fetch('/red-social/backend/api/users.php?action=send-friend-request', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ friend_id: friendId })
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('Solicitud enviada');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }
        
        // Aceptar solicitud de amistad
        async function acceptFriendRequest(requestId) {
            try {
                const response = await fetch('/red-social/backend/api/users.php?action=accept-friend-request', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: requestId })
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('Solicitud aceptada');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }
        
        // Cargar lista de amigos
        async function loadFriends(userId) {
            const friendsList = document.getElementById('friends-list');
            friendsList.innerHTML = 'Cargando...';
            
            try {
                const response = await fetch(`/red-social/backend/api/users.php?action=get-friends&user_id=${userId}`);
                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    friendsList.innerHTML = '';
                    result.data.forEach(friend => {
                        const friendDiv = document.createElement('div');
                        friendDiv.className = 'friend-item';
                        friendDiv.style.cssText = 'padding: 15px; margin-bottom: 10px; background: rgba(255,255,255,0.05); border-radius: 10px; cursor: pointer;';
                        friendDiv.innerHTML = `
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; background: #ff8a00; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                    ${friend.foto_perfil ? 
                                        `<img src="../../backend/${friend.foto_perfil}" style="width:100%; height:100%; object-fit:cover;">` : 
                                        friend.nombre[0]}
                                </div>
                                <div>
                                    <div style="font-weight: 600;">${friend.nombre}</div>
                                    <div style="font-size: 0.85rem; color: #999;">@${friend.usuario}</div>
                                </div>
                            </div>
                        `;
                        friendDiv.onclick = () => window.location.href = `perfil.php?user_id=${friend.user_id}`;
                        friendsList.appendChild(friendDiv);
                    });
                } else {
                    friendsList.innerHTML = '<p style="text-align: center; color: #999;">No hay amigos aún</p>';
                }
            } catch (error) {
                console.error('Error:', error);
                friendsList.innerHTML = '<p style="text-align: center; color: #e74c3c;">Error al cargar amigos</p>';
            }
        }
        
        function sendMessage(userId) {
            window.location.href = `mensajes.php?user_id=${userId}`;
        }
        
        // Inicializar posts
        if (typeof initializePosts === 'function') {
            initializePosts();
        }
    </script>
</body>
</html>