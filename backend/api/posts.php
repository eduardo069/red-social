<?php
/**
 * API de Publicaciones
 * Endpoints: create, get-feed, like, comment, get-comments, delete
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/PostController.php';

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$authController = new AuthController();
$postController = new PostController();

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
        case 'create':
            // POST: Crear nueva publicación
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['contenido']) || empty(trim($data['contenido']))) {
                throw new Exception('El contenido es requerido');
            }
            
            $resultado = $postController->createPost($userId, $data);
            echo json_encode($resultado);
            break;
        
        case 'get-feed':
            // GET: Obtener feed de publicaciones
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            
            $resultado = $postController->getFeed($limit, $offset);
            echo json_encode($resultado);
            break;
        
        case 'get-user-posts':
            // GET: Obtener publicaciones de un usuario
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            $targetUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : $userId;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            
            $resultado = $postController->getUserPosts($targetUserId, $limit, $offset);
            echo json_encode($resultado);
            break;
        
        case 'like':
            // POST: Dar/quitar like a una publicación
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['post_id'])) {
                throw new Exception('ID de publicación es requerido');
            }
            
            $resultado = $postController->toggleLike($data['post_id'], $userId);
            echo json_encode($resultado);
            break;
        
        case 'comment':
            // POST: Agregar comentario
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['post_id']) || !isset($data['contenido'])) {
                throw new Exception('ID de publicación y contenido son requeridos');
            }
            
            $resultado = $postController->addComment(
                $data['post_id'], 
                $userId, 
                $data['contenido']
            );
            echo json_encode($resultado);
            break;
        
        case 'get-comments':
            // GET: Obtener comentarios de una publicación
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            if (!isset($_GET['post_id'])) {
                throw new Exception('ID de publicación es requerido');
            }
            
            $resultado = $postController->getComments($_GET['post_id']);
            echo json_encode($resultado);
            break;
        
        case 'delete':
            // DELETE: Eliminar publicación
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['post_id'])) {
                throw new Exception('ID de publicación es requerido');
            }
            
            $resultado = $postController->deletePost($data['post_id'], $userId);
            echo json_encode($resultado);
            break;
        
        case 'delete-comment':
            // DELETE: Eliminar comentario
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['comment_id'])) {
                throw new Exception('ID de comentario es requerido');
            }
            
            $resultado = $postController->deleteComment($data['comment_id'], $userId);
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