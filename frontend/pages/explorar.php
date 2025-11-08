<?php
/**
 * explorar.php - Página de exploración y descubrimiento
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
            cursor: pointer;
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
        }
        
        .user-card-btn:hover {
            background: #e67700;
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
        
        .genre-tag:hover {
            background: #ff8a00;
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
            <p>Descubre nuevos amigos y conecta con personas que comparten tu pasión por la música</p>
        </div>
        
        <div class="search-section">
            <div class="search-box">
                <input type="text" id="explore-search" placeholder="Buscar usuarios, géneros musicales...">
                <button onclick="searchExplore()">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>
        </div>
        
        <div class="explore-tabs">
            <div class="explore-tab active" data-filter="all" onclick="filterUsers('all')">
                <i class="fas fa-users"></i> Todos
            </div>
            <div class="explore-tab" data-filter="popular" onclick="filterUsers('popular')">
                <i class="fas fa-fire"></i> Populares
            </div>
            <div class="explore-tab" data-filter="online" onclick="filterUsers('online')">
                <i class="fas fa-circle"></i> En línea
            </div>
            <div class="explore-tab" data-filter="suggested" onclick="filterUsers('suggested')">
                <i class="fas fa-magic"></i> Sugeridos
            </div>
        </div>
        
        <div class="genre-tags">
            <div class="genre-tag" onclick="filterByGenre('Jazz')">🎷 Jazz</div>
            <div class="genre-tag" onclick="filterByGenre('Rock')">🎸 Rock</div>
            <div class="genre-tag" onclick="filterByGenre('Pop')">🎤 Pop</div>
            <div class="genre-tag" onclick="filterByGenre('Clásica')">🎻 Clásica</div>
            <div class="genre-tag" onclick="filterByGenre('Electrónica')">🎧 Electrónica</div>
            <div class="genre-tag" onclick="filterByGenre('Hip Hop')">🎵 Hip Hop</div>
            <div class="genre-tag" onclick="filterByGenre('Reggae')">🥁 Reggae</div>
            <div class="genre-tag" onclick="filterByGenre('Blues')">🎺 Blues</div>
        </div>
        
        <div id="results-container">
            <div class="results-grid" id="users-grid">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                    <p>Cargando usuarios...</p>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <p>SoundConnect &copy; 2025 - Conectando a través de la música</p>
    </footer>
    
    <script src="../assets/js/main.js"></script>
    <script>
        const { Utils, API_BASE_URL } = window.SoundConnect;
        let currentFilter = 'all';
        let currentGenre = null;
        
        // Cargar usuarios al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
            
            // Búsqueda con enter
            document.getElementById('explore-search').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchExplore();
                }
            });
        });
        
        // Cargar usuarios
        async function loadUsers(query = '') {
            const usersGrid = document.getElementById('users-grid');
            usersGrid.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i><p>Cargando...</p></div>';
            
            let url = `${API_BASE_URL}/users.php?action=search&query=${query || 'a'}&limit=50`;
            
            const result = await Utils.fetchAPI(url);
            
            if (result.success && result.data.length > 0) {
                displayUsers(result.data);
            } else {
                usersGrid.innerHTML = `
                    <div class="loading">
                        <i class="fas fa-users" style="font-size: 3rem; opacity: 0.3;"></i>
                        <p>No se encontraron usuarios</p>
                    </div>
                `;
            }
        }
        
        // Mostrar usuarios
        function displayUsers(users) {
            const usersGrid = document.getElementById('users-grid');
            usersGrid.innerHTML = '';
            
            // Aplicar filtros
            let filteredUsers = users;
            
            if (currentFilter === 'online') {
                filteredUsers = users.filter(u => u.estado === 'online');
            } else if (currentFilter === 'popular') {
                // Ordenar por algún criterio de popularidad
                filteredUsers = users.slice(0, 12);
            }
            
            if (currentGenre) {
                filteredUsers = filteredUsers.filter(u => 
                    u.genero_musical_favorito && u.genero_musical_favorito.includes(currentGenre)
                );
            }
            
            if (filteredUsers.length === 0) {
                usersGrid.innerHTML = '<div class="loading"><p>No se encontraron usuarios con estos filtros</p></div>';
                return;
            }
            
            filteredUsers.forEach(user => {
                const userCard = createUserCard(user);
                usersGrid.appendChild(userCard);
            });
        }
        
        // Crear card de usuario
        function createUserCard(user) {
            const card = document.createElement('div');
            card.className = 'user-card';
            
            const avatarContent = user.foto_perfil ? 
                `<img src="../../backend/${user.foto_perfil}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">` :
                Utils.getInitials(user.nombre);
            
            const statusDot = user.estado === 'online' ? 
                '<span style="position: absolute; bottom: 5px; right: 5px; width: 15px; height: 15px; background: #4CAF50; border: 2px solid white; border-radius: 50%;"></span>' : '';
            
            card.innerHTML = `
                <div class="user-card-avatar" style="position: relative;">
                    ${avatarContent}
                    ${statusDot}
                </div>
                <h3>${Utils.escapeHtml(user.nombre)}</h3>
                <div class="username">@${Utils.escapeHtml(user.usuario)}</div>
                ${user.biografia ? `<div class="bio">${Utils.escapeHtml(user.biografia)}</div>` : ''}
                ${user.genero_musical_favorito ? 
                    `<div style="margin: 10px 0; color: #ff8a00;"><i class="fas fa-music"></i> ${Utils.escapeHtml(user.genero_musical_favorito)}</div>` : ''}
                <button class="user-card-btn" onclick="viewProfile(${user.id})">
                    Ver perfil
                </button>
            `;
            
            return card;
        }
        
        // Filtrar usuarios
        function filterUsers(filter) {
            currentFilter = filter;
            
            // Actualizar tabs activos
            document.querySelectorAll('.explore-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`[data-filter="${filter}"]`).classList.add('active');
            
            // Recargar con filtro
            const searchQuery = document.getElementById('explore-search').value.trim();
            loadUsers(searchQuery);
        }
        
        // Filtrar por género
        function filterByGenre(genre) {
            currentGenre = currentGenre === genre ? null : genre;
            
            // Resaltar género seleccionado
            document.querySelectorAll('.genre-tag').forEach(tag => {
                if (tag.textContent.includes(genre)) {
                    tag.style.background = currentGenre ? '#ff8a00' : 'rgba(255,138,0,0.2)';
                }
            });
            
            const searchQuery = document.getElementById('explore-search').value.trim();
            loadUsers(searchQuery);
        }
        
        // Buscar
        function searchExplore() {
            const query = document.getElementById('explore-search').value.trim();
            if (query.length >= 2) {
                currentGenre = null;
                loadUsers(query);
            } else {
                Utils.showNotification('Escribe al menos 2 caracteres', 'error');
            }
        }
        
        // Ver perfil
        function viewProfile(userId) {
            window.location.href = `perfil.php?user_id=${userId}`;
        }
    </script>
</body>
</html>