<?php
// Habilitar mostrar errores para debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conn->error);
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_password, $role);
            $stmt->fetch();
            
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                
                // Debug: verificar que las variables de sesión se establecieron
                echo "Login exitoso. Redirigiendo...";
                echo "<script>setTimeout(function(){ window.location.href = 'dashboard.php'; }, 2000);</script>";
                exit();
            } else {
                $error = 'Contraseña incorrecta.';
            }
        } else {
            $error = 'Usuario no encontrado.';
        }
        $stmt->close();
    } catch (Exception $e) {
        $error = 'Error en la base de datos: ' . $e->getMessage();
    }
}

// Verificar conexión a la base de datos
if ($conn->connect_error) {
    $error = "Error de conexión: " . $conn->connect_error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Asistencia</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h2>Iniciar Sesión</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Entrar</button>
        </form>
        
        <!-- Información de prueba -->
        <div style="margin-top: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 4px;">
            <h4>Credenciales de Prueba:</h4>
            <p><strong>Profesor:</strong> profesor1 / teacher_password</p>
            <p><strong>Estudiante:</strong> estudiante1 / student_password</p>
        </div>
    </div>
</body>
</html>