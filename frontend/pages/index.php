<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "soundconnect";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener el nombre del archivo actual para las redirecciones
$current_file = basename($_SERVER['PHP_SELF']);

// Función para registrar usuario
function registrarUsuario($usuario, $nombre, $clave, $correo, $conn, $current_file) {
    // Verificar si el usuario o correo ya existen
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ? OR correo = ?");
    $stmt->bind_param("ss", $usuario, $correo);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        return "El usuario o correo electrónico ya existe";
    }
    $stmt->close();
    
    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
    
    // Insertar nuevo usuario
    $stmt = $conn->prepare("INSERT INTO usuarios (usuario, nombre, clave, correo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $usuario, $nombre, $clave_hash, $correo);
    
    if ($stmt->execute()) {
        // Redirigir al formulario de login después del registro exitoso
        header("Location: " . $current_file . "?registro=exitoso");
        exit();
    } else {
        return "Error al registrar: " . $stmt->error;
    }
}

// Función para iniciar sesión
function iniciarSesion($usuario, $clave, $conn, $current_file) {
    $stmt = $conn->prepare("SELECT id, usuario, nombre, clave FROM usuarios WHERE usuario = ? OR correo = ?");
    $stmt->bind_param("ss", $usuario, $usuario);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $usuario_db, $nombre, $clave_hash);
    $stmt->fetch();
    
    if ($stmt->num_rows > 0 && password_verify($clave, $clave_hash)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['usuario'] = $usuario_db;
        $_SESSION['nombre'] = $nombre;
        header("Location: " . $current_file);
        exit();
    }
    return false;
}

// Procesar formulario de registro
if (isset($_POST['registro'])) {
    $usuario = trim($_POST['usuario']);
    $nombre = trim($_POST['nombre']);
    $clave = $_POST['clave'];
    $correo = trim($_POST['correo']);
    
    $resultado = registrarUsuario($usuario, $nombre, $clave, $correo, $conn, $current_file);
    // Si llegamos aquí, es porque hubo un error en el registro
    $mensaje_registro = $resultado;
}

// Procesar formulario de inicio de sesión
if (isset($_POST['login'])) {
    $usuario = trim($_POST['usuario']);
    $clave = $_POST['clave'];
    
    if (iniciarSesion($usuario, $clave, $conn, $current_file)) {
        // La redirección se maneja dentro de la función
    } else {
        $error_login = "Usuario o contraseña incorrectos";
    }
}

// Cerrar sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $current_file);
    exit();
}

// Verificar si el usuario está autenticado
$usuario_autenticado = isset($_SESSION['user_id']);

// Mostrar mensaje de registro exitoso
if (isset($_GET['registro']) && $_GET['registro'] == 'exitoso') {
    $mensaje_registro = "Registro exitoso. Ahora puedes iniciar sesión.";
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
    <link rel="stylesheet" href="style.css">
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
                        <form class="auth-form active" id="login-form" method="POST">
                            <div class="input-group">
                                <i class="fas fa-user"></i>
                                <input type="text" name="usuario" placeholder="Usuario o correo electrónico" required>
                            </div>
                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="clave" placeholder="Contraseña" required>
                            </div>
                            <button type="submit" name="login" class="auth-btn">Iniciar Sesión</button>
                            
                            <?php if (isset($error_login)): ?>
                            <div class="auth-message error"><?php echo $error_login; ?></div>
                            <?php endif; ?>
                            
                            <?php if (isset($mensaje_registro) && strpos($mensaje_registro, 'exitoso') !== false): ?>
                            <div class="auth-message success"><?php echo $mensaje_registro; ?></div>
                            <?php endif; ?>
                        </form>
                        
                        <!-- Formulario de registro -->
                        <form class="auth-form" id="register-form" method="POST">
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
                            <button type="submit" name="registro" class="auth-btn">Registrarse</button>
                            
                            <?php if (isset($mensaje_registro) && strpos($mensaje_registro, 'exitoso') === false): ?>
                            <div class="auth-message error"><?php echo $mensaje_registro; ?></div>
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
                    <li><a href="#"><i class="fas fa-home"></i> Inicio</a></li>
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
                    <div class="avatar"><?php echo substr($_SESSION['nombre'], 0, 1); ?></div>
                    <span><?php echo $_SESSION['nombre']; ?></span>
                    <div class="user-menu">
                        <a href="?logout=true"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
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
                    <div class="post-avatar"><?php echo substr($_SESSION['nombre'], 0, 1); ?></div>
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
            
            <!-- Publicaciones -->
            <div class="post">
                <div class="post-user">
                    <div class="post-avatar">MJ</div>
                    <div class="post-user-info">
                        <div class="post-username">MariaJazz</div>
                        <div class="post-time">Hace 2 horas</div>
                    </div>
                </div>
                <div class="post-content">
                    Acabo de descubrir esta increíble canción de jazz. ¡Es perfecta para relajarse después de un largo día! 🎷✨
                </div>
                <img src="https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Jazz concert" class="post-image">
                <div class="post-stats">
                    <span>125 me gusta</span>
                    <span>23 comentarios</span>
                </div>
                <div class="post-interactions">
                    <button class="interaction-btn">
                        <i class="far fa-heart"></i> Me gusta
                    </button>
                    <button class="interaction-btn">
                        <i class="far fa-comment"></i> Comentar
                    </button>
                    <button class="interaction-btn">
                        <i class="far fa-share-square"></i> Compartir
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Panel Lateral -->
        <div class="sidebar">
            <h2><i class="fas fa-user-friends"></i> Amigos</h2>
            <div class="friend-item">
                <div class="friend-avatar">JF</div>
                <div class="friend-info">
                    <div class="friend-name">JazzFan</div>
                    <div class="friend-status online">En línea - Escuchando jazz</div>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <p>SoundConnect &copy; 2023 - Conectando a través de la música</p>
    </footer>
    <?php endif; ?>
    
    <script>
        // Sistema de autenticación
        document.querySelectorAll('.auth-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Actualizar pestañas activas
                document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Mostrar formulario correspondiente
                const tabId = this.getAttribute('data-tab');
                document.querySelectorAll('.auth-form').forEach(form => form.classList.remove('active'));
                document.getElementById(tabId + '-form').classList.add('active');
            });
        });
        
        // Toggle del menú móvil
        if (document.querySelector('.mobile-menu')) {
            document.querySelector('.mobile-menu').addEventListener('click', function() {
                document.getElementById('main-nav').classList.toggle('active');
            });
        }
        
        // Interacción con botones de me gusta
        document.querySelectorAll('.interaction-btn').forEach(button => {
            button.addEventListener('click', function() {
                if (this.querySelector('.fa-heart')) {
                    const icon = this.querySelector('.fa-heart');
                    if (icon.classList.contains('far')) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        this.style.color = '#ff8a00';
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        this.style.color = '#fff';
                    }
                }
            });
        });
        
        // Cerrar menú móvil al hacer clic fuera de él
        document.addEventListener('click', function(event) {
            const nav = document.getElementById('main-nav');
            const mobileMenu = document.querySelector('.mobile-menu');
            
            if (nav && nav.classList.contains('active') && 
                !nav.contains(event.target) && 
                mobileMenu && !mobileMenu.contains(event.target)) {
                nav.classList.remove('active');
            }
        });
    </script>
</body>
</html>