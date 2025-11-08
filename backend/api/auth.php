<?php
/**
 * API de Autenticación
 * Endpoints: login, register, logout, check-session
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../controllers/AuthController.php';

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$authController = new AuthController();

// Obtener el action del request
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'login':
            // POST: Login de usuario
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['usuario']) || !isset($data['clave'])) {
                throw new Exception('Usuario y contraseña son requeridos');
            }
            
            $resultado = $authController->login($data['usuario'], $data['clave']);
            echo json_encode($resultado);
            break;
        
        case 'register':
            // POST: Registrar nuevo usuario
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['usuario']) || !isset($data['nombre']) || 
                !isset($data['clave']) || !isset($data['correo'])) {
                throw new Exception('Todos los campos son requeridos');
            }
            
            $resultado = $authController->register($data);
            echo json_encode($resultado);
            break;
        
        case 'logout':
            // POST: Cerrar sesión
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $resultado = $authController->logout();
            echo json_encode($resultado);
            break;
        
        case 'check-session':
            // GET: Verificar si hay sesión activa
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            $resultado = $authController->checkSession();
            echo json_encode($resultado);
            break;
        
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>