<?php
/**
 * Friend Model - Modelo de amistades
 * Maneja todas las operaciones de base de datos relacionadas con amistades
 * 7/11/2025 21:24
 */

class Friend {
    private $conn;
    private $table = 'amistades';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Enviar solicitud de amistad
     */
    public function sendRequest($userId, $friendId) {
        $query = "INSERT INTO " . $this->table . " 
                  (usuario_id, amigo_id, estado) 
                  VALUES (:usuario_id, :amigo_id, 'pendiente')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $userId);
        $stmt->bindParam(':amigo_id', $friendId);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Aceptar solicitud de amistad
     */
    public function acceptRequest($requestId) {
        $query = "UPDATE " . $this->table . " 
                  SET estado = 'aceptada', fecha_respuesta = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $requestId);
        
        return $stmt->execute();
    }
    
    /**
     * Rechazar solicitud de amistad
     */
    public function rejectRequest($requestId) {
        $query = "UPDATE " . $this->table . " 
                  SET estado = 'rechazada', fecha_respuesta = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $requestId);
        
        return $stmt->execute();
    }
    
    /**
     * Eliminar solicitud/amistad
     */
    public function deleteRequest($requestId) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $requestId);
        
        return $stmt->execute();
    }
    
    /**
     * Obtener solicitud por ID
     */
    public function getRequestById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Obtener relación de amistad entre dos usuarios
     */
    public function getFriendship($userId1, $userId2) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE (usuario_id = :user1 AND amigo_id = :user2) 
                     OR (usuario_id = :user2 AND amigo_id = :user1)
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user1', $userId1);
        $stmt->bindParam(':user2', $userId2);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Obtener lista de amigos de un usuario
     */
    public function getFriendsList($userId) {
        $query = "SELECT 
                    a.id as friendship_id,
                    a.fecha_respuesta,
                    CASE 
                        WHEN a.usuario_id = :user_id THEN a.amigo_id
                        ELSE a.usuario_id
                    END as user_id,
                    u.usuario,
                    u.nombre,
                    u.foto_perfil,
                    u.estado,
                    u.cancion_estado,
                    u.genero_musical_favorito
                  FROM " . $this->table . " a
                  INNER JOIN usuarios u ON u.id = CASE 
                      WHEN a.usuario_id = :user_id2 THEN a.amigo_id
                      ELSE a.usuario_id
                  END
                  WHERE (a.usuario_id = :user_id3 OR a.amigo_id = :user_id4)
                    AND a.estado = 'aceptada'
                  ORDER BY u.estado DESC, u.nombre ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':user_id2', $userId);
        $stmt->bindParam(':user_id3', $userId);
        $stmt->bindParam(':user_id4', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener solicitudes pendientes recibidas
     */
    public function getPendingRequests($userId) {
        $query = "SELECT 
                    a.id as request_id,
                    a.fecha_solicitud,
                    u.id as user_id,
                    u.usuario,
                    u.nombre,
                    u.foto_perfil,
                    u.genero_musical_favorito
                  FROM " . $this->table . " a
                  INNER JOIN usuarios u ON a.usuario_id = u.id
                  WHERE a.amigo_id = :user_id AND a.estado = 'pendiente'
                  ORDER BY a.fecha_solicitud DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener solicitudes pendientes enviadas
     */
    public function getSentRequests($userId) {
        $query = "SELECT 
                    a.id as request_id,
                    a.fecha_solicitud,
                    u.id as user_id,
                    u.usuario,
                    u.nombre,
                    u.foto_perfil,
                    u.genero_musical_favorito
                  FROM " . $this->table . " a
                  INNER JOIN usuarios u ON a.amigo_id = u.id
                  WHERE a.usuario_id = :user_id AND a.estado = 'pendiente'
                  ORDER BY a.fecha_solicitud DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Bloquear usuario
     */
    public function blockUser($userId, $blockedUserId) {
        $query = "INSERT INTO " . $this->table . " 
                  (usuario_id, amigo_id, estado, fecha_respuesta) 
                  VALUES (:usuario_id, :amigo_id, 'bloqueada', NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $userId);
        $stmt->bindParam(':amigo_id', $blockedUserId);
        
        return $stmt->execute();
    }
    
    /**
     * Obtener usuarios bloqueados
     */
    public function getBlockedUsers($userId) {
        $query = "SELECT 
                    a.id as block_id,
                    a.fecha_respuesta as blocked_date,
                    u.id as user_id,
                    u.usuario,
                    u.nombre,
                    u.foto_perfil
                  FROM " . $this->table . " a
                  INNER JOIN usuarios u ON a.amigo_id = u.id
                  WHERE a.usuario_id = :user_id AND a.estado = 'bloqueada'
                  ORDER BY a.fecha_respuesta DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Verificar si dos usuarios son amigos
     */
    public function areFriends($userId1, $userId2) {
        $query = "SELECT id FROM " . $this->table . " 
                  WHERE ((usuario_id = :user1 AND amigo_id = :user2) 
                     OR (usuario_id = :user2 AND amigo_id = :user1))
                    AND estado = 'aceptada'
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user1', $userId1);
        $stmt->bindParam(':user2', $userId2);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Contar amigos de un usuario
     */
    public function countFriends($userId) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE (usuario_id = :user_id OR amigo_id = :user_id) 
                    AND estado = 'aceptada'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Contar solicitudes pendientes
     */
    public function countPendingRequests($userId) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE amigo_id = :user_id AND estado = 'pendiente'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Obtener amigos mutuos entre dos usuarios
     */
    public function getMutualFriends($userId1, $userId2) {
        $query = "SELECT DISTINCT u.id, u.usuario, u.nombre, u.foto_perfil
                  FROM usuarios u
                  INNER JOIN " . $this->table . " a1 ON (
                      (a1.usuario_id = :user1 AND a1.amigo_id = u.id) OR
                      (a1.amigo_id = :user1 AND a1.usuario_id = u.id)
                  )
                  INNER JOIN " . $this->table . " a2 ON (
                      (a2.usuario_id = :user2 AND a2.amigo_id = u.id) OR
                      (a2.amigo_id = :user2 AND a2.usuario_id = u.id)
                  )
                  WHERE a1.estado = 'aceptada' AND a2.estado = 'aceptada'
                  ORDER BY u.nombre";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user1', $userId1);
        $stmt->bindParam(':user2', $userId2);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?>