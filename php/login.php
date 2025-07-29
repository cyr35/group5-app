<?php
session_start();
require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT id, password, role, full_name, status FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_password, $role, $full_name, $status);
            $stmt->fetch();
            
            // Verificar que el usuario esté activo
            if ($status !== 'active') {
                $error = 'Tu cuenta está inactiva. Contacta al administrador.';
            } elseif (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                $_SESSION['full_name'] = $full_name;
                
                // Redirigir según el rol
                if ($role === 'admin') {
                    header('Location: admin_panel.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            } else {
                $error = 'Contraseña incorrecta.';
            }
        } else {
            $error = 'Usuario no encontrado.';
        }
        $stmt->close();
    } catch (Exception $e) {
        $error = 'Error en el sistema. Intenta nuevamente.';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Asistencia</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .demo-credentials {
            margin-top: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            color: white;
        }
        
        .demo-credentials h3 {
            margin-bottom: 15px;
            color: white;
            text-align: center;
        }
        
        .credential-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .credential-item:last-child {
            border-bottom: none;
        }
        
        .credential-role {
            font-weight: bold;
            font-size: 14px;
        }
        
        .credential-info {
            text-align: right;
            font-size: 12px;
        }
        
        .quick-login {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            margin-left: 10px;
            transition: background 0.3s ease;
        }
        
        .quick-login:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .system-info {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
            opacity: 0.9;
        }
        
        .version-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🎓 Sistema de Asistencia</h2>
        <p style="text-align: center; color: #666; margin-bottom: 30px;">
            Inicia sesión para acceder al sistema
        </p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form action="login.php" method="post" id="loginForm">
            <div class="form-group">
                <label for="username">👤 Usuario:</label>
                <input type="text" id="username" name="username" required 
                       placeholder="Ingresa tu nombre de usuario">
            </div>
            <div class="form-group">
                <label for="password">🔒 Contraseña:</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Ingresa tu contraseña">
            </div>
            <button type="submit">🚀 Iniciar Sesión</button>
        </form>
        
        <!-- Credenciales de demostración -->
        <div class="demo-credentials">
            <h3>🔧 Credenciales de Prueba</h3>
            <div class="credential-item">
                <div>
                    <div class="credential-role">👑 Administrador</div>
                </div>
                <div class="credential-info">
                    <strong>admin</strong> / password
                    <button type="button" class="quick-login" onclick="quickLogin('admin', 'password')">
                        Acceso Rápido
                    </button>
                </div>
            </div>
            <div class="credential-item">
                <div>
                    <div class="credential-role">👨‍🏫 Profesor</div>
                </div>
                <div class="credential-info">
                    <strong>profesor1</strong> / password
                    <button type="button" class="quick-login" onclick="quickLogin('profesor1', 'password')">
                        Acceso Rápido
                    </button>
                </div>
            </div>
            <div class="credential-item">
                <div>
                    <div class="credential-role">👨‍🎓 Estudiante</div>
                </div>
                <div class="credential-info">
                    <strong>estudiante1</strong> / password
                    <button type="button" class="quick-login" onclick="quickLogin('estudiante1', 'password')">
                        Acceso Rápido
                    </button>
                </div>
            </div>
            
            <div class="system-info">
                <div>🔐 <strong>Panel de Administración:</strong> Gestiona usuarios, ve reportes completos</div>
                <div>📊 <strong>Dashboard de Profesor:</strong> Registra asistencia, ve todos los estudiantes</div>
                <div>📋 <strong>Vista de Estudiante:</strong> Consulta tu historial de asistencia</div>
                <div class="version-badge">v2.0 - Panel de Administración</div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px; color: #888; font-size: 12px;">
            <p>¿Problemas para acceder? Contacta al administrador del sistema</p>
        </div>
    </div>

    <script>
        function quickLogin(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            
            // Opcional: enviar automáticamente
            // document.getElementById('loginForm').submit();
            
            // O resaltar los campos para que el usuario vea qué se llenó
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            
            usernameField.style.background = '#e8f5e8';
            passwordField.style.background = '#e8f5e8';
            
            setTimeout(() => {
                usernameField.style.background = '';
                passwordField.style.background = '';
            }, 2000);
            
            // Enfocar el botón de envío
            document.querySelector('button[type="submit"]').focus();
        }
        
        // Efecto de carga al enviar
        document.getElementById('loginForm').addEventListener('submit', function() {
            const button = document.querySelector('button[type="submit"]');
            button.innerHTML = '⏳ Iniciando sesión...';
            button.disabled = true;
        });
    </script>
</body>
</html>