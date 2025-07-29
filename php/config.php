<?php
/**
 * Archivo de configuración del Sistema de Asistencia
 * Versión corregida con mejores prácticas de seguridad
 */

// Prevenir acceso directo al archivo
if (!defined('ATTENDANCE_SYSTEM')) {
    define('ATTENDANCE_SYSTEM', true);
}

// Configuración de errores (cambiar a false en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root'); // Cambiar por tu usuario de BD
define('DB_PASSWORD', ''); // Cambiar por tu contraseña de BD
define('DB_NAME', 'attendance_system');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'Sistema de Asistencia Estudiantil');
define('APP_VERSION', '2.0');
define('SESSION_TIMEOUT', 3600); // 1 hora en segundos
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutos en segundos

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Configuración de sesiones seguras
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_only_cookies', 1);

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Regenerar ID de sesión cada 30 minutos para mayor seguridad
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

/**
 * Función para verificar si el usuario está logueado
 * @return bool
 */
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        return false;
    }
    
    // Verificar timeout de sesión
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
        destroySession();
        return false;
    }
    
    return true;
}

/**
 * Función para verificar si el usuario es profesor
 * @return bool
 */
function isTeacher() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}

/**
 * Función para verificar si el usuario es estudiante
 * @return bool
 */
function isStudent() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

/**
 * Función para requerir autenticación
 * Redirige al login si no está autenticado
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

/**
 * Función para requerir rol de profesor
 * Redirige al dashboard si no es profesor
 */
function requireTeacher() {
    requireAuth();
    if (!isTeacher()) {
        $_SESSION['error'] = 'No tienes permisos para acceder a esta página.';
        header('Location: dashboard.php');
        exit();
    }
}

/**
 * Función para requerir rol de estudiante
 * Redirige al dashboard si no es estudiante
 */
function requireStudent() {
    requireAuth();
    if (!isStudent()) {
        $_SESSION['error'] = 'No tienes permisos para acceder a esta página.';
        header('Location: dashboard.php');
        exit();
    }
}

/**
 * Función para escapar datos HTML y prevenir XSS
 * @param mixed $data
 * @return string
 */
function escape($data) {
    if (is_array($data)) {
        return array_map('escape', $data);
    }
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Función para limpiar input de usuario
 * @param string $input
 * @return string
 */
function cleanInput($input) {
    return trim(strip_tags($input));
}

/**
 * Función para validar email
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Función para formatear fecha en español
 * @param string $date
 * @return string
 */
function formatDate($date) {
    if (empty($date)) return '';
    
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    
    $timestamp = strtotime($date);
    $dia = date('d', $timestamp);
    $mes = $meses[(int)date('n', $timestamp)];
    $año = date('Y', $timestamp);
    
    return "$dia de $mes de $año";
}

/**
 * Función para formatear fecha y hora
 * @param string $datetime
 * @return string
 */
function formatDateTime($datetime) {
    if (empty($datetime)) return '';
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Función para obtener el saludo según la hora
 * @return string
 */
function getGreeting() {
    $hour = (int)date('H');
    
    if ($hour < 12) {
        return 'Buenos días';
    } elseif ($hour < 18) {
        return 'Buenas tardes';
    } else {
        return 'Buenas noches';
    }
}

/**
 * Función para destruir sesión completamente
 */
function destroySession() {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Función para generar token CSRF
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Función para verificar token CSRF
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Función para mostrar mensajes flash
 * @param string $type (success, error, warning, info)
 * @param string $message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Función para obtener y limpiar mensajes flash
 * @param string $type
 * @return string|null
 */
function getFlashMessage($type) {
    $message = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $message;
}

/**
 * Función para verificar si hay mensajes flash
 * @param string $type
 * @return bool
 */
function hasFlashMessage($type) {
    return isset($_SESSION['flash'][$type]);
}

/**
 * Función para logging de errores
 * @param string $message
 * @param string $level
 */
function logError($message, $level = 'ERROR') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Crear directorio de logs si no existe
    $logDir = dirname(__FILE__) . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/attendance_' . date('Y-m') . '.log';
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Función para obtener la IP del cliente
 * @return string
 */
function getClientIP() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Función para validar que solo se ejecute en contexto web
 */
function validateWebContext() {
    if (php_sapi_name() === 'cli') {
        die('Este script solo puede ejecutarse desde un navegador web.');
    }
}

// Validar contexto web
validateWebContext();

/**
 * Constantes para estados de asistencia
 */
define('ATTENDANCE_PRESENT', 'present');
define('ATTENDANCE_ABSENT', 'absent');
define('ATTENDANCE_LATE', 'late');

/**
 * Array con etiquetas de estado en español
 */
$ATTENDANCE_LABELS = [
    ATTENDANCE_PRESENT => 'Presente',
    ATTENDANCE_ABSENT => 'Ausente',
    ATTENDANCE_LATE => 'Tarde'
];

/**
 * Array con clases CSS para cada estado
 */
$ATTENDANCE_CLASSES = [
    ATTENDANCE_PRESENT => 'success',
    ATTENDANCE_ABSENT => 'danger',
    ATTENDANCE_LATE => 'warning'
];

/**
 * Configuración de paginación
 */
define('RECORDS_PER_PAGE', 20);

// Configurar manejo de errores personalizado
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $errorMessage = "Error: $message en $file línea $line";
    logError($errorMessage);
    
    // En producción, no mostrar errores detallados
    if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
        $message = 'Ha ocurrido un error interno. Por favor, contacte al administrador.';
    }
    
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Configurar manejo de excepciones
set_exception_handler(function($exception) {
    $errorMessage = "Excepción no capturada: " . $exception->getMessage() . 
                   " en " . $exception->getFile() . 
                   " línea " . $exception->getLine();
    
    logError($errorMessage);
    
    // Mostrar página de error amigable
    http_response_code(500);
    include 'error_page.php';
    exit();
});
?>