<?php
require_once 'misvars.php';

// Activar visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error = '';
$success = '';

// Función simple para guardar mensaje
function saveMessage($name, $email, $message) {
    try {
        // Guardar en base de datos
        $db = getDBConnection();
        $stmt = $db->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $message]);

        // Preparar el email
        $to = SMTP_FROM;
        $subject = "Nuevo mensaje de contacto desde " . SITE_NAME;
        
        // Crear cabeceras para formato HTML
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_FROM . "\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        // Crear cuerpo del email en HTML
        $emailBody = "
        <html>
        <head>
            <title>Nuevo mensaje de contacto</title>
        </head>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Nuevo mensaje de contacto desde " . SITE_NAME . "</h2>
            <p><strong>Nombre:</strong> " . htmlspecialchars($name) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
            <p><strong>Mensaje:</strong></p>
            <p style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>" 
                . nl2br(htmlspecialchars($message)) . 
            "</p>
            <hr>
            <p style='color: #666; font-size: 12px;'>
                Este mensaje fue enviado desde el formulario de contacto de " . SITE_NAME . "
            </p>
        </body>
        </html>";

        // Enviar email
        if(mail($to, $subject, $emailBody, $headers)) {
            return true;
        } else {
            error_log("Error al enviar email de contacto");
            return false;
        }

    } catch (PDOException $e) {
        error_log("Error DB: " . $e->getMessage());
        return false;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar captcha primero
    $captcha = $_POST['g-recaptcha-response'] ?? '';
    $secretKey = RECAPTCHA_SECRET_KEY;
    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$captcha}");
    $captchaResult = json_decode($verify);

    if (!$captchaResult->success) {
        $error = "Por favor, verifica que no eres un robot.";
    } else {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $message = $_POST['message'] ?? '';

        if (empty($name) || empty($email) || empty($message)) {
            $error = "Todos los campos son obligatorios.";
        } else {
            if (saveMessage($name, $email, $message)) {
                $success = "¡Mensaje enviado correctamente! Te responderemos lo antes posible.";
            } else {
                $error = "Error al enviar el mensaje. Por favor, inténtalo de nuevo.";
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
    <title>Contacto - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        .contact-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .author-info {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }

        .author-info::before {
            content: '🎸';
            position: absolute;
            font-size: 120px;
            opacity: 0.1;
            right: -20px;
            bottom: -20px;
            transform: rotate(-15deg);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--primary);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
        }

        .form-group textarea {
            height: 150px;
            resize: vertical;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--secondary), #2980b9);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.2);
        }

        .error, .success {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-weight: 500;
        }

        .error {
            background-color: rgba(231, 76, 60, 0.1);
            color: #c0392b;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .success {
            background-color: rgba(46, 204, 113, 0.1);
            color: #27ae60;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .contact-info {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #e1e1e1;
            text-align: center;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            color: var(--primary);
            text-decoration: none;
            padding: 0.5rem;
            transition: color 0.3s ease;
        }

        .social-links a:hover {
            color: var(--secondary);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php"><?php echo SITE_NAME; ?></a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="submit-video.php">Recomendar Vídeo</a>
            <a href="logout.php">Cerrar Sesión</a>
        <?php else: ?>
            <a href="login.php">Iniciar Sesión</a>
            <a href="register.php">Registrarse</a>
        <?php endif; ?>
    </nav>

    <div class="contact-container">
        <div class="author-info">
            <h2>¡Hablemos de Música!</h2>
            <p>¿Tienes alguna sugerencia o pregunta? ¡Me encantaría escucharte!</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Nombre:</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                       placeholder="Tu nombre">
            </div>

            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       placeholder="tu@email.com">
            </div>

            <div class="form-group">
                <label for="message">Mensaje:</label>
                <textarea id="message" name="message" required
                          placeholder="¿Qué te gustaría decirnos?"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
            </div>

            <button type="submit" class="submit-btn">Enviar Mensaje</button>
        </form>

        <div class="contact-info">
            <h3>También puedes encontrarme en:</h3>
            <div class="social-links">
                <a href="#" title="YouTube">📺 YouTube</a>
                <a href="#" title="Twitter">🐦 Twitter</a>
                <a href="#" title="Instagram">📸 Instagram</a>
            </div>
        </div>
    </div>
</body>
</html> 