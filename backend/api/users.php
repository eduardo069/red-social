<?php
/**
 * API de Usuarios
 * Endpoints: get-profile, update-profile, search, upload-photo, get-friends, send-request, accept-request
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/FriendController.php';

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$authController = new AuthController();
$userController = new UserController();
$friendController = new FriendController();

// Verificar autenticación
if (!$authController->isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autenticado'
    ]);
    exit();
}

$userId = $authController->getCurrentUserId();
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'get-profile':
            // GET: Obtener perfil de usuario
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            $targetUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : $userId;
            $resultado = $userController->getProfile($targetUserId);
            echo json_encode($resultado);
            break;
        
        case 'update-profile':
            // POST: Actualizar perfil
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $resultado = $userController->updateProfile($userId, $data);
            echo json_encode($resultado);
            break;
        
        case 'change-password':
            // POST: Cambiar contraseña
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['current_password']) || !isset($data['new_password'])) {
                throw new Exception('Contraseña actual y nueva son requeridas');
            }
            
            $resultado = $userController->changePassword(
                $userId, 
                $data['current_password'], 
                $data['new_password']
            );
            echo json_encode($resultado);
            break;
        
        case 'search':
            // GET: Buscar usuarios
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            if (!isset($_GET['query']) || empty(trim($_GET['query']))) {
                throw new Exception('Término de búsqueda es requerido');
            }
            
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $resultado = $userController->searchUsers($_GET['query'], $limit);
            echo json_encode($resultado);
            break;
        
        case 'update-status':
            // POST: Actualizar estado (online/offline/ausente)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['status'])) {
                throw new Exception('Estado es requerido');
            }
            
            $resultado = $userController->updateStatus($userId, $data['status']);
            echo json_encode($resultado);
            break;
        
        case 'upload-photo':
            // POST: Subir foto de perfil
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            if (!isset($_FILES['photo'])) {
                throw new Exception('No se recibió ninguna imagen');
            }
            
            $resultado = $userController->uploadProfilePhoto($userId, $_FILES['photo']);
            echo json_encode($resultado);
            break;
        
        case 'get-stats':
            // GET: Obtener estadísticas del usuario
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            $targetUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : $userId;
            $resultado = $userController->getUserStats($targetUserId);
            echo json_encode($resultado);
            break;
        
        // ============================================
        // ENDPOINTS DE AMISTADES
        // ============================================
        
        case 'get-friends':
            // GET: Obtener lista de amigos
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            $targetUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : $userId;
            $resultado = $friendController->getFriends($targetUserId);
            echo json_encode($resultado);
            break;
        
        case 'send-friend-request':
            // POST: Enviar solicitud de amistad
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['friend_id'])) {
                throw new Exception('ID de usuario es requerido');
            }
            
            $resultado = $friendController->sendFriendRequest($userId, $data['friend_id']);
            echo json_encode($resultado);
            break;
        
        case 'accept-friend-request':
            // POST: Aceptar solicitud de amistad
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['request_id'])) {
                throw new Exception('ID de solicitud es requerido');
            }
            
            $resultado = $friendController->acceptFriendRequest($data['request_id'], $userId);
            echo json_encode($resultado);
            break;
        
        case 'reject-friend-request':
            // POST: Rechazar solicitud de amistad
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['request_id'])) {
                throw new Exception('ID de solicitud es requerido');
            }
            
            $resultado = $friendController->rejectFriendRequest($data['request_id'], $userId);
            echo json_encode($resultado);
            break;
        
        case 'cancel-friend-request':
            // POST: Cancelar solicitud enviada
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['request_id'])) {
                throw new Exception('ID de solicitud es requerido');
            }
            
            $resultado = $friendController->cancelFriendRequest($data['request_id'], $userId);
            echo json_encode($resultado);
            break;
        
        case 'remove-friend':
            // POST: Eliminar amistad
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['friendship_id'])) {
                throw new Exception('ID de amistad es requerido');
            }
            
            $resultado = $friendController->removeFriend($data['friendship_id'], $userId);
            echo json_encode($resultado);
            break;
        
        case 'get-pending-requests':
            // GET: Obtener solicitudes pendientes
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            $resultado = $friendController->getPendingRequests($userId);
            echo json_encode($resultado);
            break;
        
        case 'get-sent-requests':
            // GET: Obtener solicitudes enviadas
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            $resultado = $friendController->getSentRequests($userId);
            echo json_encode($resultado);
            break;
        
        case 'check-friendship':
            // GET: Verificar estado de amistad
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            if (!isset($_GET['user_id'])) {
                throw new Exception('ID de usuario es requerido');
            }
            
            $resultado = $friendController->getFriendshipStatus($userId, $_GET['user_id']);
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