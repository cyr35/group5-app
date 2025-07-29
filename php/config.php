<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root'); // Cambiar por tu usuario
define('DB_PASSWORD', ''); // Cambiar por tu contraseña
define('DB_NAME', 'attendance_system');

// Configuración de la aplicación
define('APP_NAME', 'Sistema de Asistencia');
define('SESSION_TIMEOUT', 3600); // 1 hora en segundos

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Función para verificar si el usuario es profesor
function isTeacher() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}

// Función para verificar si el usuario es estudiante
function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

// Función para redirigir si no está autenticado
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Función para redirigir si no es profesor
function requireTeacher() {
    requireAuth();
    if (!isTeacher()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Función para escapar datos HTML
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Función para formatear fecha
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Función para formatear fecha y hora
function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}
?>