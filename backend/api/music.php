<?php
/**
 * API de Música
 * backend/api/music.php
 * Endpoints: upload, get, list, search, delete, play, like
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/MusicController.php';

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$authController = new AuthController();
$musicController = new MusicController();

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
        
        // ============================================
        // SUBIR CANCIÓN
        // ============================================
        case 'upload':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Verificar que se subió un archivo de audio
            if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No se recibió ningún archivo de audio');
            }
            
            // Obtener datos del formulario
            $data = [
                'titulo' => $_POST['titulo'] ?? '',
                'artista' => $_POST['artista'] ?? '',
                'genero' => $_POST['genero'] ?? null,
                'descripcion' => $_POST['descripcion'] ?? null,
                'portada_url' => null
            ];
            
            // Si se subió portada, procesarla primero
            if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
                $coverResult = $musicController->uploadCover($userId, $_FILES['portada']);
                if ($coverResult['success']) {
                    $data['portada_url'] = $coverResult['data']['portada_url'];
                }
            }
            
            // Subir canción
            $resultado = $musicController->uploadSong($userId, $_FILES['audio'], $data);
            echo json_encode($resultado);
            break;
        
        // ============================================
        // SUBIR SOLO PORTADA (para actualizar después)
        // ============================================
        case 'upload-cover':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            if (!isset($_FILES['portada']) || $_FILES['portada']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No se recibió ninguna imagen');
            }
            
            $resultado = $musicController->uploadCover($userId, $_FILES['portada']);
            echo json_encode($resultado);
            break;
        
        // ============================================
        // OBTENER CANCIÓN POR ID
        // ============================================
        case 'get':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            if (!isset($_GET['id'])) {
                throw new Exception('ID de canción es requerido');
            }
            
            $songId = intval($_GET['id']);
            $resultado = $musicController->getSong($songId);
            echo json_encode($resultado);
            break;
        
        // ============================================
        // LISTAR CANCIONES DE UN USUARIO
        // ============================================
        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            $targetUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : $userId;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            
            $resultado = $musicController->getUserSongs($targetUserId, $limit, $offset);
            echo json_encode($resultado);
            break;
        
        // ============================================
        // OBTENER TODAS LAS CANCIONES (FEED)
        // ============================================
        case 'all':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            
            $resultado = $musicController->getAllSongs($limit, $offset);
            echo json_encode($resultado);
            break;
        
        // ============================================
        // BUSCAR CANCIONES
        // ============================================
        case 'search':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
                throw new Exception('Término de búsqueda es requerido');
            }
            
            $query = trim($_GET['q']);
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
            
            $resultado = $musicController->searchSongs($query, $limit);
            echo json_encode($resultado);
            break;
        
        // ============================================
        // OBTENER POR GÉNERO
        // ============================================
        case 'by-genre':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            if (!isset($_GET['genre'])) {
                throw new Exception('Género es requerido');
            }
            
            $genero = trim($_GET['genre']);
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            
            $resultado = $musicController->getSongsByGenre($genero, $limit, $offset);
            echo json_encode($resultado);
            break;
        
        // ============================================
        // OBTENER TRENDING (MÁS REPRODUCIDAS)
        // ============================================
        case 'trending':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            
            $resultado = $musicController->getTrendingSongs($limit);
            echo json_encode($resultado);
            break;
        
        // ============================================
        // ELIMINAR CANCIÓN
        // ============================================
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Obtener ID desde query string o body
            if (isset($_GET['id'])) {
                $songId = intval($_GET['id']);
            } else {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['song_id'])) {
                    throw new Exception('ID de canción es requerido');
                }
                $songId = intval($data['song_id']);
            }
            
            $resultado = $musicController->deleteSong($songId, $userId);
            echo json_encode($resultado);
            break;
        
        // ============================================
        // REGISTRAR REPRODUCCIÓN
        // ============================================
        case 'play':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['song_id'])) {
                throw new Exception('ID de canción es requerido');
            }
            
            $songId = intval($data['song_id']);
            $resultado = $musicController->playSong($songId, $userId);
            echo json_encode($resultado);
            break;
        
        // ============================================
        // DAR/QUITAR LIKE
        // ============================================
        case 'like':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['song_id'])) {
                throw new Exception('ID de canción es requerido');
            }
            
            $songId = intval($data['song_id']);
            $resultado = $musicController->toggleLike($songId, $userId);
            echo json_encode($resultado);
            break;
        
        // ============================================
        // OBTENER GÉNEROS DISPONIBLES
        // ============================================
        case 'genres':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Método no permitido');
            }
            
            // Si tienes la tabla generos_musicales
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT * FROM generos_musicales WHERE activo = 1 ORDER BY orden ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $generos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $generos
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