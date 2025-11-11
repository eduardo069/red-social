<?php
/**
 * Modelo Like - Maneja la tabla 'me_gusta'
 */

class Like {
    private $conn;
    private $table_name = "me_gusta";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Dar like a una publicación
     */
    public function like($publicacionId, $usuarioId) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (publicacion_id, usuario_id) 
                  VALUES (:publicacion_id, :usuario_id)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':publicacion_id', $publicacionId);
        $stmt->bindParam(':usuario_id', $usuarioId);
        
        try {
            if ($stmt->execute()) {
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error al dar like: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Quitar like de una publicación
     */
    public function unlike($publicacionId, $usuarioId) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE publicacion_id = :publicacion_id 
                  AND usuario_id = :usuario_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':publicacion_id', $publicacionId);
        $stmt->bindParam(':usuario_id', $usuarioId);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    /**
     * Verificar si un usuario le dio like a una publicación
     */
    public function hasUserLiked($publicacionId, $usuarioId) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE publicacion_id = :publicacion_id 
                  AND usuario_id = :usuario_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':publicacion_id', $publicacionId);
        $stmt->bindParam(':usuario_id', $usuarioId);
        
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['count'] > 0;
    }
    
    /**
     * Obtener total de likes de una publicación
     */
    public function getPostLikesCount($publicacionId) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE publicacion_id = :publicacion_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':publicacion_id', $publicacionId);
        
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return intval($row['count']);
    }
    
    /**
     * Obtener lista de usuarios que le dieron like a una publicación
     */
    public function getPostLikes($publicacionId) {
        $query = "SELECT 
                    u.id,
                    u.usuario,
                    u.nombre,
                    u.foto_perfil,
                    mg.fecha_creacion
                  FROM " . $this->table_name . " mg
                  INNER JOIN usuarios u ON mg.usuario_id = u.id
                  WHERE mg.publicacion_id = :publicacion_id
                  ORDER BY mg.fecha_creacion DESC";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':publicacion_id', $publicacionId);
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todas las publicaciones que le gustan a un usuario
     */
    public function getUserLikes($usuarioId) {
        $query = "SELECT 
                    p.*,
                    u.usuario,
                    u.nombre,
                    u.foto_perfil,
                    mg.fecha_creacion as fecha_like
                  FROM " . $this->table_name . " mg
                  INNER JOIN publicaciones p ON mg.publicacion_id = p.id
                  INNER JOIN usuarios u ON p.usuario_id = u.id
                  WHERE mg.usuario_id = :usuario_id
                  ORDER BY mg.fecha_creacion DESC";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':usuario_id', $usuarioId);
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Eliminar todos los likes de una publicación (cuando se elimina)
     */
    public function deletePostLikes($publicacionId) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE publicacion_id = :publicacion_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':publicacion_id', $publicacionId);
        
        return $stmt->execute();
    }
}
?>