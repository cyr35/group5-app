<?php
require_once 'config.php';

class Database {
    private $connection;
    private $host;
    private $username;
    private $password;
    private $database;
    
    public function __construct() {
        $this->host = DB_HOST;
        $this->username = DB_USERNAME;
        $this->password = DB_PASSWORD;
        $this->database = DB_NAME;
    }
    
    // Conectar a la base de datos
    public function connect() {
        try {
            $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            if ($this->connection->connect_error) {
                throw new Exception("Error de conexión: " . $this->connection->connect_error);
            }
            
            // Establecer charset UTF-8
            $this->connection->set_charset("utf8");
            
            return $this->connection;
            
        } catch (Exception $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
    
    // Obtener la conexión
    public function getConnection() {
        if (!$this->connection) {
            $this->connect();
        }
        return $this->connection;
    }
    
    // Cerrar conexión
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    // Preparar statement
    public function prepare($query) {
        return $this->getConnection()->prepare($query);
    }
    
    // Escapar strings
    public function escape($string) {
        return $this->getConnection()->real_escape_string($string);
    }
    
    // Obtener último ID insertado
    public function lastInsertId() {
        return $this->getConnection()->insert_id;
    }
    
    // Verificar si hay errores
    public function error() {
        return $this->getConnection()->error;
    }
}

// Crear instancia global de la base de datos
$db = new Database();
$conn = $db->getConnection();

// Función auxiliar para queries preparados
function executeQuery($query, $params = [], $types = '') {
    global $db;
    
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        die("Error al preparar la consulta: " . $db->error());
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    
    if ($stmt->error) {
        die("Error al ejecutar la consulta: " . $stmt->error);
    }
    
    return $stmt;
}

// Función para obtener un solo resultado
function fetchOne($query, $params = [], $types = '') {
    $stmt = executeQuery($query, $params, $types);
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Función para obtener múltiples resultados
function fetchAll($query, $params = [], $types = '') {
    $stmt = executeQuery($query, $params, $types);
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
