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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
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
        <a href="contact.php">Contacto</a>
    </nav>

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