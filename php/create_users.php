<?php
require_once 'config.php';

// Create connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Crear contraseñas hasheadas
$teacher_password = password_hash("teacher_password", PASSWORD_DEFAULT);
$student_password = password_hash("student_password", PASSWORD_DEFAULT);

// Insertar profesor
$stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
$username = "profesor1";
$role = "teacher";
$stmt->bind_param("sss", $username, $teacher_password, $role);

if ($stmt->execute()) {
    echo "Profesor creado exitosamente<br>";
} else {
    echo "Error creando profesor: " . $stmt->error . "<br>";
}

// Insertar estudiante
$username = "estudiante1";
$role = "student";
$stmt->bind_param("sss", $username, $student_password, $role);

if ($stmt->execute()) {
    echo "Estudiante creado exitosamente<br>";
} else {
    echo "Error creando estudiante: " . $stmt->error . "<br>";
}

$stmt->close();
$conn->close();

echo "<br>Usuarios creados. Puedes eliminar este archivo ahora.";
echo "<br><a href='login.php'>Ir al Login</a>";
?>