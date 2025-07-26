<?php
session_start();
require_once 'db_connect.php';

// Redirigir si el usuario no está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

$attendance_records = [];

if ($role === 'teacher') {
    // Para profesores, mostrar todos los registros de asistencia
    $sql = "SELECT u.username, a.attendance_date, a.status FROM attendance a JOIN users u ON a.user_id = u.id ORDER BY a.attendance_date DESC, u.username ASC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $attendance_records[] = $row;
        }
    }
} else if ($role === 'student') {
    // Para estudiantes, mostrar solo sus propios registros de asistencia
    $stmt = $conn->prepare("SELECT attendance_date, status FROM attendance WHERE user_id = ? ORDER BY attendance_date DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $attendance_records[] = $row;
        }
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Asistencia</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h2>Bienvenido, <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($role); ?>)</h2>
        <p><a href="logout.php">Cerrar Sesión</a></p>

        <?php if ($role === 'teacher'): ?>
            <p><a href="register_attendance.php">Registrar Nueva Asistencia</a></p>
            <h3>Registros de Asistencia (Todos los Estudiantes)</h3>
            <?php if (empty($attendance_records)): ?>
                <p>No hay registros de asistencia aún.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['username']); ?></td>
                                <td><?php echo htmlspecialchars($record['attendance_date']); ?></td>
                                <td><?php echo htmlspecialchars($record['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php elseif ($role === 'student'): ?>
            <h3>Mis Registros de Asistencia</h3>
            <?php if (empty($attendance_records)): ?>
                <p>No tienes registros de asistencia aún.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['attendance_date']); ?></td>
                                <td><?php echo htmlspecialchars($record['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>


