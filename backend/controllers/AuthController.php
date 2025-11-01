<?php
/**
 * AuthController - Maneja autenticación de usuarios
 * Login, registro, logout y validación de sesión
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $db;
    private $userModel;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->userModel = new User($this->db);
    }
    
    /**
     * Registrar nuevo usuario
     * @param array $data - Datos del usuario (usuario, nombre, clave, correo)
     * @return array - ['success' => bool, 'message' => string, 'data' => array]
     */
    public function register($data) {
        // Validar datos requeridos
        if (empty($data['usuario']) || empty($data['nombre']) || 
            empty($data['clave']) || empty($data['correo'])) {
            return [
                'success' => false,
                'message' => 'Todos los campos son obligatorios'
            ];
        }
        
        // Validar formato de correo
        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'El formato del correo electrónico no es válido'
            ];
        }
        
        // Validar longitud de usuario
        if (strlen($data['usuario']) < 3 || strlen($data['usuario']) > 50) {
            return [
                'success' => false,
                'message' => 'El usuario debe tener entre 3 y 50 caracteres'
            ];
        }
        
        // Validar longitud de contraseña
        if (strlen($data['clave']) < 6) {
            return [
                'success' => false,
                'message' => 'La contraseña debe tener al menos 6 caracteres'
            ];
        }
        
        // Sanitizar datos
        $usuario = htmlspecialchars(strip_tags(trim($data['usuario'])));
        $nombre = htmlspecialchars(strip_tags(trim($data['nombre'])));
        $correo = htmlspecialchars(strip_tags(trim($data['correo'])));
        $clave = $data['clave'];
        
        // Verificar si el usuario ya existe
        if ($this->userModel->existsByUsername($usuario)) {
            return [
                'success' => false,
                'message' => 'El nombre de usuario ya está en uso'
            ];
        }
        
        // Verificar si el correo ya existe
        if ($this->userModel->existsByEmail($correo)) {
            return [
                'success' => false,
                'message' => 'El correo electrónico ya está registrado'
            ];
        }
        
        // Hash de la contraseña
        $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
        
        // Crear usuario
        $userId = $this->userModel->create($usuario, $nombre, $clave_hash, $correo);
        
        if ($userId) {
            return [
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'data' => [
                    'user_id' => $userId,
                    'usuario' => $usuario
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al registrar el usuario'
            ];
        }
    }
    
    /**
     * Iniciar sesión
     * @param string $usuario - Usuario o correo
     * @param string $clave - Contraseña
     * @return array - ['success' => bool, 'message' => string, 'data' => array]
     */
    public function login($usuario, $clave) {
        // Validar datos
        if (empty($usuario) || empty($clave)) {
            return [
                'success' => false,
                'message' => 'Usuario y contraseña son obligatorios'
            ];
        }
        
        // Sanitizar usuario
        $usuario = htmlspecialchars(strip_tags(trim($usuario)));
        
        // Buscar usuario por username o email
        $user = $this->userModel->findByUsernameOrEmail($usuario);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos'
            ];
        }
        
        // Verificar contraseña
        if (!password_verify($clave, $user['clave'])) {
            return [
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos'
            ];
        }
        
        // Actualizar último acceso y estado
        $this->userModel->updateLastAccess($user['id']);
        $this->userModel->updateStatus($user['id'], 'online');
        
        // Iniciar sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['correo'] = $user['correo'];
        $_SESSION['foto_perfil'] = $user['foto_perfil'];
        
        return [
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'data' => [
                'user_id' => $user['id'],
                'usuario' => $user['usuario'],
                'nombre' => $user['nombre'],
                'correo' => $user['correo'],
                'foto_perfil' => $user['foto_perfil']
            ]
        ];
    }
    
    /**
     * Cerrar sesión
     * @return array - ['success' => bool, 'message' => string]
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Actualizar estado del usuario a offline
        if (isset($_SESSION['user_id'])) {
            $this->userModel->updateStatus($_SESSION['user_id'], 'offline');
        }
        
        // Destruir sesión
        session_unset();
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'Sesión cerrada exitosamente'
        ];
    }
    
    /**
     * Verificar si hay una sesión activa
     * @return array - ['authenticated' => bool, 'user' => array|null]
     */
    public function checkSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            return [
                'authenticated' => true,
                'user' => [
                    'user_id' => $_SESSION['user_id'],
                    'usuario' => $_SESSION['usuario'],
                    'nombre' => $_SESSION['nombre'],
                    'correo' => $_SESSION['correo'],
                    'foto_perfil' => $_SESSION['foto_perfil']
                ]
            ];
        }
        
        return [
            'authenticated' => false,
            'user' => null
        ];
    }
    
    /**
     * Verificar si el usuario está autenticado (para proteger rutas)
     * @return bool
     */
    public function isAuthenticated() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Obtener el ID del usuario actual
     * @return int|null
     */
    public function getCurrentUserId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['user_id'] ?? null;
    }
}
?>