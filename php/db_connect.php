<?php
/**
 * Clase de conexión a base de datos con manejo de errores mejorado
 * Sistema de Asistencia Estudiantil - Versión Corregida
 */

// Prevenir acceso directo
defined('ATTENDANCE_SYSTEM') or die('Acceso directo no permitido');

require_once 'config.php';

class DatabaseConnection {
    private static $instance = null;
    private $connection;
    private $host;
    private $username;
    private $password;
    private $database;
    private $charset;
    
    /**
     * Constructor privado para patrón Singleton
     */
    private function __construct() {
        $this->host = DB_HOST;
        $this->username = DB_USERNAME;
        $this->password = DB_PASSWORD;
        $this->database = DB_NAME;
        $this->charset = DB_CHARSET;
        
        $this->connect();
    }
    
    /**
     * Obtener instancia única de la conexión (Singleton)
     * @return DatabaseConnection
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Conectar a la base de datos
     * @throws Exception
     */
    private function connect() {
        try {
            // Configurar opciones de MySQLi
            $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            // Verificar conexión
            if ($this->connection->connect_error) {
                throw new Exception("Error de conexión MySQL: " . $this->connection->connect_error);
            }
            
            // Establecer charset
            if (!$this->connection->set_charset($this->charset)) {
                throw new Exception("Error estableciendo charset: " . $this->connection->error);
            }
            
            // Configurar modo SQL estricto
            $this->connection->query("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            
            // Configurar timezone
            $this->connection->query("SET time_zone = '" . date('P') . "'");
            
            logError("Conexión a base de datos establecida correctamente", 'INFO');
            
        } catch (Exception $e) {
            logError("Error fatal de conexión: " . $e->getMessage(), 'CRITICAL');
            
            // En producción, mostrar mensaje genérico
            if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
                die('Error de conexión a la base de datos. Contacte al administrador.');
            } else {
                die('Error de conexión: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Obtener la conexión MySQLi
     * @return mysqli
     */
    public function getConnection() {
        // Verificar si la conexión está activa
        if (!$this->connection || !$this->connection->ping()) {
            logError("Conexión perdida, reconectando...", 'WARNING');
            $this->connect();
        }
        
        return $this->connection;
    }
    
    /**
     * Preparar una consulta
     * @param string $query
     * @return mysqli_stmt
     * @throws Exception
     */
    public function prepare($query) {
        $stmt = $this->getConnection()->prepare($query);
        
        if (!$stmt) {
            $error = "Error preparando consulta: " . $this->getConnection()->error . " | Query: " . $query;
            logError($error, 'ERROR');
            throw new Exception("Error en la consulta de base de datos");
        }
        
        return $stmt;
    }
    
    /**
     * Escapar string para consultas (uso con consultas no preparadas)
     * @param string $string
     * @return string
     */
    public function escape($string) {
        return $this->getConnection()->real_escape_string($string);
    }
    
    /**
     * Obtener último ID insertado
     * @return int
     */
    public function getLastInsertId() {
        return $this->getConnection()->insert_id;
    }
    
    /**
     * Obtener número de filas afectadas
     * @return int
     */
    public function getAffectedRows() {
        return $this->getConnection()->affected_rows;
    }
    
    /**
     * Iniciar transacción
     */
    public function beginTransaction() {
        $this->getConnection()->autocommit(false);
        $this->getConnection()->begin_transaction();
    }
    
    /**
     * Confirmar transacción
     */
    public function commit() {
        $this->getConnection()->commit();
        $this->getConnection()->autocommit(true);
    }
    
    /**
     * Rollback de transacción
     */
    public function rollback() {
        $this->getConnection()->rollback();
        $this->getConnection()->autocommit(true);
    }
    
    /**
     * Cerrar conexión
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
    }
    
    /**
     * Prevenir clonación
     */
    private function __clone() {}
    
    /**
     * Prevenir deserialización
     */
    public function __wakeup() {
        throw new Exception("No se puede deserializar una instancia de " . __CLASS__);
    }
    
    /**
     * Destructor
     */
    public function __destruct() {
        $this->close();
    }
}

/**
 * Clase para manejo de consultas preparadas
 */
class QueryBuilder {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConnection::getInstance();
    }
    
    /**
     * Ejecutar consulta preparada
     * @param string $query
     * @param array $params
     * @param string $types
     * @return mysqli_stmt
     * @throws Exception
     */
    public function execute($query, $params = [], $types = '') {
        try {
            $stmt = $this->db->prepare($query);
            
            if (!empty($params)) {
                if (empty($types)) {
                    // Auto-detectar tipos si no se proporcionan
                    $types = $this->detectTypes($params);
                }
                
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error ejecutando consulta: " . $stmt->error);
            }
            
            return $stmt;
            
        } catch (Exception $e) {
            logError("Error en QueryBuilder::execute - " . $e->getMessage() . " | Query: " . $query, 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtener un solo registro
     * @param string $query
     * @param array $params
     * @param string $types
     * @return array|null
     */
    public function fetchOne($query, $params = [], $types = '') {
        $stmt = $this->execute($query, $params, $types);
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Obtener múltiples registros
     * @param string $query
     * @param array $params
     * @param string $types
     * @return array
     */
    public function fetchAll($query, $params = [], $types = '') {
        $stmt = $this->execute($query, $params, $types);
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Insertar registro y devolver ID
     * @param string $table
     * @param array $data
     * @return int
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = str_repeat('?,', count($data) - 1) . '?';
        $values = array_values($data);
        $types = $this->detectTypes($values);
        
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->execute($query, $values, $types);
        
        return $this->db->getLastInsertId();
    }
    
    /**
     * Actualizar registros
     * @param string $table
     * @param array $data
     * @param string $where
     * @param array $whereParams
     * @return int Número de filas afectadas
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = implode(', ', array_map(function($key) {
            return "{$key} = ?";
        }, array_keys($data)));
        
        $values = array_merge(array_values($data), $whereParams);
        $types = $this->detectTypes($values);
        
        $query = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $this->execute($query, $values, $types);
        
        return $this->db->getAffectedRows();
    }
    
    /**
     * Eliminar registros
     * @param string $table
     * @param string $where
     * @param array $params
     * @return int Número de filas afectadas
     */
    public function delete($table, $where, $params = []) {
        $types = $this->detectTypes($params);
        $query = "DELETE FROM {$table} WHERE {$where}";
        $this->execute($query, $params, $types);
        
        return $this->db->getAffectedRows();
    }
    
    /**
     * Contar registros
     * @param string $table
     * @param string $where
     * @param array $params
     * @return int
     */
    public function count($table, $where = '1=1', $params = []) {
        $query = "SELECT COUNT(*) as total FROM {$table} WHERE {$where}";
        $result = $this->fetchOne($query, $params);
        return (int)$result['total'];
    }
    
    /**
     * Auto-detectar tipos de parámetros
     * @param array $params
     * @return string
     */
    private function detectTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b'; // blob para otros tipos
            }
        }
        return $types;
    }
}

// Instancia global del QueryBuilder
$db = DatabaseConnection::getInstance();
$query = new QueryBuilder();

/**
 * Funciones auxiliares para compatibilidad con código existente
 */

/**
 * Ejecutar consulta preparada (función auxiliar)
 * @param string $sql
 * @param array $params
 * @param string $types
 * @return mysqli_stmt
 */
function executeQuery($sql, $params = [], $types = '') {
    global $query;
    return $query->execute($sql, $params, $types);
}

/**
 * Obtener un registro (función auxiliar)
 * @param string $sql
 * @param array $params
 * @param string $types
 * @return array|null
 */
function fetchOne($sql, $params = [], $types = '') {
    global $query;
    return $query->fetchOne($sql, $params, $types);
}

/**
 * Obtener múltiples registros (función auxiliar)
 * @param string $sql
 * @param array $params
 * @param string $types
 * @return array
 */
function fetchAll($sql, $params = [], $types = '') {
    global $query;
    return $query->fetchAll($sql, $params, $types);
}

/**
 * Insertar registro (función auxiliar)
 * @param string $table
 * @param array $data
 * @return int
 */
function insertRecord($table, $data) {
    global $query;
    return $query->insert($table, $data);
}

/**
 * Actualizar registro (función auxiliar)
 * @param string $table
 * @param array $data
 * @param string $where
 * @param array $whereParams
 * @return int
 */
function updateRecord($table, $data, $where, $whereParams = []) {
    global $query;
    return $query->update($table, $data, $where, $whereParams);
}

/**
 * Eliminar registro (función auxiliar)
 * @param string $table
 * @param string $where
 * @param array $params
 * @return int
 */
function deleteRecord($table, $where, $params = []) {
    global $query;
    return $query->delete($table, $where, $params);
}

/**
 * Contar registros (función auxiliar)
 * @param string $table
 * @param string $where
 * @param array $params
 * @return int
 */
function countRecords($table, $where = '1=1', $params = []) {
    global $query;
    return $query->count($table, $where, $params);
}

/**
 * Función para validar conexión de base de datos
 * @return bool
 */
function testDatabaseConnection() {
    try {
        $db = DatabaseConnection::getInstance();
        $result = $db->getConnection()->query("SELECT 1");
        return $result !== false;
    } catch (Exception $e) {
        logError("Test de conexión falló: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Función para obtener información de la base de datos
 * @return array
 */
function getDatabaseInfo() {
    try {
        $db = DatabaseConnection::getInstance();
        $conn = $db->getConnection();
        
        return [
            'server_info' => $conn->server_info,
            'server_version' => $conn->server_version,
            'client_info' => $conn->client_info,
            'host_info' => $conn->host_info,
            'protocol_version' => $conn->protocol_version,
            'character_set' => $conn->character_set_name()
        ];
    } catch (Exception $e) {
        logError("Error obteniendo info de BD: " . $e->getMessage(), 'ERROR');
        return [];
    }
}

// Verificar conexión al cargar el archivo
if (!testDatabaseConnection()) {
    logError("Error crítico: No se puede conectar a la base de datos", 'CRITICAL');
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        die('Error: No se puede conectar a la base de datos. Verifica la configuración.');
    }
}
?>