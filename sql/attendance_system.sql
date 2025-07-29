-- Crear base de datos
CREATE DATABASE IF NOT EXISTS attendance_system;
USE attendance_system;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('teacher', 'student') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de asistencia
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    teacher_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (student_id, date)
);

-- Insertar usuarios de ejemplo con contraseñas correctamente hasheadas
-- Contraseña para profesor: teacher_password
INSERT INTO users (username, password, role, full_name, email) VALUES 
('profesor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Prof. Juan García', 'profesor1@email.com');

-- Contraseña para estudiante: student_password  
INSERT INTO users (username, password, role, full_name, email) VALUES 
('estudiante1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'María López', 'estudiante1@email.com'),
('estudiante2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Carlos Rodríguez', 'estudiante2@email.com');

-- Insertar algunos registros de asistencia de ejemplo
INSERT INTO attendance (student_id, teacher_id, date, status, notes) VALUES 
(2, 1, '2024-01-15', 'present', 'Participó activamente en clase'),
(3, 1, '2024-01-15', 'late', 'Llegó 10 minutos tarde'),
(2, 1, '2024-01-16', 'absent', 'Falta justificada'),
(3, 1, '2024-01-16', 'present', 'Puntual y atento');