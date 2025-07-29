<?php
/**
 * Página de login del Sistema de Asistencia
 * Versión corregida con mejores prácticas de seguridad
 */

define('ATTENDANCE_SYSTEM', true);
require_once 'config.php';
require_once 'db_connect.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Variables para mensajes
$error_message = '';
$success_message = '';
$redirect_url = $_GET['redirect'] ?? 'dashboard.php';

// Verificar intentos de login fallidos (protección contra fuerza bruta)
$client_ip = getClientIP();
$lockout_key = 'login_attempts_' . md5($client_ip);

if (!isset($_SESSION[$lockout_key])) {
    $_SESSION[$lockout_key] = ['attempts' => 0, 'last_attempt' => 0];
}

$login_attempts = $_SESSION[$lockout_key];

// Verificar si está bloqueado
$is_locked = ($login_attempts['attempts'] >= MAX_LOGIN_ATTEMPTS) && 
             (time() - $login_attempts['last_attempt'] < LOCKOUT_TIME);

if ($is_locked) {
    $remaining_time = LOCKOUT_TIME - (time() - $login_attempts['last_attempt']);
    $error_message = "Demasiados intentos fallidos. Intenta de nuevo en " . ceil($remaining_time / 60) . " minutos.";
}

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validar token CSRF
    if (!verifyCSRFToken($csrf_token)) {
        $error_message = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
    }
    // Validar campos requeridos
    elseif (empty($username) || empty($password)) {
        $error_message = 'Por favor, complete todos los campos.';
    }
    // Validar longitud
    elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error_message = 'El nombre de usuario debe tener entre 3 y 50 caracteres.';
    }
    elseif (strlen($password) < 6) {
        $error_message = 'La contraseña debe tener al menos 6 caracteres.';
    }
    else {
        try {
            // Buscar usuario en la base de datos
            $user = fetchOne(
                "SELECT id, username, password, role, full_name, email, is_active 
                 FROM users WHERE username = ? AND is_active = 1",
                [$username],
                's'
            );
            
            if ($user && password_verify($password, $user['password'])) {
                // Login exitoso - resetear intentos
                $_SESSION[$lockout_key] = ['attempts' => 0, 'last_attempt' => 0];
                
                // Regenerar ID de sesión por seguridad
                session_regenerate_id(true);
                
                // Establecer variables de sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                
                // Log del login exitoso
                logError("Login exitoso: Usuario {$user['username']} (ID: {$user['id']}) desde IP: $client_ip", 'INFO');
                
                // Actualizar último login en la base de datos
                executeQuery(
                    "UPDATE users SET updated_at = NOW() WHERE id = ?",
                    [$user['id']],
                    'i'
                );
                
                // Mensaje flash de bienvenida
                setFlashMessage('success', getGreeting() . ', ' . $user['full_name'] . '!');
                
                // Redirigir
                header('Location: ' . $redirect_url);
                exit();
                
            } else {
                // Login fallido - incrementar intentos
                $_SESSION[$lockout_key]['attempts']++;
                $_SESSION[$lockout_key]['last_attempt'] = time();
                
                // Log del intento fallido
                logError("Intento de login fallido: Usuario '$username' desde IP: $client_ip", 'WARNING');
                
                $remaining_attempts = MAX_LOGIN_ATTEMPTS - $_SESSION[$lockout_key]['attempts'];
                
                if ($remaining_attempts > 0) {
                    $error_message = "Usuario o contraseña incorrectos. Te quedan $remaining_attempts intentos.";
                } else {
                    $error_message = "Demasiados intentos fallidos. Tu IP ha sido bloqueada por " . (LOCKOUT_TIME / 60) . " minutos.";
                }
            }
            
        } catch (Exception $e) {
            logError("Error en proceso de login: " . $e->getMessage(), 'ERROR');
            $error_message = 'Error interno del servidor. Intenta de nuevo más tarde.';
        }
    }
}

// Resetear intentos si ha pasado el tiempo de bloqueo
if ($is_locked && (time() - $login_attempts['last_attempt']) >= LOCKOUT_TIME) {
    $_SESSION[$lockout_key] = ['attempts' => 0, 'last_attempt' => 0];
    $is_locked = false;
}

