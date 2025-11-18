<?php
/**
 * explorar.php - Página de exploración y descubrimiento
 * ACTUALIZADO CON MÚSICA - 17/11/2025
 */

session_start();

require_once __DIR__ . '/../../backend/controllers/AuthController.php';
require_once __DIR__ . '/../../backend/controllers/UserController.php';
require_once __DIR__ . '/../../backend/controllers/PostController.php';

$authController = new AuthController();
$userController = new UserController();
$postController = new PostController();

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
    <title>Explorar - SoundConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .explore-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            text-align: center;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .explore-header h1 {
            margin: 0 0 10px;
            font-size: 2.5rem;
        }
        
        .explore-header p {
            color: rgba(255,255,255,0.9);
        }
        
        /* NUEVO: Tabs principales */
        .main-tabs {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .main-tab {
            padding: 15px 40px;
            background: rgba(255,255,255,0.05);
            border: 3px solid transparent;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .main-tab.active {
            background: linear-gradient(135deg, #ff8a00, #e52e71);
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(255, 138, 0, 0.4);
        }
        
        .main-tab:hover {
            transform: translateY(-2px);
        }
        
        .main-tab i {
            margin-right: 8px;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .search-section {
            max-width: 600px;
            margin: 0 auto 30px;
        }
        
        .search-box {
            display: flex;
            background: rgba(255,255,255,0.1);
            border-radius: 30px;
            padding: 5px;
            border: 2px solid rgba(255,255,255,0.2);
        }
        
        .search-box input {
            flex: 1;
            background: transparent;
            border: none;
            padding: 12px 20px;
            color: white;
            font-size: 1rem;
        }
        
        .search-box input::placeholder {
            color: rgba(255,255,255,0.6);
        }
        
        .search-box input:focus {
            outline: none;
        }
        
        .search-box button {
            background: white;
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .search-box button:hover {
            transform: scale(1.05);
        }
        
        .explore-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .explore-tab {
            padding: 12px 25px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            color: #fff;
        }
        
        .explore-tab.active {
            background: #ff8a00;
            border-color: #ff8a00;
        }
        
        .explore-tab:hover {
            border-color: #ff8a00;
        }
        
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .user-card {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .user-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.08);
        }
        
        .user-card-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            color: white;
        }
        
        .user-card h3 {
            margin: 0 0 5px;
            font-size: 1.2rem;
        }
        
        .user-card .username {
            color: #999;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .user-card .bio {
            color: #ddd;
            font-size: 0.85rem;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .user-card-btn {
            background: #ff8a00;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin: 5px;
        }
        
        .user-card-btn:hover {
            background: #e67700;
            transform: translateY(-2px);
        }
        
        .genre-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }
        
        .genre-tag {
            padding: 8px 15px;
            background: rgba(255,138,0,0.2);
            border: 1px solid #ff8a00;
            border-radius: 15px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .genre-tag:hover, .genre-tag.active {
            background: #ff8a00;
        }
        
        /* NUEVO: Estilos para música */
        .music-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .song-card {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .song-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.08);
        }
        
        .song-card-cover {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .song-card-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .song-card-cover .play-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .song-card:hover .play-overlay {
            opacity: 1;
        }
        
        .play-overlay button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #ff8a00;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .play-overlay button:hover {
            transform: scale(1.1);
        }
        
        .song-card-info {
            padding: 15px;
        }
        
        .song-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 5px;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .song-card-artist {
            color: #aaa;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .song-card-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #666;
            font-size: 0.85rem;
            margin-top: 10px;
        }
        
        .song-card-stats i {
            color: #ff8a00;
            margin-right: 5px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <div class="main-content">
        <div class="explore-header">
            <h1><i class="fas fa-compass"></i> Explorar</h1>
            <p>Descubre nuevos amigos y música increíble</p>
        </div>
        
        <!-- NUEVO: Tabs principales -->
        <div class="main-tabs">
            <div class="main-tab active" data-main-tab="users" onclick="switchMainTab('users')">
                <i class="fas fa-users"></i> Usuarios
            </div>
            <div class="main-tab" data-main-tab="music" onclick="switchMainTab('music')">
                <i class="fas fa-music"></i> Música
            </div>
        </div>
        
        <!-- ========================================== -->
        <!-- TAB: USUARIOS -->
        <!-- ========================================== -->
        <div id="users-content" class="tab-content active">
            <div class="search-section">
                <div class="search-box">
                    <input type="text" id="users-search" placeholder="Buscar usuarios...">
                    <button onclick="searchUsers()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </div>
            
            <div class="explore-tabs">
                <div class="explore-tab active" data-filter="all" onclick="filterUsers('all')">
                    <i class="fas fa-users"></i> Todos
                </div>
                <div class="explore-tab" data-filter="online" onclick="filterUsers('online')">
                    <i class="fas fa-circle"></i> En línea
                </div>
            </div>
            
            <div class="genre-tags">
                <div class="genre-tag" onclick="filterUsersByGenre('Jazz')">🎷 Jazz</div>
                <div class="genre-tag" onclick="filterUsersByGenre('Rock')">🎸 Rock</div>
                <div class="genre-tag" onclick="filterUsersByGenre('Pop')">🎤 Pop</div>
                <div class="genre-tag" onclick="filterUsersByGenre('Electrónica')">🎧 Electrónica</div>
                <div class="genre-tag" onclick="filterUsersByGenre('Hip Hop')">🎵 Hip Hop</div>
                <div class="genre-tag" onclick="filterUsersByGenre('Reggaeton')">🥁 Reggaeton</div>
            </div>
            
            <div class="results-grid" id="users-grid">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                    <p>Cargando usuarios...</p>
                </div>
            </div>
        </div>
        
        <!-- ========================================== -->
        <!-- TAB: MÚSICA -->
        <!-- ========================================== -->
        <div id="music-content" class="tab-content">
            <div class="search-section">
                <div class="search-box">
                    <input type="text" id="music-search" placeholder="Buscar canciones, artistas...">
                    <button onclick="searchMusic()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </div>
            
            <div class="explore-tabs">
                <div class="explore-tab active" data-music-filter="all" onclick="filterMusic('all')">
                    <i class="fas fa-music"></i> Todas
                </div>
                <div class="explore-tab" data-music-filter="trending" onclick="filterMusic('trending')">
                    <i class="fas fa-fire"></i> Trending
                </div>
            </div>
            
            <div class="genre-tags">
                <div class="genre-tag" onclick="filterMusicByGenre('Rock')">🎸 Rock</div>
                <div class="genre-tag" onclick="filterMusicByGenre('Pop')">🎤 Pop</div>
                <div class="genre-tag" onclick="filterMusicByGenre('Hip Hop')">🎵 Hip Hop</div>
                <div class="genre-tag" onclick="filterMusicByGenre('Electrónica')">🎧 Electrónica</div>
                <div class="genre-tag" onclick="filterMusicByGenre('Jazz')">🎷 Jazz</div>
                <div class="genre-tag" onclick="filterMusicByGenre('Reggaeton')">🥁 Reggaeton</div>
                <div class="genre-tag" onclick="filterMusicByGenre('Indie')">🎸 Indie</div>
                <div class="genre-tag" onclick="filterMusicByGenre('R&B')">🎤 R&B</div>
            </div>
            
            <div class="music-grid" id="music-grid">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                    <p>Cargando música...</p>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <p>SoundConnect &copy; 2025 - Conectando a través de la música</p>
    </footer>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/friends.js"></script>
    <script src="../assets/js/music-player.js"></script>
    <script>
        // ✅ SOLUCIÓN: No redeclarar, usar directamente desde window
        const SC = window.SoundConnect; // Alias corto
        const currentUserId = <?php echo $currentUserId; ?>;
        
        let currentUserFilter = 'all';
        let currentUserGenre = null;
        let currentMusicFilter = 'all';
        let currentMusicGenre = null;
        
        // ==========================================
        // INICIALIZACIÓN
        // ==========================================
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
            
            // Búsqueda con enter
            document.getElementById('users-search').addEventListener('keypress', e => {
                if (e.key === 'Enter') searchUsers();
            });
            
            document.getElementById('music-search').addEventListener('keypress', e => {
                if (e.key === 'Enter') searchMusic();
            });
        });
        
        // ==========================================
        // TABS PRINCIPALES
        // ==========================================
        function switchMainTab(tab) {
            // Actualizar tabs
            document.querySelectorAll('.main-tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`[data-main-tab="${tab}"]`).classList.add('active');
            
            // Mostrar contenido
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById(`${tab}-content`).classList.add('active');
            
            // Cargar datos si es la primera vez
            if (tab === 'music') {
                const musicGrid = document.getElementById('music-grid');
                if (musicGrid.querySelector('.loading')) {
                    loadMusic();
                }
            }
        }
        
        // ==========================================
        // USUARIOS
        // ==========================================
        async function loadUsers(query = '') {
            const usersGrid = document.getElementById('users-grid');
            usersGrid.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i><p>Cargando...</p></div>';
            
            // ✅ NO enviar query si está vacío
            let url = `${SC.API_BASE_URL}/users.php?action=search&limit=50`;
            if (query && query.trim().length > 0) {
                url += `&query=${encodeURIComponent(query)}`;
            }
            
            const result = await SC.Utils.fetchAPI(url);
            
            if (result.success && result.data.length > 0) {
                displayUsers(result.data);
            } else {
                usersGrid.innerHTML = '<div class="loading"><i class="fas fa-users" style="font-size: 3rem; opacity: 0.3;"></i><p>No se encontraron usuarios</p></div>';
            }
        }
        
        function displayUsers(users) {
            const usersGrid = document.getElementById('users-grid');
            usersGrid.innerHTML = '';
            
            let filtered = users.filter(u => u.id !== currentUserId);
            
            if (currentUserFilter === 'online') {
                filtered = filtered.filter(u => u.estado === 'online');
            }
            
            if (currentUserGenre) {
                filtered = filtered.filter(u => 
                    u.genero_musical_favorito && u.genero_musical_favorito.includes(currentUserGenre)
                );
            }
            
            if (filtered.length === 0) {
                usersGrid.innerHTML = '<div class="loading"><p>No hay usuarios con estos filtros</p></div>';
                return;
            }
            
            filtered.forEach(user => usersGrid.appendChild(createUserCard(user)));
            
            setTimeout(() => window.FriendshipSystem?.updateButtons(), 500);
        }
        
        function createUserCard(user) {
            const card = document.createElement('div');
            card.className = 'user-card';
            
            const avatarContent = user.foto_perfil ? 
                `<img src="../../backend/${user.foto_perfil}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">` :
                SC.Utils.getInitials(user.nombre);
            
            const statusDot = user.estado === 'online' ? 
                '<span style="position: absolute; bottom: 5px; right: 5px; width: 15px; height: 15px; background: #4CAF50; border: 2px solid white; border-radius: 50%;"></span>' : '';
            
            card.innerHTML = `
                <div class="user-card-avatar" style="position: relative;">
                    ${avatarContent}
                    ${statusDot}
                </div>
                <h3>${SC.Utils.escapeHtml(user.nombre)}</h3>
                <div class="username">@${SC.Utils.escapeHtml(user.usuario)}</div>
                ${user.biografia ? `<div class="bio">${SC.Utils.escapeHtml(user.biografia)}</div>` : ''}
                ${user.genero_musical_favorito ? 
                    `<div style="margin: 10px 0; color: #ff8a00;"><i class="fas fa-music"></i> ${SC.Utils.escapeHtml(user.genero_musical_favorito)}</div>` : ''}
                
                <div style="display: flex; gap: 10px; justify-content: center; margin-top: 15px; flex-wrap: wrap;">
                    <button class="user-card-btn" onclick="viewProfile(${user.id})">
                        <i class="fas fa-eye"></i> Ver perfil
                    </button>
                    <button class="user-card-btn friend-action-btn" 
                            data-friend-action 
                            data-user-id="${user.id}"
                            data-current-user="${currentUserId}"
                            style="background: #667eea;">
                        <i class="fas fa-user-plus"></i> Agregar
                    </button>
                </div>
            `;
            
            return card;
        }
        
        function filterUsers(filter) {
            currentUserFilter = filter;
            document.querySelectorAll('.explore-tab[data-filter]').forEach(t => t.classList.remove('active'));
            document.querySelector(`[data-filter="${filter}"]`).classList.add('active');
            loadUsers(document.getElementById('users-search').value.trim());
        }
        
        function filterUsersByGenre(genre) {
            currentUserGenre = currentUserGenre === genre ? null : genre;
            document.querySelectorAll('#users-content .genre-tag').forEach(tag => {
                tag.classList.toggle('active', tag.textContent.includes(genre) && currentUserGenre);
            });
            loadUsers(document.getElementById('users-search').value.trim());
        }
        
        function searchUsers() {
            const query = document.getElementById('users-search').value.trim();
            if (query.length >= 2) {
                currentUserGenre = null;
                loadUsers(query);
            } else {
                SC.Utils.showNotification('Escribe al menos 2 caracteres', 'error');
            }
        }
        
        function viewProfile(userId) {
            window.location.href = `perfil.php?user_id=${userId}`;
        }
        
        // ==========================================
        // MÚSICA
        // ==========================================
        async function loadMusic(endpoint = 'all', params = '') {
            const musicGrid = document.getElementById('music-grid');
            musicGrid.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i><p>Cargando música...</p></div>';
            
            let url = `/red-social/backend/api/music.php?action=${endpoint}`;
            if (params) url += '&' + params;
            
            const result = await SC.Utils.fetchAPI(url);
            
            if (result.success && result.data.length > 0) {
                displayMusic(result.data);
            } else {
                musicGrid.innerHTML = '<div class="loading"><i class="fas fa-music" style="font-size: 3rem; opacity: 0.3;"></i><p>No se encontraron canciones</p></div>';
            }
        }
        
        function displayMusic(songs) {
            const musicGrid = document.getElementById('music-grid');
            musicGrid.innerHTML = '';
            
            songs.forEach(song => musicGrid.appendChild(createSongCard(song)));
            
            // Inicializar reproductores
            setTimeout(() => window.MusicPlayer?.init(), 500);
        }
        
        function createSongCard(song) {
            const card = document.createElement('div');
            card.className = 'song-card';
            
            const coverUrl = song.portada_url 
                ? `/red-social/backend/${song.portada_url}` 
                : '../assets/images/default-cover.jpg';
            
            card.innerHTML = `
                <div class="song-card-cover">
                    <img src="${coverUrl}" alt="${SC.Utils.escapeHtml(song.titulo)}">
                    <div class="play-overlay">
                        <button onclick="playSongInline(${song.id})">
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
                </div>
                <div class="song-card-info">
                    <div class="song-card-title">${SC.Utils.escapeHtml(song.titulo)}</div>
                    <div class="song-card-artist">${SC.Utils.escapeHtml(song.artista)}</div>
                    ${song.genero ? `<div style="color: #ff8a00; font-size: 0.85rem;"><i class="fas fa-tag"></i> ${SC.Utils.escapeHtml(song.genero)}</div>` : ''}
                    <div class="song-card-stats">
                        <span><i class="fas fa-play-circle"></i> ${song.reproducciones || 0}</span>
                        <span><i class="fas fa-heart"></i> ${song.likes || 0}</span>
                    </div>
                </div>
            `;
            
            return card;
        }
        
        function playSongInline(songId) {
            // Crear modal con reproductor completo
            const modal = document.createElement('div');
            modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:10000;display:flex;align-items:center;justify-content:center;';
            modal.innerHTML = `
                <div style="background:#1a1a2e;border-radius:20px;padding:30px;max-width:500px;width:90%;">
                    <div class="music-player" data-song-id="${songId}" data-audio-url="..." id="modal-player-${songId}"></div>
                    <button onclick="this.closest('div[style]').remove()" style="margin-top:20px;width:100%;padding:12px;background:#ff8a00;border:none;border-radius:10px;color:white;font-weight:600;cursor:pointer;">Cerrar</button>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Cargar datos de la canción y crear reproductor
            fetch(`/red-social/backend/api/music.php?action=get&id=${songId}`)
                .then(r => r.json())
                .then(result => {
                    if (result.success) {
                        const playerDiv = document.getElementById(`modal-player-${songId}`);
                        playerDiv.outerHTML = window.MusicPlayer.createHTML(result.data);
                        window.MusicPlayer.init();
                    }
                });
        }
        
        function filterMusic(filter) {
            currentMusicFilter = filter;
            document.querySelectorAll('.explore-tab[data-music-filter]').forEach(t => t.classList.remove('active'));
            document.querySelector(`[data-music-filter="${filter}"]`).classList.add('active');
            
            if (filter === 'trending') {
                loadMusic('trending', 'limit=20');
            } else {
                loadMusic('all', 'limit=50');
            }
        }
        
        function filterMusicByGenre(genre) {
            currentMusicGenre = currentMusicGenre === genre ? null : genre;
            document.querySelectorAll('#music-content .genre-tag').forEach(tag => {
                tag.classList.toggle('active', tag.textContent.includes(genre) && currentMusicGenre);
            });
            
            if (currentMusicGenre) {
                loadMusic('by-genre', `genre=${encodeURIComponent(genre)}&limit=50`);
            } else {
                loadMusic('all', 'limit=50');
            }
        }
        
        function searchMusic() {
            const query = document.getElementById('music-search').value.trim();
            if (query.length >= 2) {
                currentMusicGenre = null;
                loadMusic('search', `q=${encodeURIComponent(query)}&limit=50`);
            } else {
                SC.Utils.showNotification('Escribe al menos 2 caracteres', 'error');
            }
        }
    </script>
</body>
</html>