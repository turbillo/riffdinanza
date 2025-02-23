<?php
require_once 'misvars.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar captcha
    $captcha = $_POST['g-recaptcha-response'] ?? '';
    $secretKey = RECAPTCHA_SECRET_KEY;
    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$captcha}");
    $captchaResult = json_decode($verify);

    if (!$captchaResult->success) {
        $error = "Por favor, verifica que no eres un robot.";
    } else {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        try {
            $db = getDBConnection();
            
            // Añadimos un log para debug
            error_log("Intento de login para email: " . $email);
            
            $stmt = $db->prepare("SELECT id, email, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Añadimos logs para debug
                error_log("Usuario encontrado con ID: " . $user['id']);
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    error_log("Login exitoso para usuario ID: " . $user['id']);
                    
                    header('Location: index.php');
                    exit;
                } else {
                    error_log("Contraseña incorrecta para usuario ID: " . $user['id']);
                    $error = "Correo electrónico o contraseña incorrectos.";
                }
            } else {
                error_log("No se encontró usuario con email: " . $email);
                $error = "Correo electrónico o contraseña incorrectos.";
            }
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            $error = "Error en el inicio de sesión. Por favor, intenta más tarde.";
        }
    }
}

// Añadimos un log para ver si hay sesión activa
if (isset($_SESSION['user_id'])) {
    error_log("Sesión activa para usuario ID: " . $_SESSION['user_id']);
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
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" action="">
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

            <button type="submit" class="btn">Iniciar Sesión</button>
        </form>

        <?php if (defined('DEBUG') && DEBUG): ?>
        <div class="debug-info">
            <p>Session ID: <?php echo session_id(); ?></p>
            <p>Session Data: <?php print_r($_SESSION); ?></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html> 