// Generar token CSRF
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo APP_NAME; ?> - Sistema de gestión de asistencia escolar">
    <title><?php echo APP_NAME; ?> - Iniciar Sesión</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 480px;
            width: 100%;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="80" r="2" fill="white" opacity="0.1"/><circle cx="40" cy="60" r="1" fill="white" opacity="0.1"/></svg>');
        }
        
        .login-header > * {
            position: relative;
            z-index: 1;
        }
        
        .login-body {
            padding: 2.5rem 2rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group .form-control {
            padding-left: 3rem;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-login:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .demo-credentials {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            border: 1px solid #dee2e6;
        }
        
        .demo-user {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border: 1px solid #e9ecef;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .demo-user:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .demo-user:last-child {
            margin-bottom: 0;
        }
        
        .loading-spinner {
            display: none;
        }
        
        .btn-login.loading .loading-spinner {
            display: inline-block;
        }
        
        .btn-login.loading .btn-text {
            display: none;
        }
        
        .lockout-timer {
            background: #dc3545;
            color: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7 col-sm-9">
                <div class="login-container">
                    <!-- Header -->
                    <div class="login-header">
                        <i class="fas fa-graduation-cap fa-4x mb-3"></i>
                        <h2 class="mb-2"><?php echo APP_NAME; ?></h2>
                        <p class="mb-0 opacity-75">Iniciar Sesión</p>
                        <small class="opacity-50">Versión <?php echo APP_VERSION; ?></small>
                    </div>
                    
                    <!-- Body -->
                    <div class="login-body">
                        <!-- Mensajes -->
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo escape($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success d-flex align-items-center" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo escape($success_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Contador de bloqueo -->
                        <?php if ($is_locked): ?>
                            <div class="lockout-timer" id="lockoutTimer">
                                <i class="fas fa-lock mb-2"></i><br>
                                Acceso bloqueado. Tiempo restante: <span id="countdown"></span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Formulario -->
                        <form method="POST" action="" id="loginForm" <?php echo $is_locked ? 'style="display:none;"' : ''; ?>>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label fw-semibold">
                                    <i class="fas fa-user me-1"></i> Usuario
                                </label>
                                <div class="input-group">
                                    <span class="input-icon">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           name="username" 
                                           value="<?php echo escape($_POST['username'] ?? ''); ?>"
                                           maxlength="50"
                                           autocomplete="username"
                                           required 
                                           <?php echo $is_locked ? 'disabled' : ''; ?>>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="fas fa-lock me-1"></i> Contraseña
                                </label>
                                <div class="input-group">
                                    <span class="input-icon">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           autocomplete="current-password"
                                           required 
                                           <?php echo $is_locked ? 'disabled' : ''; ?>>
                                    <button type="button" 
                                            class="btn btn-outline-secondary" 
                                            id="togglePassword"
                                            <?php echo $is_locked ? 'disabled' : ''; ?>>
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" 
                                        class="btn btn-primary btn-login" 
                                        id="loginBtn"
                                        <?php echo $is_locked ? 'disabled' : ''; ?>>
                                    <span class="btn-text">
                                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                                    </span>
                                    <span class="loading-spinner">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Verificando...
                                    </span>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Credenciales de demo -->
                        <div class="demo-credentials">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-info-circle me-1"></i> Credenciales de Demostración
                            </h6>
                            
                            <div class="demo-user" onclick="fillCredentials('profesor1', 'teacher_password')">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><i class="fas fa-chalkboard-teacher me-1"></i> Profesor</strong><br>
                                        <small class="text-muted">Usuario: profesor1</small>
                                    </div>
                                    <i class="fas fa-arrow-right text-primary"></i>
                                </div>
                            </div>
                            
                            <div class="demo-user" onclick="fillCredentials('estudiante1', 'student_password')">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><i class="fas fa-user-graduate me-1"></i> Estudiante</strong><br>
                                        <small class="text-muted">Usuario: estudiante1</small>
                                    </div>
                                    <i class="fas fa-arrow-right text-primary"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Info adicional -->
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Conexión segura • IP: <?php echo escape($client_ip); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Fill demo credentials
        function fillCredentials(username, password) {
            if (<?php echo $is_locked ? 'true' : 'false'; ?>) return;
            
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            document.getElementById('username').focus();
        }
        
        // Loading state for form
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });
        
        // Countdown timer for lockout
        <?php if ($is_locked): ?>
        let remainingTime = <?php echo LOCKOUT_TIME - (time() - $login_attempts['last_attempt']); ?>;
        
        function updateCountdown() {
            const minutes = Math.floor(remainingTime / 60);
            const seconds = remainingTime % 60;
            
            document.getElementById('countdown').textContent = 
                minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            
            if (remainingTime <= 0) {
                location.reload();
                return;
            }
            
            remainingTime--;
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
        <?php endif; ?>
        
        // Auto-focus on username field
        <?php if (!$is_locked): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        <?php endif; ?>
    </script>
</body>
</html>