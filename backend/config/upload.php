<?php
/**
 * upload.php - Configuración de subida de archivos
 * Define límites, extensiones permitidas y rutas
 * backend/config/upload.php
 * 16/11/2025
 */

// ============================================
// RUTAS DE ALMACENAMIENTO
// ============================================

define('UPLOAD_BASE_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_MUSIC_DIR', UPLOAD_BASE_DIR . 'music/');
define('UPLOAD_IMAGES_DIR', UPLOAD_BASE_DIR . 'images/');
define('UPLOAD_PROFILE_DIR', UPLOAD_BASE_DIR . 'profile/');
define('UPLOAD_AVATARS_DIR', UPLOAD_BASE_DIR . 'avatars/');

// Rutas relativas para URLs (para mostrar en el frontend)
define('UPLOAD_MUSIC_URL', '/red-social/backend/uploads/music/');
define('UPLOAD_IMAGES_URL', '/red-social/backend/uploads/images/');
define('UPLOAD_PROFILE_URL', '/red-social/backend/uploads/profile/');
define('UPLOAD_AVATARS_URL', '/red-social/backend/uploads/avatars/');

// ============================================
// LÍMITES DE TAMAÑO (en bytes)
// ============================================

define('MAX_FILE_SIZE_MUSIC', 10 * 1024 * 1024);    // 10 MB para música
define('MAX_FILE_SIZE_IMAGE', 5 * 1024 * 1024);     // 5 MB para imágenes
define('MAX_FILE_SIZE_PROFILE', 2 * 1024 * 1024);   // 2 MB para fotos de perfil

// ============================================
// EXTENSIONES PERMITIDAS
// ============================================

