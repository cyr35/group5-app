<?php
session_start();
require_once 'db_connect.php';

// Verificar que sea administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$message_type = '';

// Procesar eliminación de registro
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'delete') {
    $attendance_id = $_POST['attendance_id'];
    $stmt = $conn->prepare("DELETE FROM attendance WHERE id = ?");
    $stmt->bind_param("i", $attendance_id);
    
    if ($stmt->execute()) {
        $message = 'Registro de asistencia eliminado exitosamente.';
        $message_type = 'success';
    } else {
        $message = 'Error al eliminar el registro.';
        $message_type = 'error';
    }
    $stmt->close();
}

// Filtros
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // Primer día del mes actual
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Hoy
$student_filter = $_GET['student'] ?? '';
$status_filter = $_GET['status'] ?? '';
$teacher_filter = $_GET['teacher'] ?? '';

// Construir consulta con filtros
$sql = "SELECT a.id, a.attendance_date, a.status, a.recorded_at,
               u.username as student_username, u.full_name as student_name,
               t.username as teacher_username, t.full_name as teacher_name
        FROM attendance a 
        JOIN users u ON a.user_id = u.id 
        LEFT JOIN users t ON a.recorded_by = t.id
        WHERE a.attendance_date BETWEEN ? AND ?";

$params = [$date_from, $date_to];
$types = "ss";

if ($student_filter) {
    $sql .= " AND u.username LIKE ?";
    $params[] = "%$student_filter%";
    $types .= "s";
}

if ($status_filter) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($teacher_filter) {
    $sql .= " AND t.username LIKE ?";
    $params[] = "%$teacher_filter%";
    $types .= "s";
}

$sql .= " ORDER BY a.attendance_date DESC, u.username ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$attendance_records = $stmt->get_result();

// Obtener estadísticas del período
$stats_sql = "SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                COUNT(DISTINCT user_id) as unique_students,
                COUNT(DISTINCT attendance_date) as unique_days
              FROM attendance a 
              JOIN users u ON a.user_id = u.id 
              WHERE a.attendance_date BETWEEN ? AND ?";

$stats_params = [$date_from, $date_to];
if ($student_filter) {
    $stats_sql .= " AND u.username LIKE ?";
    $stats_params[] = "%$student_filter%";
}

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param(str_repeat("s", count($stats_params)), ...$stats_params);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Asistencia - Panel de Administración</title>
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
                <li><a href="admin_panel.php">📊 Dashboard</a></li>
                <li><a href="manage_users.php">👥 Gestionar Usuarios</a></li>
                <li><a href="view_attendance.php" class="active">📋 Ver Asistencia</a></li>
                <li><a href="reports.php">📈 Reportes</a></li>
                <li><a href="system_settings.php">⚙️ Configuración</a></li>
                <li style="margin-top: 20px;"><a href="dashboard.php">🏠 Dashboard Normal</a></li>
                <li><a href="logout.php">🚪 Cerrar Sesión</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1>Visualizar Asistencia</h1>
                <p>Consulta y administra todos los registros de asistencia</p>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas del período -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_records']; ?></h3>
                        <p>Total Registros</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['present_count']; ?></h3>
                        <p>Presentes</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">❌</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['absent_count']; ?></h3>
                        <p>Ausentes</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['unique_students']; ?></h3>
                        <p>Estudiantes</p>
                    </div>
                </div>
            </div>

            <!-- Filtros de búsqueda -->
            <div class="form-container">
                <h3>Filtrar Registros de Asistencia</h3>
                <form method="GET" action="view_attendance.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="date_from">Fecha Desde</label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_to">Fecha Hasta</label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="student">Buscar Estudiante</label>
                            <input type="text" id="student" name="student" 
                                   placeholder="Nombre de usuario del estudiante"
                                   value="<?php echo htmlspecialchars($student_filter); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Estado</label>
                            <select id="status" name="status">
                                <option value="">Todos los estados</option>
                                <option value="present" <?php echo ($status_filter == 'present') ? 'selected' : ''; ?>>Presente</option>
                                <option value="absent" <?php echo ($status_filter == 'absent') ? 'selected' : ''; ?>>Ausente</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="teacher">Profesor que Registró</label>
                            <input type="text" id="teacher" name="teacher" 
                                   placeholder="Nombre de usuario del profesor"
                                   value="<?php echo htmlspecialchars($teacher_filter); ?>">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="view_attendance.php" class="btn" style="background: #718096; color: white;">Limpiar Filtros</a>
                        <a href="export_attendance.php?<?php echo http_build_query($_GET); ?>" 
                           class="btn btn-success">📥 Exportar Excel</a>
                    </div>
                </form>
            </div>

            <!-- Acciones rápidas -->
            <div class="quick-actions">
                <h3>Acciones Rápidas</h3>
                <div class="actions-grid">
                    <a href="view_attendance.php?date_from=<?php echo date('Y-m-d'); ?>&date_to=<?php echo date('Y-m-d'); ?>" 
                       class="action-btn">
                        <span class="action-icon">📅</span>
                        <span>Asistencia Hoy</span>
                    </a>
                    <a href="view_attendance.php?date_from=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&date_to=<?php echo date('Y-m-d'); ?>" 
                       class="action-btn">
                        <span class="action-icon">📊</span>
                        <span>Última Semana</span>
                    </a>
                    <a href="view_attendance.php?status=absent&date_from=<?php echo date('Y-m-01'); ?>" 
                       class="action-btn">
                        <span class="action-icon">⚠️</span>
                        <span>Ausencias del Mes</span>
                    </a>
                    <a href="register_attendance.php" class="action-btn">
                        <span class="action-icon">➕</span>
                        <span>Registrar Asistencia</span>
                    </a>
                </div>
            </div>

            <!-- Tabla de registros -->
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Estudiante</th>
                            <th>Nombre Completo</th>
                            <th>Estado</th>
                            <th>Registrado Por</th>
                            <th>Fecha de Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($attendance_records->num_rows > 0): ?>
                            <?php while ($record = $attendance_records->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $record['id']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($record['attendance_date'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($record['student_username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($record['student_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="attendance-badge <?php echo $record['status'] == 'present' ? 'high' : 'low'; ?>">
                                        <?php echo $record['status'] == 'present' ? '✅ Presente' : '❌ Ausente'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($record['teacher_username'] ?? 'Sistema'); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($record['recorded_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_attendance.php?id=<?php echo $record['id']; ?>" 
                                           class="btn btn-sm" style="background: #4299e1; color: white;" title="Editar">✏️</a>
                                        <button onclick="confirmDelete(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['student_username']); ?