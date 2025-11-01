<?php
/**
 * Configuración de base de datos para SoundConnect
 * Este archivo maneja la conexión a MySQL usando PDO
 */

class Database {
    private $host = "localhost";
    private $db_name = "soundconnect";
    private $username = "root";
    private $password = "";
    private $conn;
    
    /**
     * Obtener la conexión a la base de datos
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            
            // Configurar PDO para que lance excepciones en errores
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Configurar para que devuelva arrays asociativos por defecto
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Deshabilitar emulación de prepared statements para mayor seguridad
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
        } catch(PDOException $e) {
            echo "Error de conexión: " . $e->getMessage();
            return null;
        }
        
        return $this->conn;
    }
    
    /**
     * Cerrar la conexión
     */
    public function closeConnection() {
        $this->conn = null;
    }
}
?>