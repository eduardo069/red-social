<?php
/**
 * Index.php - Página principal de SoundConnect
 * VERSIÓN CORREGIDA - 07/11/2025
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar sesión
session_start();

// Incluir controladores
require_once __DIR__ . '/../../backend/controllers/AuthController.php';
require_once __DIR__ . '/../../backend/controllers/PostController.php';
require_once __DIR__ . '/../../backend/controllers/FriendController.php';

// Inicializar controladores
$authController = new AuthController();
$postController = new PostController();
$friendController = new FriendController();

// Variables para mensajes
$mensaje_registro = '';
$error_login = '';

// DEBUG: Log de POST
error_log("POST Data: " . print_r($_POST, true));

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registro'])) {
    error_log("=== PROCESANDO REGISTRO ===");
    
    $data = [
        'usuario' => $_POST['usuario'] ?? '',
        'nombre' => $_POST['nombre'] ?? '',
        'clave' => $_POST['clave'] ?? '',
        'correo' => $_POST['correo'] ?? ''
    ];
    
    error_log("Datos registro: " . print_r($data, true));
    
    $resultado = $authController->register($data);
    
    error_log("Resultado registro: " . print_r($resultado, true));
    
    if ($resultado['success']) {
        header("Location: index.php?registro=exitoso");
        exit();
    } else {
        $mensaje_registro = $resultado['message'];
    }
}

// Procesar formulario de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    error_log("=== PROCESANDO LOGIN ===");
    
    $usuario = $_POST['usuario'] ?? '';
    $clave = $_POST['clave'] ?? '';
    
    error_log("Usuario: $usuario");
    
    $resultado = $authController->login($usuario, $clave);
    
    error_log("Resultado login: " . print_r($resultado, true));
    
    if ($resultado['success']) {
        error_log("Login exitoso, redirigiendo...");
        header("Location: index.php");
        exit();
    } else {
        $error_login = $resultado['message'];
        error_log("Error login: " . $error_login);
    }
}

// Cerrar sesión
if (isset($_GET['logout'])) {
    $authController->logout();
    header("Location: index.php");
    exit();
}

// Verificar si el usuario está autenticado
$sessionCheck = $authController->checkSession();
$usuario_autenticado = $sessionCheck['authenticated'];

// Mostrar mensaje de registro exitoso
if (isset($_GET['registro']) && $_GET['registro'] == 'exitoso') {
    $mensaje_registro = "✅ Registro exitoso. Ahora puedes iniciar sesión.";
}

// Si está autenticado, obtener datos adicionales
$posts = [];
$friends = [];
if ($usuario_autenticado) {
    $userId = $sessionCheck['user']['user_id'];
    
    // Obtener feed de publicaciones
    $feedResult = $postController->getFeed(20, 0);
    if ($feedResult['success']) {
        $posts = $feedResult['data'];
    }
    
    // Obtener lista de amigos
    $friendsResult = $friendController->getFriends($userId);
    if ($friendsResult['success']) {
        $friends = $friendsResult['data'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoundConnect - Tu Red Social Musical</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php if (!$usuario_autenticado): ?>
    <!-- PANTALLA COMPLETA DE AUTENTICACIÓN -->
    <div class="fullscreen-auth">
        <div class="auth-background">
            <div class="auth-overlay"></div>
            <div class="auth-content">
                <div class="auth-logo">
                    <i class="fas fa-music"></i>
                    <h1>SoundConnect</h1>
                    <p>Conectando a través de la música</p>
                </div>
                
                <div class="auth-container">
                    <div class="auth-box">
                        <div class="auth-tabs">
                            <div class="auth-tab active" data-tab="login">Iniciar Sesión</div>
                            <div class="auth-tab" data-tab="register">Registrarse</div>
                        </div>
                        
                        <!-- Formulario de inicio de sesión -->
                        <form class="auth-form active" id="login-form" method="POST" action="index.php">
                            <div class="input-group">
                                <i class="fas fa-user"></i>
                                <input type="text" name="usuario" placeholder="Usuario o correo electrónico" required>
                            </div>
                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="clave" placeholder="Contraseña" required>
                            </div>
                            <!-- IMPORTANTE: Este campo hidden es lo que activa el if isset($_POST['login']) -->
                            <input type="hidden" name="login" value="1">
                            <button type="submit" class="auth-btn">Iniciar Sesión</button>
                            
                            <?php if (!empty($error_login)): ?>
                            <div class="auth-message error"><?php echo htmlspecialchars($error_login); ?></div>
                            <?php endif; ?>
                            
                            <?php if (!empty($mensaje_registro) && strpos($mensaje_registro, 'exitoso') !== false): ?>
                            <div class="auth-message success"><?php echo htmlspecialchars($mensaje_registro); ?></div>
                            <?php endif; ?>
                        </form>
                        
                        <!-- Formulario de registro -->
                        <form class="auth-form" id="register-form" method="POST" action="index.php">
                            <div class="input-group">
                                <i class="fas fa-user"></i>
                                <input type="text" name="usuario" placeholder="Usuario" required>
                            </div>
                            <div class="input-group">
                                <i class="fas fa-id-card"></i>
                                <input type="text" name="nombre" placeholder="Nombre completo" required>
                            </div>
                            <div class="input-group">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="correo" placeholder="Correo electrónico" required>
                            </div>
                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="clave" placeholder="Contraseña" required>
                            </div>
                            <!-- IMPORTANTE: Este campo hidden es lo que activa el if isset($_POST['registro']) -->
                            <input type="hidden" name="registro" value="1">
                            <button type="submit" class="auth-btn">Registrarse</button>
                            
                            <?php if (!empty($mensaje_registro) && strpos($mensaje_registro, 'exitoso') === false): ?>
                            <div class="auth-message error"><?php echo htmlspecialchars($mensaje_registro); ?></div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- CONTENIDO PRINCIPAL (solo visible cuando está autenticado) -->
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
                    <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a href="#"><i class="fas fa-compass"></i> Explorar</a></li>
                    <li><a href="#"><i class="fas fa-users"></i> Comunidad</a></li>
                    <li><a href="#"><i class="fas fa-calendar-alt"></i> Eventos</a></li>
                    <li><a href="#"><i class="fas fa-envelope"></i> Mensajes</a></li>
                </ul>
            </nav>
            
            <div class="user-actions">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Buscar música, artistas...">
                </div>
                <div class="user-profile">
                    <div class="avatar"><?php echo substr($sessionCheck['user']['nombre'], 0, 1); ?></div>
                    <span><?php echo htmlspecialchars($sessionCheck['user']['nombre']); ?></span>
                    <div class="user-menu">
                        <a href="index.php?logout=true"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div class="main-content">
        <!-- Feed Central -->
        <div class="feed">
            <!-- Crear Publicación -->
            <div class="create-post">
                <div class="post-header">
                    <div class="post-avatar"><?php echo substr($sessionCheck['user']['nombre'], 0, 1); ?></div>
                    <input type="text" class="post-input" placeholder="¿Qué estás escuchando?">
                </div>
                <div class="post-actions">
                    <button class="action-btn">
                        <i class="fas fa-image"></i> Foto
                    </button>
                    <button class="action-btn">
                        <i class="fas fa-music"></i> Música
                    </button>
                    <button class="action-btn">
                        <i class="fas fa-map-marker-alt"></i> Ubicación
                    </button>
                    <button class="post-btn">Publicar</button>
                </div>
            </div>
            
            <!-- Publicaciones dinámicas desde la BD -->
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
                                } elseif ($diff->i > 0) {
                                    echo "Hace " . $diff->i . " minuto" . ($diff->i > 1 ? "s" : "");
                                } else {
                                    echo "Hace un momento";
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
                    
                    <?php if (!empty($post['cancion_nombre'])): ?>
                    <div class="post-song">
                        <i class="fas fa-music"></i>
                        <span><?php echo htmlspecialchars($post['cancion_nombre']); ?></span>
                        <?php if (!empty($post['cancion_artista'])): ?>
                        <span> - <?php echo htmlspecialchars($post['cancion_artista']); ?></span>
                        <?php endif; ?>
                    </div>
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
                        <button class="interaction-btn share-btn">
                            <i class="far fa-share-square"></i> Compartir
                        </button>
                    </div>
                    <div class="comments-section" style="display: none;">
                        <div class="comments-list"></div>
                        <div class="comment-form">
                            <input type="text" class="comment-input" placeholder="Escribe un comentario..." data-post-id="<?php echo $post['id']; ?>">
                            <button class="comment-submit" data-post-id="<?php echo $post['id']; ?>">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-posts">
                    <i class="fas fa-music"></i>
                    <p>No hay publicaciones aún. ¡Sé el primero en compartir algo!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Panel Lateral -->
        <div class="sidebar">
            <h2><i class="fas fa-user-friends"></i> Amigos</h2>
            
            <?php if (!empty($friends)): ?>
                <?php foreach ($friends as $friend): ?>
                <div class="friend-item">
                    <div class="friend-avatar">
                        <?php if (!empty($friend['foto_perfil'])): ?>
                            <img src="../../backend/<?php echo htmlspecialchars($friend['foto_perfil']); ?>" alt="Avatar">
                        <?php else: ?>
                            <?php echo substr($friend['nombre'], 0, 1); ?>
                        <?php endif; ?>
                    </div>
                    <div class="friend-info">
                        <div class="friend-name"><?php echo htmlspecialchars($friend['nombre']); ?></div>
                        <div class="friend-status <?php echo $friend['estado']; ?>">
                            <?php 
                            if ($friend['estado'] == 'online') {
                                echo "En línea";
                                if (!empty($friend['cancion_estado'])) {
                                    echo " - " . htmlspecialchars($friend['cancion_estado']);
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
            <?php else: ?>
                <div class="no-friends">
                    <p>Aún no tienes amigos. ¡Comienza a conectar con otros usuarios!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <p>SoundConnect &copy; 2025 - Conectando a través de la música</p>
    </footer>
    <?php endif; ?>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/posts.js"></script>
    <script src="../assets/js/auth.js"></script>
</body>
</html>