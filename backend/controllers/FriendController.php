<?php
/**
 * FriendController - Maneja sistema de amistades
 * Solicitudes, aceptar/rechazar, listar amigos
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Friend.php';

class FriendController {
    private $db;
    private $friendModel;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->friendModel = new Friend($this->db);
    }
    
    /**
     * Enviar solicitud de amistad
     * @param int $userId - Usuario que envía
     * @param int $friendId - Usuario destinatario
     * @return array
     */
    public function sendFriendRequest($userId, $friendId) {
        // Validar que no sea el mismo usuario
        if ($userId == $friendId) {
            return [
                'success' => false,
                'message' => 'No puedes enviarte una solicitud a ti mismo'
            ];
        }
        
        // Verificar si ya existe una amistad
        $existingFriendship = $this->friendModel->getFriendship($userId, $friendId);
        
        if ($existingFriendship) {
            if ($existingFriendship['estado'] == 'pendiente') {
                return [
                    'success' => false,
                    'message' => 'Ya existe una solicitud de amistad pendiente'
                ];
            } else if ($existingFriendship['estado'] == 'aceptada') {
                return [
                    'success' => false,
                    'message' => 'Ya son amigos'
                ];
            } else if ($existingFriendship['estado'] == 'bloqueada') {
                return [
                    'success' => false,
                    'message' => 'No se puede enviar solicitud'
                ];
            }
        }
        
        // Enviar solicitud
        $requestId = $this->friendModel->sendRequest($userId, $friendId);
        
        if ($requestId) {
            return [
                'success' => true,
                'message' => 'Solicitud de amistad enviada',
                'data' => ['request_id' => $requestId]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al enviar la solicitud'
            ];
        }
    }
    
    /**
     * Aceptar solicitud de amistad
     * @param int $requestId - ID de la solicitud
     * @param int $userId - Usuario que acepta (debe ser el destinatario)
     * @return array
     */
    public function acceptFriendRequest($requestId, $userId) {
        // Obtener la solicitud
        $request = $this->friendModel->getRequestById($requestId);
        
        if (!$request) {
            return [
                'success' => false,
                'message' => 'Solicitud no encontrada'
            ];
        }
        
        // Verificar que el usuario es el destinatario
        if ($request['amigo_id'] != $userId) {
            return [
                'success' => false,
                'message' => 'No tienes permiso para aceptar esta solicitud'
            ];
        }
        
        // Verificar que la solicitud está pendiente
        if ($request['estado'] != 'pendiente') {
            return [
                'success' => false,
                'message' => 'Esta solicitud ya fue respondida'
            ];
        }
        
        // Aceptar solicitud
        if ($this->friendModel->acceptRequest($requestId)) {
            return [
                'success' => true,
                'message' => 'Solicitud de amistad aceptada',
                'data' => ['friendship_id' => $requestId]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al aceptar la solicitud'
            ];
        }
    }
    
    /**
     * Rechazar solicitud de amistad
     * @param int $requestId
     * @param int $userId
     * @return array
     */
    public function rejectFriendRequest($requestId, $userId) {
        // Obtener la solicitud
        $request = $this->friendModel->getRequestById($requestId);
        
        if (!$request) {
            return [
                'success' => false,
                'message' => 'Solicitud no encontrada'
            ];
        }
        
        // Verificar que el usuario es el destinatario
        if ($request['amigo_id'] != $userId) {
            return [
                'success' => false,
                'message' => 'No tienes permiso para rechazar esta solicitud'
            ];
        }
        
        // Verificar que la solicitud está pendiente
        if ($request['estado'] != 'pendiente') {
            return [
                'success' => false,
                'message' => 'Esta solicitud ya fue respondida'
            ];
        }
        
        // Rechazar solicitud
        if ($this->friendModel->rejectRequest($requestId)) {
            return [
                'success' => true,
                'message' => 'Solicitud de amistad rechazada'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al rechazar la solicitud'
            ];
        }
    }
    
    /**
     * Cancelar solicitud de amistad enviada
     * @param int $requestId
     * @param int $userId
     * @return array
     */
    public function cancelFriendRequest($requestId, $userId) {
        // Obtener la solicitud
        $request = $this->friendModel->getRequestById($requestId);
        
        if (!$request) {
            return [
                'success' => false,
                'message' => 'Solicitud no encontrada'
            ];
        }
        
        // Verificar que el usuario es quien envió la solicitud
        if ($request['usuario_id'] != $userId) {
            return [
                'success' => false,
                'message' => 'No tienes permiso para cancelar esta solicitud'
            ];
        }
        
        // Verificar que la solicitud está pendiente
        if ($request['estado'] != 'pendiente') {
            return [
                'success' => false,
                'message' => 'Esta solicitud ya fue respondida'
            ];
        }
        
        // Eliminar solicitud
        if ($this->friendModel->deleteRequest($requestId)) {
            return [
                'success' => true,
                'message' => 'Solicitud cancelada'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al cancelar la solicitud'
            ];
        }
    }
    
    /**
     * Eliminar amistad
     * @param int $friendshipId
     * @param int $userId
     * @return array
     */
    public function removeFriend($friendshipId, $userId) {
        // Obtener la amistad
        $friendship = $this->friendModel->getRequestById($friendshipId);
        
        if (!$friendship) {
            return [
                'success' => false,
                'message' => 'Amistad no encontrada'
            ];
        }
        
        // Verificar que el usuario es parte de la amistad
        if ($friendship['usuario_id'] != $userId && $friendship['amigo_id'] != $userId) {
            return [
                'success' => false,
                'message' => 'No tienes permiso para eliminar esta amistad'
            ];
        }
        
        // Eliminar amistad
        if ($this->friendModel->deleteRequest($friendshipId)) {
            return [
                'success' => true,
                'message' => 'Amistad eliminada'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al eliminar la amistad'
            ];
        }
    }
    
    /**
     * Obtener lista de amigos de un usuario
     * @param int $userId
     * @return array
     */
    public function getFriends($userId) {
        $friends = $this->friendModel->getFriendsList($userId);
        
        return [
            'success' => true,
            'data' => $friends,
            'count' => count($friends)
        ];
    }
    
    /**
     * Obtener solicitudes pendientes recibidas
     * @param int $userId
     * @return array
     */
    public function getPendingRequests($userId) {
        $requests = $this->friendModel->getPendingRequests($userId);
        
        return [
            'success' => true,
            'data' => $requests,
            'count' => count($requests)
        ];
    }
    
    /**
     * Obtener solicitudes pendientes enviadas
     * @param int $userId
     * @return array
     */
    public function getSentRequests($userId) {
        $requests = $this->friendModel->getSentRequests($userId);
        
        return [
            'success' => true,
            'data' => $requests,
            'count' => count($requests)
        ];
    }
    
    /**
     * Verificar si dos usuarios son amigos
     * @param int $userId1
     * @param int $userId2
     * @return array
     */
    public function areFriends($userId1, $userId2) {
        $friendship = $this->friendModel->getFriendship($userId1, $userId2);
        
        $areFriends = $friendship && $friendship['estado'] == 'aceptada';
        
        return [
            'success' => true,
            'data' => [
                'are_friends' => $areFriends,
                'status' => $friendship ? $friendship['estado'] : null
            ]
        ];
    }
    
    /**
     * Obtener estado de amistad entre dos usuarios
     * @param int $userId1
     * @param int $userId2
     * @return array
     */
    public function getFriendshipStatus($userId1, $userId2) {
        $friendship = $this->friendModel->getFriendship($userId1, $userId2);
        
        if (!$friendship) {
            return [
                'success' => true,
                'data' => [
                    'status' => 'none',
                    'message' => 'No existe relación de amistad'
                ]
            ];
        }
        
        $status = $friendship['estado'];
        $message = '';
        
        switch ($status) {
            case 'pendiente':
                if ($friendship['usuario_id'] == $userId1) {
                    $message = 'Solicitud enviada';
                } else {
                    $message = 'Solicitud recibida';
                }
                break;
            case 'aceptada':
                $message = 'Son amigos';
                break;
            case 'rechazada':
                $message = 'Solicitud rechazada';
                break;
            case 'bloqueada':
                $message = 'Usuario bloqueado';
                break;
        }
        
        return [
            'success' => true,
            'data' => [
                'status' => $status,
                'message' => $message,
                'friendship_id' => $friendship['id'],
                'initiated_by' => $friendship['usuario_id']
            ]
        ];
    }
    
    /**
     * Bloquear usuario
     * @param int $userId - Usuario que bloquea
     * @param int $blockedUserId - Usuario bloqueado
     * @return array
     */
    public function blockUser($userId, $blockedUserId) {
        if ($userId == $blockedUserId) {
            return [
                'success' => false,
                'message' => 'No puedes bloquearte a ti mismo'
            ];
        }
        
        // Eliminar cualquier amistad existente
        $friendship = $this->friendModel->getFriendship($userId, $blockedUserId);
        if ($friendship) {
            $this->friendModel->deleteRequest($friendship['id']);
        }
        
        // Crear registro de bloqueo
        if ($this->friendModel->blockUser($userId, $blockedUserId)) {
            return [
                'success' => true,
                'message' => 'Usuario bloqueado'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al bloquear usuario'
            ];
        }
    }
    
    /**
     * Desbloquear usuario
     * @param int $userId
     * @param int $blockedUserId
     * @return array
     */
    public function unblockUser($userId, $blockedUserId) {
        $friendship = $this->friendModel->getFriendship($userId, $blockedUserId);
        
        if (!$friendship || $friendship['estado'] != 'bloqueada') {
            return [
                'success' => false,
                'message' => 'El usuario no está bloqueado'
            ];
        }
        
        if ($this->friendModel->deleteRequest($friendship['id'])) {
            return [
                'success' => true,
                'message' => 'Usuario desbloqueado'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al desbloquear usuario'
            ];
        }
    }
}
?>