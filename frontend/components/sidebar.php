<?php
/**
 * sidebar.php - Componente de panel lateral (amigos, sugerencias)
 */

// Asegurarse de que tenemos los controladores necesarios
if (!isset($friendController)) {
    require_once __DIR__ . '/../../backend/controllers/FriendController.php';
    $friendController = new FriendController();
}

if (!isset($currentUserId)) {
    $currentUserId = $_SESSION['user_id'] ?? null;
}

// Obtener amigos si tenemos el ID del usuario
$friends = [];
if ($currentUserId) {
    $friendsResult = $friendController->getFriends($currentUserId);
    $friends = $friendsResult['success'] ? $friendsResult['data'] : [];
}
?>

<div class="sidebar">
    <!-- Sección de Amigos -->
    <div class="sidebar-section">
        <h2><i class="fas fa-user-friends"></i> Amigos</h2>
        
        <?php if (!empty($friends)): ?>
            <div class="friends-list">
                <?php 
                // Mostrar solo los primeros 8 amigos
                $displayFriends = array_slice($friends, 0, 8);
                foreach ($displayFriends as $friend): 
                ?>
                    <div class="friend-item" onclick="window.location.href='perfil.php?user_id=<?php echo $friend['user_id']; ?>'">
                        <div class="friend-avatar">
                            <?php if (!empty($friend['foto_perfil'])): ?>
                                <img src="../../backend/<?php echo htmlspecialchars($friend['foto_perfil']); ?>" alt="Avatar">
                            <?php else: ?>
                                <?php echo substr($friend['nombre'], 0, 1); ?>
                            <?php endif; ?>
                            <span class="status-indicator <?php echo $friend['estado']; ?>"></span>
                        </div>
                        <div class="friend-info">
                            <div class="friend-name"><?php echo htmlspecialchars($friend['nombre']); ?></div>
                            <div class="friend-status <?php echo $friend['estado']; ?>">
                                <?php 
                                if ($friend['estado'] == 'online') {
                                    echo "En línea";
                                    if (!empty($friend['cancion_estado'])) {
                                        echo " - " . htmlspecialchars(substr($friend['cancion_estado'], 0, 30));
                                        if (strlen($friend['cancion_estado']) > 30) echo "...";
                                    }
                                } elseif ($friend['estado'] == 'ausente') {
                                    echo "Ausente";
                                } else {
                                    echo "Desconectado";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($friends) > 8): ?>
                    <a href="perfil.php" class="view-all-friends">
                        Ver todos (<?php echo count($friends); ?>)
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="no-friends">
                <i class="fas fa-users" style="font-size: 2rem; opacity: 0.3; margin-bottom: 10px;"></i>
                <p>Aún no tienes amigos</p>
                <button onclick="window.location.href='explorar.php'" class="sidebar-btn">
                    <i class="fas fa-search"></i> Explorar usuarios
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Sección de Sugerencias -->
    <div class="sidebar-section">
        <h2><i class="fas fa-star"></i> Sugerencias</h2>
        <div class="suggestions">
            <div class="suggestion-item">
                <i class="fas fa-music"></i>
                <div>
                    <strong>Comparte tu música</strong>
                    <p>Crea una publicación con tu canción favorita</p>
                </div>
            </div>
            <div class="suggestion-item">
                <i class="fas fa-user-plus"></i>
                <div>
                    <strong>Conecta con otros</strong>
                    <p>Explora y agrega nuevos amigos</p>
                </div>
            </div>
            <div class="suggestion-item">
                <i class="fas fa-comment"></i>
                <div>
                    <strong>Participa</strong>
                    <p>Comenta y da like a las publicaciones</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .sidebar {
        background: rgba(255,255,255,0.03);
        border-radius: 15px;
        padding: 20px;
    }
    
    .sidebar-section {
        margin-bottom: 30px;
    }
    
    .sidebar-section:last-child {
        margin-bottom: 0;
    }
    
    .sidebar-section h2 {
        margin: 0 0 20px;
        font-size: 1.2rem;
        color: #fff;
    }
    
    .sidebar-section h2 i {
        margin-right: 10px;
        color: #ff8a00;
    }
    
    .friends-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .friend-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .friend-item:hover {
        background: rgba(255,255,255,0.05);
    }
    
    .friend-avatar {
        width: 45px;
        height: 45px;
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
    
    .friend-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .status-indicator {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #1a1a2e;
    }
    
    .status-indicator.online {
        background: #4CAF50;
    }
    
    .status-indicator.ausente {
        background: #ff9800;
    }
    
    .status-indicator.offline {
        background: #999;
    }
    
    .friend-info {
        flex: 1;
        min-width: 0;
    }
    
    .friend-name {
        font-weight: 600;
        margin-bottom: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .friend-status {
        font-size: 0.8rem;
        color: #999;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .friend-status.online {
        color: #4CAF50;
    }
    
    .no-friends {
        text-align: center;
        padding: 30px 20px;
        color: #999;
    }
    
    .no-friends p {
        margin: 10px 0 15px;
    }
    
    .sidebar-btn {
        background: #ff8a00;
        border: none;
        padding: 10px 20px;
        border-radius: 20px;
        color: white;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .sidebar-btn:hover {
        background: #e67700;
        transform: translateY(-2px);
    }
    
    .view-all-friends {
        display: block;
        text-align: center;
        padding: 10px;
        color: #ff8a00;
        text-decoration: none;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s;
    }
    
    .view-all-friends:hover {
        background: rgba(255,138,0,0.1);
    }
    
    .suggestions {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .suggestion-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px;
        background: rgba(255,138,0,0.1);
        border-radius: 10px;
        border-left: 3px solid #ff8a00;
    }
    
    .suggestion-item i {
        font-size: 1.5rem;
        color: #ff8a00;
        margin-top: 3px;
    }
    
    .suggestion-item strong {
        display: block;
        margin-bottom: 4px;
        color: #fff;
    }
    
    .suggestion-item p {
        margin: 0;
        font-size: 0.85rem;
        color: #999;
    }
</style>