<?php
require_once 'misvars.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar captcha
    $captcha = $_POST['g-recaptcha-response'];
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
            $stmt = $db->prepare("SELECT id, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                header('Location: index.php');
                exit;
            } else {
                $error = "Correo electrónico o contraseña incorrectos.";
            }
        } catch (PDOException $e) {
            $error = "Error en el inicio de sesión. Por favor, intenta más tarde.";
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
        .error { color: var(--accent); }
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
                <input type="email" id="email" name="email" required>
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
    </div>
</body>
</html> 