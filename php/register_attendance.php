<?php
require_once 'config.php';
require_once 'db_connect.php';

// Solo profesores pueden acceder
requireTeacher();

$success_message = '';
$error_message = '';

// Obtener todos los estudiantes
$students = fetchAll("SELECT id, username, full_name FROM users WHERE role = 'student' ORDER BY full_name");

// Obtener fecha seleccionada (por defecto hoy)
$selected_date = $_POST['attendance_date'] ?? date('Y-m-d');

// Verificar si ya existe asistencia para esta fecha
$existing_attendance = fetchAll(
    "SELECT student_id, status, notes FROM attendance WHERE date = ? AND teacher_id = ?",
    [$selected_date, $_SESSION['user_id']],
    'si'
);

// Convertir a array asociativo para fácil acceso
$existing_data = [];
foreach ($existing_attendance as $record) {
    $existing_data[$record['student_id']] = $record;
}

// Procesar formulario de asistencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $attendance_date = $_POST['attendance_date'];
    $attendance_data = $_POST['attendance'] ?? [];
    $notes_data = $_POST['notes'] ?? [];
    
    if (empty($attendance_date)) {
        $error_message = 'Por favor seleccione una fecha.';
    } else {
        $success_count = 0;
        $error_count = 0;
        
        foreach ($students as $student) {
            $student_id = $student['id'];
            $status = $attendance_data[$student_id] ?? 'absent';
            $notes = trim($notes_data[$student_id] ?? '');
            
            // Verificar si ya existe registro para este estudiante y fecha
            $existing = fetchOne(
                "SELECT id FROM attendance WHERE student_id = ? AND date = ?",
                [$student_id, $attendance_date],
                'is'
            );
            
            try {
                if ($existing) {
                    // Actualizar registro existente
                    executeQuery(
                        "UPDATE attendance SET status = ?, notes = ?, teacher_id = ? WHERE student_id = ? AND date = ?",
                        [$status, $notes, $_SESSION['user_id'], $student_id, $attendance_date],
                        'ssiis'
                    );
                } else {
                    // Crear nuevo registro
                    executeQuery(
                        "INSERT INTO attendance (student_id, teacher_id, date, status, notes) VALUES (?, ?, ?, ?, ?)",
                        [$student_id, $_SESSION['user_id'], $attendance_date, $status, $notes],
                        'iisss'
                    );
                }
                $success_count++;
            } catch (Exception $e) {
                $error_count++;
            }
        }
        
        if ($success_count > 0) {
            $success_message = "Asistencia guardada exitosamente para {$success_count} estudiantes.";
            
            // Recargar datos existentes
            $existing_attendance = fetchAll(
                "SELECT student_id, status, notes FROM attendance WHERE date = ? AND teacher_id = ?",
                [$selected_date, $_SESSION['user_id']],
                'si'
            );
            
            $existing_data = [];
            foreach ($existing_attendance as $record) {
                $existing_data[$record['student_id']] = $record;
            }
        }
        
        if ($error_count > 0) {
            $error_message = "Hubo errores al guardar {$error_count} registros.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Registrar Asistencia</title>
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
        }
        .student-row {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .student-row:last-child {
            border-bottom: none;
        }
        .status-radio {
            margin: 0 10px;
        }
        .status-radio input[type="radio"] {
            margin-right: 5px;
        }
        .btn-save {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
        }
        .btn-save:hover {
            background: linear-gradient(135deg, #218838 0%, #1aa085 100%);
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
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3">
                    <i class="fas fa-clipboard-check"></i>
                    Registrar Asistencia
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Registrar Asistencia</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if ($success_message): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i>
                <?php echo escape($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo escape($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Formulario de Asistencia -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-alt"></i>
                    Asistencia - <?php echo formatDate($selected_date); ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <!-- Selector de Fecha -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="attendance_date" class="form-label">
                                <i class="fas fa-calendar"></i> Fecha de Asistencia
                            </label>
                            <input type="date" class="form-control" id="attendance_date" 
                                   name="attendance_date" value="<?php echo $selected_date; ?>" 
                                   onchange="this.form.submit()">
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="setToday()">
                                    <i class="fas fa-calendar-day"></i> Hoy
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="setYesterday()">
                                    <i class="fas fa-calendar-minus"></i> Ayer
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($students)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            No hay estudiantes registrados en el sistema.
                        </div>
                    <?php else: ?>
                        <!-- Lista de Estudiantes -->
                        <div class="mb-4">
                            <h6>Estudiantes (<?php echo count($students); ?>)</h6>
                            <hr>
                            
                            <?php foreach ($students as $student): 
                                $existing_record = $existing_data[$student['id']] ?? null;
                                $current_status = $existing_record['status'] ?? 'present';
                                $current_notes = $existing_record['notes'] ?? '';
                            ?>
                                <div class="student-row">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <strong><?php echo escape($student['full_name']); ?></strong><br>
                                            <small class="text-muted">@<?php echo escape($student['username']); ?></small>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="d-flex">
                                                <div class="status-radio">
                                                    <label class="form-check-label text-success">
                                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                               value="present" <?php echo $current_status === 'present' ? 'checked' : ''; ?>>
                                                        <i class="fas fa-check"></i> Presente
                                                    </label>
                                                </div>
                                                <div class="status-radio">
                                                    <label class="form-check-label text-warning">
                                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                               value="late" <?php echo $current_status === 'late' ? 'checked' : ''; ?>>
                                                        <i class="fas fa-clock"></i> Tarde
                                                    </label>
                                                </div>
                                                <div class="status-radio">
                                                    <label class="form-check-label text-danger">
                                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                               value="absent" <?php echo $current_status === 'absent' ? 'checked' : ''; ?>>
                                                        <i class="fas fa-times"></i> Ausente
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <input type="text" class="form-control form-control-sm" 
                                                   name="notes[<?php echo $student['id']; ?>]" 
                                                   placeholder="Notas opcionales"
                                                   value="<?php echo escape($current_notes); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="button" class="btn btn-outline-success" onclick="markAllPresent()">
                                            <i class="fas fa-check-double"></i> Marcar Todos Presentes
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" onclick="markAllAbsent()">
                                            <i class="fas fa-times-circle"></i> Marcar Todos Ausentes
                                        </button>
                                    </div>
                                    <div>
                                        <a href="dashboard.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Cancelar
                                        </a>
                                        <button type="submit" name="save_attendance" class="btn btn-success btn-save">
                                            <i class="fas fa-save"></i> Guardar Asistencia
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function setToday() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('attendance_date').value = today;
            document.getElementById('attendance_date').form.submit();
        }
        
        function setYesterday() {
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            document.getElementById('attendance_date').value = yesterday.toISOString().split('T')[0];
            document.getElementById('attendance_date').form.submit();
        }
        
        function markAllPresent() {
            const radios = document.querySelectorAll('input[type="radio"][value="present"]');
            radios.forEach(radio => radio.checked = true);
        }
        
        function markAllAbsent() {
            const radios = document.querySelectorAll('input[type="radio"][value="absent"]');
            radios.forEach(radio => radio.checked = true);
        }
    </script>
</body>
</html>