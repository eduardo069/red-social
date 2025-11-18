<?php
/**
 * Music Model - Modelo de canciones
 * Maneja todas las operaciones de base de datos relacionadas con música
 * backend/models/Music.php
 */

class Music {
    private $conn;
    private $table = 'canciones';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crear nueva canción
     */
    public function create($userId, $titulo, $artista, $genero, $archivo_url, 
                          $portada_url = null, $duracion = null, $tamanio = null, 
                          $descripcion = null) {
        $query = "INSERT INTO " . $this->table . " 
                  (usuario_id, titulo, artista, genero, archivo_url, portada_url, 
                   duracion, tamanio, descripcion, activo) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([
            $userId, $titulo, $artista, $genero, $archivo_url, 
            $portada_url, $duracion, $tamanio, $descripcion
        ])) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Obtener canción por ID
     */
    public function getById($id) {
        $query = "SELECT 
                    c.*,
                    u.usuario,
                    u.nombre as nombre_usuario,
                    u.foto_perfil
                  FROM " . $this->table . " c
                  INNER JOIN usuarios u ON c.usuario_id = u.id
                  WHERE c.id = ? AND c.activo = 1
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener canciones de un usuario
     */
    public function getByUserId($userId, $limit = 50, $offset = 0) {
        $query = "SELECT 
                    c.*,
                    u.usuario,
                    u.nombre as nombre_usuario,
                    u.foto_perfil
                  FROM " . $this->table . " c
                  INNER JOIN usuarios u ON c.usuario_id = u.id
                  WHERE c.usuario_id = ? AND c.activo = 1
                  ORDER BY c.fecha_subida DESC
                  LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $limit, $offset]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todas las canciones (feed público)
     */
    public function getAll($limit = 50, $offset = 0) {
        $query = "SELECT 
                    c.*,
                    u.usuario,
                    u.nombre as nombre_usuario,
                    u.foto_perfil
                  FROM " . $this->table . " c
                  INNER JOIN usuarios u ON c.usuario_id = u.id
                  WHERE c.activo = 1
                  ORDER BY c.fecha_subida DESC
                  LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit, $offset]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Buscar canciones
     */
    public function search($searchTerm, $limit = 50) {
        $query = "SELECT 
                    c.*,
                    u.usuario,
                    u.nombre as nombre_usuario,
                    u.foto_perfil
                  FROM " . $this->table . " c
                  INNER JOIN usuarios u ON c.usuario_id = u.id
                  WHERE c.activo = 1
                    AND (c.titulo LIKE ? 
                         OR c.artista LIKE ? 
                         OR c.genero LIKE ?)
                  ORDER BY c.reproducciones DESC, c.fecha_subida DESC
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $searchParam = "%{$searchTerm}%";
        $stmt->execute([$searchParam, $searchParam, $searchParam, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener canciones por género
     */
    public function getByGenre($genero, $limit = 50, $offset = 0) {
        $query = "SELECT 
                    c.*,
                    u.usuario,
                    u.nombre as nombre_usuario,
                    u.foto_perfil
                  FROM " . $this->table . " c
                  INNER JOIN usuarios u ON c.usuario_id = u.id
                  WHERE c.genero = ? AND c.activo = 1
                  ORDER BY c.reproducciones DESC, c.fecha_subida DESC
                  LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$genero, $limit, $offset]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener canciones trending (más reproducidas)
     */
    public function getTrending($limit = 20) {
        $query = "SELECT 
                    c.*,
                    u.usuario,
                    u.nombre as nombre_usuario,
                    u.foto_perfil
                  FROM " . $this->table . " c
                  INNER JOIN usuarios u ON c.usuario_id = u.id
                  WHERE c.activo = 1
                  ORDER BY c.reproducciones DESC, c.likes DESC
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Incrementar contador de reproducciones
     */
    public function incrementPlay($id) {
        $query = "UPDATE " . $this->table . " 
                  SET reproducciones = reproducciones + 1 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
    
    /**
     * Actualizar información de canción
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        
        $query = "UPDATE " . $this->table . " 
                  SET " . implode(', ', $fields) . " 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }
    
    /**
     * Eliminar canción (soft delete)
     */
    public function delete($id) {
        $query = "UPDATE " . $this->table . " 
                  SET activo = 0 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
    
    /**
     * Eliminar canción permanentemente
     */
    public function hardDelete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
    
    /**
     * Verificar si usuario le dio like a una canción
     */
    public function hasLiked($songId, $userId) {
        $query = "SELECT id FROM cancion_likes 
                  WHERE cancion_id = ? AND usuario_id = ? 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$songId, $userId]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Agregar like a canción
     */
    public function addLike($songId, $userId) {
        try {
            $this->conn->beginTransaction();
            
            // Insertar like
            $query1 = "INSERT INTO cancion_likes (cancion_id, usuario_id) 
                       VALUES (?, ?)";
            $stmt1 = $this->conn->prepare($query1);
            $stmt1->execute([$songId, $userId]);
            
            // Incrementar contador
            $query2 = "UPDATE " . $this->table . " 
                       SET likes = likes + 1 
                       WHERE id = ?";
            $stmt2 = $this->conn->prepare($query2);
            $stmt2->execute([$songId]);
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
    
    /**
     * Quitar like de canción
     */
    public function removeLike($songId, $userId) {
        try {
            $this->conn->beginTransaction();
            
            // Eliminar like
            $query1 = "DELETE FROM cancion_likes 
                       WHERE cancion_id = ? AND usuario_id = ?";
            $stmt1 = $this->conn->prepare($query1);
            $stmt1->execute([$songId, $userId]);
            
            // Decrementar contador
            $query2 = "UPDATE " . $this->table . " 
                       SET likes = GREATEST(likes - 1, 0) 
                       WHERE id = ?";
            $stmt2 = $this->conn->prepare($query2);
            $stmt2->execute([$songId]);
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
    
    /**
     * Registrar reproducción
     */
    public function logPlay($songId, $userId = null, $duration = null, $completed = false) {
        $query = "INSERT INTO reproducciones 
                  (cancion_id, usuario_id, duracion_reproducida, completada, ip_address) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        
        return $stmt->execute([
            $songId, 
            $userId, 
            $duration, 
            $completed ? 1 : 0, 
            $ip
        ]);
    }
    
    /**
     * Contar canciones de un usuario
     */
    public function countByUser($userId) {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table . " 
                  WHERE usuario_id = ? AND activo = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($result['total']);
    }
    
    /**
     * Obtener estadísticas de una canción
     */
    public function getStats($songId) {
        $query = "SELECT 
                    c.reproducciones,
                    c.likes,
                    (SELECT COUNT(*) FROM reproducciones WHERE cancion_id = c.id) as total_plays,
                    (SELECT COUNT(*) FROM reproducciones WHERE cancion_id = c.id AND completada = 1) as completed_plays
                  FROM " . $this->table . " c
                  WHERE c.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$songId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>