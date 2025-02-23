<?php
require_once 'misvars.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

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
        $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
        $youtube_url = filter_var($_POST['youtube_url'], FILTER_SANITIZE_URL);

        // Validar URL de YouTube
        if (!preg_match('/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $youtube_url, $matches)) {
            $error = "Por favor, introduce una URL válida de YouTube.";
        } else {
            try {
                $db = getDBConnection();
                $stmt = $db->prepare("INSERT INTO videos (title, youtube_url, user_id) VALUES (?, ?, ?)");
                $stmt->execute([$title, $youtube_url, $_SESSION['user_id']]);
                
                $success = "¡Vídeo recomendado correctamente!";
            } catch (PDOException $e) {
                $error = "Error al guardar el vídeo. Por favor, intenta más tarde.";
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
    <title>Recomendar Vídeo - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        .submit-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .help-text {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.25rem;
        }
        .btn {
            background: var(--secondary);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background: #2980b9;
        }
        .error { 
            color: var(--accent);
            padding: 0.5rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .success { 
            color: green;
            background: #e8f5e9;
            padding: 0.5rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        #preview {
            margin-top: 1rem;
            display: none;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php"><?php echo SITE_NAME; ?></a>
        <a href="logout.php">Cerrar Sesión</a>
        <a href="contact.php">Contacto</a>
    </nav>

    <div class="submit-container">
        <h2>Recomendar un Vídeo</h2>
        
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <form method="POST" action="" id="videoForm">
            <div class="form-group">
                <label for="title">Título del Vídeo:</label>
                <input type="text" id="title" name="title" required>
                <p class="help-text">Introduce un título descriptivo para el vídeo</p>
            </div>

            <div class="form-group">
                <label for="youtube_url">URL de YouTube:</label>
                <input type="url" id="youtube_url" name="youtube_url" required 
                       placeholder="https://www.youtube.com/watch?v=..." 
                       onchange="previewVideo()">
                <p class="help-text">Pega la URL completa del vídeo de YouTube</p>
            </div>

            <div id="preview"></div>

            <div class="form-group">
                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
            </div>

            <button type="submit" class="btn">Recomendar Vídeo</button>
        </form>
    </div>

    <script>
    function previewVideo() {
        const url = document.getElementById('youtube_url').value;
        const previewDiv = document.getElementById('preview');
        
        // Extraer ID del video
        const regExp = /^.*(youtu\.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        const match = url.match(regExp);

        if (match && match[2].length === 11) {
            const videoId = match[2];
            previewDiv.innerHTML = `
                <h3>Vista previa:</h3>
                <iframe width="100%" 
                        height="315" 
                        src="https://www.youtube.com/embed/${videoId}" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen></iframe>`;
            previewDiv.style.display = 'block';
        } else {
            previewDiv.innerHTML = '<p class="error">URL de YouTube no válida</p>';
            previewDiv.style.display = 'block';
        }
    }
    </script>
</body>
</html> 