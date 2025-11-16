<?php
/**
 * Friend Model - Modelo de amistades
 * Maneja todas las operaciones de base de datos relacionadas con amistades
 * CORREGIDO - 14/11/2025
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
                  VALUES (?, ?, 'pendiente')";
        
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([$userId, $friendId])) {
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
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$requestId]);
    }
    
    /**
     * Rechazar solicitud de amistad
     */
    public function rejectRequest($requestId) {
        $query = "UPDATE " . $this->table . " 
                  SET estado = 'rechazada', fecha_respuesta = NOW() 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$requestId]);
    }
    
    /**
     * Eliminar solicitud/amistad
     */
    public function deleteRequest($requestId) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$requestId]);
    }
    
    /**
     * Obtener solicitud por ID
     */
    public function getRequestById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener relación de amistad entre dos usuarios
     * CORREGIDO: Usa ? en lugar de parámetros nombrados repetidos
     */
    public function getFriendship($userId1, $userId2) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE (usuario_id = ? AND amigo_id = ?) 
                     OR (usuario_id = ? AND amigo_id = ?)
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId1, $userId2, $userId2, $userId1]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener lista de amigos de un usuario
     * CORREGIDO: Usa ? en lugar de parámetros nombrados repetidos
     */
    public function getFriendsList($userId) {
        $query = "SELECT 
                    a.id as friendship_id,
                    a.fecha_respuesta,
                    CASE 
                        WHEN a.usuario_id = ? THEN a.amigo_id
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
                      WHEN a.usuario_id = ? THEN a.amigo_id
                      ELSE a.usuario_id
                  END
                  WHERE (a.usuario_id = ? OR a.amigo_id = ?)
                    AND a.estado = 'aceptada'
                  ORDER BY u.estado DESC, u.nombre ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $userId, $userId, $userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                  WHERE a.amigo_id = ? AND a.estado = 'pendiente'
                  ORDER BY a.fecha_solicitud DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                  WHERE a.usuario_id = ? AND a.estado = 'pendiente'
                  ORDER BY a.fecha_solicitud DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Bloquear usuario
     */
    public function blockUser($userId, $blockedUserId) {
        $query = "INSERT INTO " . $this->table . " 
                  (usuario_id, amigo_id, estado, fecha_respuesta) 
                  VALUES (?, ?, 'bloqueada', NOW())";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$userId, $blockedUserId]);
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
                  WHERE a.usuario_id = ? AND a.estado = 'bloqueada'
                  ORDER BY a.fecha_respuesta DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verificar si dos usuarios son amigos
     * CORREGIDO: Usa ? en lugar de parámetros nombrados repetidos
     */
    public function areFriends($userId1, $userId2) {
        $query = "SELECT id FROM " . $this->table . " 
                  WHERE ((usuario_id = ? AND amigo_id = ?) 
                     OR (usuario_id = ? AND amigo_id = ?))
                    AND estado = 'aceptada'
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId1, $userId2, $userId2, $userId1]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Contar amigos de un usuario
     * CORREGIDO: Usa ? en lugar de parámetros nombrados repetidos
     */
    public function countFriends($userId) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE (usuario_id = ? OR amigo_id = ?) 
                    AND estado = 'aceptada'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $userId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    /**
     * Contar solicitudes pendientes
     */
    public function countPendingRequests($userId) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE amigo_id = ? AND estado = 'pendiente'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    /**
     * Obtener amigos mutuos entre dos usuarios
     * CORREGIDO: Usa ? en lugar de parámetros nombrados repetidos
     */
    public function getMutualFriends($userId1, $userId2) {
        $query = "SELECT DISTINCT u.id, u.usuario, u.nombre, u.foto_perfil
                  FROM usuarios u
                  INNER JOIN " . $this->table . " a1 ON (
                      (a1.usuario_id = ? AND a1.amigo_id = u.id) OR
                      (a1.amigo_id = ? AND a1.usuario_id = u.id)
                  )
                  INNER JOIN " . $this->table . " a2 ON (
                      (a2.usuario_id = ? AND a2.amigo_id = u.id) OR
                      (a2.amigo_id = ? AND a2.usuario_id = u.id)
                  )
                  WHERE a1.estado = 'aceptada' AND a2.estado = 'aceptada'
                  ORDER BY u.nombre";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId1, $userId1, $userId2, $userId2]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>