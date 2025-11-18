<?php
/**
 * MusicController - Maneja operaciones de música
 * Subir, obtener, buscar, eliminar canciones
 * backend/controllers/MusicController.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/upload.php';
require_once __DIR__ . '/../models/Music.php';

class MusicController {
    private $db;
    private $musicModel;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->musicModel = new Music($this->db);
    }
    
    /**
     * Subir nueva canción
     */
    public function uploadSong($userId, $file, $data) {
        // Validar archivo de audio
        $validation = UploadHelper::validateMusicFile($file);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => implode(', ', $validation['errors'])
            ];
        }
        
        // Validar datos requeridos
        if (empty($data['titulo']) || empty($data['artista'])) {
            return [
                'success' => false,
                'message' => 'Título y artista son requeridos'
            ];
        }
        
        // Generar nombre único para el archivo
        $fileName = UploadHelper::generateUniqueFileName($userId, 'song', $validation['extension']);
        $destination = UPLOAD_MUSIC_DIR . $fileName;
        
        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success' => false,
                'message' => 'Error al guardar el archivo de audio'
            ];
        }
        
        // Extraer duración del MP3
        $duracion = $this->extractDuration($destination);
        
        // Preparar datos
        $titulo = htmlspecialchars(strip_tags(trim($data['titulo'])));
        $artista = htmlspecialchars(strip_tags(trim($data['artista'])));
        $genero = isset($data['genero']) ? htmlspecialchars(strip_tags(trim($data['genero']))) : null;
        $descripcion = isset($data['descripcion']) ? htmlspecialchars(strip_tags(trim($data['descripcion']))) : null;
        
        $archivo_url = 'uploads/music/' . $fileName;
        $portada_url = isset($data['portada_url']) ? $data['portada_url'] : null;
        $tamanio = $validation['size'];
        
        // Guardar en base de datos
        $songId = $this->musicModel->create(
            $userId, 
            $titulo, 
            $artista, 
            $genero, 
            $archivo_url, 
            $portada_url, 
            $duracion, 
            $tamanio, 
            $descripcion
        );
        
        if ($songId) {
            return [
                'success' => true,
                'message' => 'Canción subida exitosamente',
                'data' => [
                    'song_id' => $songId,
                    'archivo_url' => $archivo_url,
                    'duracion' => $duracion,
                    'duracion_formateada' => $this->formatDuration($duracion)
                ]
            ];
        } else {
            // Si falla la BD, eliminar archivo
            UploadHelper::deleteFile($destination);
            return [
                'success' => false,
                'message' => 'Error al guardar la canción en la base de datos'
            ];
        }
    }
    
    /**
     * Subir portada de canción
     */
    public function uploadCover($userId, $file) {
        // Validar imagen
        $validation = UploadHelper::validateImageFile($file, false);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => implode(', ', $validation['errors'])
            ];
        }
        
        // Generar nombre único
        $fileName = UploadHelper::generateUniqueFileName($userId, 'cover', $validation['extension']);
        $destination = UPLOAD_IMAGES_DIR . $fileName;
        
        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success' => false,
                'message' => 'Error al guardar la portada'
            ];
        }
        
        // Redimensionar a tamaño cuadrado
        $resized = UploadHelper::resizeImage(
            $destination,
            $destination,
            800,
            800,
            true // Cuadrada
        );
        
        if (!$resized) {
            UploadHelper::deleteFile($destination);
            return [
                'success' => false,
                'message' => 'Error al procesar la imagen'
            ];
        }
        
        $coverUrl = 'uploads/images/' . $fileName;
        
        return [
            'success' => true,
            'data' => [
                'portada_url' => $coverUrl
            ]
        ];
    }
    
    /**
     * Obtener canción por ID
     */
    public function getSong($songId) {
        $song = $this->musicModel->getById($songId);
        
        if ($song) {
            // Formatear duración
            $song['duracion_formateada'] = $this->formatDuration($song['duracion']);
            $song['tamanio_formateado'] = UploadHelper::formatFileSize($song['tamanio']);
            
            return [
                'success' => true,
                'data' => $song
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Canción no encontrada'
            ];
        }
    }
    
    /**
     * Obtener canciones de un usuario
     */
    public function getUserSongs($userId, $limit = 50, $offset = 0) {
        $songs = $this->musicModel->getByUserId($userId, $limit, $offset);
        
        // Formatear duraciones
        foreach ($songs as &$song) {
            $song['duracion_formateada'] = $this->formatDuration($song['duracion']);
            $song['tamanio_formateado'] = UploadHelper::formatFileSize($song['tamanio']);
        }
        
        return [
            'success' => true,
            'data' => $songs,
            'count' => count($songs)
        ];
    }
    
    /**
     * Obtener todas las canciones
     */
    public function getAllSongs($limit = 50, $offset = 0) {
        $songs = $this->musicModel->getAll($limit, $offset);
        
        foreach ($songs as &$song) {
            $song['duracion_formateada'] = $this->formatDuration($song['duracion']);
        }
        
        return [
            'success' => true,
            'data' => $songs,
            'count' => count($songs)
        ];
    }
    
    /**
     * Buscar canciones
     */
    public function searchSongs($query, $limit = 50) {
        if (empty($query)) {
            return [
                'success' => false,
                'message' => 'Término de búsqueda es requerido'
            ];
        }
        
        $songs = $this->musicModel->search($query, $limit);
        
        foreach ($songs as &$song) {
            $song['duracion_formateada'] = $this->formatDuration($song['duracion']);
        }
        
        return [
            'success' => true,
            'data' => $songs,
            'count' => count($songs)
        ];
    }
    
    /**
     * Obtener canciones por género
     */
    public function getSongsByGenre($genero, $limit = 50, $offset = 0) {
        $songs = $this->musicModel->getByGenre($genero, $limit, $offset);
        
        foreach ($songs as &$song) {
            $song['duracion_formateada'] = $this->formatDuration($song['duracion']);
        }
        
        return [
            'success' => true,
            'data' => $songs,
            'count' => count($songs)
        ];
    }
    
    /**
     * Obtener canciones trending
     */
    public function getTrendingSongs($limit = 20) {
        $songs = $this->musicModel->getTrending($limit);
        
        foreach ($songs as &$song) {
            $song['duracion_formateada'] = $this->formatDuration($song['duracion']);
        }
        
        return [
            'success' => true,
            'data' => $songs
        ];
    }
    
    /**
     * Eliminar canción
     */
    public function deleteSong($songId, $userId) {
        $song = $this->musicModel->getById($songId);
        
        if (!$song) {
            return [
                'success' => false,
                'message' => 'Canción no encontrada'
            ];
        }
        
        // Verificar que el usuario es el propietario
        if ($song['usuario_id'] != $userId) {
            return [
                'success' => false,
                'message' => 'No tienes permiso para eliminar esta canción'
            ];
        }
        
        // Eliminar archivo físico
        $filePath = __DIR__ . '/../' . $song['archivo_url'];
        UploadHelper::deleteFile($filePath);
        
        // Eliminar portada si existe
        if ($song['portada_url']) {
            $coverPath = __DIR__ . '/../' . $song['portada_url'];
            UploadHelper::deleteFile($coverPath);
        }
        
        // Eliminar de BD
        if ($this->musicModel->hardDelete($songId)) {
            return [
                'success' => true,
                'message' => 'Canción eliminada correctamente'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al eliminar la canción'
            ];
        }
    }
    
    /**
     * Registrar reproducción
     */
    public function playSong($songId, $userId = null) {
        // Incrementar contador
        $this->musicModel->incrementPlay($songId);
        
        // Registrar en historial
        $this->musicModel->logPlay($songId, $userId);
        
        return [
            'success' => true,
            'message' => 'Reproducción registrada'
        ];
    }
    
    /**
     * Dar/quitar like a canción
     */
    public function toggleLike($songId, $userId) {
        $hasLiked = $this->musicModel->hasLiked($songId, $userId);
        
        if ($hasLiked) {
            $result = $this->musicModel->removeLike($songId, $userId);
            $action = 'removed';
        } else {
            $result = $this->musicModel->addLike($songId, $userId);
            $action = 'added';
        }
        
        if ($result) {
            return [
                'success' => true,
                'action' => $action,
                'message' => $action === 'added' ? 'Like agregado' : 'Like removido'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al procesar el like'
            ];
        }
    }
    
    /**
     * Extraer duración de archivo MP3 (método simple sin getID3)
     */
    private function extractDuration($filePath) {
        // Método 1: Intentar con ffprobe si está disponible
        if (function_exists('exec')) {
            $output = [];
            $command = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($filePath);
            @exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && !empty($output[0])) {
                return intval(floatval($output[0]));
            }
        }
        
        // Método 2: Estimación basada en tamaño (fallback)
        // Aproximadamente 1 minuto = 1MB para MP3 de 128kbps
        $fileSize = filesize($filePath);
        $estimatedDuration = intval($fileSize / 16000); // Estimación aproximada
        
        return $estimatedDuration > 0 ? $estimatedDuration : null;
    }
    
    /**
     * Formatear duración en MM:SS
     */
    private function formatDuration($seconds) {
        if (!$seconds) return '--:--';
        
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        
        return sprintf('%d:%02d', $minutes, $secs);
    }
}
?>