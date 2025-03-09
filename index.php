<?php
require_once 'misvars.php';

// Configuración de paginación
$videos_per_page = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $videos_per_page;

// Obtener videos
$db = getDBConnection();
$stmt = $db->prepare("
    SELECT v.*, u.email as user_email 
    FROM videos v 
    LEFT JOIN users u ON v.user_id = u.id 
    ORDER BY v.published_at DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $videos_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener total de videos para paginación
$total_videos = $db->query("SELECT COUNT(*) FROM videos")->fetchColumn();
$total_pages = ceil($total_videos / $videos_per_page);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .header-banner {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
                        url('assets/images/background.webp') center/cover;
            height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 2rem;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
            background-attachment: fixed;
            transition: all 0.3s ease;
        }

        .header-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at center, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.7) 100%);
            z-index: 1;
        }

        .header-banner h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.8);
            position: relative;
            z-index: 2;
            letter-spacing: 2px;
        }

        .header-banner .stats {
            position: relative;
            z-index: 2;
        }

        .header-description {
            max-width: 800px;
            margin: 0 auto 3rem;
            padding: 2rem;
            text-align: center;
            line-height: 1.6;
            font-size: 1.2rem;
            color: var(--text);
        }

        .highlight {
            color: var(--secondary);
            font-weight: 500;
        }

        .stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 2rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--light);
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <a href="index.php"><?php echo SITE_NAME; ?></a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="submit-video.php">Recomendar Vídeo</a>
                <?php if($_SESSION['user_id'] <= 4): ?>
                    <a href="usersmanagement.php">Gestión Usuarios</a>
                <?php endif; ?>
                <?php if($_SESSION['user_id'] <= 4): ?>
                    <a href="usersaudit.php">Auditoría Usuarios</a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="contact.php">Contacto</a>
        </div>
        <div class="nav-right">
            <?php if(isset($_SESSION['user_id'])): ?>
                <span class="user-email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></span>
                <a href="logout.php" class="auth-link">Cerrar Sesión</a>
            <?php else: ?>
                <a href="register.php" class="auth-link">Registrarse</a>
                <a href="login.php" class="auth-link">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </nav>

    <header class="header-banner">
        <h1><?php echo SITE_NAME; ?></h1>
        <div class="stats">
            <div class="stat-item">
                <div class="stat-number"><?php echo $total_videos; ?></div>
                <div class="stat-label">Vídeos</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php 
                    $stmt = $db->query("SELECT COUNT(*) FROM users");
                    echo $stmt->fetchColumn();
                ?></div>
                <div class="stat-label">Usuarios</div>
            </div>
        </div>
    </header>

    <div class="header-description">
        <h2>Bienvenido a la Comunidad de Guitarristas</h2>
        <p>
            Riffdinanza es tu destino definitivo para descubrir, aprender y compartir 
            los mejores tutoriales de guitarra. Desde legendarios solos de rock hasta 
            técnicas avanzadas de shred, nuestra comunidad reúne contenido de calidad 
            para guitarristas de todos los niveles.
        </p>
        <p>
            Únete a nuestra comunidad para <span class="highlight">compartir tus videos favoritos</span>, 
            interactuar con otros guitarristas y ser parte de este viaje musical. 
            Ya sea que estés comenzando tu camino en la guitarra o seas un músico experimentado, 
            aquí encontrarás inspiración y recursos para mejorar tu técnica.
        </p>
    </div>

    <div class="video-grid">
        <?php foreach($videos as $video): ?>
            <div class="video-card">
                <div class="video-thumbnail">
                    <?php
                    // Extraer ID del video de YouTube
                    preg_match("/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^\"&?\/\s]{11})/", $video['youtube_url'], $matches);
                    $youtube_id = $matches[1] ?? '';
                    ?>
                    <iframe 
                        width="100%" 
                        height="169" 
                        src="https://www.youtube.com/embed/<?php echo htmlspecialchars($youtube_id); ?>" 
                        frameborder="0" 
                        allowfullscreen>
                    </iframe>
                </div>
                <div class="video-info">
                    <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                    <p>Compartido por: <?php echo htmlspecialchars($video['user_email']); ?></p>
                    <p>Fecha: <?php echo date('d/m/Y', strtotime($video['published_at'])); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if($total_pages > 1): ?>
    <div class="pagination">
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" 
               class="<?php echo $page === $i ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</body>
</html> 