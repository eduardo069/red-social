<?php
/**
 * editar-perfil.php - Editar información del perfil
 * Permite cambiar nombre, usuario, correo, biografía, género musical y contraseña
 */

session_start();

require_once __DIR__ . '/../../backend/controllers/AuthController.php';
require_once __DIR__ . '/../../backend/controllers/UserController.php';

$authController = new AuthController();
$userController = new UserController();

// Verificar autenticación
$sessionCheck = $authController->checkSession();
if (!$sessionCheck['authenticated']) {
    header("Location: index.php");
    exit();
}

$currentUserId = $sessionCheck['user']['user_id'];

// Obtener datos actuales del usuario
$profileResult = $userController->getProfile($currentUserId);
if (!$profileResult['success']) {
    header("Location: inicio.php");
    exit();
}
$userData = $profileResult['data'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - SoundConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .edit-profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .edit-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .edit-header h1 {
            margin: 0;
            font-size: 2rem;
        }
        
        .edit-section {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .edit-section h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #ff8a00;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #ddd;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #ff8a00;
            background: rgba(255,255,255,0.15);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #999;
            font-size: 0.85rem;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: #ff8a00;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e67700;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 138, 0, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle input {
            padding-right: 45px;
        }
        
        .password-toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 5px 10px;
            transition: color 0.3s;
        }
        
        .password-toggle-btn:hover {
            color: #ff8a00;
        }
        
        /* ALERTAS MEJORADAS */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .alert.show {
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInDown 0.3s;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.3);
            border: 2px solid #4CAF50;
            color: #4CAF50;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        
        .alert-error {
            background: rgba(244, 67, 54, 0.3);
            border: 2px solid #f44336;
            color: #f44336;
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
        }
        
        .alert i {
            font-size: 1.3rem;
        }
        
        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* SPINNER DE CARGA EN BOTONES */
        .btn.loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }
        
        .btn.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spinner 0.6s linear infinite;
        }
        
        @keyframes spinner {
            to {
                transform: rotate(360deg);
            }
        }
        
        .divider {
            height: 2px;
            background: rgba(255,255,255,0.1);
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <div class="main-content">
        <div class="edit-profile-container">
            <div class="edit-header">
                <h1><i class="fas fa-user-edit"></i> Editar Perfil</h1>
            </div>
            
            <!-- Alertas -->
            <div id="alert-container"></div>
            
            <!-- Información Básica -->
            <div class="edit-section">
                <h2><i class="fas fa-user"></i> Información Básica</h2>
                
                <form id="basic-info-form">
                    <div class="form-group">
                        <label for="nombre">Nombre Completo</label>
                        <input type="text" id="nombre" name="nombre" 
                               value="<?php echo htmlspecialchars($userData['nombre']); ?>" 
                               required>
                        <small>Este es el nombre que verán otros usuarios</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="usuario">Nombre de Usuario</label>
                        <input type="text" id="usuario" name="usuario" 
                               value="<?php echo htmlspecialchars($userData['usuario']); ?>" 
                               required disabled>
                        <small>⚠️ El nombre de usuario no se puede cambiar</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="correo">Correo Electrónico</label>
                        <input type="email" id="correo" name="correo" 
                               value="<?php echo htmlspecialchars($userData['correo']); ?>" 
                               required disabled>
                        <small>⚠️ El correo no se puede cambiar por seguridad</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="biografia">Biografía</label>
                        <textarea id="biografia" name="biografia" 
                                  maxlength="500"><?php echo htmlspecialchars($userData['biografia'] ?? ''); ?></textarea>
                        <small>Cuéntanos sobre ti (máximo 500 caracteres)</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='perfil.php'">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Preferencias Musicales -->
            <div class="edit-section">
                <h2><i class="fas fa-music"></i> Preferencias Musicales</h2>
                
                <form id="music-preferences-form">
                    <div class="form-group">
                        <label for="genero_musical">Género Musical Favorito</label>
                        <select id="genero_musical" name="genero_musical_favorito">
                            <option value="">Selecciona un género</option>
                            <option value="Rock" <?php echo ($userData['genero_musical_favorito'] ?? '') === 'Rock' ? 'selected' : ''; ?>>🎸 Rock</option>
                            <option value="Pop" <?php echo ($userData['genero_musical_favorito'] ?? '') === 'Pop' ? 'selected' : ''; ?>>🎤 Pop</option>
                            <option value="Jazz" <?php echo ($userData['genero_musical_favorito'] ?? '') === 'Jazz' ? 'selected' : ''; ?>>🎷 Jazz</option>
                            <option value="Hip Hop" <?php echo ($userData['genero_musical_favorito'] ?? '') === 'Hip Hop' ? 'selected' : ''; ?>>🎵 Hip Hop</option>
                            <option value="Electrónica" <?php echo ($userData['genero_musical_favorito'] ?? '') === 'Electrónica' ? 'selected' : ''; ?>>🎧 Electrónica</option>
                            <option value="Reggaeton" <?php echo ($userData['genero_musical_favorito'] ?? '') === 'Reggaeton' ? 'selected' : ''; ?>>🥁 Reggaeton</option>
                            <option value="Indie" <?php echo ($userData['genero_musical_favorito'] ?? '') === 'Indie' ? 'selected' : ''; ?>>🎸 Indie</option>
                            <option value="R&B" <?php echo ($userData['genero_musical_favorito'] ?? '') === 'R&B' ? 'selected' : ''; ?>>🎤 R&B</option>
                            <option value="Clásica" <?php echo ($userData['genero_musical_favorito'] ?? '') === 'Clásica' ? 'selected' : ''; ?>>🎻 Clásica</option>
                            <option value="Metal" <?php echo ($userData['genero_musical_favorito'] ?? '') === 'Metal' ? 'selected' : ''; ?>>🤘 Metal</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="cancion_estado">Canción de Estado</label>
                        <input type="text" id="cancion_estado" name="cancion_estado" 
                               value="<?php echo htmlspecialchars($userData['cancion_estado'] ?? ''); ?>" 
                               placeholder="Ej: Escuchando 'Bohemian Rhapsody' - Queen"
                               maxlength="255">
                        <small>Comparte qué estás escuchando ahora</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Preferencias
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Cambiar Contraseña -->
            <div class="edit-section">
                <h2><i class="fas fa-lock"></i> Cambiar Contraseña</h2>
                
                <form id="change-password-form">
                    <div class="form-group password-toggle">
                        <label for="current_password">Contraseña Actual</label>
                        <input type="password" id="current_password" name="current_password" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('current_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label for="new_password">Nueva Contraseña</label>
                        <input type="password" id="new_password" name="new_password" 
                               minlength="6" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('new_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                        <small>Mínimo 6 caracteres</small>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label for="confirm_password">Confirmar Nueva Contraseña</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               minlength="6" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-key"></i> Cambiar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <footer>
        <p>SoundConnect &copy; 2025 - Conectando a través de la música</p>
    </footer>
    
    <script src="../assets/js/main.js"></script>
    <script>
        const SC = window.SoundConnect;
        
        // Función para mostrar alertas MEJORADA
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} show`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            alertContainer.innerHTML = '';
            alertContainer.appendChild(alert);
            
            // Scroll suave hacia la alerta
            alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Remover después de 5 segundos con animación
            setTimeout(() => {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }
        
        // Función para añadir estado de carga a un botón
        function setButtonLoading(button, isLoading) {
            if (isLoading) {
                button.classList.add('loading');
                button.disabled = true;
                button.dataset.originalText = button.innerHTML;
                button.innerHTML = '<span style="opacity: 0;">Guardando...</span>';
            } else {
                button.classList.remove('loading');
                button.disabled = false;
                button.innerHTML = button.dataset.originalText;
            }
        }
        
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const btn = input.nextElementSibling;
            const icon = btn.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Actualizar información básica
        document.getElementById('basic-info-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            setButtonLoading(submitBtn, true);
            
            const formData = {
                nombre: document.getElementById('nombre').value,
                biografia: document.getElementById('biografia').value
            };
            
            const result = await SC.Utils.fetchAPI(`${SC.API_BASE_URL}/users.php?action=update-profile`, {
                method: 'POST',
                body: JSON.stringify(formData)
            });
            
            setButtonLoading(submitBtn, false);
            
            if (result.success) {
                showAlert('✅ Información actualizada correctamente', 'success');
            } else {
                showAlert('❌ ' + result.message, 'error');
            }
        });
        
        // Actualizar preferencias musicales
        document.getElementById('music-preferences-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            setButtonLoading(submitBtn, true);
            
            const formData = {
                genero_musical_favorito: document.getElementById('genero_musical').value,
                cancion_estado: document.getElementById('cancion_estado').value
            };
            
            const result = await SC.Utils.fetchAPI(`${SC.API_BASE_URL}/users.php?action=update-profile`, {
                method: 'POST',
                body: JSON.stringify(formData)
            });
            
            setButtonLoading(submitBtn, false);
            
            if (result.success) {
                showAlert('🎵 Preferencias musicales actualizadas correctamente', 'success');
            } else {
                showAlert('❌ ' + result.message, 'error');
            }
        });
        
        // Cambiar contraseña
        document.getElementById('change-password-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Validar que las contraseñas coincidan
            if (newPassword !== confirmPassword) {
                showAlert('❌ Las contraseñas nuevas no coinciden', 'error');
                return;
            }
            
            setButtonLoading(submitBtn, true);
            
            const result = await SC.Utils.fetchAPI(`${SC.API_BASE_URL}/users.php?action=change-password`, {
                method: 'POST',
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword
                })
            });
            
            setButtonLoading(submitBtn, false);
            
            if (result.success) {
                showAlert('🔒 Contraseña cambiada correctamente', 'success');
                e.target.reset();
            } else {
                showAlert('❌ ' + result.message, 'error');
            }
        });
    </script>
</body>
</html>