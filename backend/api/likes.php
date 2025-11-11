<?php
/**
 * API de Likes (Me gusta)
 * Endpoints: toggle, get-post-likes, get-user-likes
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../models/Like.php';
require_once __DIR__ . '/../config/database.php';

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$authController = new AuthController();

// Verificar autenticación
if (!$authController->isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autenticado'
    ]);
    exit();
}

$currentUserId = $authController->getCurrentUserId();

// Conectar a BD
$database = new Database();
$db = $database->getConnection();
$likeModel = new Like($db);

// Obtener el action del request
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'toggle':
            // POST: Dar o quitar like a una publicación
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['publicacion_id'])) {
                throw new Exception('ID de publicación es requerido');
            }
            
            $publicacionId = intval($data['publicacion_id']);
            
            // Verificar si ya le dio like
            $hasLiked = $likeModel->hasUserLiked($publicacionId, $currentUserId);
            
            if ($hasLiked) {
                // Quitar like
                $result = $likeModel->unlike($publicacionId, $currentUserId);
                $message = 'Like removido';
                $liked = false;
            } else {
                // Dar like
                $result = $likeModel->like($publicacionId, $currentUserId);
                $message = 'Like agregado';
                $liked = true;
            }
            
            if ($result) {
                // Obtener total de likes
                $totalLikes = $likeModel->getPostLikesCount($publicacionId);
                
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'liked' => $liked,
                        'total_likes' => $totalLikes
                    ]
                ]);
            } else {
                throw new Exception('Error al procesar el like');
            }
            break;
        
        case 'get-post-likes':
            // GET: Obtener todos los likes de una publicación
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            if (!isset($_GET['publicacion_id'])) {
                throw new Exception('ID de publicación es requerido');
            }
            
            $publicacionId = intval($_GET['publicacion_id']);
            
            $likes = $likeModel->getPostLikes($publicacionId);
            $totalLikes = $likeModel->getPostLikesCount($publicacionId);
            $hasLiked = $likeModel->hasUserLiked($publicacionId, $currentUserId);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'likes' => $likes,
                    'total_likes' => $totalLikes,
                    'user_has_liked' => $hasLiked
                ]
            ]);
            break;
        
        case 'get-user-likes':
            // GET: Obtener todas las publicaciones que le gustan a un usuario
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : $currentUserId;
            
            $likes = $likeModel->getUserLikes($userId);
            
            echo json_encode([
                'success' => true,
                'data' => $likes
            ]);
            break;
        
        case 'check-multiple':
            // POST: Verificar si el usuario le dio like a múltiples publicaciones
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['publicacion_ids']) || !is_array($data['publicacion_ids'])) {
                throw new Exception('Array de IDs de publicaciones es requerido');
            }
            
            $likedPosts = [];
            foreach ($data['publicacion_ids'] as $postId) {
                $postId = intval($postId);
                if ($likeModel->hasUserLiked($postId, $currentUserId)) {
                    $likedPosts[] = $postId;
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'liked_posts' => $likedPosts
                ]
            ]);
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