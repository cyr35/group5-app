<?php
session_start();
require_once 'db_connect.php';

// Redirigir si el usuario no está logueado o no es un profesor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_username = $_POST['student_username'];
    $attendance_date = $_POST['attendance_date'];
    $status = $_POST['status'];

    // Obtener el ID del estudiante
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND role = 'student'");
    $stmt->bind_param("s", $student_username);
    $stmt->execute();
    $stmt->bind_result($student_id);
    $stmt->fetch();
    $stmt->close();

    if ($student_id) {
        // Verificar si ya existe un registro de asistencia para este estudiante en esta fecha
        $stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND attendance_date = ?");
        $stmt->bind_param("is", $student_id, $attendance_date);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $message = 'Ya existe un registro de asistencia para este estudiante en esta fecha.';
        } else {
            // Insertar el registro de asistencia
            $stmt = $conn->prepare("INSERT INTO attendance (user_id, attendance_date, status) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $student_id, $attendance_date, $status);
            if ($stmt->execute()) {
                $message = 'Asistencia registrada exitosamente.';
            } else {
                $message = 'Error al registrar la asistencia.';
            }
        }
        $stmt->close();
    } else {
        $message = 'Estudiante no encontrado o no es un estudiante válido.';
    }
}

// Obtener la lista de estudiantes para el formulario
$students = [];
$result = $conn->query("SELECT username FROM users WHERE role = 'student'");
while ($row = $result->fetch_assoc()) {
    $students[] = $row['username'];
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Asistencia - Sistema de Asistencia</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h2>Registrar Asistencia</h2>
        <p><a href="dashboard.php">Volver al Dashboard</a></p>
        <?php if ($message): ?>
            <p class="<?php echo (strpos($message, 'exitosamente') !== false) ? 'success' : 'error'; ?>"><?php echo $message; ?></p>
        <?php endif; ?>
        <form action="register_attendance.php" method="post">
            <div class="form-group">
                <label for="student_username">Estudiante:</label>
                <select id="student_username" name="student_username" required>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo htmlspecialchars($student); ?>"><?php echo htmlspecialchars($student); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="attendance_date">Fecha:</label>
                <input type="date" id="attendance_date" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label for="status">Estado:</label>
                <select id="status" name="status" required>
                    <option value="present">Presente</option>
                    <option value="absent">Ausente</option>
                </select>
            </div>
            <button type="submit">Registrar</button>
        </form>
    </div>
</body>
</html>


