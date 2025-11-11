<?php
/**
 * User Model - Modelo de usuarios
 * Maneja todas las operaciones de base de datos relacionadas con usuarios
 */

class User {
    private $conn;
    private $table = 'usuarios';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crear nuevo usuario
     */
    public function create($usuario, $nombre, $clave_hash, $correo) {
        $query = "INSERT INTO " . $this->table . " 
                  (usuario, nombre, clave, correo) 
                  VALUES (:usuario, :nombre, :clave, :correo)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':clave', $clave_hash);
        $stmt->bindParam(':correo', $correo);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Obtener usuario por ID
     */
    public function getById($id) {
        $query = "SELECT id, usuario, nombre, correo, biografia, foto_perfil, 
                         genero_musical_favorito, fecha_registro, ultimo_acceso, 
                         estado, cancion_estado, clave
                  FROM " . $this->table . "
                  WHERE id = :id
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Buscar usuario por username o email
     */
    public function findByUsernameOrEmail($usuario) {
        $query = "SELECT id, usuario, nombre, correo, biografia, foto_perfil, 
                         genero_musical_favorito, fecha_registro, ultimo_acceso, 
                         estado, cancion_estado, clave
                  FROM " . $this->table . "
                  WHERE usuario = :usuario OR correo = :correo
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':correo', $usuario);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Verificar si existe un usuario por username
     */
    public function existsByUsername($usuario) {
        $query = "SELECT id FROM " . $this->table . " 
                  WHERE usuario = :usuario 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Verificar si existe un usuario por email
     */
    public function existsByEmail($correo) {
        $query = "SELECT id FROM " . $this->table . " 
                  WHERE correo = :correo 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Actualizar último acceso
     */
    public function updateLastAccess($id) {
        $query = "UPDATE " . $this->table . " 
                  SET ultimo_acceso = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Actualizar estado (online, offline, ausente)
     */
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table . " 
                  SET estado = :estado 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':estado', $status);
        
        return $stmt->execute();
    }
    
    /**
     * Actualizar perfil de usuario
     */
    public function updateProfile($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $query = "UPDATE " . $this->table . " 
                  SET " . implode(', ', $fields) . " 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Actualizar contraseña
     */
    public function updatePassword($id, $clave_hash) {
        $query = "UPDATE " . $this->table . " 
                  SET clave = :clave 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':clave', $clave_hash);
        
        return $stmt->execute();
    }
    
    /**
     * Buscar usuarios
     */
    public function search($searchTerm, $limit = 20) {
        $query = "SELECT id, usuario, nombre, foto_perfil, biografia, 
                         genero_musical_favorito, estado
                  FROM " . $this->table . "
                  WHERE usuario LIKE :search 
                     OR nombre LIKE :search
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        
        $searchParam = "%{$searchTerm}%";
        $stmt->bindParam(':search', $searchParam);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
/**
     * Obtener estadísticas del usuario
     */
    public function getStats($userId) {
        $stats = [
            'total_posts' => 0,
            'total_friends' => 0,
            'total_likes' => 0
        ];
        
        try {
            // Total de publicaciones
            $query1 = "SELECT COUNT(*) as total_posts FROM publicaciones WHERE usuario_id = ?";
            $stmt1 = $this->conn->prepare($query1);
            $stmt1->execute([$userId]);
            $result1 = $stmt1->fetch();
            $stats['total_posts'] = intval($result1['total_posts']);
            
            // Total de amigos
            $query2 = "SELECT COUNT(*) as total_friends FROM amistades 
                       WHERE (usuario_id = ? OR amigo_id = ?) 
                       AND estado = 'aceptada'";
            $stmt2 = $this->conn->prepare($query2);
            $stmt2->execute([$userId, $userId]);
            $result2 = $stmt2->fetch();
            $stats['total_friends'] = intval($result2['total_friends']);
            
            // Total de likes recibidos
            $query3 = "SELECT COUNT(*) as total_likes FROM me_gusta mg
                       INNER JOIN publicaciones p ON mg.publicacion_id = p.id
                       WHERE p.usuario_id = ?";
            $stmt3 = $this->conn->prepare($query3);
            $stmt3->execute([$userId]);
            $result3 = $stmt3->fetch();
            $stats['total_likes'] = intval($result3['total_likes']);
            
        } catch (PDOException $e) {
            error_log("Error en getStats: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Obtener todos los usuarios (para admin)
     */
    public function getAll($limit = 50, $offset = 0) {
        $query = "SELECT id, usuario, nombre, correo, foto_perfil, 
                         genero_musical_favorito, fecha_registro, estado
                  FROM " . $this->table . "
                  ORDER BY fecha_registro DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Eliminar usuario
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
}
?>