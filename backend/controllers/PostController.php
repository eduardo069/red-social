<?php
/**
 * PostController - Maneja publicaciones
 * CRUD de posts, likes, comentarios
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Post.php';

class PostController {
    private $db;
    private $postModel;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->postModel = new Post($this->db);
    }
    
    /**
     * Crear una nueva publicación
     * @param int $userId - ID del usuario
     * @param array $data - Datos de la publicación
     * @return array
     */
    public function createPost($userId, $data) {
        // Validar que haya contenido
        if (empty($data['contenido'])) {
            return [
                'success' => false,
                'message' => 'El contenido de la publicación es obligatorio'
            ];
        }
        
        // Sanitizar datos
        $contenido = htmlspecialchars(strip_tags(trim($data['contenido'])));
        $imagen_url = isset($data['imagen_url']) ? htmlspecialchars(strip_tags(trim($data['imagen_url']))) : null;
        $cancion_nombre = isset($data['cancion_nombre']) ? htmlspecialchars(strip_tags(trim($data['cancion_nombre']))) : null;
        $cancion_artista = isset($data['cancion_artista']) ? htmlspecialchars(strip_tags(trim($data['cancion_artista']))) : null;
        $cancion_url = isset($data['cancion_url']) ? htmlspecialchars(strip_tags(trim($data['cancion_url']))) : null;
        $ubicacion = isset($data['ubicacion']) ? htmlspecialchars(strip_tags(trim($data['ubicacion']))) : null;
        
        // Validar longitud del contenido
        if (strlen($contenido) > 5000) {
            return [
                'success' => false,
                'message' => 'El contenido no puede exceder los 5000 caracteres'
            ];
        }
        
        // Crear publicación
        $postId = $this->postModel->create(
            $userId,
            $contenido,
            $imagen_url,
            $cancion_nombre,
            $cancion_artista,
            $cancion_url,
            $ubicacion
        );
        
        if ($postId) {
            $post = $this->postModel->getById($postId);
            return [
                'success' => true,
                'message' => 'Publicación creada exitosamente',
                'data' => $post
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al crear la publicación'
            ];
        }
    }
    
    /**
     * Obtener todas las publicaciones (feed)
     * @param int $limit - Número de publicaciones
     * @param int $offset - Desplazamiento
     * @return array
     */
    public function getFeed($limit = 20, $offset = 0) {
        $posts = $this->postModel->getAll($limit, $offset);
        
        return [
            'success' => true,
            'data' => $posts,
            'count' => count($posts)
        ];
    }
    
    /**
     * Obtener publicaciones de un usuario específico
     * @param int $userId - ID del usuario
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getUserPosts($userId, $limit = 20, $offset = 0) {
        $posts = $this->postModel->getByUserId($userId, $limit, $offset);
        
        return [
            'success' => true,
            'data' => $posts,
            'count' => count($posts)
        ];
    }
    
    /**
     * Obtener una publicación por ID
     * @param int $postId
     * @return array
     */
    public function getPost($postId) {
        $post = $this->postModel->getById($postId);
        
        if ($post) {
            return [
                'success' => true,
                'data' => $post
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Publicación no encontrada'
            ];
        }
    }
    
    /**
     * Actualizar una publicación
     * @param int $postId
     * @param int $userId - Usuario que intenta actualizar
     * @param array $data
     * @return array
     */
    public function updatePost($postId, $userId, $data) {
        // Verificar que la publicación existe y pertenece al usuario
        $post = $this->postModel->getById($postId);
        
        if (!$post) {
            return [
                'success' => false,
                'message' => 'Publicación no encontrada'
            ];
        }
        
        if ($post['usuario_id'] != $userId) {
            return [
                'success' => false,
                'message' => 'No tienes permiso para editar esta publicación'
            ];
        }
        
        // Sanitizar datos
        $contenido = htmlspecialchars(strip_tags(trim($data['contenido'])));
        
        if (strlen($contenido) > 5000) {
            return [
                'success' => false,
                'message' => 'El contenido no puede exceder los 5000 caracteres'
            ];
        }
        
        // Actualizar
        if ($this->postModel->update($postId, $contenido)) {
            $updatedPost = $this->postModel->getById($postId);
            return [
                'success' => true,
                'message' => 'Publicación actualizada exitosamente',
                'data' => $updatedPost
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al actualizar la publicación'
            ];
        }
    }
    
    /**
     * Eliminar una publicación
     * @param int $postId
     * @param int $userId - Usuario que intenta eliminar
     * @return array
     */
    public function deletePost($postId, $userId) {
        // Verificar que la publicación existe y pertenece al usuario
        $post = $this->postModel->getById($postId);
        
        if (!$post) {
            return [
                'success' => false,
                'message' => 'Publicación no encontrada'
            ];
        }
        
        if ($post['usuario_id'] != $userId) {
            return [
                'success' => false,
                'message' => 'No tienes permiso para eliminar esta publicación'
            ];
        }
        
        // Eliminar
        if ($this->postModel->delete($postId)) {
            return [
                'success' => true,
                'message' => 'Publicación eliminada exitosamente'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al eliminar la publicación'
            ];
        }
    }
    
    /**
     * Dar o quitar like a una publicación
     * @param int $postId
     * @param int $userId
     * @return array
     */
    public function toggleLike($postId, $userId) {
        // Verificar que la publicación existe
        $post = $this->postModel->getById($postId);
        
        if (!$post) {
            return [
                'success' => false,
                'message' => 'Publicación no encontrada'
            ];
        }
        
        // Verificar si ya le dio like
        $hasLiked = $this->postModel->hasLiked($postId, $userId);
        
        if ($hasLiked) {
            // Quitar like
            if ($this->postModel->removeLike($postId, $userId)) {
                return [
                    'success' => true,
                    'message' => 'Like eliminado',
                    'action' => 'removed',
                    'total_likes' => $this->postModel->getLikesCount($postId)
                ];
            }
        } else {
            // Agregar like
            if ($this->postModel->addLike($postId, $userId)) {
                return [
                    'success' => true,
                    'message' => 'Like agregado',
                    'action' => 'added',
                    'total_likes' => $this->postModel->getLikesCount($postId)
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Error al procesar el like'
        ];
    }
    
    /**
     * Agregar un comentario a una publicación
     * @param int $postId
     * @param int $userId
     * @param string $contenido
     * @return array
     */
    public function addComment($postId, $userId, $contenido) {
        // Validar contenido
        if (empty($contenido)) {
            return [
                'success' => false,
                'message' => 'El comentario no puede estar vacío'
            ];
        }
        
        // Sanitizar
        $contenido = htmlspecialchars(strip_tags(trim($contenido)));
        
        if (strlen($contenido) > 1000) {
            return [
                'success' => false,
                'message' => 'El comentario no puede exceder los 1000 caracteres'
            ];
        }
        
        // Verificar que la publicación existe
        $post = $this->postModel->getById($postId);
        
        if (!$post) {
            return [
                'success' => false,
                'message' => 'Publicación no encontrada'
            ];
        }
        
        // Agregar comentario
        $commentId = $this->postModel->addComment($postId, $userId, $contenido);
        
        if ($commentId) {
            $comment = $this->postModel->getCommentById($commentId);
            return [
                'success' => true,
                'message' => 'Comentario agregado exitosamente',
                'data' => $comment
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al agregar el comentario'
            ];
        }
    }
    
    /**
     * Obtener comentarios de una publicación
     * @param int $postId
     * @return array
     */
    public function getComments($postId) {
        $comments = $this->postModel->getComments($postId);
        
        return [
            'success' => true,
            'data' => $comments,
            'count' => count($comments)
        ];
    }
    
    /**
     * Eliminar un comentario
     * @param int $commentId
     * @param int $userId
     * @return array
     */
    public function deleteComment($commentId, $userId) {
        $comment = $this->postModel->getCommentById($commentId);
        
        if (!$comment) {
            return [
                'success' => false,
                'message' => 'Comentario no encontrado'
            ];
        }
        
        if ($comment['usuario_id'] != $userId) {
            return [
                'success' => false,
                'message' => 'No tienes permiso para eliminar este comentario'
            ];
        }
        
        if ($this->postModel->deleteComment($commentId)) {
            return [
                'success' => true,
                'message' => 'Comentario eliminado exitosamente'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al eliminar el comentario'
            ];
        }
    }
}
?>