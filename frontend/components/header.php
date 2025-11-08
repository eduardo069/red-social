<?php
/**
 * header.php - Componente de encabezado reutilizable
 */

// Verificar que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener el nombre de la página actual
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<header>
    <div class="header-container">
        <div class="logo">
            <i class="fas fa-music"></i>
            <h1>SoundConnect</h1>
        </div>
        
        <div class="mobile-menu">
            <i class="fas fa-bars"></i>
        </div>
        
        <nav id="main-nav">
            <ul>
                <li class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">
                    <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                </li>
                <li class="<?php echo $current_page === 'explorar' ? 'active' : ''; ?>">
                    <a href="explorar.php"><i class="fas fa-compass"></i> Explorar</a>
                </li>
                <li class="<?php echo $current_page === 'perfil' ? 'active' : ''; ?>">
                    <a href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a>
                </li>
                <li class="<?php echo $current_page === 'mensajes' ? 'active' : ''; ?>">
                    <a href="mensajes.php"><i class="fas fa-envelope"></i> Mensajes</a>
                </li>
            </ul>
        </nav>
        
        <div class="user-actions">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Buscar música, artistas...">
            </div>
            <div class="user-profile">
                <div class="avatar">
                    <?php 
                    if (isset($_SESSION['foto_perfil']) && !empty($_SESSION['foto_perfil'])) {
                        echo '<img src="../../backend/' . htmlspecialchars($_SESSION['foto_perfil']) . '" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">';
                    } else {
                        echo substr($_SESSION['nombre'], 0, 1); 
                    }
                    ?>
                </div>
                <span><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                <div class="user-menu">
                    <a href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a>
                    <a href="index.php?logout=true"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
    /* Estilos específicos para el componente header */
    nav li.active a {
        color: #ff8a00;
        border-bottom: 2px solid #ff8a00;
    }
    
    .user-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: #16213e;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        padding: 10px 0;
        min-width: 200px;
        display: none;
        z-index: 1000;
    }
    
    .user-menu.active {
        display: block;
    }
    
    .user-menu a {
        display: block;
        padding: 12px 20px;
        color: #fff;
        text-decoration: none;
        transition: background 0.3s;
    }
    
    .user-menu a:hover {
        background: rgba(255,255,255,0.05);
    }
    
    .user-menu a i {
        margin-right: 10px;
        width: 20px;
    }
</style>