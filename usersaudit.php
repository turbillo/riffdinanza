<?php
require_once 'misvars.php';
session_start();

// Verificar si el usuario está logueado y tiene permisos
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] > 4) {
    header('Location: index.php');
    exit();
}

// Configuración de paginación
$records_per_page = 100;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Obtener filtros
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$db = getDBConnection();

// Construir la consulta base
$query = "FROM login_audit la LEFT JOIN users u ON la.user_id = u.id WHERE 1=1";
$params = [];

// Aplicar filtros
if ($status_filter) {
    $query .= " AND la.status = ?";
    $params[] = $status_filter;
}
if ($date_from) {
    $query .= " AND DATE(la.attempt_time) >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $query .= " AND DATE(la.attempt_time) <= ?";
    $params[] = $date_to;
}
if ($search) {
    $query .= " AND (la.email LIKE ? OR la.ip_address LIKE ? OR la.details LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Obtener total de registros
$stmt = $db->prepare("SELECT COUNT(*) " . $query);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Obtener registros de la página actual
$query = "SELECT la.*, u.email as user_email " . $query . " 
          ORDER BY la.attempt_time DESC 
          LIMIT $records_per_page OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$audit_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría de Usuarios - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .audit-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
        }
        .filters {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }
        .filter-group {
            flex: 1;
        }
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
        }
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .audit-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        .audit-table th,
        .audit-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .audit-table th {
            background: var(--primary);
            color: white;
        }
        .audit-table tr:hover {
            background: #f5f5f5;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .status-success { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .status-blocked { background: #fff3cd; color: #856404; }
        .pagination {
            margin-top: 1rem;
            display: flex;
            justify-content: center;
            gap: 0.5rem;
        }
        .pagination a {
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: var(--primary);
        }
        .pagination a.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .btn-export {
            background: var(--secondary);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1rem;
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
                    <a href="usersaudit.php" class="active">Auditoría</a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="contact.php">Contacto</a>
        </div>
        <div class="nav-right">
            <?php if(isset($_SESSION['user_id'])): ?>
                <span class="user-email"><?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
                <a href="logout.php" class="auth-link">Cerrar Sesión</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="audit-container">
        <h1>Auditoría de Accesos</h1>
        
        <form method="GET" class="filters">
            <div class="filter-group">
                <label for="status">Estado:</label>
                <select name="status" id="status">
                    <option value="">Todos</option>
                    <option value="success" <?php echo $status_filter === 'success' ? 'selected' : ''; ?>>Exitoso</option>
                    <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Fallido</option>
                    <option value="blocked" <?php echo $status_filter === 'blocked' ? 'selected' : ''; ?>>Bloqueado</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="date_from">Desde:</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
            </div>
            <div class="filter-group">
                <label for="date_to">Hasta:</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
            </div>
            <div class="filter-group">
                <label for="search">Buscar:</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Email, IP o detalles...">
            </div>
            <div class="filter-group">
                <button type="submit" class="btn">Filtrar</button>
            </div>
        </form>

        <a href="export_audit.php<?php echo $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>" 
           class="btn-export">Exportar a CSV</a>

        <table class="audit-table">
            <thead>
                <tr>
                    <th>Fecha y Hora</th>
                    <th>Email</th>
                    <th>IP</th>
                    <th>Estado</th>
                    <th>Navegador</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audit_records as $record): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i:s', strtotime($record['attempt_time'])); ?></td>
                        <td><?php echo htmlspecialchars($record['email']); ?></td>
                        <td><?php echo htmlspecialchars($record['ip_address']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $record['status']; ?>">
                                <?php 
                                    echo match($record['status']) {
                                        'success' => 'Exitoso',
                                        'failed' => 'Fallido',
                                        'blocked' => 'Bloqueado',
                                        default => $record['status']
                                    };
                                ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars(substr($record['user_agent'], 0, 50)) . '...'; ?></td>
                        <td><?php echo htmlspecialchars($record['details']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search); ?>" 
                       class="<?php echo $page === $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 