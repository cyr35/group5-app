<?php
require_once 'config.php';
require_once 'db_connect.php';

// Verificar autenticación
requireAuth();

// Obtener datos del usuario actual
$current_user = fetchOne(
    "SELECT * FROM users WHERE id = ?",
    [$_SESSION['user_id']],
    'i'
);

// Datos para estadísticas
$stats = [];

if (isTeacher()) {
    // Estadísticas para profesores
    $stats['total_students'] = fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'student'")['count'];
    $stats['today_attendance'] = fetchOne(
        "SELECT COUNT(*) as count FROM attendance WHERE date = CURDATE() AND teacher_id = ?",
        [$_SESSION['user_id']],
        'i'
    )['count'];
    
    // Obtener estudiantes para el registro de asistencia
    $students = fetchAll("SELECT id, username, full_name FROM users WHERE role = 'student' ORDER BY full_name");
    
    // Obtener asistencias recientes
    $recent_attendance = fetchAll(
        "SELECT a.*, s.full_name as student_name, s.username as student_username
         FROM attendance a 
         JOIN users s ON a.student_id = s.id 
         WHERE a.teacher_id = ? 
         ORDER BY a.date DESC, a.created_at DESC 
         LIMIT 10",
        [$_SESSION['user_id']],
        'i'
    );
    
} else {
    // Estadísticas para estudiantes
    $stats['total_days'] = fetchOne(
        "SELECT COUNT(*) as count FROM attendance WHERE student_id = ?",
        [$_SESSION['user_id']],
        'i'
    )['count'];
    
    $stats['present_days'] = fetchOne(
        "SELECT COUNT(*) as count FROM attendance WHERE student_id = ? AND status = 'present'",
        [$_SESSION['user_id']],
        'i'
    )['count'];
    
    $stats['absent_days'] = fetchOne(
        "SELECT COUNT(*) as count FROM attendance WHERE student_id = ? AND status = 'absent'",
        [$_SESSION['user_id']],
        'i'
    )['count'];
    
    // Calcular porcentaje de asistencia
    $attendance_percentage = $stats['total_days'] > 0 ? 
        round(($stats['present_days'] / $stats['total_days']) * 100, 1) : 0;
    
    // Obtener historial de asistencia del estudiante
    $my_attendance = fetchAll(
        "SELECT a.*, t.full_name as teacher_name 
         FROM attendance a 
         JOIN users t ON a.teacher_id = t.id 
         WHERE a.student_id = ? 
         ORDER BY a.date DESC 
         LIMIT 20",
        [$_SESSION['user_id']],
        'i'
    );
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .stat-card.warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
        .stat-card.danger {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
        }
        .table th {
            border-top: none;
            background-color: #f8f9fa;
        }
        .badge-present {
            background-color: #28a745;
        }
        .badge-absent {
            background-color: #dc3545;
        }
        .badge-late {
            background-color: #ffc107;
            color: #000;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-user-graduate"></i>
                <?php echo APP_NAME; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user"></i>
                        <?php echo escape($current_user['full_name']); ?>
                        <span class="badge bg-light text-dark ms-1">
                            <?php echo ucfirst($current_user['role']); ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Bienvenida -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard - Bienvenido, <?php echo escape($current_user['full_name']); ?>
                </h1>
                <p class="text-muted">Fecha: <?php echo formatDate(date('Y-m-d')); ?></p>
            </div>
        </div>

        <?php if (isTeacher()): ?>
            <!-- Dashboard para Profesores -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <h3><?php echo $stats['total_students']; ?></h3>
                            <p class="mb-0">Total Estudiantes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card stat-card success">
                        <div class="card-body text-center">
                            <i class="fas fa-check-circle fa-3x mb-3"></i>
                            <h3><?php echo $stats['today_attendance']; ?></h3>
                            <p class="mb-0">Asistencias Hoy</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registro de Asistencia -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clipboard-check"></i>
                                Registrar Asistencia
                            </h5>
                        </div>
                        <div class="card-body">
                            <a href="register_attendance.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Registrar Asistencia del Día
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asistencias Recientes -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history"></i>
                                Asistencias Recientes
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_attendance)): ?>
                                <p class="text-muted text-center">No hay registros de asistencia.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Estudiante</th>
                                                <th>Fecha</th>
                                                <th>Estado</th>
                                                <th>Notas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_attendance as $record): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo escape($record['student_name']); ?></strong><br>
                                                        <small class="text-muted">@<?php echo escape($record['student_username']); ?></small>
                                                    </td>
                                                    <td><?php echo formatDate($record['date']); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $record['status']; ?>">
                                                            <?php 
                                                            $status_labels = [
                                                                'present' => 'Presente',
                                                                'absent' => 'Ausente', 
                                                                'late' => 'Tarde'
                                                            ];
                                                            echo $status_labels[$record['status']];
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo escape($record['notes']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Dashboard para Estudiantes -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar fa-2x mb-3"></i>
                            <h4><?php echo $stats['total_days']; ?></h4>
                            <p class="mb-0">Total Días</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card success">
                        <div class="card-body text-center">
                            <i class="fas fa-check fa-2x mb-3"></i>
                            <h4><?php echo $stats['present_days']; ?></h4>
                            <p class="mb-0">Presentes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card danger">
                        <div class="card-body text-center">
                            <i class="fas fa-times fa-2x mb-3"></i>
                            <h4><?php echo $stats['absent_days']; ?></h4>
                            <p class="mb-0">Ausentes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card warning">
                        <div class="card-body text-center">
                            <i class="fas fa-percentage fa-2x mb-3"></i>
                            <h4><?php echo $attendance_percentage; ?>%</h4>
                            <p class="mb-0">Asistencia</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mi Historial de Asistencia -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user-clock"></i>
                                Mi Historial de Asistencia
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($my_attendance)): ?>
                                <p class="text-muted text-center">No hay registros de asistencia.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Estado</th>
                                                <th>Profesor</th>
                                                <th>Notas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($my_attendance as $record): ?>
                                                <tr>
                                                    <td><?php echo formatDate($record['date']); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $record['status']; ?>">
                                                            <?php 
                                                            $status_labels = [
                                                                'present' => 'Presente',
                                                                'absent' => 'Ausente', 
                                                                'late' => 'Tarde'
                                                            ];
                                                            echo $status_labels[$record['status']];
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo escape($record['teacher_name']); ?></td>
                                                    <td><?php echo escape($record['notes'] ?: '-'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>