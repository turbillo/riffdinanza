<?php
require_once 'misvars.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

function logAudit($email, $status, $user_id = null, $details = null) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        INSERT INTO login_audit 
        (user_id, email, ip_address, user_agent, status, attempt_time, details) 
        VALUES (?, ?, ?, ?, ?, NOW(), ?)
    ");
    
    $stmt->execute([
        $user_id,
        $email,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'],
        $status,
        $details
    ]);
}

function checkLoginAttempts($ip) {
    $db = getDBConnection();
    
    // Limpiar intentos antiguos (más de 10 minutos)
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $stmt->execute();
    
    // Verificar si está bloqueado
    $stmt = $db->prepare("
        SELECT COUNT(*) as attempts, 
               MAX(attempt_time) as last_attempt 
        FROM login_attempts 
        WHERE ip_address = ? 
        AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    $stmt->execute([$ip]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['attempts'] >= 10) {
        logAudit('', 'blocked', null, 'Demasiados intentos fallidos: ' . $result['attempts'] . ' intentos');
        return [
            'blocked' => true,
            'remaining_time' => strtotime($result['last_attempt']) + 600 - time()
        ];
    }
    
    return ['blocked' => false];
}

function recordLoginAttempt($ip, $email) {
    $db = getDBConnection();
    $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, email, attempt_time) VALUES (?, ?, NOW())");
    $stmt->execute([$ip, $email]);
}

$error = '';
$blocked_info = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'];
    $check = checkLoginAttempts($ip);
    
    if ($check['blocked']) {
        $blocked_info = $check;
        $error = "Demasiados intentos fallidos. Por favor, espera " . ceil($check['remaining_time']/60) . " minutos.";
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Por favor, completa todos los campos.';
            logAudit($email, 'failed', null, 'Campos incompletos');
        } else {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT id, email, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                
                // Registrar login exitoso
                logAudit($email, 'success', $user['id'], 'Login exitoso');
                
                // Limpiar intentos fallidos
                $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
                $stmt->execute([$ip]);
                
                header('Location: index.php');
                exit();
            } else {
                recordLoginAttempt($ip, $email);
                logAudit($email, 'failed', null, 'Credenciales inválidas');
                $error = 'Credenciales inválidas.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        .form-container {
            max-width: 400px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            background: var(--secondary);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .error { 
            color: var(--accent); 
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid var(--accent);
            border-radius: 4px;
            background-color: rgba(231, 76, 60, 0.1);
        }
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .login-error {
            color: #ff3333;
            background-color: #ffe6e6;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .blocked-timer {
            font-weight: bold;
            color: #ff3333;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php"><?php echo SITE_NAME; ?></a>
        <a href="register.php">Registrarse</a>
        <a href="contact.php">Contacto</a>
    </nav>

    <div class="form-container">
        <h2>Iniciar Sesión</h2>
        
        <?php if ($error): ?>
            <div class="login-error">
                <?php echo htmlspecialchars($error); ?>
                <?php if ($blocked_info && $blocked_info['blocked']): ?>
                    <div class="blocked-timer" id="blocked-timer" 
                         data-remaining="<?php echo $blocked_info['remaining_time']; ?>">
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="auth-form">
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
            </div>

            <button type="submit" class="btn"
                    <?php echo ($blocked_info && $blocked_info['blocked']) ? 'disabled' : ''; ?>>
                Iniciar Sesión
            </button>
        </form>

        <div class="auth-links">
            <a href="register.php">¿No tienes cuenta? Regístrate</a>
        </div>

        <?php if (defined('DEBUG') && DEBUG): ?>
        <div class="debug-info">
            <p>Session ID: <?php echo session_id(); ?></p>
            <p>Session Data: <?php print_r($_SESSION); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($blocked_info && $blocked_info['blocked']): ?>
    <script>
        function updateTimer() {
            const timerElement = document.getElementById('blocked-timer');
            let remaining = parseInt(timerElement.dataset.remaining);
            
            const updateDisplay = () => {
                if (remaining <= 0) {
                    location.reload();
                    return;
                }
                
                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                timerElement.textContent = `Tiempo restante: ${minutes}:${seconds.toString().padStart(2, '0')}`;
                remaining--;
                timerElement.dataset.remaining = remaining;
            };
            
            updateDisplay();
            setInterval(updateDisplay, 1000);
        }
        
        document.addEventListener('DOMContentLoaded', updateTimer);
    </script>
    <?php endif; ?>
</body>
</html> 