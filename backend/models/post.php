<?php
/**
 * Post Model - Modelo de publicaciones
 * Maneja todas las operaciones de base de datos relacionadas con publicaciones
 */

class Post {
    private $conn;
    private $table = 'publicaciones';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crear nueva publicación
     */
    public function create($userId, $contenido, $imagen_url = null, $cancion_nombre = null, 
                          $cancion_artista = null, $cancion_url = null, $ubicacion = null) {
        $query = "INSERT INTO " . $this->table . " 
                  (usuario_id, contenido, imagen_url, cancion_nombre, cancion_artista, 
                   cancion_url, ubicacion) 
                  VALUES (:usuario_id, :contenido, :imagen_url, :cancion_nombre, 
                          :cancion_artista, :cancion_url, :ubicacion)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':usuario_id', $userId);
        $stmt->bindParam(':contenido', $contenido);
        $stmt->bindParam(':imagen_url', $imagen_url);
        $stmt->bindParam(':cancion_nombre', $cancion_nombre);
        $stmt->bindParam(':cancion_artista', $cancion_artista);
        $stmt->bindParam(':cancion_url', $cancion_url);
        $stmt->bindParam(':ubicacion', $ubicacion);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Obtener todas las publicaciones (feed)
     */
    public function getAll($limit = 20, $offset = 0) {
        $query = "SELECT 
                    p.id,
                    p.contenido,
                    p.imagen_url,
                    p.cancion_nombre,
                    p.cancion_artista,
                    p.cancion_url,
                    p.ubicacion,
                    p.fecha_creacion,
                    p.fecha_modificacion,
                    u.id as usuario_id,
                    u.usuario,
                    u.nombre,
                    u.foto_perfil,
                    (SELECT COUNT(*) FROM me_gusta WHERE publicacion_id = p.id) as total_likes,
                    (SELECT COUNT(*) FROM comentarios WHERE publicacion_id = p.id) as total_comentarios
                  FROM " . $this->table . " p
                  INNER JOIN usuarios u ON p.usuario_id = u.id
                  ORDER BY p.fecha_creacion DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener publicación por ID
     */
    public function getById($id) {
        $query = "SELECT 
                    p.id,
                    p.contenido,
                    p.imagen_url,
                    p.cancion_nombre,
                    p.cancion_artista,
                    p.cancion_url,
                    p.ubicacion,
                    p.fecha_creacion,
                    p.fecha_modificacion,
                    u.id as usuario_id,
                    u.usuario,
                    u.nombre,
                    u.foto_perfil,
                    (SELECT COUNT(*) FROM me_gusta WHERE publicacion_id = p.id) as total_likes,
                    (SELECT COUNT(*) FROM comentarios WHERE publicacion_id = p.id) as total_comentarios
                  FROM " . $this->table . " p
                  INNER JOIN usuarios u ON p.usuario_id = u.id
                  WHERE p.id = :id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Obtener publicaciones de un usuario específico
     */
    public function getByUserId($userId, $limit = 20, $offset = 0) {
        $query = "SELECT 
                    p.id,
                    p.contenido,
                    p.imagen_url,
                    p.cancion_nombre,
                    p.cancion_artista,
                    p.cancion_url,
                    p.ubicacion,
                    p.fecha_creacion,
                    p.fecha_modificacion,
                    u.id as usuario_id,
                    u.usuario,
                    u.nombre,
                    u.foto_perfil,
                    (SELECT COUNT(*) FROM me_gusta WHERE publicacion_id = p.id) as total_likes,
                    (SELECT COUNT(*) FROM comentarios WHERE publicacion_id = p.id) as total_comentarios
                  FROM " . $this->table . " p
                  INNER JOIN usuarios u ON p.usuario_id = u.id
                  WHERE p.usuario_id = :usuario_id
                  ORDER BY p.fecha_creacion DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Actualizar publicación
     */
    public function update($id, $contenido) {
        $query = "UPDATE " . $this->table . " 
                  SET contenido = :contenido, 
                      fecha_modificacion = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':contenido', $contenido);
        
        return $stmt->execute();
    }
    
    /**
     * Eliminar publicación
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Verificar si un usuario le dio like a una publicación
     */
    public function hasLiked($postId, $userId) {
        $query = "SELECT id FROM me_gusta 
                  WHERE publicacion_id = :post_id AND usuario_id = :user_id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Agregar like
     */
    public function addLike($postId, $userId) {
        $query = "INSERT INTO me_gusta (publicacion_id, usuario_id) 
                  VALUES (:post_id, :user_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    }
    
    /**
     * Quitar like
     */
    public function removeLike($postId, $userId) {
        $query = "DELETE FROM me_gusta 
                  WHERE publicacion_id = :post_id AND usuario_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    }
    
    /**
     * Obtener número de likes
     */
    public function getLikesCount($postId) {
        $query = "SELECT COUNT(*) as total FROM me_gusta 
                  WHERE publicacion_id = :post_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Agregar comentario
     */
    public function addComment($postId, $userId, $contenido) {
        $query = "INSERT INTO comentarios (publicacion_id, usuario_id, contenido) 
                  VALUES (:post_id, :user_id, :contenido)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':contenido', $contenido);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Obtener comentarios de una publicación
     */
    public function getComments($postId) {
        $query = "SELECT 
                    c.id,
                    c.contenido,
                    c.fecha_creacion,
                    c.fecha_modificacion,
                    u.id as usuario_id,
                    u.usuario,
                    u.nombre,
                    u.foto_perfil
                  FROM comentarios c
                  INNER JOIN usuarios u ON c.usuario_id = u.id
                  WHERE c.publicacion_id = :post_id
                  ORDER BY c.fecha_creacion ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener comentario por ID
     */
    public function getCommentById($id) {
        $query = "SELECT 
                    c.id,
                    c.publicacion_id,
                    c.contenido,
                    c.fecha_creacion,
                    c.fecha_modificacion,
                    u.id as usuario_id,
                    u.usuario,
                    u.nombre,
                    u.foto_perfil
                  FROM comentarios c
                  INNER JOIN usuarios u ON c.usuario_id = u.id
                  WHERE c.id = :id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Eliminar comentario
     */
    public function deleteComment($id) {
        $query = "DELETE FROM comentarios WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Obtener feed de publicaciones de amigos
     */
    public function getFriendsFeed($userId, $limit = 20, $offset = 0) {
        $query = "SELECT 
                    p.id,
                    p.contenido,
                    p.imagen_url,
                    p.cancion_nombre,
                    p.cancion_artista,
                    p.cancion_url,
                    p.ubicacion,
                    p.fecha_creacion,
                    p.fecha_modificacion,
                    u.id as usuario_id,
                    u.usuario,
                    u.nombre,
                    u.foto_perfil,
                    (SELECT COUNT(*) FROM me_gusta WHERE publicacion_id = p.id) as total_likes,
                    (SELECT COUNT(*) FROM comentarios WHERE publicacion_id = p.id) as total_comentarios
                  FROM " . $this->table . " p
                  INNER JOIN usuarios u ON p.usuario_id = u.id
                  WHERE p.usuario_id IN (
                      SELECT CASE 
                          WHEN usuario_id = :user_id THEN amigo_id
                          WHEN amigo_id = :user_id THEN usuario_id
                      END
                      FROM amistades
                      WHERE (usuario_id = :user_id OR amigo_id = :user_id)
                      AND estado = 'aceptada'
                  )
                  OR p.usuario_id = :user_id
                  ORDER BY p.fecha_creacion DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?>