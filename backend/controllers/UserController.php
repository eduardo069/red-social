<?php
/**
 * UserController - Maneja operaciones de usuarios
 * Perfiles, búsqueda, actualización
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $db;
    private $userModel;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->userModel = new User($this->db);
    }
    
    /**
     * Obtener perfil de usuario por ID
     * @param int $userId
     * @return array
     */
    public function getProfile($userId) {
        $user = $this->userModel->getById($userId);
        
        if ($user) {
            // No devolver información sensible
            unset($user['clave']);
            
            return [
                'success' => true,
                'data' => $user
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado'
            ];
        }
    }
    
    /**
     * Obtener perfil de usuario por username
     * @param string $username
     * @return array
     */
    public function getProfileByUsername($username) {
        $user = $this->userModel->findByUsernameOrEmail($username);
        
        if ($user) {
            // No devolver información sensible
            unset($user['clave']);
            
            return [
                'success' => true,
                'data' => $user
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado'
            ];
        }
    }
    
    /**
     * Actualizar perfil de usuario
     * @param int $userId
     * @param array $data
     * @return array
     */
    public function updateProfile($userId, $data) {
        // Validar que el usuario existe
        $user = $this->userModel->getById($userId);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado'
            ];
        }
        
        // Preparar datos a actualizar
        $updateData = [];
        
        // Nombre
        if (isset($data['nombre'])) {
            $nombre = htmlspecialchars(strip_tags(trim($data['nombre'])));
            if (empty($nombre)) {
                return [
                    'success' => false,
                    'message' => 'El nombre no puede estar vacío'
                ];
            }
            $updateData['nombre'] = $nombre;
        }
        
        // Biografía
        if (isset($data['biografia'])) {
            $biografia = htmlspecialchars(strip_tags(trim($data['biografia'])));
            if (strlen($biografia) > 500) {
                return [
                    'success' => false,
                    'message' => 'La biografía no puede exceder los 500 caracteres'
                ];
            }
            $updateData['biografia'] = $biografia;
        }
        
        // Género musical favorito
        if (isset($data['genero_musical_favorito'])) {
            $genero = htmlspecialchars(strip_tags(trim($data['genero_musical_favorito'])));
            $updateData['genero_musical_favorito'] = $genero;
        }
        
        // Canción de estado
        if (isset($data['cancion_estado'])) {
            $cancion = htmlspecialchars(strip_tags(trim($data['cancion_estado'])));
            if (strlen($cancion) > 255) {
                return [
                    'success' => false,
                    'message' => 'La canción de estado no puede exceder los 255 caracteres'
                ];
            }
            $updateData['cancion_estado'] = $cancion;
        }
        
        // Foto de perfil
        if (isset($data['foto_perfil'])) {
            $foto = htmlspecialchars(strip_tags(trim($data['foto_perfil'])));
            $updateData['foto_perfil'] = $foto;
        }
        
        // Actualizar
        if ($this->userModel->updateProfile($userId, $updateData)) {
            $updatedUser = $this->userModel->getById($userId);
            unset($updatedUser['clave']);
            
            return [
                'success' => true,
                'message' => 'Perfil actualizado exitosamente',
                'data' => $updatedUser
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al actualizar el perfil'
            ];
        }
    }
    
    /**
     * Cambiar contraseña
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return array
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Obtener usuario
        $user = $this->userModel->getById($userId);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado'
            ];
        }
        
        // Verificar contraseña actual
        if (!password_verify($currentPassword, $user['clave'])) {
            return [
                'success' => false,
                'message' => 'La contraseña actual es incorrecta'
            ];
        }
        
        // Validar nueva contraseña
        if (strlen($newPassword) < 6) {
            return [
                'success' => false,
                'message' => 'La nueva contraseña debe tener al menos 6 caracteres'
            ];
        }
        
        // Hash de la nueva contraseña
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Actualizar contraseña
        if ($this->userModel->updatePassword($userId, $newPasswordHash)) {
            return [
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al actualizar la contraseña'
            ];
        }
    }
    
    /**
     * Buscar usuarios
     * @param string $query - Término de búsqueda
     * @param int $limit
     * @return array
     */
    public function searchUsers($query, $limit = 20) {
        if (empty($query)) {
            return [
                'success' => false,
                'message' => 'El término de búsqueda no puede estar vacío'
            ];
        }
        
        $query = htmlspecialchars(strip_tags(trim($query)));
        $users = $this->userModel->search($query, $limit);
        
        // Remover información sensible
        foreach ($users as &$user) {
            unset($user['clave']);
            unset($user['correo']); // El correo es privado en búsquedas
        }
        
        return [
            'success' => true,
            'data' => $users,
            'count' => count($users)
        ];
    }
    
    /**
     * Actualizar estado del usuario (online, offline, ausente)
     * @param int $userId
     * @param string $status
     * @return array
     */
    public function updateStatus($userId, $status) {
        $validStatuses = ['online', 'offline', 'ausente'];
        
        if (!in_array($status, $validStatuses)) {
            return [
                'success' => false,
                'message' => 'Estado no válido'
            ];
        }
        
        if ($this->userModel->updateStatus($userId, $status)) {
            return [
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'data' => ['status' => $status]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al actualizar el estado'
            ];
        }
    }
    
    /**
     * Subir foto de perfil
     * @param int $userId
     * @param array $file - Archivo de $_FILES
     * @return array
     */
    public function uploadProfilePhoto($userId, $file) {
        // Validar que se subió un archivo
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'Error al subir el archivo'
            ];
        }
        
        // Validar tipo de archivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Tipo de archivo no permitido. Solo se permiten imágenes (JPEG, PNG, GIF, WebP)'
            ];
        }
        
        // Validar tamaño (máximo 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            return [
                'success' => false,
                'message' => 'El archivo es demasiado grande. Tamaño máximo: 5MB'
            ];
        }
        
        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'profile_' . $userId . '_' . time() . '.' . $extension;
        
        // Directorio de destino
        $uploadDir = __DIR__ . '/../uploads/profiles/';
        
        // Crear directorio si no existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $destination = $uploadDir . $fileName;
        
        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Actualizar en la base de datos
            $photoUrl = 'uploads/profiles/' . $fileName;
            
            if ($this->userModel->updateProfile($userId, ['foto_perfil' => $photoUrl])) {
                return [
                    'success' => true,
                    'message' => 'Foto de perfil actualizada exitosamente',
                    'data' => ['foto_perfil' => $photoUrl]
                ];
            } else {
                // Eliminar archivo si falla la actualización en BD
                unlink($destination);
                return [
                    'success' => false,
                    'message' => 'Error al guardar la foto en la base de datos'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Error al guardar el archivo'
            ];
        }
    }
    
    /**
     * Obtener estadísticas del usuario
     * @param int $userId
     * @return array
     */
    public function getUserStats($userId) {
        $stats = $this->userModel->getStats($userId);
        
        return [
            'success' => true,
            'data' => $stats
        ];
    }
}
?>