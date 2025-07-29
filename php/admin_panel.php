<?php
session_start();
require_once 'db_connect.php';

// Verificar que sea administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Obtener estadísticas
$stats = [];

// Total de usuarios por rol
$result = $conn->query("SELECT role, COUNT(*) as count FROM users WHERE status = 'active' GROUP BY role");
while ($row = $result->fetch_assoc()) {
    $stats[$row['role']] = $row['count'];
}

// Total de registros de asistencia este mes
$current_month = date('Y-m');
$attendance_result = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE attendance_date LIKE '$current_month%'");
$stats['attendance_month'] = $attendance_result->fetch_assoc()['count'];

// Estudiantes con baja asistencia (menos del 80%)
$low_attendance_query = "
    SELECT u.username, u.full_name, 
           COUNT(a.id) as total_days,
           SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
           ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 2) as percentage
    FROM users u 
    LEFT JOIN attendance a ON u.id = a.user_id 
    WHERE u.role = 'student' AND u.status = 'active'
    GROUP BY u.id 
    HAVING percentage < 80 AND total_days > 0
    ORDER BY percentage ASC
    LIMIT 5";
$low_attendance = $conn->query($low_attendance_query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Sistema de Asistencia</title>
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </div>
            <ul class="nav-menu">
                <li><a href="admin_panel.php" class="active">📊 Dashboard</a></li>
                <li><a href="manage_users.php">👥 Gestionar Usuarios</a></li>
                <li><a href="view_attendance.php">📋 Ver Asistencia</a></li>
                <li><a href="reports.php">📈 Reportes</a></li>
                <li><a href="system_settings.php">⚙️ Configuración</a></li>
                <li style="margin-top: 20px;"><a href="dashboard.php">🏠 Dashboard Normal</a></li>
                <li><a href="logout.php">🚪 Cerrar Sesión</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1>Dashboard de Administración</h1>
                <p>Resumen general del sistema de asistencia</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👨‍🎓</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['student'] ?? 0; ?></h3>
                        <p>Estudiantes Activos</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👨‍🏫</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['teacher'] ?? 0; ?></h3>
                        <p>Profesores</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👑</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['admin'] ?? 0; ?></h3>
                        <p>Administradores</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['attendance_month']; ?></h3>
                        <p>Registros Este Mes</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2>Acciones Rápidas</h2>
                <div class="actions-grid">
                    <a href="manage_users.php?action=add" class="action-btn">
                        <span class="action-icon">➕</span>
                        <span>Agregar Usuario</span>
                    </a>
                    <a href="view_attendance.php?filter=today" class="action-btn">
                        <span class="action-icon">📋</span>
                        <span>Asistencia Hoy</span>
                    </a>
                    <a href="reports.php?type=weekly" class="action-btn">
                        <span class="action-icon">📊</span>
                        <span>Reporte Semanal</span>
                    </a>
                    <a href="system_settings.php" class="action-btn">
                        <span class="action-icon">⚙️</span>
                        <span>Configurar Sistema</span>
                    </a>
                </div>
            </div>

            <!-- Low Attendance Alert -->
            <?php if ($low_attendance && $low_attendance->num_rows > 0): ?>
            <div class="alert-section">
                <h2>⚠️ Estudiantes con Baja Asistencia</h2>
                <div class="alert-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Nombre Completo</th>
                                <th>Días Registrados</th>
                                <th>Asistencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($student = $low_attendance->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['username']); ?></td>
                                <td><?php echo htmlspecialchars($student['full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo $student['total_days']; ?></td>
                                <td>
                                    <span class="attendance-badge low">
                                        <?php echo $student['percentage']; ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>