define('ALLOWED_MUSIC_EXTENSIONS', ['mp3', 'wav', 'ogg', 'm4a']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_PROFILE_EXTENSIONS', ['jpg', 'jpeg', 'png']);

// ============================================
// MIME TYPES PERMITIDOS
// ============================================

define('ALLOWED_MUSIC_MIMES', [
    'audio/mpeg',           // MP3
    'audio/mp3',            // MP3
    'audio/wav',            // WAV
    'audio/wave',           // WAV
    'audio/x-wav',          // WAV
    'audio/ogg',            // OGG
    'audio/mp4',            // M4A
    'audio/x-m4a'           // M4A
]);

define('ALLOWED_IMAGE_MIMES', [
    'image/jpeg',           // JPG/JPEG
    'image/jpg',            // JPG
    'image/png',            // PNG
    'image/gif',            // GIF
    'image/webp'            // WEBP
]);

// ============================================
// CONFIGURACIÓN DE IMÁGENES
// ============================================

define('IMAGE_MAX_WIDTH', 1920);           // Ancho máximo para redimensionar
define('IMAGE_MAX_HEIGHT', 1080);          // Alto máximo para redimensionar
define('PROFILE_IMAGE_SIZE', 400);         // Tamaño para fotos de perfil (cuadradas)
define('IMAGE_QUALITY', 85);               // Calidad de compresión JPEG (0-100)

// ============================================
// AVATARES PREDETERMINADOS
// ============================================

define('AVATARS_DIR', UPLOAD_BASE_DIR . 'avatars/');
define('AVATARS_URL', '/red-social/backend/uploads/avatars/');
define('DEFAULT_AVATAR', 'default-avatar.jpg');

// Lista de avatares disponibles (archivos .jpg en tu carpeta)
// NOTA: Modifica este array con los nombres exactos de tus archivos
define('AVAILABLE_AVATARS', [
    'alienlatino', 'gatocyberpunk', 'pulpbaterista', 'robot50', 'simiorockero',
    'sirenapop', 'zorroelegante'
]);

// ============================================
// CLASE DE UTILIDADES PARA UPLOADS
// ============================================

class UploadHelper {
    
    /**
     * Validar archivo de música
     */
    public static function validateMusicFile($file) {
        $errors = [];
        
        // Verificar si hay errores en la subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = self::getUploadError($file['error']);
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Verificar tamaño
        if ($file['size'] > MAX_FILE_SIZE_MUSIC) {
            $maxMB = MAX_FILE_SIZE_MUSIC / (1024 * 1024);
            $errors[] = "El archivo excede el tamaño máximo de {$maxMB}MB";
        }
        
        // Verificar extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_MUSIC_EXTENSIONS)) {
            $allowed = implode(', ', ALLOWED_MUSIC_EXTENSIONS);
            $errors[] = "Formato no permitido. Formatos aceptados: {$allowed}";
        }
        
        // Verificar MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, ALLOWED_MUSIC_MIMES)) {
            $errors[] = "Tipo de archivo no válido. Solo se permiten archivos de audio.";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'size' => $file['size']
        ];
    }
    
    /**
     * Validar archivo de imagen
     */
    public static function validateImageFile($file, $isProfile = false) {
        $errors = [];
        
        // Verificar errores de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = self::getUploadError($file['error']);
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Verificar tamaño
        $maxSize = $isProfile ? MAX_FILE_SIZE_PROFILE : MAX_FILE_SIZE_IMAGE;
        if ($file['size'] > $maxSize) {
            $maxMB = $maxSize / (1024 * 1024);
            $errors[] = "El archivo excede el tamaño máximo de {$maxMB}MB";
        }
        
        // Verificar extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = $isProfile ? ALLOWED_PROFILE_EXTENSIONS : ALLOWED_IMAGE_EXTENSIONS;
        
        if (!in_array($extension, $allowedExts)) {
            $allowed = implode(', ', $allowedExts);
            $errors[] = "Formato no permitido. Formatos aceptados: {$allowed}";
        }
        
        // Verificar MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, ALLOWED_IMAGE_MIMES)) {
            $errors[] = "Tipo de archivo no válido. Solo se permiten imágenes.";
        }
        
        // Verificar que sea una imagen real
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $errors[] = "El archivo no es una imagen válida.";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'size' => $file['size'],
            'width' => $imageInfo[0] ?? null,
            'height' => $imageInfo[1] ?? null
        ];
    }
    
    /**
     * Generar nombre único para archivo
     */
    public static function generateUniqueFileName($userId, $prefix, $extension) {
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        return "{$prefix}_{$userId}_{$timestamp}_{$random}.{$extension}";
    }
    
    /**
     * Crear directorios si no existen
     */
    public static function ensureDirectoriesExist() {
        $dirs = [
            UPLOAD_MUSIC_DIR,
            UPLOAD_IMAGES_DIR,
            UPLOAD_PROFILE_DIR,
            UPLOAD_AVATARS_DIR
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Redimensionar imagen manteniendo proporción
     */
    public static function resizeImage($sourcePath, $destPath, $maxWidth, $maxHeight, $isSquare = false) {
        $imageInfo = getimagesize($sourcePath);
        if ($imageInfo === false) {
            return false;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        // Crear imagen desde archivo
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }
        
        if ($isSquare) {
            // Para fotos de perfil: hacer cuadrado
            $size = min($width, $height);
            $x = ($width - $size) / 2;
            $y = ($height - $size) / 2;
            
            $dest = imagecreatetruecolor($maxWidth, $maxHeight);
            
            // Preservar transparencia para PNG
            if ($type == IMAGETYPE_PNG) {
                imagealphablending($dest, false);
                imagesavealpha($dest, true);
            }
            
            imagecopyresampled($dest, $source, 0, 0, $x, $y, $maxWidth, $maxHeight, $size, $size);
        } else {
            // Para imágenes normales: mantener proporción
            $ratio = $width / $height;
            
            if ($width > $maxWidth || $height > $maxHeight) {
                if ($ratio > 1) {
                    $newWidth = $maxWidth;
                    $newHeight = $maxWidth / $ratio;
                } else {
                    $newHeight = $maxHeight;
                    $newWidth = $maxHeight * $ratio;
                }
            } else {
                $newWidth = $width;
                $newHeight = $height;
            }
            
            $dest = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preservar transparencia para PNG
            if ($type == IMAGETYPE_PNG) {
                imagealphablending($dest, false);
                imagesavealpha($dest, true);
            }
            
            imagecopyresampled($dest, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        }
        
        // Guardar imagen
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($dest, $destPath, IMAGE_QUALITY);
                break;
            case IMAGETYPE_PNG:
                imagepng($dest, $destPath);
                break;
            case IMAGETYPE_GIF:
                imagegif($dest, $destPath);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($dest);
        
        return true;
    }
    
    /**
     * Eliminar archivo de forma segura
     */
    public static function deleteFile($filePath) {
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
    
    /**
     * Obtener mensaje de error de upload
     */
    private static function getUploadError($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return "El archivo es demasiado grande";
            case UPLOAD_ERR_PARTIAL:
                return "El archivo se subió parcialmente";
            case UPLOAD_ERR_NO_FILE:
                return "No se subió ningún archivo";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Falta la carpeta temporal";
            case UPLOAD_ERR_CANT_WRITE:
                return "Error al escribir el archivo en el disco";
            default:
                return "Error desconocido al subir el archivo";
        }
    }
    
    /**
     * Formatear tamaño de archivo
     */
    public static function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    /**
     * Verificar si un avatar predeterminado existe
     */
    public static function isValidAvatar($avatarName) {
        // Quitar extensión si la tiene
        $avatarName = pathinfo($avatarName, PATHINFO_FILENAME);
        return in_array($avatarName, AVAILABLE_AVATARS);
    }
    
    /**
     * Obtener lista de avatares disponibles con URLs
     */
    public static function getAvailableAvatars() {
        $avatars = [];
        foreach (AVAILABLE_AVATARS as $avatar) {
            $avatars[] = [
                'name' => $avatar,
                'url' => AVATARS_URL . $avatar . '.jpg'  // Cambiado a .jpg
            ];
        }
        return $avatars;
    }
}

// ============================================
// INICIALIZAR DIRECTORIOS AL CARGAR
// ============================================

UploadHelper::ensureDirectoriesExist();
?>