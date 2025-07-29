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

// Procesar actualización de configuraciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $settings = $_POST['settings'];
    
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
        $stmt->close();
    }
    
    $message = 'Configuraciones actualizadas exitosamente.';
    $message_type = 'success';
}

// Obtener configuraciones actuales
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Obtener estadísticas del sistema
$stats = [];

// Total de usuarios por rol
$result = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
while ($row = $result->fetch_assoc()) {
    $stats['users_' . $row['role']] = $row['count'];
}

// Registros de asistencia por mes (últimos 6 meses)
$monthly_stats = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $result = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE attendance_date LIKE '$month%'");
    $monthly_stats[$month] = $result->fetch_assoc()['count'];
}

// Espacio en base de datos (aproximado)
$result = $conn->query("SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM attendance) as total_attendance,
    (SELECT COUNT(*) FROM system_settings) as total_settings");
$db_stats = $result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - Panel de Administración</title>
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
                <li><a href="view_attendance.php">📋 Ver Asistencia</a></li>
                <li><a href="reports.php">📈 Reportes</a></li>
                <li><a href="system_settings.php" class="active">⚙️ Configuración</a></li>
                <li style="margin-top: 20px;"><a href="dashboard.php">🏠 Dashboard Normal</a></li>
                <li><a href="logout.php">🚪 Cerrar Sesión</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1>Configuración del Sistema</h1>
                <p>Administra las configuraciones generales del sistema</p>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas del Sistema -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?php echo ($stats['users_student'] ?? 0) + ($stats['users_teacher'] ?? 0) + ($stats['users_admin'] ?? 0); ?></h3>
                        <p>Total Usuarios</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <h3><?php echo $db_stats['total_attendance']; ?></h3>
                        <p>Registros de Asistencia</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💾</div>
                    <div class="stat-info">
                        <h3><?php echo number_format(($db_stats['total_users'] + $db_stats['total_attendance']) * 0.1, 1); ?>KB</h3>
                        <p>Espacio Aprox. BD</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <h3><?php echo date('Y'); ?></h3>
                        <p>Año Académico</p>
                    </div>
                </div>
            </div>

            <!-- Configuraciones del Sistema -->
            <div class="form-container">
                <h2>⚙️ Configuraciones Generales</h2>
                <form method="POST" action="system_settings.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="system_name">Nombre del Sistema</label>
                            <input type="text" id="system_name" name="settings[system_name]" 
                                   value="<?php echo htmlspecialchars($settings['system_name'] ?? ''); ?>" 
                                   placeholder="Sistema de Registro de Asistencia">
                        </div>
                        
                        <div class="form-group">
                            <label for="academic_year">Año Académico</label>
                            <input type="text" id="academic_year" name="settings[academic_year]" 
                                   value="<?php echo htmlspecialchars($settings['academic_year'] ?? ''); ?>" 
                                   placeholder="2025">
                        </div>
                        
                        <div class="form-group">
                            <label for="default_password">Contraseña por Defecto</label>
                            <input type="text" id="default_password" name="settings[default_password]" 
                                   value="<?php echo htmlspecialchars($settings['default_password'] ?? ''); ?>" 
                                   placeholder="student123">
                            <small style="color: #666;">Para nuevos usuarios estudiantes</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="max_students">Máximo de Estudiantes</label>
                            <input type="number" id="max_students" name="settings[max_students]" 
                                   value="<?php echo htmlspecialchars($settings['max_students'] ?? ''); ?>" 
                                   placeholder="500" min="1" max="10000">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">💾 Guardar Configuraciones</button>
                </form>
            </div>

            <!-- Estadísticas por Mes -->
            <div class="form-container">
                <h2>📈 Actividad por Mes (Últimos 6 Meses)</h2>
                <div class="monthly-chart">
                    <?php foreach ($monthly_stats as $month => $count): ?>
                    <div class="month-bar">
                        <div class="bar-container">
                            <div class="bar-fill" style="height: <?php echo min(100, ($count / max(1, max($monthly_stats))) * 100); ?>%;"></div>
                        </div>
                        <div class="bar-label">
                            <div class="month-name"><?php echo date('M Y', strtotime($month . '-01')); ?></div>
                            <div class="month-count"><?php echo $count; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Herramientas de Mantenimiento -->
            <div class="form-container">
                <h2>🔧 Herramientas de Mantenimiento</h2>
                <div class="maintenance-tools">
                    <div class="tool-section">
                        <h3>🗄️ Base de Datos</h3>
                        <p>Herramientas para el mantenimiento de la base de datos</p>
                        <div class="tool-buttons">
                            <button onclick="exportDatabase()" class="btn btn-primary">📥 Exportar BD</button>
                            <button onclick="cleanOldRecords()" class="btn" style="background: #f56500; color: white;">🧹 Limpiar Registros Antiguos</button>
                        </div>
                    </div>
                    
                    <div class="tool-section">
                        <h3>👥 Usuarios</h3>
                        <p>Gestión masiva de usuarios del sistema</p>
                        <div class="tool-buttons">
                            <a href="bulk_import.php" class="btn btn-success">📤 Importar Usuarios CSV</a>
                            <button onclick="resetInactiveUsers()" class="btn" style="background: #718096; color: white;">🔄 Resetear Inactivos</button>
                        </div>
                    </div>
                    
                    <div class="tool-section">
                        <h3>📊 Reportes</h3>
                        <p>Generación de reportes del sistema</p>
                        <div class="tool-buttons">
                            <a href="generate_report.php?type=full" class="btn btn-primary">📋 Reporte Completo</a>
                            <a href="generate_report.php?type=statistics" class="btn btn-success">📈 Estadísticas</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del Sistema -->
            <div class="form-container">
                <h2>ℹ️ Información del Sistema</h2>
                <div class="system-info-grid">
                    <div class="info-item">
                        <div class="info-label">Versión del Sistema</div>
                        <div class="info-value">v2.0 - Panel de Administración</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">PHP Version</div>
                        <div class="info-value"><?php echo phpversion(); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Base de Datos</div>
                        <div class="info-value">MariaDB/MySQL</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Última Actualización</div>
                        <div class="info-value"><?php echo date('d/m/Y H:i'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Espacio Total BD</div>
                        <div class="info-value"><?php echo number_format(($db_stats['total_users'] + $db_stats['total_attendance']) * 0.1, 2); ?> KB</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Sesión Iniciada</div>
                        <div class="info-value"><?php echo date('d/m/Y H:i'); ?></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        .monthly-chart {
            display: flex;
            justify-content: space-around;
            align-items: flex-end;
            height: 200px;
            padding: 20px 0;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .month-bar {
            display: flex;
            flex-direction: column;
            align-items: