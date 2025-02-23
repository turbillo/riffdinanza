<?php
require_once 'misvars.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar captcha
    $captcha = $_POST['g-recaptcha-response'];
    $secretKey = RECAPTCHA_SECRET_KEY;
    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$captcha}");
    $captchaResult = json_decode($verify);

    if (!$captchaResult->success) {
        $error = "Por favor, verifica que no eres un robot.";
    } else {
        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);

        // Guardar en la base de datos
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $message]);

            // Enviar email usando PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Configuración del servidor
                $mail->isSMTP();
                $mail->Host = 'smtp.tuservidor.com'; // Reemplazar con tu servidor SMTP
                $mail->SMTPAuth = true;
                $mail->Username = 'tu_usuario_smtp';  // Reemplazar con tu usuario SMTP
                $mail->Password = 'tu_password_smtp'; // Reemplazar con tu contraseña SMTP
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Destinatarios
                $mail->setFrom($email, $name);
                $mail->addAddress(CONTACT_EMAIL);

                // Contenido
                $mail->isHTML(true);
                $mail->Subject = 'Nuevo mensaje de contacto desde ' . SITE_NAME;
                $mail->Body = "
                    <h2>Nuevo mensaje de contacto</h2>
                    <p><strong>Nombre:</strong> {$name}</p>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Mensaje:</strong></p>
                    <p>{$message}</p>
                ";

                $mail->send();
                $success = "¡Mensaje enviado correctamente!";
            } catch (Exception $e) {
                $error = "Error al enviar el mensaje. Por favor, intenta más tarde.";
            }
        } catch (PDOException $e) {
            $error = "Error al procesar el mensaje. Por favor, intenta más tarde.";
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        .contact-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .author-info {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: var(--light);
            border-radius: 8px;
        }
        .author-info img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group textarea {
            height: 150px;
        }
        .btn {
            background: var(--secondary);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .error { color: var(--accent); }
        .success { color: green; }
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
            <h2>Sobre el Autor</h2>
            <p><?php echo SITE_AUTHOR; ?></p>
            <p>Creador y administrador de <?php echo SITE_NAME; ?></p>
        </div>

        <h2>Contacto</h2>
        
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Nombre:</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="email">Tu Correo Electrónico:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="message">Mensaje:</label>
                <textarea id="message" name="message" required></textarea>
            </div>

            <div class="form-group">
                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
            </div>

            <button type="submit" class="btn">Enviar Mensaje</button>
        </form>
    </div>
</body>
</html> 