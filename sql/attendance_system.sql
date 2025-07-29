-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS attendance_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE attendance_system;

-- Eliminar tablas si existen (para reinstalación limpia)
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS users;

-- Tabla de usuarios
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('teacher', 'student') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de asistencia
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    teacher_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_date (student_id, date),
    INDEX idx_date (date),
    INDEX idx_student_id (student_id),
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuarios de ejemplo con contraseñas correctamente hasheadas
-- Las contraseñas son: teacher_password y student_password
-- Hash generado con: password_hash('teacher_password', PASSWORD_DEFAULT)
INSERT INTO users (username, password, role, full_name, email) VALUES 
('profesor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Prof. Juan García', 'profesor1@escuela.edu'),
('profesor2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Prof. Ana Martínez', 'profesor2@escuela.edu');

INSERT INTO users (username, password, role, full_name, email) VALUES 
('estudiante1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'María López Hernández', 'maria.lopez@estudiante.edu'),
('estudiante2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Carlos Rodríguez Pérez', 'carlos.rodriguez@estudiante.edu'),
('estudiante3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Ana Fernández Silva', 'ana.fernandez@estudiante.edu'),
('estudiante4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Diego Morales Castro', 'diego.morales@estudiante.edu'),
('estudiante5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Laura Sánchez Ruiz', 'laura.sanchez@estudiante.edu');

-- Insertar registros de asistencia de ejemplo para los últimos 30 días
INSERT INTO attendance (student_id, teacher_id, date, status, notes) VALUES 
-- Asistencias de hace 7 días
(3, 1, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'present', 'Participación excelente'),
(4, 1, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'late', 'Llegó 10 minutos tarde'),
(5, 1, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'present', 'Muy atento en clase'),
(6, 1, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'absent', 'Falta justificada por enfermedad'),
(7, 1, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'present', 'Completó todas las actividades'),

-- Asistencias de hace 6 días
(3, 1, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'present', 'Buen comportamiento'),
(4, 1, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'present', 'Puntual y participativo'),
(5, 1, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'late', 'Llegó tarde por transporte'),
(6, 1, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'present', 'Ya se recuperó'),
(7, 1, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'present', 'Excelente trabajo'),

-- Asistencias de hace 5 días
(3, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'present', 'Muy participativo'),
(4, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'present', 'Buen rendimiento'),
(5, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'present', 'Puntual'),
(6, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'present', 'Actitud positiva'),
(7, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'absent', 'Falta no justificada'),

-- Asistencias de hace 4 días
(3, 1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'late', 'Llegó 15 minutos tarde'),
(4, 1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'present', 'Muy bien'),
(5, 1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'present', 'Excelente participación'),
(6, 1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'present', 'Buen día'),
(7, 1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'present', 'Se reintegró bien'),

-- Asistencias de hace 3 días
(3, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'present', 'Día normal'),
(4, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'present', 'Muy atento'),
(5, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'present', 'Participativo'),
(6, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'late', 'Problemas de transporte'),
(7, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'present', 'Buen comportamiento'),

-- Asistencias de ayer
(3, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'present', 'Excelente día'),
(4, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'present', 'Muy bien'),
(5, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'present', 'Participativo'),
(6, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'present', 'Puntual'),
(7, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'absent', 'Cita médica');

-- Script para generar hash de contraseña (comentado para referencia)
/*
Para generar un nuevo hash de contraseña, usa este código PHP:
<?php
echo password_hash('tu_contraseña_aqui', PASSWORD_DEFAULT);
?>
*/

-- Consultas útiles para verificar la instalación
-- SELECT * FROM users;
-- SELECT COUNT(*) as total_users FROM users;
-- SELECT COUNT(*) as total_attendance FROM attendance;
-- SELECT u.full_name, a.date, a.status FROM attendance a JOIN users u ON a.student_id = u.id ORDER BY a.date DESC LIMIT 10;